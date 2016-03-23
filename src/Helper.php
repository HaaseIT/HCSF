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

namespace HaaseIT\HCSF;


class Helper
{
    public static function getSignedGlideURL($file, $w = 0, $h =0)
    {
        $urlBuilder = \League\Glide\Urls\UrlBuilderFactory::create('', GLIDE_SIGNATURE_KEY);

        if ($w == 0 && $h == 0) return false;
        if ($w != 0) $param['w'] = $w;
        if ($h != 0) $param['h'] = $h;
        if ($w != 0 && $h != 0) $param['fit'] = 'stretch';

        return $urlBuilder->getUrl($file, $param);
    }

    public static function mailWrapper($C, $to, $subject = '(No subject)', $message = '', $aImagesToEmbed = array(), $aFilesToAttach = array()) {
        //include_once(PATH_LIBRARIESROOT.'phpmailer/PHPMailerAutoload.php');
        $mail = new \PHPMailer;
        $mail->CharSet = 'UTF-8';
        if ($C['mail_method'] == 'sendmail') {
            $mail->isSendmail();
        } elseif ($C['mail_method'] == 'smtp') {
            $mail->isSMTP();
            $mail->Host = $C['mail_smtp_server'];
            $mail->Port = $C['mail_smtp_port'];
            if ($C['mail_smtp_auth'] == true) {
                $mail->SMTPAuth = true;
                $mail->Username = $C['mail_smtp_auth_user'];
                $mail->Password = $C['mail_smtp_auth_pwd'];
                if ($C['mail_smtp_secure']) {
                    if ($C['mail_smtp_secure_method'] == 'ssl') {
                        $mail->SMTPSecure = 'ssl';
                    } else {
                        $mail->SMTPSecure = 'tls';
                    }
                }
            }
        } else {
            $mail->isMail();
        }
        $mail->From = $C["email_sender"];
        $mail->FromName = $C["email_sendername"];
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        if (is_array($aImagesToEmbed) && count($aImagesToEmbed)) {
            $sPathImagesToEmbed = PATH_DOCROOT.$C['directory_images'].'/'.$C['directory_images_items'].'/'.$C['directory_images_items_email'].'/';
            foreach ($aImagesToEmbed as $sValue) {
                if (getimagesize($sPathImagesToEmbed.$sValue)) $mail->AddEmbeddedImage($sPathImagesToEmbed.$sValue, $sValue);
            }
        }

        if (is_array($aFilesToAttach) && count($aFilesToAttach)) {
            foreach ($aFilesToAttach as $sValue) {
                if (file_exists($sValue)) {
                    $mail->AddAttachment($sValue);
                }
            }
        }

        //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        return $mail->send();
    }

    // don't remove this, this is the fallback for unavailable twig functions
    public static function reachThrough($string) {
        return $string;
    }

