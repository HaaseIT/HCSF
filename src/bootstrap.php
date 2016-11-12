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

$container = new Pimple\Container();

$AuraLoader = new \Aura\Autoload\Loader;
$AuraLoader->register();
$AuraLoader->addPrefix('\HaaseIT\HCSF', __DIR__.'/../src');

// PSR-7 Stuff
// Init request object
$container['request'] = \Zend\Diactoros\ServerRequestFactory::fromGlobals();

// cleanup request
$requesturi = urldecode($container['request']->getRequestTarget());
$parsedrequesturi = \substr($requesturi, \strlen(\dirname($_SERVER['PHP_SELF'])));
if (substr($parsedrequesturi, 1, 1) != '/') {
    $parsedrequesturi = '/'.$parsedrequesturi;
}
$container['request'] = $container['request']->withRequestTarget($parsedrequesturi);

use Symfony\Component\Yaml\Yaml;
$container['conf'] = function ($c) {
    $conf['core'] = Yaml::parse(file_get_contents(PATH_BASEDIR.'config/core.yml'));
    if (is_file(PATH_BASEDIR.'config/core.local.yml')) {
        $conf['core'] = array_merge($conf['core'], Yaml::parse(file_get_contents(PATH_BASEDIR.'config/core.local.yml')));
    }

    $conf['countries'] = Yaml::parse(file_get_contents(PATH_BASEDIR.'config/countries.yml'));
    if (is_file(PATH_BASEDIR.'config/countries.local.yml')) {
        $conf['countries'] = array_merge($conf['countries'], Yaml::parse(file_get_contents(PATH_BASEDIR.'config/countries.local.yml')));
    }

    $conf['secrets'] = Yaml::parse(file_get_contents(PATH_BASEDIR.'config/secrets.yml'));
    if (is_file(PATH_BASEDIR.'config/secrets.local.yml')) {
        $conf['secrets'] = array_merge($conf['secrets'], Yaml::parse(file_get_contents(PATH_BASEDIR.'config/secrets.local.yml')));
    }

    $conf['core']['directory_images'] = trim($conf['core']['directory_images'], " \t\n\r\0\x0B/"); // trim this

    if (!empty($conf['core']['maintenancemode']) && $conf['core']['maintenancemode']) {
        $conf['core']["enable_module_customer"] = false;
        $conf['core']["enable_module_shop"] = false;
        $conf['core']["templatecache_enable"] = false;
        $conf['core']["debug"] = false;
        $conf['core']['textcatsverbose'] = false;
    } else {
        $conf['core']['maintenancemode'] = false;
    }

    if ($conf['core']["enable_module_shop"]) {
        $conf['core']["enable_module_customer"] = true;
    }

    if ($conf['core']["enable_module_customer"]) {
        $conf['customer'] = Yaml::parse(file_get_contents(PATH_BASEDIR.'config/customer.yml'));
        if (is_file(__DIR__.'/config/config.customer.yml')) {
            $conf['customer'] = array_merge($conf['customer'], Yaml::parse(file_get_contents(PATH_BASEDIR.'config/customer.local.yml')));
        }
    }

    if ($conf['core']["enable_module_shop"]) {
        $conf['shop'] = Yaml::parse(file_get_contents(PATH_BASEDIR.'config/shop.yml'));
        if (is_file(PATH_BASEDIR.'config/shop.local.yml')) {
            $conf['shop'] = array_merge($conf['shop'], Yaml::parse(file_get_contents(PATH_BASEDIR.'config/shop.local.yml')));
        }
        if (isset($conf['shop']["vat_disable"]) && $conf['shop']["vat_disable"]) {
            $conf['shop']["vat"] = ["full" => 0, "reduced" => 0];
        }
    }

    return $conf;
};

define("GLIDE_SIGNATURE_KEY", $container['conf']['secrets']['glide_signkey']);

if (isset($container['conf']['core']["debug"]) && $container['conf']['core']["debug"]) HaaseIT\Tools::$bEnableDebug = true;

