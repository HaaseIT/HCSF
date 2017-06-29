<?php

namespace HaaseIT\HCSF;

use Zend\ServiceManager\ServiceManager;

class HCSF
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var HelperConfig
     */
    protected $config;

    /**
     * @var \HaaseIT\HCSF\Helper
     */
    protected $helper;

    /**
     * @var \HaaseIT\HCSF\Customer\Helper
     */
    protected $helperCustomer;

    /**
     * @var \HaaseIT\HCSF\Shop\Helper
     */
    protected $helperShop;

    /**
     * HCSF constructor.
     * @param string $basedir
     */
    public function __construct($basedir)
    {
        define('HCSF_BASEDIR', dirname(__DIR__).DIRECTORY_SEPARATOR);
        define('DB_ADDRESSFIELDS', 'cust_id, cust_no, cust_email, cust_corp, cust_name, cust_street, cust_zip, cust_town, cust_phone, cust_cellphone, cust_fax, cust_country, cust_group, cust_active, cust_emailverified, cust_tosaccepted, cust_cancellationdisclaimeraccepted');
        define('DB_ITEMFIELDS', 'itm_no, itm_name, itm_price, itm_vatid, itm_rg, itm_img, itm_group, itm_data, itm_weight, itml_name_override, itml_text1, itml_text2, itm_index');
        define('DB_ITEMGROUPFIELDS', 'itmg_no, itmg_name, itmg_img, itmgt_shorttext, itmgt_details');
        define('FILE_PAYPALLOG', 'ipnlog.txt');
        define('CLI', php_sapi_name() === 'cli');

        define("PATH_BASEDIR", $basedir.DIRECTORY_SEPARATOR);
        define("PATH_LOGS", PATH_BASEDIR.'hcsflogs/');
        define("PATH_CACHE", PATH_BASEDIR.'cache/');
        define("DIRNAME_TEMPLATECACHE", 'templates');
        define("PATH_TEMPLATECACHE", PATH_CACHE.DIRNAME_TEMPLATECACHE);
        define("PATH_PURIFIERCACHE", PATH_CACHE.'htmlpurifier/');
        define("DIRNAME_GLIDECACHE", 'glide');
        define("PATH_GLIDECACHE", PATH_CACHE.DIRNAME_GLIDECACHE);

        // set scale for bcmath
        bcscale(6);
    }

    public function init()
    {
        $this->serviceManager = new ServiceManager();

        if (!CLI) {
            $this->setupRequest();
        }

        $this->serviceManager->setFactory('config', function () {
            return new HelperConfig();
        });
        $this->config = $this->serviceManager->get('config');

        $this->serviceManager->setFactory('helper', function (ServiceManager $serviceManager) {
            return new \HaaseIT\HCSF\Helper($serviceManager);
        });
        $this->helper = $this->serviceManager->get('helper');

        if ($this->config->getCore('enable_module_customer')) {
            $this->serviceManager->setFactory('helpercustomer', function (ServiceManager $serviceManager) {
                return new \HaaseIT\HCSF\Customer\Helper($serviceManager);
            });
            $this->helperCustomer = $this->serviceManager->get('helpercustomer');
        }

        if ($this->config->getCore('enable_module_shop')) {
            $this->serviceManager->setFactory('helpershop', function (ServiceManager $serviceManager) {
                return new \HaaseIT\HCSF\Shop\Helper($serviceManager);
            });
            $this->helperShop = $this->serviceManager->get('helpershop');
        }

        define("PATH_DOCROOT", PATH_BASEDIR.$this->config->getCore('dirname_docroot'));
        if ($this->config->getCore('debug')) {
            \HaaseIT\Toolbox\Tools::$bEnableDebug = true;
        }

        if (!CLI) {
            $this->setupSession();
        }

        date_default_timezone_set($this->config->getCore('defaulttimezone'));

        $this->serviceManager->setFactory('hardcodedtextcats', function () {
            return $this->setupHardcodedTextcats();
        });

        $this->serviceManager->setFactory('db', function () {
            return null;
        });

        if (!$this->config->getCore('maintenancemode') || CLI) {
            $this->setupDB();
            $this->setupTextcats();
            $this->config->loadNavigation($this->serviceManager);
        }

        if (!CLI) {
            $this->setupTwig();
        }

        if ($this->config->getCore('enable_module_shop')) {
            $this->serviceManager->setFactory('oItem', function (ServiceManager $serviceManager) {
                return new \HaaseIT\HCSF\Shop\Items($serviceManager);
            });
        }

        if (!CLI) {
            $router = new \HaaseIT\HCSF\Router($this->serviceManager);
            return $router->getPage();
        }

        return true;
    }

    protected function setupRequest()
    {
        // PSR-7 Stuff
        // Init request object
        $this->serviceManager->setFactory('request', function () {
            $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();

            // cleanup request
            $requesturi = urldecode($request->getRequestTarget());
            $parsedrequesturi = substr($requesturi, strlen(dirname(filter_input(INPUT_SERVER, 'PHP_SELF'))));
            if (substr($parsedrequesturi, 1, 1) !== '/') {
                $parsedrequesturi = '/'.$parsedrequesturi;
            }
            return $request->withRequestTarget($parsedrequesturi);
        });
    }

    protected function setupSession()
    {
        if ($this->config->getCore('enable_module_customer') && filter_input(INPUT_COOKIE, 'acceptscookies') === 'yes') {
            // Session handling
            // session.use_trans_sid wenn nötig aktivieren
            session_name('sid');
            // Session wenn nötig starten
            if (empty(session_id())) {
                session_start();
            }

            $serverremoteaddr = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
            $serveruseragent = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT');
            // check if the stored ip and ua equals the clients, if not, reset. if not set at all, reset
            if (!empty($_SESSION['hijackprevention'])) {
                if (
                    $_SESSION['hijackprevention']['remote_addr'] != $serverremoteaddr
                    ||
                    $_SESSION['hijackprevention']['user_agent'] != $serveruseragent
                ) {
                    session_regenerate_id();
                    session_unset();
                }
            } else {
                session_regenerate_id();
                session_unset();
                $_SESSION['hijackprevention']['remote_addr'] = $serverremoteaddr;
                $_SESSION['hijackprevention']['user_agent'] = $serveruseragent;
            }
        }
    }

    protected function setupHardcodedTextcats()
    {
        $lang = $this->config->getLang();
        $langavailable = $this->config->getCore('lang_available');
        if (file_exists(HCSF_BASEDIR.'src/hardcodedtextcats/'.$lang.'.php')) {
            $HT = require HCSF_BASEDIR.'src/hardcodedtextcats/'.$lang.'.php';
        } else {
            if (file_exists(HCSF_BASEDIR.'src/hardcodedtextcats/'.key($langavailable).'.php')) {
                $HT = require HCSF_BASEDIR.'src/hardcodedtextcats/'.key($langavailable).'.php';
            } else {
                $HT = require HCSF_BASEDIR.'src/hardcodedtextcats/de.php';
            }
        }

        return new HardcodedText($HT);
    }

    protected function setupDB()
    {
        $this->serviceManager->setFactory('dbal', function () {
            $config = new \Doctrine\DBAL\Configuration();

            $connectionParams = [
                'url' =>
                    $this->config->getSecret('db_type').'://'
                    .$this->config->getSecret('db_user').':'
                    .$this->config->getSecret('db_password').'@'
                    .$this->config->getSecret('db_server').'/'
                    .$this->config->getSecret('db_name'),
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
            return $serviceManager->get('dbal')->getWrappedConnection();
        });
    }

    protected function setupTextcats()
    {
        $this->serviceManager->setFactory('textcats', function (ServiceManager $serviceManager) {
            $langavailable = $this->config->getCore('lang_available');
            $textcats = new \HaaseIT\Toolbox\Textcat(
                $this->config->getLang(),
                $serviceManager->get('db'),
                key($langavailable),
                $this->config->getCore('textcatsverbose'),
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
                'debug' => $this->config->getCore('debug') ? true : false,
            ];
            if ($this->config->getCore('templatecache_enable') &&
                is_dir(PATH_TEMPLATECACHE) && is_writable(PATH_TEMPLATECACHE)) {
                $twig_options['cache'] = PATH_TEMPLATECACHE;
            }
            $twig = new \Twig_Environment($loader, $twig_options);

            if ($this->config->getCore('allow_parsing_of_page_content')) {
                $twig->addExtension(new \Twig_Extension_StringLoader());
            } else { // make sure, template_from_string is callable
                $twig->addFunction(new \Twig_SimpleFunction('template_from_string', [$this->helper, 'reachThrough']));
            }

            if (!$this->config->getCore('maintenancemode')) {
                $twig->addFunction(new \Twig_SimpleFunction('T', [$serviceManager->get('textcats'), 'T']));
            } else {
                $twig->addFunction(new \Twig_SimpleFunction('T', [$this->helper, 'returnEmptyString']));
            }

            $twig->addFunction(new \Twig_SimpleFunction('HT', [$serviceManager->get('hardcodedtextcats'), 'get']));
            $twig->addFunction(new \Twig_SimpleFunction('gFF', '\HaaseIT\Toolbox\Tools::getFormField'));
            $twig->addFunction(new \Twig_SimpleFunction('ImgURL', [$this->helper, 'getSignedGlideURL']));
            $twig->addFunction(new \Twig_SimpleFunction('callback', [$this->helper, 'twigCallback']));
            $twig->addFunction(new \Twig_SimpleFunction('makeLinkHRefWithAddedGetVars', '\HaaseIT\Toolbox\Tools::makeLinkHRefWithAddedGetVars'));
            $twig->addFilter(new \Twig_SimpleFilter('decodehtmlentity', 'html_entity_decode'));

            return $twig;
        });
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param Page $P
     * @return array
     */
    public function generatePage(Page $P)
    {
        $requesturi = $this->serviceManager->get('request')->getRequestTarget();

        $aP = [
            'language' => $this->config->getLang(),
            'pageconfig' => $P->cb_pageconfig,
            'pagetype' => $P->cb_pagetype,
            'subnavkey' => $P->cb_subnav,
            'requesturi' => $requesturi,
            'requesturiarray' => parse_url($requesturi),
            'locale_format_date' => $this->config->getCore('locale_format_date'),
            'locale_format_date_time' => $this->config->getCore('locale_format_date_time'),
            'maintenancemode' => $this->config->getCore('maintenancemode'),
            'numberformat_decimals' => $this->config->getCore('numberformat_decimals'),
            'numberformat_decimal_point' => $this->config->getCore('numberformat_decimal_point'),
            'numberformat_thousands_seperator' => $this->config->getCore('numberformat_thousands_seperator'),
            'customroottemplate' => $P->getCustomRootTemplate(),
            'headers' => $P->getHeaders(),
        ];
        if ($this->config->getCore('enable_module_customer')) {
            $aP['isloggedin'] = $this->helperCustomer->getUserData();
            $aP['enable_module_customer'] = true;
        }
        if ($this->config->getCore('enable_module_shop')) {
            $aP['currency'] = $this->config->getShop('waehrungssymbol');
            $aP['orderamounts'] = $this->config->getShop('orderamounts');
            if (!empty($this->config->getShop('vat')['full'])) {
                $aP['vatfull'] = $this->config->getShop('vat')['full'];
            }
            if (!empty($this->config->getShop('vat')['reduced'])) {
                $aP['vatreduced'] = $this->config->getShop('vat')['reduced'];
            }
            if (!empty($this->config->getShop('custom_order_fields'))) {
                $aP['custom_order_fields'] = $this->config->getShop('custom_order_fields');
            }
            $aP['enable_module_shop'] = true;
        }
        if (isset($P->cb_key)) {
            $aP['path'] = pathinfo($P->cb_key);
        } else {
            $aP['path'] = pathinfo($aP['requesturi']);
        }
        if ($P->cb_customcontenttemplate != null) {
            $aP['customcontenttemplate'] = $P->cb_customcontenttemplate;
        }
        if ($P->cb_customdata != null) {
            $aP['customdata'] = $P->cb_customdata;
        }
        $serverhttpreferer = filter_input(INPUT_SERVER, 'HTTP_REFERER');
        if ($serverhttpreferer !== null) {
            $aP['referer'] = $serverhttpreferer;
        }

        // if there is no subnav defined but there is a default subnav defined, use it
        // subnavkey can be used in the templates to find out, where we are
        if (empty($aP['subnavkey']) && !empty($this->config->getCore('subnav_default'))) {
            $aP['subnavkey'] = $this->config->getCore('subnav_default');
            $P->cb_subnav = $this->config->getCore('subnav_default');
        }
        if ($P->cb_subnav != null && !empty($this->config->getNavigation($P->cb_subnav))) {
            $aP['subnav'] = $this->config->getNavigation($P->cb_subnav);
        }

        // Get page title, meta-keywords, meta-description
        $aP['pagetitle'] = $P->oPayload->getTitle();
        $aP['keywords'] = $P->oPayload->cl_keywords;
        $aP['description'] = $P->oPayload->cl_description;

        // Shopping cart infos
        if ($this->config->getCore('enable_module_shop')) {
            $aP['cartinfo'] = $this->helperShop->getShoppingcartData();
        }

        $aP['countrylist'][] = ' | ';
        $configcountries = $this->config->getCountries('countries_' .$this->config->getLang());
        foreach ($configcountries as $sKey => $sValue) {
            $aP['countrylist'][] = $sKey.'|'.$sValue;
        }

        if (
            $this->config->getCore('enable_module_shop')
            && (
                $aP['pagetype'] === 'itemoverview'
                || $aP['pagetype'] === 'itemoverviewgrpd'
                || $aP['pagetype'] === 'itemdetail'
            )
        ) {
            $aP = $this->helperShop->handleItemPage($this->serviceManager, $P, $aP);
        }

        $aP['content'] = $P->oPayload->cl_html;

        $aP['content'] = str_replace('@', '&#064;', $aP['content']); // Change @ to HTML Entity -> maybe less spam mails

        $aP['lang_available'] = $this->config->getCore('lang_available');
        $aP['lang_detection_method'] = $this->config->getCore('lang_detection_method');
        $aP['lang_by_domain'] = $this->config->getCore('lang_by_domain');

        if ($this->config->getCore('debug')) {
            $this->helper->getDebug($aP, $P);
            $aP['debugdata'] = \HaaseIT\Toolbox\Tools::$sDebug;
        }

        return $aP;
    }
}
