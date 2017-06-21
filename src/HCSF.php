<?php

namespace HaaseIT\HCSF;

use Zend\ServiceManager\ServiceManager;
use HaaseIT\HCSF\Shop\Helper as SHelper;

class HCSF
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    public function __construct()
    {
        define('HCSF_BASEDIR', dirname(__DIR__).DIRECTORY_SEPARATOR);
        define('DB_ADDRESSFIELDS', 'cust_id, cust_no, cust_email, cust_corp, cust_name, cust_street, cust_zip, cust_town, cust_phone, cust_cellphone, cust_fax, cust_country, cust_group, cust_active, cust_emailverified, cust_tosaccepted, cust_cancellationdisclaimeraccepted');
        define('DB_ITEMFIELDS', 'itm_no, itm_name, itm_price, itm_vatid, itm_rg, itm_img, itm_group, itm_data, itm_weight, itml_name_override, itml_text1, itml_text2, itm_index');
        define('DB_ITEMGROUPFIELDS', 'itmg_no, itmg_name, itmg_img, itmgt_shorttext, itmgt_details');
        define('FILE_PAYPALLOG', 'ipnlog.txt');

        // set scale for bcmath
        bcscale(6);
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

        date_default_timezone_set(HelperConfig::$core['defaulttimezone']);

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

        if (HelperConfig::$core['enable_module_shop']) {
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
        if (isset($_COOKIE['acceptscookies']) && HelperConfig::$core['enable_module_customer'] && $_COOKIE['acceptscookies'] === 'yes') {
            // Session handling
            // session.use_trans_sid wenn nötig aktivieren
            session_name('sid');
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
            if (file_exists(HCSF_BASEDIR.'src/hardcodedtextcats/'.key(HelperConfig::$core['lang_available']).'.php')) {
                $HT = require HCSF_BASEDIR.'src/hardcodedtextcats/'.key(HelperConfig::$core['lang_available']).'.php';
            } else {
                $HT = require HCSF_BASEDIR.'src/hardcodedtextcats/de.php';
            }
        }

        HardcodedText::init($HT);
    }

    protected function setupDB()
    {
        $this->serviceManager->setFactory('dbal', function () {
            $config = new \Doctrine\DBAL\Configuration();

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
            return $serviceManager->get('dbal')->getWrappedConnection();
        });
    }

    protected function setupTextcats()
    {
        $this->serviceManager->setFactory('textcats', function (ServiceManager $serviceManager) {
            $langavailable = HelperConfig::$core['lang_available'];
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
                'debug' => HelperConfig::$core['debug'] ? true : false,
            ];
            if (HelperConfig::$core['templatecache_enable'] &&
                is_dir(PATH_TEMPLATECACHE) && is_writable(PATH_TEMPLATECACHE)) {
                $twig_options['cache'] = PATH_TEMPLATECACHE;
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
            $twig->addFilter(new \Twig_SimpleFilter('decodehtmlentity', 'html_entity_decode'));

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

    /**
     * @param Page $P
     * @return array
     */
    public function generatePage(Page $P)
    {
        $requesturi = $this->serviceManager->get('request')->getRequestTarget();

        $aP = [
            'language' => HelperConfig::$lang,
            'pageconfig' => $P->cb_pageconfig,
            'pagetype' => $P->cb_pagetype,
            'subnavkey' => $P->cb_subnav,
            'requesturi' => $requesturi,
            'requesturiarray' => parse_url($requesturi),
            'locale_format_date' => HelperConfig::$core['locale_format_date'],
            'locale_format_date_time' => HelperConfig::$core['locale_format_date_time'],
            'maintenancemode' => HelperConfig::$core['maintenancemode'],
            'numberformat_decimals' => HelperConfig::$core['numberformat_decimals'],
            'numberformat_decimal_point' => HelperConfig::$core['numberformat_decimal_point'],
            'numberformat_thousands_seperator' => HelperConfig::$core['numberformat_thousands_seperator'],
            'customroottemplate' => $P->getCustomRootTemplate(),
            'headers' => $P->getHeaders(),
        ];
        if (HelperConfig::$core['enable_module_customer']) {
            $aP['isloggedin'] = \HaaseIT\HCSF\Customer\Helper::getUserData();
            $aP['enable_module_customer'] = true;
        }
        if (HelperConfig::$core['enable_module_shop']) {
            $aP['currency'] = HelperConfig::$shop['waehrungssymbol'];
            $aP['orderamounts'] = HelperConfig::$shop['orderamounts'];
            if (isset(HelperConfig::$shop['vat']['full'])) {
                $aP['vatfull'] = HelperConfig::$shop['vat']['full'];
            }
            if (isset(HelperConfig::$shop['vat']['reduced'])) {
                $aP['vatreduced'] = HelperConfig::$shop['vat']['reduced'];
            }
            if (isset(HelperConfig::$shop['custom_order_fields'])) {
                $aP['custom_order_fields'] = HelperConfig::$shop['custom_order_fields'];
            }
            $aP['enable_module_shop'] = true;
        }
        if (isset($P->cb_key)) {
            $aP['path'] = pathinfo($P->cb_key);
        } else {
            $aP['path'] = pathinfo($aP['requesturi']);
        }
        if ($P->cb_customcontenttemplate != NULL) {
            $aP['customcontenttemplate'] = $P->cb_customcontenttemplate;
        }
        if ($P->cb_customdata != NULL) {
            $aP['customdata'] = $P->cb_customdata;
        }
        if (isset($_SERVER['HTTP_REFERER'])) {
            $aP['referer'] = $_SERVER['HTTP_REFERER'];
        }

        // if there is no subnav defined but there is a default subnav defined, use it
        // subnavkey can be used in the templates to find out, where we are
        if ((!isset($aP['subnavkey']) || $aP['subnavkey'] == '') && HelperConfig::$core['subnav_default'] != '') {
            $aP['subnavkey'] = HelperConfig::$core['subnav_default'];
            $P->cb_subnav = HelperConfig::$core['subnav_default'];
        }
        if ($P->cb_subnav != NULL && isset(HelperConfig::$navigation[$P->cb_subnav])) {
            $aP['subnav'] = HelperConfig::$navigation[$P->cb_subnav];
        }

        // Get page title, meta-keywords, meta-description
        $aP['pagetitle'] = $P->oPayload->getTitle();
        $aP['keywords'] = $P->oPayload->cl_keywords;
        $aP['description'] = $P->oPayload->cl_description;

        // TODO: Add head scripts to DB
        //if (isset($P["head_scripts"]) && $P["head_scripts"] != '') $aP["head_scripts"] = $P["head_scripts"];

        // Shopping cart infos
        if (HelperConfig::$core['enable_module_shop']) {
            $aP['cartinfo'] = SHelper::getShoppingcartData();
        }

        $aP['countrylist'][] = ' | ';
        foreach (HelperConfig::$countries['countries_' .HelperConfig::$lang] as $sKey => $sValue) {
            $aP['countrylist'][] = $sKey.'|'.$sValue;
        }

        if (
            HelperConfig::$core['enable_module_shop']
            && (
                $aP['pagetype'] === 'itemoverview'
                || $aP['pagetype'] === 'itemoverviewgrpd'
                || $aP['pagetype'] === 'itemdetail'
            )
        ) {
            $aP = SHelper::handleItemPage($this->serviceManager, $P, $aP);
        }

        $aP['content'] = $P->oPayload->cl_html;

        $aP['content'] = str_replace('@', '&#064;', $aP['content']); // Change @ to HTML Entity -> maybe less spam mails

        $aP['lang_available'] = HelperConfig::$core['lang_available'];
        $aP['lang_detection_method'] = HelperConfig::$core['lang_detection_method'];
        $aP['lang_by_domain'] = HelperConfig::$core['lang_by_domain'];

        if (HelperConfig::$core['debug']) {
            \HaaseIT\HCSF\Helper::getDebug($aP, $P);
            $aP['debugdata'] = \HaaseIT\Toolbox\Tools::$sDebug;
        }

        return $aP;
    }
}