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

require_once __DIR__.'/../vendor/autoload.php';

$AuraLoader = new \Aura\Autoload\Loader;
$AuraLoader->register();
$AuraLoader->addPrefix('\HaaseIT\HCSF', __DIR__.'/../src');

// PSR-7 Stuff
// Init request object
$request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();

// cleanup request
$requesturi = urldecode($request->getRequestTarget());
$parsedrequesturi = \substr($requesturi, \strlen(\dirname($_SERVER['PHP_SELF'])));
if (substr($parsedrequesturi, 1, 1) != '/') {
    $parsedrequesturi = '/'.$parsedrequesturi;
}
$request = $request->withRequestTarget($parsedrequesturi);

use Symfony\Component\Yaml\Yaml;
$C = Yaml::parse(file_get_contents(__DIR__.'/config/config.core.dist.yml'));
if (is_file(__DIR__.'/config/config.core.yml')) $C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.core.yml')));
$C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.countries.yml')));
$C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.scrts.yml')));
$C['directory_images'] = trim($C['directory_images'], " \t\n\r\0\x0B/"); // trim this
if (!empty($C['maintenancemode']) && $C['maintenancemode']) {
    $C["enable_module_customer"] = false;
    $C["enable_module_shop"] = false;
    $C["templatecache_enable"] = false;
    $C["debug"] = false;
    $C['textcatsverbose'] = false;
} else {
    $C['maintenancemode'] = false;
}

if (isset($C["debug"]) && $C["debug"]) HaaseIT\Tools::$bEnableDebug = true;
require_once __DIR__.'/config/constants.fixed.php';

