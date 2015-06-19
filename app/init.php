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

require_once __DIR__.'/../vendor/autoload.php';

$AuraLoader = new \Aura\Autoload\Loader;
$AuraLoader->register();
$AuraLoader->addPrefix('\HaaseIT\HCSF', __DIR__.'/../src');

use Symfony\Component\Yaml\Yaml;

// Load core config
require_once __DIR__.'/config/constants.fixed.php';
$C = Yaml::parse(file_get_contents(__DIR__.'/config/config.core.yml'));
if (isset($C["debug"]) && $C["debug"]) HaaseIT\Tools::$bEnableDebug = true;

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
}

define("DIRNAME_IMAGES", '_img/');
define("DIRNAME_ITEMS", 'items/');
define("DIRNAME_ITEMSSMALLEST", '100/');
define("PATH_EMAILATTACHMENTS", PATH_DOCROOT.'_assets/');

if ($C["enable_module_shop"]) $C["enable_module_customer"] = true;

$C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.countries.yml')));
$C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.scrts.yml')));
if ($C["enable_module_customer"]) $C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.customer.yml')));
if ($C["enable_module_shop"]) {
    define("PATH_ORDERLOG", PATH_DOCROOT.'_admin/orderlogs/');
    define("PATH_PAYPALLOG", PATH_DOCROOT.'_admin/ipnlogs/');
    define("FILE_PAYPALLOG", 'ipnlog.txt');
    $C = array_merge($C, Yaml::parse(file_get_contents(__DIR__.'/config/config.shop.yml')));
    if (isset($C["vat_disable"]) && $C["vat_disable"]) {
        $C["vat"] = array("full" => 0, "reduced" => 0);
    }
}

require_once PATH_BASEDIR.'src/functions.template.php';

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
    is_dir(PATH_TEMPLATECACHE) && is_writable(PATH_TEMPLATECACHE)) {
    $twig_options["cache"] = PATH_TEMPLATECACHE;
}
$twig = new Twig_Environment($loader, $twig_options);
if (isset($C["debug"]) && $C["debug"]) {
    $twig->addExtension(new Twig_Extension_Debug());
}
$twig->addFunction('T', new Twig_Function_Function('\HaaseIT\Textcat::T'));
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
// more init stuff
// ----------------------------------------------------------------------------
\HaaseIT\Textcat::init($DB, $sLang, key($C["lang_available"]));

require_once __DIR__.'/config/config.navi.php';
if (isset($C["navstruct"]["admin"])) {
    unset($C["navstruct"]["admin"]);
}

if ($C["enable_module_customer"]) {
    require_once __DIR__.'/../src/customer/functions.customer.php';
}

if ($C["enable_module_shop"]) {
    require_once __DIR__ . '/../src/shop/Items.php';
    require_once __DIR__ . '/../src/shop/functions.shoppingcart.php';

    $oItem = new \HaaseIT\HCSF\Shop\Items($C, $DB, $sLang);

    $C["navstruct"]["admin"]["Bestellungen"] = '/_admin/shop/shopadmin.php';
    $C["navstruct"]["admin"]["Artikel"] = '/_admin/shop/itemadmin.php';
    $C["navstruct"]["admin"]["Artikelgruppen"] = '/_admin/shop/itemgroupadmin.php';
} else {
    $oItem = '';
}

if ($C["enable_module_customer"]) {
    require_once __DIR__.'/../src/customer/functions.customer.php';
    $C["navstruct"]["admin"]["Kunden"] = '/_admin/customer/customeradmin.php';
}

$C["navstruct"]["admin"]["Seiten"] = '/_admin/pageadmin.php';
$C["navstruct"]["admin"]["Textkataloge"] = '/_admin/textcatadmin.php';
$C["navstruct"]["admin"]["Templatecache leeren"] = '/_admin/cleartemplatecache.php';

// ----------------------------------------------------------------------------
// Begin routing
// ----------------------------------------------------------------------------
// Only do routing if original app.php is called. if not, no routing, no fetching db content