    public static function generatePage($C, $P, $sLang, $oItem, $requesturi)
    {
        $aP = array(
            'language' => $sLang,
            'pageconfig' => $P->cb_pageconfig,
            'pagetype' => $P->cb_pagetype,
            'subnavkey' => $P->cb_subnav,
            'requesturi' => $requesturi,
            'requesturiarray' => parse_url($requesturi),
            'locale_format_date' => $C['locale_format_date'],
            'locale_format_date_time' => $C['locale_format_date_time'],
            'maintenancemode' => $C['maintenancemode'],
            'numberformat_decimals' => $C['numberformat_decimals'],
            'numberformat_decimal_point' => $C['numberformat_decimal_point'],
            'numberformat_thousands_seperator' => $C['numberformat_thousands_seperator'],
        );
        if ($C["enable_module_customer"]) {
            $aP["isloggedin"] = \HaaseIT\HCSF\Customer\Helper::getUserData();
            $aP["enable_module_customer"] = true;
        }
        if ($C["enable_module_shop"]) {
            $aP["currency"] = $C["waehrungssymbol"];
            $aP["orderamounts"] = $C["orderamounts"];
            if (isset($C["vat"]["full"])) $aP["vatfull"] = $C["vat"]["full"];
            if (isset($C["vat"]["reduced"])) $aP["vatreduced"] = $C["vat"]["reduced"];
            if (isset($C["custom_order_fields"])) $aP["custom_order_fields"] = $C["custom_order_fields"];
            $aP["enable_module_shop"] = true;
        }
        if (isset($P->cb_key)) $aP["path"] = pathinfo($P->cb_key);
        else $aP["path"] = pathinfo($aP["requesturi"]);
        if ($P->cb_customcontenttemplate != NULL) $aP["customcontenttemplate"] = $P->cb_customcontenttemplate;
        if ($P->cb_customdata != NULL) $aP["customdata"] = $P->cb_customdata;
        if (isset($_SERVER["HTTP_REFERER"])) $aP["referer"] = $_SERVER["HTTP_REFERER"];

        reset($C["lang_available"]);

        // if there is no subnav defined but there is a default subnav defined, use it
        // subnavkey can be used in the templates to find out, where we are
        if ((!isset($aP["subnavkey"]) || $aP["subnavkey"] == '') && $C["subnav_default"] != '') {
            $aP["subnavkey"] = $C["subnav_default"];
            $P->cb_subnav = $C["subnav_default"];
        }
        if ($P->cb_subnav != NULL && isset($C["navstruct"][$P->cb_subnav])) $aP["subnav"] = $C["navstruct"][$P->cb_subnav];

        // Get page title, meta-keywords, meta-description
        $aP["pagetitle"] = $P->oPayload->getTitle();

        $aP["keywords"] = $P->oPayload->cl_keywords;
        $aP["description"] = $P->oPayload->cl_description;

        // TODO: Add head scripts to DB
        //if (isset($P["head_scripts"]) && $P["head_scripts"] != '') $aP["head_scripts"] = $P["head_scripts"];

        // Language selector
        // TODO: move content of langselector out of php script
        if (count($C["lang_available"]) > 1) {
            $aP["langselector"] = self::getLangSelector($C, $sLang);
        }

        // Shopping cart infos
        if ($C["enable_module_shop"]) {
            $aP["cartinfo"] = \HaaseIT\HCSF\Shop\Helper::getShoppingcartData($C);
        }

        $aP["countrylist"][] = ' | ';
        foreach ($C["countries_".$sLang] as $sKey => $sValue) {
            $aP["countrylist"][] = $sKey.'|'.$sValue;
        }

        if ($C["enable_module_shop"] && ($aP["pagetype"] == 'itemoverview' || $aP["pagetype"] == 'itemoverviewgrpd' || $aP["pagetype"] == 'itemdetail')) {
            $aP = \HaaseIT\HCSF\Shop\Helper::handleItemPage($C, $oItem, $P, $aP);
        }

        $aP["content"] = $P->oPayload->cl_html;

        $aP["content"] = str_replace("@", "&#064;", $aP["content"]); // Change @ to HTML Entity -> maybe less spam mails
        $aP["content"] = str_replace("[quote]", "'", $aP["content"]);

        // TODO!!!
        /*
        if (!isset($P["keep_placeholders"]) || !$P["keep_placeholders"]) {
            $aP["content"] = stripslashes(str_replace('[sp]', '&nbsp;', $aP["content"]));
        } else {
            $aP["content"] .= stripslashes($aP["content"]);
        }
        */

        if (isset($_POST) && count($_POST)) {
            \HaaseIT\Tools::debug($_POST, '$_POST');
        } elseif (isset($_REQUEST) && count($_REQUEST)) {
            \HaaseIT\Tools::debug($_REQUEST, '$_REQUEST');
        }
        if (isset($_SESSION) && count($_SESSION)) {
            \HaaseIT\Tools::debug($_SESSION, '$_SESSION');
        }
        \HaaseIT\Tools::debug($aP, '$aP');
        \HaaseIT\Tools::debug($P, '$P');

        $aP["debugdata"] = \HaaseIT\Tools::$sDebug;

        return $aP;
    }