if ($C["enable_module_customer"] && isset($_COOKIE["acceptscookies"]) && $_COOKIE["acceptscookies"] == 'yes') {
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

if ($C["enable_module_shop"]) $C["enable_module_customer"] = true;

if ($C["enable_module_customer"]) {
    $C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.customer.dist.yml')));
    if (is_file(__DIR__.'/config/config.customer.yml')) {
        $C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.customer.yml')));
    }
}
define("PATH_LOGS", __DIR__.'/../hcsflogs/');
if ($C["enable_module_shop"]) {
    define("FILE_PAYPALLOG", 'ipnlog.txt');
    $C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.shop.dist.yml')));
    if (is_file(__DIR__.'/config/config.shop.yml')) {
        $C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.shop.yml')));
    }
    if (isset($C["vat_disable"]) && $C["vat_disable"]) {
        $C["vat"] = ["full" => 0, "reduced" => 0];
    }
}

date_default_timezone_set($C["defaulttimezone"]);

// ----------------------------------------------------------------------------
// Begin Twig loading and init
// ----------------------------------------------------------------------------

$loader = new Twig_Loader_Filesystem([__DIR__.'/../customviews', __DIR__.'/../src/views/']);
$twig_options = [
    'autoescape' => false,
    'debug' => (isset($C["debug"]) && $C["debug"] ? true : false)
];
if (isset($C["templatecache_enable"]) && $C["templatecache_enable"] &&
    is_dir(PATH_TEMPLATECACHE) && is_writable(PATH_TEMPLATECACHE)) {
    $twig_options["cache"] = PATH_TEMPLATECACHE;
}
$twig = new Twig_Environment($loader, $twig_options);

if ($C['allow_parsing_of_page_content']) {
    $twig->addExtension(new Twig_Extension_StringLoader());
} else { // make sure, template_from_string is callable
    $twig->addFunction('template_from_string', new Twig_Function_Function('\HaaseIT\HCSF\Helper::reachThrough'));
}

if (isset($C["debug"]) && $C["debug"]) {
    //$twig->addExtension(new Twig_Extension_Debug());
}
$twig->addFunction('T', new Twig_Function_Function('\HaaseIT\Textcat::T'));
$twig->addFunction('HT', new Twig_Function_Function('\HaaseIT\HCSF\HardcodedText::get'));
$twig->addFunction('gFF', new Twig_Function_Function('\HaaseIT\Tools::getFormField'));
$twig->addFunction('ImgURL', new Twig_Function_Function('\HaaseIT\HCSF\Helper::getSignedGlideURL'));

$sLang = \HaaseIT\HCSF\Helper::getLanguage($C);

if (file_exists(PATH_BASEDIR.'src/hardcodedtextcats/'.$sLang.'.php')) {
    $HT = require PATH_BASEDIR.'src/hardcodedtextcats/'.$sLang.'.php';
} else {
    if (file_exists(PATH_BASEDIR.'src/hardcodedtextcats/'.key($C["lang_available"]).'.php')) {
        $HT = require PATH_BASEDIR.'src/hardcodedtextcats/'.key($C["lang_available"]).'.php';
    } else {
        $HT = require PATH_BASEDIR.'src/hardcodedtextcats/de.php';
    }
}
use \HaaseIT\HCSF\HardcodedText;
HardcodedText::init($HT);

if (!$C['maintenancemode']) {
// ----------------------------------------------------------------------------
// Begin database init
// ----------------------------------------------------------------------------
    $DB = new \PDO($C["db_type"] . ':host=' . $C["db_server"] . ';dbname=' . $C["db_name"], $C["db_user"], $C["db_password"], [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',]);
    $DB->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    $DB->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    $DB->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); // ERRMODE_SILENT / ERRMODE_WARNING / ERRMODE_EXCEPTION

    // ----------------------------------------------------------------------------
    // more init stuff
    // ----------------------------------------------------------------------------
    \HaaseIT\Textcat::init($DB, $sLang, key($C["lang_available"]), ($C['textcatsverbose']), PATH_LOGS);

    require_once __DIR__.'/config/config.navi.php';
    if (isset($C["navstruct"]["admin"])) {
        unset($C["navstruct"]["admin"]);
    }
} else {
    $c['navstruct'] = [];
    $DB = null;
}

$C["navstruct"]["admin"][HardcodedText::get('admin_nav_home')] = '/_admin/index.html';

if ($C["enable_module_shop"]) {
    $oItem = new \HaaseIT\HCSF\Shop\Items($C, $DB, $sLang);

    $C["navstruct"]["admin"][HardcodedText::get('admin_nav_orders')] = '/_admin/shopadmin.html';
    $C["navstruct"]["admin"][HardcodedText::get('admin_nav_items')] = '/_admin/itemadmin.html';
    $C["navstruct"]["admin"][HardcodedText::get('admin_nav_itemgroups')] = '/_admin/itemgroupadmin.html';
} else {
    $oItem = '';
}

if ($C["enable_module_customer"]) {
    $C["navstruct"]["admin"][HardcodedText::get('admin_nav_customers')] = '/_admin/customeradmin.html';
}

$C["navstruct"]["admin"][HardcodedText::get('admin_nav_pages')] = '/_admin/pageadmin.html';
$C["navstruct"]["admin"][HardcodedText::get('admin_nav_textcats')] = '/_admin/textcatadmin.html';
$C["navstruct"]["admin"][HardcodedText::get('admin_nav_cleartemplatecache')] = '/_admin/cleartemplatecache.html';
$C["navstruct"]["admin"][HardcodedText::get('admin_nav_clearimagecache')] = '/_admin/clearimagecache.html';
$C["navstruct"]["admin"][HardcodedText::get('admin_nav_phpinfo')] = '/_admin/phpinfo.html';
$C["navstruct"]["admin"][HardcodedText::get('admin_nav_dbstatus')] = '/_admin/dbstatus.html';

// ----------------------------------------------------------------------------
// Begin routing
// ----------------------------------------------------------------------------

$router = new \HaaseIT\HCSF\Router($C, $DB, $sLang, $request, $twig, $oItem);
$P = $router->getPage();
