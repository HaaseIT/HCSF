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
    public static function getSignedGlideURL($file, $width = 0, $height =0)
    {
        $urlBuilder = \League\Glide\Urls\UrlBuilderFactory::create('', GLIDE_SIGNATURE_KEY);

        if ($width == 0 && $height == 0) return false;
        if ($width != 0) $param['w'] = $width;
        if ($height != 0) $param['h'] = $height;
        if ($width != 0 && $height != 0) $param['fit'] = 'stretch';

        return $urlBuilder->getUrl($file, $param);
    }

    public static function mailWrapper($C, $to, $subject = '(No subject)', $message = '', $aImagesToEmbed = [], $aFilesToAttach = []) {
        $mail = new \PHPMailer;
        $mail->CharSet = 'UTF-8';

        $mail->isMail();
        if ($C['core']['mail_method'] == 'sendmail') {
            $mail->isSendmail();
        } elseif ($C['core']['mail_method'] == 'smtp') {
            $mail->isSMTP();
            $mail->Host = $C['secrets']['mail_smtp_server'];
            $mail->Port = $C['secrets']['mail_smtp_port'];
            if ($C['secrets']['mail_smtp_auth'] == true) {
                $mail->SMTPAuth = true;
                $mail->Username = $C['secrets']['mail_smtp_auth_user'];
                $mail->Password = $C['secrets']['mail_smtp_auth_pwd'];
                if ($C['secrets']['mail_smtp_secure']) {
                    $mail->SMTPSecure = 'tls';
                    if ($C['secrets']['mail_smtp_secure_method'] == 'ssl') {
                        $mail->SMTPSecure = 'ssl';
                    }
                }
            }
        }

        $mail->From = $C['core']["email_sender"];
        $mail->FromName = $C['core']["email_sendername"];
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        if (is_array($aImagesToEmbed) && count($aImagesToEmbed)) {
            foreach ($aImagesToEmbed as $sKey => $imgdata) {
                $imginfo = getimagesizefromstring($imgdata['binimg']);
                $mail->AddStringEmbeddedImage($imgdata['binimg'], $sKey, $sKey, 'base64', $imginfo['mime']);
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

    public static function generatePage($container, $P, $requesturi)
    {
        $aP = [
            'language' => $container['lang'],
            'pageconfig' => $P->cb_pageconfig,
            'pagetype' => $P->cb_pagetype,
            'subnavkey' => $P->cb_subnav,
            'requesturi' => $requesturi,
            'requesturiarray' => parse_url($requesturi),
            'locale_format_date' => $container['conf']['core']['locale_format_date'],
            'locale_format_date_time' => $container['conf']['core']['locale_format_date_time'],
            'maintenancemode' => $container['conf']['core']['maintenancemode'],
            'numberformat_decimals' => $container['conf']['core']['numberformat_decimals'],
            'numberformat_decimal_point' => $container['conf']['core']['numberformat_decimal_point'],
            'numberformat_thousands_seperator' => $container['conf']['core']['numberformat_thousands_seperator'],
        ];
        if ($container['conf']['core']["enable_module_customer"]) {
            $aP["isloggedin"] = \HaaseIT\HCSF\Customer\Helper::getUserData();
            $aP["enable_module_customer"] = true;
        }
        if ($container['conf']['core']["enable_module_shop"]) {
            $aP["currency"] = $container['conf']['shop']["waehrungssymbol"];
            $aP["orderamounts"] = $container['conf']['shop']["orderamounts"];
            if (isset($container['conf']['shop']["vat"]["full"])) $aP["vatfull"] = $container['conf']['shop']["vat"]["full"];
            if (isset($container['conf']['shop']["vat"]["reduced"])) $aP["vatreduced"] = $container['conf']['shop']["vat"]["reduced"];
            if (isset($container['conf']['shop']["custom_order_fields"])) $aP["custom_order_fields"] = $container['conf']['shop']["custom_order_fields"];
            $aP["enable_module_shop"] = true;
        }
        if (isset($P->cb_key)) $aP["path"] = pathinfo($P->cb_key);
        else $aP["path"] = pathinfo($aP["requesturi"]);
        if ($P->cb_customcontenttemplate != NULL) $aP["customcontenttemplate"] = $P->cb_customcontenttemplate;
        if ($P->cb_customdata != NULL) $aP["customdata"] = $P->cb_customdata;
        if (isset($_SERVER["HTTP_REFERER"])) $aP["referer"] = $_SERVER["HTTP_REFERER"];

        // if there is no subnav defined but there is a default subnav defined, use it
        // subnavkey can be used in the templates to find out, where we are
        if ((!isset($aP["subnavkey"]) || $aP["subnavkey"] == '') && $container['conf']['core']["subnav_default"] != '') {
            $aP["subnavkey"] = $container['conf']['core']["subnav_default"];
            $P->cb_subnav = $container['conf']['core']["subnav_default"];
        }
        if ($P->cb_subnav != NULL && isset($container['navstruct'][$P->cb_subnav])) $aP["subnav"] = $container['navstruct'][$P->cb_subnav];

        // Get page title, meta-keywords, meta-description
        $aP["pagetitle"] = $P->oPayload->getTitle();
        $aP["keywords"] = $P->oPayload->cl_keywords;
        $aP["description"] = $P->oPayload->cl_description;

        // TODO: Add head scripts to DB
        //if (isset($P["head_scripts"]) && $P["head_scripts"] != '') $aP["head_scripts"] = $P["head_scripts"];

        // Language selector
        // TODO: move content of langselector out of php script
        if (count($container['conf']['core']["lang_available"]) > 1) {
            $aP["langselector"] = self::getLangSelector($container);
        }

        // Shopping cart infos
        if ($container['conf']['core']["enable_module_shop"]) {
            $aP["cartinfo"] = \HaaseIT\HCSF\Shop\Helper::getShoppingcartData($container);
        }

        $aP["countrylist"][] = ' | ';
        foreach ($container['conf']['countries']["countries_".$container['lang']] as $sKey => $sValue) {
            $aP["countrylist"][] = $sKey.'|'.$sValue;
        }

        if (
            $container['conf']['core']["enable_module_shop"]
            && (
                $aP["pagetype"] == 'itemoverview'
                || $aP["pagetype"] == 'itemoverviewgrpd'
                || $aP["pagetype"] == 'itemdetail'
            )
        ) {
            $aP = \HaaseIT\HCSF\Shop\Helper::handleItemPage($container, $P, $aP);
        }

        $aP["content"] = $P->oPayload->cl_html;

        $aP["content"] = str_replace("@", "&#064;", $aP["content"]); // Change @ to HTML Entity -> maybe less spam mails

        if ($container['conf']['core']['debug']) {
            self::getDebug($aP, $P);
        }

        $aP["debugdata"] = \HaaseIT\Tools::$sDebug;

        return $aP;
    }

    private static function getDebug($aP, $P)
    {
        if (!empty($_POST)) {
            \HaaseIT\Tools::debug($_POST, '$_POST');
        } elseif (!empty($_REQUEST)) {
            \HaaseIT\Tools::debug($_REQUEST, '$_REQUEST');
        }
        if (!empty($_SESSION)) {
            \HaaseIT\Tools::debug($_SESSION, '$_SESSION');
        }
        \HaaseIT\Tools::debug($aP, '$aP');
        \HaaseIT\Tools::debug($P, '$P');
    }

    private static function getLangSelector($container)
    {
        $sLangselector = '';
        if ($container['conf']['core']["lang_detection_method"] == 'domain') {
            $aSessionGetVarsForLangSelector = [];
            if (session_status() == PHP_SESSION_ACTIVE) {
                $aSessionGetVarsForLangSelector[session_name()] = session_id();
            }
            $aRequestURL = parse_url($_SERVER["REQUEST_URI"]);
        }
        foreach ($container['conf']['core']["lang_available"] as $sKey => $sValue) {
            if ($container['lang'] != $sKey) {
                if ($container['conf']['core']["lang_detection_method"] == 'domain') {
                    $sLangselector .= '<a href="//www.' . $container['conf']['core']["lang_by_domain"][$sKey] . $aRequestURL["path"] . \HaaseIT\Tools::makeLinkHRefWithAddedGetVars('', $aSessionGetVarsForLangSelector) . '">' . $container['textcats']->T("misc_language_" . $sKey) . '</a> ';
                } else {
                    $sLangselector .= '<a href="' . \HaaseIT\Tools::makeLinkHRefWithAddedGetVars('', ['language' => $sKey]) . '">' . $container['textcats']->T("misc_language_" . $sKey) . '</a> ';
                }
            }
        }
        $sLangselector = \HaaseIT\Tools::cutStringend($sLangselector, 1);

        return $sLangselector;
    }

    public static function getLanguage($container)
    {
        $langavailable = $container['conf']['core']["lang_available"];
        if (
            $container['conf']['core']["lang_detection_method"] == 'domain'
            && isset($container['conf']['core']["lang_by_domain"])
            && is_array($container['conf']['core']["lang_by_domain"])
        ) { // domain based language detection
            foreach ($container['conf']['core']["lang_by_domain"] as $sKey => $sValue) {
                if ($_SERVER["SERVER_NAME"] == $sValue || $_SERVER["SERVER_NAME"] == 'www.'.$sValue) {
                    $sLang = $sKey;
                    break;
                }
            }
        } elseif ($container['conf']['core']["lang_detection_method"] == 'legacy') { // legacy language detection
            $sLang = key($langavailable);
            if (isset($_GET["language"]) && array_key_exists($_GET["language"], $langavailable)) {
                $sLang = strtolower($_GET["language"]);
                setcookie('language', strtolower($_GET["language"]), 0, '/');
            } elseif (isset($_COOKIE["language"]) && array_key_exists($_COOKIE["language"], $langavailable)) {
                $sLang = strtolower($_COOKIE["language"]);
            } elseif (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) && array_key_exists(substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2), $langavailable)) {
                $sLang = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2);
            }
        }
        if (!isset($sLang)) {
            $sLang = key($langavailable);
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
            $configkey = 'textcat';
            $configsection = 'core';
        } elseif ($purpose == 'page') {
            $configkey = 'pagetext';
            $configsection = 'core';
        } elseif ($purpose == 'item') {
            $configkey = 'itemtext';
            $configsection = 'shop';
        } elseif ($purpose == 'itemgroup') {
            $configkey = 'itemgrouptext';
            $configsection = 'shop';
        } else {
            return false;
        }

        if (
            isset($C[$configsection][$configkey.'_unsafe_html_whitelist'])
            && trim($C[$configsection][$configkey.'_unsafe_html_whitelist']) != ''
        ) {
            $purifier_config->set('HTML.Allowed', $C[$configsection][$configkey.'_unsafe_html_whitelist']);
        }
        if (isset($C[$configsection][$configkey.'_loose_filtering']) && $C[$configsection][$configkey.'_loose_filtering']) {
            $purifier_config->set('HTML.Trusted', true);
            $purifier_config->set('Attr.EnableID', true);
            $purifier_config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);
        }

        return new \HTMLPurifier($purifier_config);
    }
}