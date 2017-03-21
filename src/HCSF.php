<?php

namespace HaaseIT\HCSF;

use Zend\ServiceManager\ServiceManager;

class HCSF
{
    protected $serviceManager;

    public function __construct()
    {
        define('HCSF_BASEDIR', dirname(__DIR__).DIRECTORY_SEPARATOR);
    }

    public function init()
    {
        $this->serviceManager = new ServiceManager();

        $this->setupRequest();

        HelperConfig::init();
        if (HelperConfig::$core['debug']) {
            \HaaseIT\Toolbox\Tools::$bEnableDebug = true;
        }

        $this->setupSession();

        date_default_timezone_set(HelperConfig::$core["defaulttimezone"]);

        $this->setupHardcodedTextcats();

        $this->serviceManager->setFactory('db', function () {
            return null;
        });

        if (!HelperConfig::$core['maintenancemode']) {
            $this->setupDB();
            $this->setupTextcats();
            HelperConfig::loadNavigation($this->serviceManager);
        }

        $this->setupTwig();

        if (HelperConfig::$core["enable_module_shop"]) {
            $this->serviceManager->setFactory('oItem', function (ServiceManager $serviceManager) {
                return new \HaaseIT\HCSF\Shop\Items($serviceManager);
            });
        }

        $router = new \HaaseIT\HCSF\Router($this->serviceManager);
        return $router->getPage();
    }

    protected function setupRequest()
    {
        // PSR-7 Stuff
        // Init request object
        $this->serviceManager->setFactory('request', function () {
            $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();

            // cleanup request
            $requesturi = urldecode($request->getRequestTarget());
            $parsedrequesturi = substr($requesturi, strlen(dirname($_SERVER['PHP_SELF'])));
            if (substr($parsedrequesturi, 1, 1) !== '/') {
                $parsedrequesturi = '/'.$parsedrequesturi;
            }
            return $request->withRequestTarget($parsedrequesturi);
        });
    }

    protected function setupSession()
    {
        if (HelperConfig::$core["enable_module_customer"] && isset($_COOKIE["acceptscookies"]) && $_COOKIE["acceptscookies"] == 'yes') {
// Session handling
// session.use_trans_sid wenn nötig aktivieren
            ini_set('session.use_only_cookies', 0);
            session_name('sid');
            if(ini_get('session.use_trans_sid') == 1) {
                ini_set('session.use_trans_sid', 0);
            }
// Session wenn nötig starten
            if (session_id() == '') {
                session_start();
            }

            // check if the stored ip and ua equals the clients, if not, reset. if not set at all, reset
            if (!empty($_SESSION['hijackprevention'])) {
                if (
                    $_SESSION['hijackprevention']['remote_addr'] != $_SERVER['REMOTE_ADDR']
                    ||
                    $_SESSION['hijackprevention']['user_agent'] != $_SERVER['HTTP_USER_AGENT']
                ) {
                    \session_regenerate_id();
                    \session_unset();
                }
            } else {
                \session_regenerate_id();
                \session_unset();
                $_SESSION['hijackprevention']['remote_addr'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['hijackprevention']['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            }
        }
    }

    protected function setupHardcodedTextcats()
    {
        if (file_exists(HCSF_BASEDIR.'src/hardcodedtextcats/'.HelperConfig::$lang.'.php')) {
            $HT = require HCSF_BASEDIR.'src/hardcodedtextcats/'.HelperConfig::$lang.'.php';
        } else {
            if (file_exists(HCSF_BASEDIR.'src/hardcodedtextcats/'.key(HelperConfig::$core["lang_available"]).'.php')) {
                $HT = require HCSF_BASEDIR.'src/hardcodedtextcats/'.key(HelperConfig::$core["lang_available"]).'.php';
            } else {
                $HT = require HCSF_BASEDIR.'src/hardcodedtextcats/de.php';
            }
        }

        HardcodedText::init($HT);
    }

    protected function setupDB()
    {
        $this->serviceManager->setFactory('dbal', function () {
            $config = \Doctrine\DBAL\Configuration::class();

            $connectionParams = [
                'url' =>
                    HelperConfig::$secrets['db_type'].'://'
                    .HelperConfig::$secrets['db_user'].':'
                    .HelperConfig::$secrets['db_password'].'@'
                    .HelperConfig::$secrets['db_server'].'/'
                    .HelperConfig::$secrets['db_name'],
                'charset' => 'UTF8',
                'driverOptions' => [
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ],
            ];

            return \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        });

        $this->serviceManager->setFactory('db', function (ServiceManager $serviceManager) {
            return $serviceManager->get('dbal')->getConnection()->getWrappedConnection();
        });
    }

    protected function setupTextcats()
    {
        $this->serviceManager->setFactory('textcats', function (ServiceManager $serviceManager) {
            $langavailable = HelperConfig::$core["lang_available"];
            $textcats = new \HaaseIT\Toolbox\Textcat(
                HelperConfig::$lang,
                $serviceManager->get('db'),
                key($langavailable),
                HelperConfig::$core['textcatsverbose'],
                PATH_LOGS
            );
            $textcats->loadTextcats();

            return $textcats;
        });
    }

    protected function setupTwig()
    {
        $this->serviceManager->setFactory('twig', function (ServiceManager $serviceManager) {
            $loader = new \Twig_Loader_Filesystem([PATH_BASEDIR.'customviews', HCSF_BASEDIR.'src/views/']);

            $twig_options = [
                'autoescape' => false,
                'debug' => (HelperConfig::$core["debug"] ? true : false),
            ];
            if (HelperConfig::$core["templatecache_enable"] &&
                is_dir(PATH_TEMPLATECACHE) && is_writable(PATH_TEMPLATECACHE)) {
                $twig_options["cache"] = PATH_TEMPLATECACHE;
            }
            $twig = new \Twig_Environment($loader, $twig_options);

            if (HelperConfig::$core['allow_parsing_of_page_content']) {
                $twig->addExtension(new \Twig_Extension_StringLoader());
            } else { // make sure, template_from_string is callable
                $twig->addFunction(new \Twig_SimpleFunction('template_from_string', '\HaaseIT\HCSF\Helper::reachThrough'));
            }

            if (!HelperConfig::$core['maintenancemode']) {
                $twig->addFunction(new \Twig_SimpleFunction('T', [$serviceManager->get('textcats'), 'T']));
            } else {
                $twig->addFunction(new \Twig_SimpleFunction('T', '\HaaseIT\HCSF\Helper::returnEmptyString'));
            }

            $twig->addFunction(new \Twig_SimpleFunction('HT', '\HaaseIT\HCSF\HardcodedText::get'));
            $twig->addFunction(new \Twig_SimpleFunction('gFF', '\HaaseIT\Toolbox\Tools::getFormField'));
            $twig->addFunction(new \Twig_SimpleFunction('ImgURL', '\HaaseIT\HCSF\Helper::getSignedGlideURL'));
            $twig->addFunction(new \Twig_SimpleFunction('callback', 'HaaseIT\HCSF\Helper::twigCallback'));
            $twig->addFunction(new \Twig_SimpleFunction('makeLinkHRefWithAddedGetVars', '\HaaseIT\Toolbox\Tools::makeLinkHRefWithAddedGetVars'));

            return $twig;
        });
    }

    /**
     * @return mixed
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }
}