    public static function getLangSelector($C, $sLang)
    {
        $sLangselector = '';
        if ($C["lang_detection_method"] == 'domain') {
            $aSessionGetVarsForLangSelector = array();
            if (session_status() == PHP_SESSION_ACTIVE) {
                $aSessionGetVarsForLangSelector[session_name()] = session_id();
            }
            $aRequestURL = parse_url($_SERVER["REQUEST_URI"]);
        }
        foreach ($C["lang_available"] as $sKey => $sValue) {
            if ($sLang != $sKey) {
                if ($C["lang_detection_method"] == 'domain') {
                    $sLangselector .= '<a href="//www.' . $C["lang_by_domain"][$sKey] . $aRequestURL["path"] . \HaaseIT\Tools::makeLinkHRefWithAddedGetVars('', $aSessionGetVarsForLangSelector) . '">' . \HaaseIT\Textcat::T("misc_language_" . $sKey) . '</a> ';
                } else {
                    $sLangselector .= '<a href="' . \HaaseIT\Tools::makeLinkHRefWithAddedGetVars('', array('language' => $sKey)) . '">' . \HaaseIT\Textcat::T("misc_language_" . $sKey) . '</a> ';
                }
            }
        }
        $sLangselector = \HaaseIT\Tools::cutStringend($sLangselector, 1);

        return $sLangselector;
    }

    public static function getLanguage($C)
    {
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

        return $sLang;
    }

    public static function getPurifier($C, $purpose)
    {
        $purifier_config = \HTMLPurifier_Config::createDefault();
        $purifier_config->set('Core.Encoding', 'UTF-8');
        $purifier_config->set('Cache.SerializerPath', PATH_PURIFIERCACHE);
        $purifier_config->set('HTML.Doctype', $C['purifier_doctype']);

        if ($purpose == 'textcat') {
            // textcats
            if (isset($C['textcat_unsafe_html_whitelist']) && trim($C['textcat_unsafe_html_whitelist']) != '') {
                $purifier_config->set('HTML.Allowed', $C['textcat_unsafe_html_whitelist']);
            }
            if (isset($C['textcat_loose_filtering']) && $C['textcat_loose_filtering']) {
                $purifier_config->set('HTML.Trusted', true);
                $purifier_config->set('Attr.EnableID', true);
                $purifier_config->set('Attr.AllowedFrameTargets', array('_blank', '_self', '_parent', '_top'));
            }
        } elseif ($purpose == 'page') {
            // pageadmin
            if (isset($C['pagetext_unsafe_html_whitelist']) && trim($C['pagetext_unsafe_html_whitelist']) != '') {
                $purifier_config->set('HTML.Allowed', $C['pagetext_unsafe_html_whitelist']);
            }
            if (isset($C['pagetext_loose_filtering']) && $C['pagetext_loose_filtering']) {
                $purifier_config->set('HTML.Trusted', true);
                $purifier_config->set('Attr.EnableID', true);
                $purifier_config->set('Attr.AllowedFrameTargets', array('_blank', '_self', '_parent', '_top'));
            }
        } elseif ($purpose == 'item') {
            if (isset($C['itemtext_unsafe_html_whitelist']) && trim($C['itemtext_unsafe_html_whitelist']) != '') {
                $purifier_config->set('HTML.Allowed', $C['itemtext_unsafe_html_whitelist']);
            }
            if (isset($C['itemtext_loose_filtering']) && $C['itemtext_loose_filtering']) {
                $purifier_config->set('HTML.Trusted', true);
                $purifier_config->set('Attr.EnableID', true);
                $purifier_config->set('Attr.AllowedFrameTargets', array('_blank', '_self', '_parent', '_top'));
            }
        } elseif ($purpose == 'itemgroup') {
            if (isset($C['itemgrouptext_unsafe_html_whitelist']) && trim($C['itemgrouptext_unsafe_html_whitelist']) != '') {
                $purifier_config->set('HTML.Allowed', $C['itemgrouptext_unsafe_html_whitelist']);
            }
            if (isset($C['itemgrouptext_loose_filtering']) && $C['itemgrouptext_loose_filtering']) {
                $purifier_config->set('HTML.Trusted', true);
                $purifier_config->set('Attr.EnableID', true);
                $purifier_config->set('Attr.AllowedFrameTargets', array('_blank', '_self', '_parent', '_top'));
            }
        } else {
            return false;
        }


        return new \HTMLPurifier($purifier_config);
    }
}