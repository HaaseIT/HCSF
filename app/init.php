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

ini_set('display_errors', 1);
error_reporting(E_ALL);
//error_reporting(0);

mb_internal_encoding('UTF-8');
header("Content-Type: text/html; charset=UTF-8");

if (ini_get('session.auto_start') == 1) {
    die('Please disable session.autostart for this to work.');
}

if (isset($_COOKIE["acceptscookies"]) && $_COOKIE["acceptscookies"] == 'yes') {
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
}

include_once(__DIR__.'/../vendor/autoload.php');

use Symfony\Component\Yaml\Yaml;

// Load core config
include_once(__DIR__.'/config/constants.fixed.php');
include_once(__DIR__.'/config/config.core.php');

if ($C["enable_module_shop"]) $C["enable_module_customer"] = true;

$C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.countries.yml')));
$C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.scrts.yml')));
if ($C["enable_module_customer"]) $C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.customer.yml')));
if ($C["enable_module_shop"]) {
    define("PATH_ORDERLOG", $_SERVER['DOCUMENT_ROOT'].'/_admin/orderlogs/');
    define("PATH_PAYPALLOG", $_SERVER['DOCUMENT_ROOT'].'/_admin/ipnlogs/');
    define("FILE_PAYPALLOG", $_SERVER['DOCUMENT_ROOT'].'/ipnlog.txt');
    include_once(__DIR__.'/config/config.shop.php');
    if (isset($TMP["vat_disable"]) && $TMP["vat_disable"]) {
        $TMP["vat"] = array("full" => 0, "reduced" => 0);
    }
}

include_once(__DIR__.'/../src/functions.template.php');
include_once(__DIR__.'/../src/functions.misc.php');
include_once(__DIR__.'/../src/Tools.php');
if (isset($C["debug"]) && $C["debug"]) HaaseIT\Tools::$bEnableDebug = true;
include_once(__DIR__.'/../src/DBTools.php');
include_once(__DIR__.'/../src/functions.db.php');

date_default_timezone_set($C["defaulttimezone"]);

// ----------------------------------------------------------------------------
// Begin Twig loading and init
// ----------------------------------------------------------------------------

$loader = new Twig_Loader_Filesystem(__DIR__.'/../src/views/');
$twig_options = array(
    'autoescape' => false,
    'debug' => (isset($C["debug"]) && $C["debug"] ? true : false)
);
if (isset($C["templatecache_enable"]) && $C["templatecache_enable"] &&
    is_dir(__DIR__.'/../templatecache/') && is_writable(__DIR__.'/../templatecache/')) {
    $twig_options["cache"] = __DIR__.'/../templatecache/';
}
$twig = new Twig_Environment($loader, $twig_options);
if (isset($C["debug"]) && $C["debug"]) {
    $twig->addExtension(new Twig_Extension_Debug());
}
$twig->addFunction('T', new Twig_Function_Function('T'));
$twig->addFunction('gFF', new Twig_Function_Function('\HaaseIT\Tools::getFormField'));

// ----------------------------------------------------------------------------
// Begin language detection
// ----------------------------------------------------------------------------
if ($C["lang_detection_method"] == 'domain' && isset($C["lang_by_domain"]) && is_array($C["lang_by_domain"])) { // domain based language detection
    foreach ($C["lang_by_domain"] as $sKey => $sValue) {
        if ($_SERVER["HTTP_HOST"] == $sValue || $_SERVER["HTTP_HOST"] == 'www.'.$sValue) {
            $sLang = $sKey;
            break;
        }
    }
} elseif ($C["lang_detection_method"] == 'legacy') { // legacy language detection
    if (isset($_GET["language"]) && array_key_exists($_GET["language"], $C["lang_available"])) {
        $sLang = strtolower($_GET["language"]);
        setcookie('language', strtolower($_GET["language"]), 0, '/');
    } elseif (isset($_COOKIE["language"]) && array_key_exists($_COOKIE["language"], $C["lang_available"])) {
        $sLang = strtolower($_COOKIE["language"]);
    } elseif (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) && array_key_exists(substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2), $C["lang_available"])) {
        $sLang = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2);
    } else {
        $sLang = key($C["lang_available"]);
    }
}
if (!isset($sLang)) {
    $sLang = key($C["lang_available"]);
}

// ----------------------------------------------------------------------------
// Begin database init
// ----------------------------------------------------------------------------
$DB = new \PDO($C["db_type"].':host='.$C["db_server"].';dbname='.$C["db_name"], $C["db_user"], $C["db_password"], array( \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8', ));
$DB->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
$DB->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
$DB->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); // ERRMODE_SILENT / ERRMODE_WARNING / ERRMODE_EXCEPTION

// ----------------------------------------------------------------------------
// Begin routing
// ----------------------------------------------------------------------------
// Only do routing if original app.php is called. if not, no routing, no fetching db content
if ($_SERVER["PHP_SELF"] == '/app.php') {
    $aURL = parse_url($_SERVER["REQUEST_URI"]);
    $sPath = $aURL["path"];
    $aPath = explode('/', $sPath);
    if (strpos($aPath[count($aPath)-1], '.') === false && $aPath[count($aPath)-1] != '') $sPath .= '/';

    // /women/item/0010.html
    if ($C["enable_module_shop"] && mb_strpos($sPath, '/item/') !== false) {
        $aTMP["exploded_request_path"] = explode('/', $sPath);
        $aTMP["position_of_itemsstring_in_path"] = count($aTMP["exploded_request_path"])-2;
        if ($aTMP["exploded_request_path"][$aTMP["position_of_itemsstring_in_path"]] == 'item') {
            $aTMP["exploded_request_file"] = explode('.', $aTMP["exploded_request_path"][count($aTMP["exploded_request_path"])-1]);
            if ($aTMP["exploded_request_file"][count($aTMP["exploded_request_file"])-1] == 'html') {
                $aRoutingoverride["itemno"] = $aTMP["exploded_request_file"][0];
                $aRoutingoverride["cb_pagetype"] = 'itemdetail';
                $sPath = '';
                for ($i = 0; $i < $aTMP["position_of_itemsstring_in_path"]; $i++) {
                    $sPath .= $aTMP["exploded_request_path"][$i].'/';
                }
            }
        }
        //HaaseIT\Tools::debug($sPath);
        //HaaseIT\Tools::debug($aTMP);
        //HaaseIT\Tools::debug($aRoutingoverride);
        unset($aTMP);
    }

    if ($sPath[strlen($sPath)-1] == '/') $sPath .= 'index.html';
    //$sPath = ltrim(trim($aURL["path"]), '/');
    //$sPath = rtrim(trim($sPath), '/');

    $P = getContent($C, $DB, $sPath, $sLang);
    if ($P) $P["base"]["cb_pageconfig"] = json_decode($P["base"]["cb_pageconfig"], true);
    if (isset($P) && isset($aRoutingoverride) && count($aRoutingoverride)) {
        $P["base"] = array_merge($P["base"], $aRoutingoverride);
    }
}

$T = loadTextcats($sLang, $C, $DB);
//HaaseIT\Tools::debug($T);

include_once(__DIR__.'/config/config.navi.php');

if ($C["enable_module_customer"]) include_once(__DIR__.'/../src/customer/functions.customer.php');

if ($C["enable_module_shop"]) {
    include_once(__DIR__ . '/../src/shop/Item.php');
    include_once(__DIR__ . '/../src/shop/functions.shoppingcart.php');

    $oItem = new \HaaseIT\Shop\Item($C, $DB, $sLang);
} else {
    $oItem = '';
}