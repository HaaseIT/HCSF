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
    $conf = Yaml::parse(file_get_contents(__DIR__.'/config/config.core.dist.yml'));
    if (is_file(__DIR__.'/config/config.core.yml')) $conf = array_merge($conf, Yaml::parse(file_get_contents(__DIR__.'/config/config.core.yml')));
    $conf = array_merge($conf, Yaml::parse(file_get_contents(__DIR__.'/config/config.countries.yml')));
    $conf = array_merge($conf, Yaml::parse(file_get_contents(__DIR__.'/config/config.scrts.yml')));
    $conf['directory_images'] = trim($conf['directory_images'], " \t\n\r\0\x0B/"); // trim this

    if (!empty($conf['maintenancemode']) && $conf['maintenancemode']) {
        $conf["enable_module_customer"] = false;
        $conf["enable_module_shop"] = false;
        $conf["templatecache_enable"] = false;
        $conf["debug"] = false;
        $conf['textcatsverbose'] = false;
    } else {
        $conf['maintenancemode'] = false;
    }

    if ($conf["enable_module_shop"]) $conf["enable_module_customer"] = true;

    if ($conf["enable_module_customer"]) {
        $conf = array_merge($conf, Yaml::parse(file_get_contents(__DIR__.'/config/config.customer.dist.yml')));
        if (is_file(__DIR__.'/config/config.customer.yml')) {
            $conf = array_merge($conf, Yaml::parse(file_get_contents(__DIR__.'/config/config.customer.yml')));
        }
    }

    if ($conf["enable_module_shop"]) {
        $conf = array_merge($conf, Yaml::parse(file_get_contents(__DIR__.'/config/config.shop.dist.yml')));
        if (is_file(__DIR__.'/config/config.shop.yml')) {
            $conf = array_merge($conf, Yaml::parse(file_get_contents(__DIR__.'/config/config.shop.yml')));
        }
        if (isset($conf["vat_disable"]) && $conf["vat_disable"]) {
            $conf["vat"] = ["full" => 0, "reduced" => 0];
        }
    }

    return $conf;
};

require_once __DIR__.'/config/constants.fixed.php';

if (isset($container['conf']["debug"]) && $container['conf']["debug"]) HaaseIT\Tools::$bEnableDebug = true;

if ($container['conf']["enable_module_customer"] && isset($_COOKIE["acceptscookies"]) && $_COOKIE["acceptscookies"] == 'yes') {
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

date_default_timezone_set($container['conf']["defaulttimezone"]);

$container['lang'] = \HaaseIT\HCSF\Helper::getLanguage($container);

if (file_exists(PATH_BASEDIR.'src/hardcodedtextcats/'.$container['lang'].'.php')) {
    $HT = require PATH_BASEDIR.'src/hardcodedtextcats/'.$container['lang'].'.php';
} else {
    if (file_exists(PATH_BASEDIR.'src/hardcodedtextcats/'.key($container['conf']["lang_available"]).'.php')) {
        $HT = require PATH_BASEDIR.'src/hardcodedtextcats/'.key($container['conf']["lang_available"]).'.php';
    } else {
        $HT = require PATH_BASEDIR.'src/hardcodedtextcats/de.php';
    }
}
use \HaaseIT\HCSF\HardcodedText;
HardcodedText::init($HT);

$container['navstruct'] = [];
$container['db'] = null;
$container['entitymanager'] = null;
if (!$container['conf']['maintenancemode']) {
// ----------------------------------------------------------------------------
// Begin database init
// ----------------------------------------------------------------------------

    $container['entitymanager'] = function ($c)
    {
        $doctrineconfig = Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration([PATH_BASEDIR."/src"], $c['conf']['debug']);

        $connectionParams = array(
            'url' => $c['conf']["db_type"].'://'.$c['conf']["db_user"].':'.$c['conf']["db_password"].'@'.$c['conf']["db_server"].'/'.$c['conf']["db_name"],
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
    /*
    $langavailable = $container['conf']["lang_available"];
    \HaaseIT\Textcat::init($container['db'], $container['lang'], key($langavailable), $container['conf']['textcatsverbose'], PATH_LOGS);
    */

    $container['textcats'] = function ($c)
    {
        $langavailable = $c['conf']["lang_available"];
        $textcats = new \HaaseIT\Textcat($c, key($langavailable), $c['conf']['textcatsverbose'], PATH_LOGS);
        $textcats->loadTextcats();

        return $textcats;
    };

    $container['navstruct'] = function ($c)
    {
        $navstruct = include __DIR__.'/config/config.navi.php';

        if (isset($navstruct["admin"])) {
            unset($navstruct["admin"]);
        }

        $navstruct["admin"][HardcodedText::get('admin_nav_home')] = '/_admin/index.html';

        if ($c['conf']["enable_module_shop"]) {
            $navstruct["admin"][HardcodedText::get('admin_nav_orders')] = '/_admin/shopadmin.html';
            $navstruct["admin"][HardcodedText::get('admin_nav_items')] = '/_admin/itemadmin.html';
            $navstruct["admin"][HardcodedText::get('admin_nav_itemgroups')] = '/_admin/itemgroupadmin.html';
        }

        if ($c['conf']["enable_module_customer"]) {
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
        'debug' => (isset($c['conf']["debug"]) && $c['conf']["debug"] ? true : false)
    ];
    if (isset($c['conf']["templatecache_enable"]) && $c['conf']["templatecache_enable"] &&
        is_dir(PATH_TEMPLATECACHE) && is_writable(PATH_TEMPLATECACHE)) {
        $twig_options["cache"] = PATH_TEMPLATECACHE;
    }
    $twig = new Twig_Environment($loader, $twig_options);

    if ($c['conf']['allow_parsing_of_page_content']) {
        $twig->addExtension(new Twig_Extension_StringLoader());
    } else { // make sure, template_from_string is callable
        $twig->addFunction('template_from_string', new Twig_Function_Function('\HaaseIT\HCSF\Helper::reachThrough'));
    }

    if (isset($c['conf']["debug"]) && $c['conf']["debug"]) {
        //$twig->addExtension(new Twig_Extension_Debug());
    }
    //$twig->addFunction('T', new Twig_Function_Function('$c[\'textcats\']->T'));
    $twig->addFunction(new Twig_SimpleFunction('T', [$c['textcats'], 'T']));

    $twig->addFunction('HT', new Twig_Function_Function('\HaaseIT\HCSF\HardcodedText::get'));
    $twig->addFunction('gFF', new Twig_Function_Function('\HaaseIT\Tools::getFormField'));
    $twig->addFunction('ImgURL', new Twig_Function_Function('\HaaseIT\HCSF\Helper::getSignedGlideURL'));

    return $twig;
};

$container['oItem'] = '';
if ($container['conf']["enable_module_shop"]) {
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
