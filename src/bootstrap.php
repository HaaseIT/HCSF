<?php

/*
    HCSF - A multilingual CMS and Shopsystem
    Copyright (C) 2014  Marcus Haase - mail@marcus.haase.name

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

ini_set('display_errors', 0);
ini_set('xdebug.overload_var_dump', 0);
ini_set('xdebug.var_display_max_depth', 10);
ini_set('html_errors', 0);
error_reporting(E_ALL);
//error_reporting(0);

mb_internal_encoding('UTF-8');
header("Content-Type: text/html; charset=UTF-8");

if (ini_get('session.auto_start') == 1) {
    die('Please disable session.autostart for this to work.');
}

// set scale for bcmath
bcscale(6);

define("MINUTE", 60);
define("HOUR", MINUTE * 60);
define("DAY", HOUR * 24);
define("WEEK", DAY * 7);

define("DB_ADDRESSFIELDS", 'cust_id, cust_no, cust_email, cust_corp, cust_name, cust_street, cust_zip, cust_town, cust_phone, cust_cellphone, cust_fax, cust_country, cust_group, cust_active, cust_emailverified, cust_tosaccepted, cust_cancellationdisclaimeraccepted');
define("DB_ITEMFIELDS", 'itm_no, itm_name, itm_price, itm_vatid, itm_rg, itm_img, itm_group, itm_data, itm_weight, itml_name_override, itml_text1, itml_text2, itm_index');
define("DB_ITEMGROUPFIELDS", 'itmg_no, itmg_name, itmg_img, itmgt_shorttext, itmgt_details');

define("PATH_BASEDIR", __DIR__.'/../');
define("PATH_DOCROOT", PATH_BASEDIR.'web/');

define("PATH_CACHE", PATH_BASEDIR.'cache/');
define("DIRNAME_TEMPLATECACHE", 'templates');
define("PATH_TEMPLATECACHE", PATH_CACHE.DIRNAME_TEMPLATECACHE);
define("PATH_PURIFIERCACHE", PATH_CACHE.'htmlpurifier/');
define("DIRNAME_GLIDECACHE", 'glide');
define("PATH_GLIDECACHE", PATH_CACHE.DIRNAME_GLIDECACHE);

define("PATH_LOGS", PATH_BASEDIR.'hcsflogs/');
define("FILE_PAYPALLOG", 'ipnlog.txt');

const ENTITY_CUSTOMER = 'HaaseIT\HCSF\Entities\Customer\Customer';
const ENTITY_USERPAGE_LANG = 'HaaseIT\HCSF\Entities\UserpageLang';
const ENTITY_USERPAGE_BASE = 'HaaseIT\HCSF\Entities\UserpageBase';

require_once __DIR__.'/../vendor/autoload.php';

$serviceManager = new Zend\ServiceManager\ServiceManager();

$AuraLoader = new \Aura\Autoload\Loader;
$AuraLoader->register();
$AuraLoader->addPrefix('\HaaseIT\HCSF', __DIR__.'/../src');

// PSR-7 Stuff
// Init request object
$serviceManager->setFactory('request', function () {
    $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();

    // cleanup request
    $requesturi = urldecode($request->getRequestTarget());
    $parsedrequesturi = substr($requesturi, strlen(dirname($_SERVER['PHP_SELF'])));
    if (substr($parsedrequesturi, 1, 1) != '/') {
        $parsedrequesturi = '/'.$parsedrequesturi;
    }
    return $request->withRequestTarget($parsedrequesturi);
});
/* old, pimple
$container['request'] = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
$requesturi = urldecode($container['request']->getRequestTarget());
$parsedrequesturi = \substr($requesturi, \strlen(\dirname($_SERVER['PHP_SELF'])));
if (substr($parsedrequesturi, 1, 1) != '/') {
    $parsedrequesturi = '/'.$parsedrequesturi;
}
$container['request'] = $container['request']->withRequestTarget($parsedrequesturi);
*/

use HaaseIT\HCSF\HelperConfig;
HelperConfig::init();

if (HelperConfig::$core['debug']) {
    HaaseIT\Tools::$bEnableDebug = true;
}

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

date_default_timezone_set(HelperConfig::$core["defaulttimezone"]);