if ($_SERVER["PHP_SELF"] == '/app.php') {
    $aURL = parse_url($_SERVER["REQUEST_URI"]);
    $sPath = $aURL["path"];

    // if the path is one of the predefined urls, skip further routing
    if ($C["enable_module_customer"] && $sPath == '/_misc/login.html') {
        $P = new \HaaseIT\HCSF\CorePage($C, $sLang);
        $P->cb_pagetype = 'content';


        if (!isset($_POST["sAction"]) || $_POST["sAction"] != "login") {
            $P->cb_customcontenttemplate = 'customer/login';
        } else {
            $mLogin = getLogin($C, $DB);
            if (isset($mLogin["status"]) && $mLogin["status"] == 'success') {
                $P->oPayload->cl_html = \HaaseIT\Textcat::T("login_success") . '<br>';
                header('Location: /_misc/userhome.html?login=true');
            } elseif (isset($mLogin["status"]) && $mLogin["status"] == 'tosnotaccepted') {
                $P->oPayload->cl_html = \HaaseIT\Textcat::T("login_fail_tosnotaccepted") . '<br>';
            } elseif (isset($mLogin["status"]) && $mLogin["status"] == 'emailnotverified') {
                $P->oPayload->cl_html = \HaaseIT\Textcat::T("login_fail_emailnotverified") . '<br><br>';
                $P->oPayload->cl_html .= '<a href="/_misc/resendverificationmail.html?email=' . $mLogin["data"][DB_CUSTOMERFIELD_EMAIL] . '">' . \HaaseIT\Textcat::T("login_fail_emailnotverifiedresend") . '</a>';
            } elseif (isset($mLogin["status"]) && $mLogin["status"] == 'accountinactive') {
                $P->oPayload->cl_html = \HaaseIT\Textcat::T("login_fail_accountinactive") . '<br>';
            } else {
                $P->oPayload->cl_html = \HaaseIT\Textcat::T("login_fail");
            }
        }

        if ($C["enable_module_shop"]) {
            refreshCartItems($C, $oItem);
        }
    } elseif ($C["enable_module_customer"] && $sPath == '/_misc/logout.html') {
        $P = new \HaaseIT\HCSF\CorePage($C, $sLang);
        $P->cb_pagetype = 'content';

        unset($_SESSION["user"]);
        if ($C["enable_module_shop"] && isset($_SESSION["cart"])) {
            refreshCartItems($C, $oItem);
        }
        $P->oPayload->cl_html = \HaaseIT\Textcat::T("logout_message");
    } elseif ($C["enable_module_customer"] && $sPath == '/_misc/userhome.html') {
        require_once PATH_BASEDIR . 'src/customer/worker.userhome.php';
    } elseif ($C["enable_module_customer"] && $sPath == '/_misc/register.html') {
        require_once PATH_BASEDIR . 'src/customer/worker.register.php';
    } elseif ($C["enable_module_customer"] && $sPath == '/_misc/forgotpassword.html') {
        $P = new \HaaseIT\HCSF\CorePage($C, $sLang);
        $P->cb_pagetype = 'content';

        if (getUserData()) {
            $P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
        } else {
            $P->cb_customcontenttemplate = 'customer/forgotpassword';

            $aErr = array();
            if (isset($_POST["doSend"]) && $_POST["doSend"] == 'yes') {
                $aErr = handleForgotPassword($DB, $C, $aErr);
                if (count($aErr) == 0) {
                    $P->cb_customdata["forgotpw"]["showsuccessmessage"] = true;
                } else {
                    $P->cb_customdata["forgotpw"]["errors"] = $aErr;
                }
            }
        }
    } elseif ($C["enable_module_customer"] && $sPath == '/_misc/rp.html') {
        require_once PATH_BASEDIR . 'src/customer/worker.rp.php';
    } elseif ($C["enable_module_customer"] && $sPath == '/_misc/verifyemail.html') {
        $P = new \HaaseIT\HCSF\CorePage($C, $sLang);
        $P->cb_pagetype = 'content';

        if (getUserData()) {
            $P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
        } else {
            $sQ = "SELECT " . DB_CUSTOMERFIELD_EMAIL . ", " . DB_CUSTOMERTABLE_PKEY . " FROM " . DB_CUSTOMERTABLE;
            $sQ .= " WHERE " . DB_CUSTOMERFIELD_EMAILVERIFICATIONCODE . " = :key AND " . DB_CUSTOMERFIELD_EMAILVERIFIED . " = 'n'";
            //debug( $sQ );
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':key', $_GET["key"], PDO::PARAM_STR);
            $hResult->execute();
            $iRows = $hResult->rowCount();
            //debug( $iRows );

            if ($iRows == 1) {
                $aRow = $hResult->fetch();
                $aData = array(DB_CUSTOMERFIELD_EMAILVERIFIED => 'y', DB_CUSTOMERTABLE_PKEY => $aRow[DB_CUSTOMERTABLE_PKEY]);
                $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_CUSTOMERTABLE, DB_CUSTOMERTABLE_PKEY);
                $hResult = $DB->prepare($sQ);
                foreach ($aData as $sKey => $sValue) {
                    $hResult->bindValue(':' . $sKey, $sValue);
                }
                $hResult->execute();
                $P->oPayload->cl_html = \HaaseIT\Textcat::T("register_emailverificationsuccess");
            } else {
                $P->oPayload->cl_html = \HaaseIT\Textcat::T("register_emailverificationfail");
            }
        }
    } elseif ($C["enable_module_customer"] && $sPath == '/_misc/resendverificationmail.html') {
        $P = new \HaaseIT\HCSF\CorePage($C, $sLang);
        $P->cb_pagetype = 'content';

        if (getUserData()) {
            $P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
        } else {
            $sQ = "SELECT " . DB_ADDRESSFIELDS . ", " . DB_CUSTOMERFIELD_EMAILVERIFICATIONCODE . " FROM " . DB_CUSTOMERTABLE;
            $sQ .= " WHERE " . DB_CUSTOMERFIELD_EMAIL . " = :email";
            $sQ .= " AND " . DB_CUSTOMERFIELD_EMAILVERIFIED . " = 'n'";
            //debug($sQ);
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':email', trim($_GET["email"]), PDO::PARAM_STR);
            $hResult->execute();
            $iRows = $hResult->rowCount();
            if ($iRows == 1) {
                $aRow = $hResult->fetch();
                $sEmailVerificationcode = $aRow[DB_CUSTOMERFIELD_EMAILVERIFICATIONCODE];

                sendVerificationMail($sEmailVerificationcode, $aRow[DB_CUSTOMERFIELD_EMAIL], $C, true);

                $P->oPayload->cl_html = \HaaseIT\Textcat::T("register_verificationmailresent");
            }
        }
    } elseif ($C["enable_module_shop"] && $sPath == '/_misc/myorders.html') {
        require_once PATH_BASEDIR . 'src/shop/worker.myorders.php';
    } elseif ($C["enable_module_shop"] && $sPath == '/_misc/itemsearch.html') {
        $P = [
            'base' => ['cb_pagetype' => 'itemoverview', 'cb_pageconfig' => '', 'cb_subnav' => ''],
            'lang' => ['cl_lang' => $sLang, 'cl_html' => '']
        ];
    } elseif ($C["enable_module_shop"] && $sPath == '/_misc/checkedout.html') {
        $P = new \HaaseIT\HCSF\CorePage($C, $sLang);
        $P->cb_pagetype = 'content';

        if ($C["show_pricesonlytologgedin"] && !getUserData()) {
            $P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_notloggedin");
        } else {
            $P->cb_customcontenttemplate = 'shop/checkedout';

            $iId = \filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $sQ = "SELECT * FROM " . DB_ORDERTABLE . " ";
            $sQ .= "WHERE o_id = :id AND o_paymentcompleted = 'n'";

            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':id', $iId, PDO::PARAM_INT);

            $hResult->execute();

            if ($hResult->rowCount() == 1) {
                $P->cb_customdata["order"] = $hResult->fetch();
                $P->cb_customdata["gesamtbrutto"] = calculateTotalFromDB($P->cb_customdata["order"]);
            }
        }
    } elseif ($C["enable_module_shop"] && $sPath == '/_misc/updateshippingcost.html') {
        require_once PATH_BASEDIR . 'src/shop/worker.updateshippingcost.php';
    } elseif ($C["enable_module_shop"] && $sPath == '/_misc/shoppingcart.html') {
        require_once PATH_BASEDIR . 'src/shop/worker.shoppingcart.php';
    } elseif ($C["enable_module_shop"] && $sPath == '/_misc/update-cart.html') {
        require_once PATH_BASEDIR . 'src/shop/worker.update-cart.php';
    } elseif ($C["enable_module_shop"] && $sPath == '/_misc/sofortueberweisung.html') {
        require_once PATH_BASEDIR . 'src/shop/worker.sofortueberweisung.php';
    } elseif ($C["enable_module_shop"] && $sPath == '/_misc/paypal.html') {
        require_once PATH_BASEDIR . 'src/shop/worker.paypal.php';
    } elseif ($C["enable_module_shop"] && $sPath == '/_misc/paypal_notify.html') {
        require_once PATH_BASEDIR . 'src/shop/worker.paypal_notify.php';
    } else { // else: do the default routing
        $aPath = explode('/', $sPath);
        //HaaseIT\Tools::debug($aPath);

        //require_once PATH_BASEDIR.'src/PagePayload.php';
        //require PATH_BASEDIR.'src/UserPage.php';
        // /xxxx/item/0010.html
        if ($C["enable_module_shop"]) {
            $aTMP["parts_in_path"] = count($aPath);
            // if the last dir in path is 'item' and the last part of the path is not empty
            if ($aPath[$aTMP["parts_in_path"] - 2] == 'item' && $aPath[$aTMP["parts_in_path"] - 1] != '') {

                $aTMP["exploded_request_file"] = explode('.', $aPath[$aTMP["parts_in_path"] - 1]);
                //\HaaseIT\Tools::debug($aTMP["exploded_request_file"]);

                // if the filename ends in '.html', get the requested itemno
                if ($aTMP["exploded_request_file"][count($aTMP["exploded_request_file"]) - 1] == 'html') {
                    // to allow dots in the filename, we have to iterate through all parts of the filename
                    $aRoutingoverride["itemno"] = '';
                    for ($i = 0; $i < count($aTMP["exploded_request_file"]) - 1; $i++) {
                        $aRoutingoverride["itemno"] .= $aTMP["exploded_request_file"][$i].'.';
                    }
                    // remove the trailing dot
                    $aRoutingoverride["itemno"] = \HaaseIT\Tools::cutStringEnd($aRoutingoverride["itemno"], 1);

                    //\HaaseIT\Tools::debug($aRoutingoverride["itemno"]);
                    $aRoutingoverride["cb_pagetype"] = 'itemdetail';

                    // rebuild the path string without the trailing '/item/itemno.html'
                    $sPath = '';
                    for ($i = 0; $i < $aTMP["parts_in_path"] - 2; $i++) {
                        $sPath .= $aPath[$i] . '/';
                    }
                }
            }
            //HaaseIT\Tools::debug($sPath);
            //HaaseIT\Tools::debug($aTMP);
            //HaaseIT\Tools::debug($aRoutingoverride);
            unset($aTMP);
        }

        // if the page is not found as it is, try some more options
        $P = new \HaaseIT\HCSF\UserPage($C, $sLang, $DB, $sPath);
        if ($P->cb_id == NULL) {
            /*
            If the last part of the path doesn't include a dot (.) and is not empty, apend a slash.
            If there is already a slash at the end, the last part of the path array will be empty.
             */
            if (mb_strpos($aPath[count($aPath) - 1], '.') === false && $aPath[count($aPath) - 1] != '') $sPath .= '/';

            if ($sPath[strlen($sPath) - 1] == '/') $sPath .= 'index.html';
            //$sPath = ltrim(trim($aURL["path"]), '/');
            //$sPath = rtrim(trim($sPath), '/');

            $P = new \HaaseIT\HCSF\UserPage($C, $sLang, $DB, $sPath);
        }
        unset($aPath); // no longer needed
        //die(var_dump($P));

        if ($P->cb_id == NULL) {
            unset($P);
        } else {
            // Support for shorturls
            if ($P->cb_pagetype == 'shorturl') {
                header('Location: '.$P->cb_pageconfig, true, 302);
                exit();
            }

            if (isset($P) && isset($aRoutingoverride) && count($aRoutingoverride)) {
                $P->cb_pagetype = $aRoutingoverride["cb_pagetype"];
                //$P["base"] = array_merge($P["base"], $aRoutingoverride);
            }
        }
    }

    //die(var_dump($P));
    if (!isset($P)) {
        // TODO: testen

        $P = new \HaaseIT\HCSF\CorePage($C, $sLang);
        $P->cb_pagetype = 'error';

        $P->oPayload->cl_html = \HaaseIT\Textcat::T("misc_page_not_found");
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    } elseif (isset($P) && $P->oPayload == NULL) {
        if (!($P->cb_pagetype == 'itemoverview' || $P->cb_pagetype == 'itemoverviewgrpd' || $P->cb_pagetype == 'itemdetail')) {
            $P->oPayload->cl_html = \HaaseIT\Textcat::T("misc_content_not_found");
            header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
        }
    } elseif($P->oPayload->cl_lang != $sLang) {
        $P->oPayload->cl_html = \HaaseIT\Textcat::T("misc_page_not_available_lang").'<br><br>'.$P->oPayload->cl_html;
    }
    //die(var_dump($P));
}