if ($container['conf']['core']["enable_module_customer"] && isset($_COOKIE["acceptscookies"]) && $_COOKIE["acceptscookies"] == 'yes') {
// Session handling
// session.use_trans_sid wenn nötig aktivieren
    ini_set('session.use_only_cookies', 0); // TODO find another way to pass session when language detection == domain
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

date_default_timezone_set($container['conf']['core']["defaulttimezone"]);

$container['lang'] = \HaaseIT\HCSF\Helper::getLanguage($container);

if (file_exists(PATH_BASEDIR.'src/hardcodedtextcats/'.$container['lang'].'.php')) {
    $HT = require PATH_BASEDIR.'src/hardcodedtextcats/'.$container['lang'].'.php';
} else {
    if (file_exists(PATH_BASEDIR.'src/hardcodedtextcats/'.key($container['conf']['core']["lang_available"]).'.php')) {
        $HT = require PATH_BASEDIR.'src/hardcodedtextcats/'.key($container['conf']['core']["lang_available"]).'.php';
    } else {
        $HT = require PATH_BASEDIR.'src/hardcodedtextcats/de.php';
    }
}
use \HaaseIT\HCSF\HardcodedText;
HardcodedText::init($HT);

$container['navstruct'] = [];
$container['db'] = null;
$container['entitymanager'] = null;
if (!$container['conf']['core']['maintenancemode']) {
// ----------------------------------------------------------------------------
// Begin database init
// ----------------------------------------------------------------------------

    $container['entitymanager'] = function ($c)
    {
        $doctrineconfig = Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration([PATH_BASEDIR."/src"], $c['conf']['core']['debug']);

        $connectionParams = array(
            'url' =>
                $c['conf']['secrets']['db_type'].'://'
                .$c['conf']['secrets']['db_user'].':'
                .$c['conf']['secrets']['db_password'].'@'
                .$c['conf']['secrets']['db_server'].'/'
                .$c['conf']['secrets']['db_name'],
            'charset' => 'UTF8',
            'driverOptions' => [
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ],
        );

        return Doctrine\ORM\EntityManager::create($connectionParams, $doctrineconfig);
    };

    $container['db'] = function ($c)
    {
        return $c['entitymanager']->getConnection()->getWrappedConnection();
    };

    // ----------------------------------------------------------------------------
    // more init stuff
    // ----------------------------------------------------------------------------
    $container['textcats'] = function ($c)
    {
        $langavailable = $c['conf']['core']["lang_available"];
        $textcats = new \HaaseIT\Textcat($c, key($langavailable), $c['conf']['core']['textcatsverbose'], PATH_LOGS);
        $textcats->loadTextcats();

        return $textcats;
    };

    $container['navstruct'] = function ($c)
    {
        if (is_file(PATH_BASEDIR.'config/navigation.local.yml')) {
            $navstruct = Yaml::parse(file_get_contents(PATH_BASEDIR.'config/navigation.local.yml'));
        } else {
            $navstruct = Yaml::parse(file_get_contents(PATH_BASEDIR.'config/navigation.yml'));
        }

        if (!empty($navstruct) && $c['conf']['core']['navigation_fetch_text_from_textcats']) {
            foreach ($navstruct as $key => $item) {
                foreach ($item as $subkey => $subitem) {
                    if (!empty($c['textcats']->T($subkey))) {
                        $TMP[$key][$c['textcats']->T($subkey)] = $subitem;
                    } else {
                        $TMP[$key][$subkey] = $subitem;
                    }
                }
            }
            $navstruct = $TMP;
            unset($TMP);
        }

        if (isset($navstruct["admin"])) {
            unset($navstruct["admin"]);
        }

        $navstruct["admin"][HardcodedText::get('admin_nav_home')] = '/_admin/index.html';

        if ($c['conf']['core']["enable_module_shop"]) {
            $navstruct["admin"][HardcodedText::get('admin_nav_orders')] = '/_admin/shopadmin.html';
            $navstruct["admin"][HardcodedText::get('admin_nav_items')] = '/_admin/itemadmin.html';
            $navstruct["admin"][HardcodedText::get('admin_nav_itemgroups')] = '/_admin/itemgroupadmin.html';
        }

        if ($c['conf']['core']["enable_module_customer"]) {
            $navstruct["admin"][HardcodedText::get('admin_nav_customers')] = '/_admin/customeradmin.html';
        }

        $navstruct["admin"][HardcodedText::get('admin_nav_pages')] = '/_admin/pageadmin.html';
        $navstruct["admin"][HardcodedText::get('admin_nav_textcats')] = '/_admin/textcatadmin.html';
        $navstruct["admin"][HardcodedText::get('admin_nav_cleartemplatecache')] = '/_admin/cleartemplatecache.html';
        $navstruct["admin"][HardcodedText::get('admin_nav_clearimagecache')] = '/_admin/clearimagecache.html';
        $navstruct["admin"][HardcodedText::get('admin_nav_phpinfo')] = '/_admin/phpinfo.html';
        $navstruct["admin"][HardcodedText::get('admin_nav_dbstatus')] = '/_admin/dbstatus.html';

        return $navstruct;
    };
}

// ----------------------------------------------------------------------------
// Begin Twig loading and init
// ----------------------------------------------------------------------------

$container['twig'] = function ($c) {
    $loader = new Twig_Loader_Filesystem([__DIR__.'/../customviews', __DIR__.'/../src/views/']);
    $twig_options = [
        'autoescape' => false,
        'debug' => (isset($c['conf']['core']["debug"]) && $c['conf']['core']["debug"] ? true : false)
    ];
    if (isset($c['conf']['core']["templatecache_enable"]) && $c['conf']['core']["templatecache_enable"] &&
        is_dir(PATH_TEMPLATECACHE) && is_writable(PATH_TEMPLATECACHE)) {
        $twig_options["cache"] = PATH_TEMPLATECACHE;
    }
    $twig = new Twig_Environment($loader, $twig_options);

    if ($c['conf']['core']['allow_parsing_of_page_content']) {
        $twig->addExtension(new Twig_Extension_StringLoader());
    } else { // make sure, template_from_string is callable
        $twig->addFunction('template_from_string', new Twig_Function_Function('\HaaseIT\HCSF\Helper::reachThrough'));
    }

    $twig->addFunction(new Twig_SimpleFunction('T', [$c['textcats'], 'T']));

    $twig->addFunction('HT', new Twig_Function_Function('\HaaseIT\HCSF\HardcodedText::get'));
    $twig->addFunction('gFF', new Twig_Function_Function('\HaaseIT\Tools::getFormField'));
    $twig->addFunction('ImgURL', new Twig_Function_Function('\HaaseIT\HCSF\Helper::getSignedGlideURL'));
    $twig->addFunction('makeLinkHRefWithAddedGetVars', new Twig_Function_Function('\HaaseIT\Tools::makeLinkHRefWithAddedGetVars'));

    return $twig;
};

$container['oItem'] = '';
if ($container['conf']['core']["enable_module_shop"]) {
    $container['oItem'] = function ($c)
    {
        return new \HaaseIT\HCSF\Shop\Items($c);
    };
}

// ----------------------------------------------------------------------------
// Begin routing
// ----------------------------------------------------------------------------

$router = new \HaaseIT\HCSF\Router($container);
$P = $router->getPage();