if (file_exists(PATH_BASEDIR.'src/hardcodedtextcats/'.HelperConfig::$lang.'.php')) {
    $HT = require PATH_BASEDIR.'src/hardcodedtextcats/'.HelperConfig::$lang.'.php';
} else {
    if (file_exists(PATH_BASEDIR.'src/hardcodedtextcats/'.key(HelperConfig::$core["lang_available"]).'.php')) {
        $HT = require PATH_BASEDIR.'src/hardcodedtextcats/'.key(HelperConfig::$core["lang_available"]).'.php';
    } else {
        $HT = require PATH_BASEDIR.'src/hardcodedtextcats/de.php';
    }
}
use \HaaseIT\HCSF\HardcodedText;
HardcodedText::init($HT);

//$container['db'] = null;
//$container['entitymanager'] = null;
if (!HelperConfig::$core['maintenancemode']) {
// ----------------------------------------------------------------------------
// Begin database init
// ----------------------------------------------------------------------------
    $serviceManager->setFactory('entitymanager', function () {
        $doctrineconfig = Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration([PATH_BASEDIR."/src"], HelperConfig::$core['debug']);

        $connectionParams = array(
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
        );

        return Doctrine\ORM\EntityManager::create($connectionParams, $doctrineconfig);
    });

    $serviceManager->setFactory('db', function () use($serviceManager) {
        return $serviceManager->get('entitymanager')->getConnection()->getWrappedConnection();
    });

    // ----------------------------------------------------------------------------
    // more init stuff
    // ----------------------------------------------------------------------------
    $serviceManager->setFactory('textcats', function () use($serviceManager) {
        $langavailable = HelperConfig::$core["lang_available"];
        $textcats = new \HaaseIT\Textcat(
            HelperConfig::$lang,
            $serviceManager->get('db'),
            key($langavailable),
            HelperConfig::$core['textcatsverbose'],
            PATH_LOGS
        );
        $textcats->loadTextcats();

        return $textcats;
    });

    HelperConfig::loadNavigation($serviceManager);
}

// ----------------------------------------------------------------------------
// Begin Twig loading and init
// ----------------------------------------------------------------------------

$serviceManager->setFactory('twig', function () use($serviceManager) {
    $loader = new Twig_Loader_Filesystem([PATH_BASEDIR.'customviews', PATH_BASEDIR.'src/views/']);

    $twig_options = [
        'autoescape' => false,
        'debug' => (HelperConfig::$core["debug"] ? true : false),
    ];
    if (HelperConfig::$core["templatecache_enable"] &&
        is_dir(PATH_TEMPLATECACHE) && is_writable(PATH_TEMPLATECACHE)) {
        $twig_options["cache"] = PATH_TEMPLATECACHE;
    }
    $twig = new Twig_Environment($loader, $twig_options);

    if (HelperConfig::$core['allow_parsing_of_page_content']) {
        $twig->addExtension(new Twig_Extension_StringLoader());
    } else { // make sure, template_from_string is callable
        $twig->addFunction('template_from_string', new Twig_Function_Function('\HaaseIT\HCSF\Helper::reachThrough'));
    }

    $twig->addFunction(new Twig_SimpleFunction('T', [$serviceManager->get('textcats'), 'T']));

    $twig->addFunction('HT', new Twig_Function_Function('\HaaseIT\HCSF\HardcodedText::get'));
    $twig->addFunction('gFF', new Twig_Function_Function('\HaaseIT\Tools::getFormField'));
    $twig->addFunction('ImgURL', new Twig_Function_Function('\HaaseIT\HCSF\Helper::getSignedGlideURL'));
    $twig->addFunction('makeLinkHRefWithAddedGetVars', new Twig_Function_Function('\HaaseIT\Tools::makeLinkHRefWithAddedGetVars'));

    return $twig;
});

//$container['oItem'] = '';
if (HelperConfig::$core["enable_module_shop"]) {
    $serviceManager->setFactory('oItem', function () use($serviceManager) {
        return new \HaaseIT\HCSF\Shop\Items($serviceManager);
    });
}

// ----------------------------------------------------------------------------
// Begin routing
// ----------------------------------------------------------------------------

$router = new \HaaseIT\HCSF\Router($serviceManager);
$P = $router->getPage();
