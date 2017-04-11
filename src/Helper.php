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

use HaaseIT\HCSF\Shop\Helper as SHelper;
use HaaseIT\Toolbox\Tools;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Helper
 * @package HaaseIT\HCSF
 */
class Helper
{
    /**
     * @param $file
     * @param int $width
     * @param int $height
     * @return bool|string
     */
    public static function getSignedGlideURL($file, $width = 0, $height = 0)
    {
        $urlBuilder = \League\Glide\Urls\UrlBuilderFactory::create('', HelperConfig::$secrets['glide_signkey']);

        $param = [];
        if ($width == 0 && $height == 0) {
            return false;
        }
        if ($width != 0) {
            $param['w'] = $width;
        }
        if ($height != 0) {
            $param['h'] = $height;
        }
        if ($width != 0 && $height != 0) {
            $param['fit'] = 'stretch';
        }

        return $urlBuilder->getUrl($file, $param);
    }

    /**
     * @param $to
     * @param string $subject
     * @param string $message
     * @param array $aImagesToEmbed
     * @param array $aFilesToAttach
     * @return bool
     */
    public static function mailWrapper($to, $subject = '(No subject)', $message = '', $aImagesToEmbed = [], $aFilesToAttach = []) {
        $mail = new \PHPMailer;
        $mail->CharSet = 'UTF-8';

        $mail->isMail();
        if (HelperConfig::$core['mail_method'] === 'sendmail') {
            $mail->isSendmail();
        } elseif (HelperConfig::$core['mail_method'] === 'smtp') {
            $mail->isSMTP();
            $mail->Host = HelperConfig::$secrets['mail_smtp_server'];
            $mail->Port = HelperConfig::$secrets['mail_smtp_port'];
            if (HelperConfig::$secrets['mail_smtp_auth'] === true) {
                $mail->SMTPAuth = true;
                $mail->Username = HelperConfig::$secrets['mail_smtp_auth_user'];
                $mail->Password = HelperConfig::$secrets['mail_smtp_auth_pwd'];
                if (HelperConfig::$secrets['mail_smtp_secure']) {
                    $mail->SMTPSecure = 'tls';
                    if (HelperConfig::$secrets['mail_smtp_secure_method'] === 'ssl') {
                        $mail->SMTPSecure = 'ssl';
                    }
                }
            }
        }

        $mail->From = HelperConfig::$core['email_sender'];
        $mail->FromName = HelperConfig::$core['email_sendername'];
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        if (is_array($aImagesToEmbed) && count($aImagesToEmbed)) {
            foreach ($aImagesToEmbed as $sKey => $imgdata) {
                $imginfo = getimagesizefromstring($imgdata['binimg']);
                $mail->addStringEmbeddedImage($imgdata['binimg'], $sKey, $sKey, 'base64', $imginfo['mime']);
            }
        }

        if (is_array($aFilesToAttach) && count($aFilesToAttach)) {
            foreach ($aFilesToAttach as $sValue) {
                if (file_exists($sValue)) {
                    $mail->addAttachment($sValue);
                }
            }
        }

        //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        return $mail->send();
    }

    // don't remove this, this is the fallback for unavailable twig functions
    /**
     * @param $string
     * @return mixed
     */
    public static function reachThrough($string) {
        return $string;
    }
    // don't remove this, this is the fallback for unavailable twig functions
    /**
     * @return string
     */
    public static function returnEmptyString() {
        return '';
    }

    /**
     * @param ServiceManager $serviceManager
     * @param Page $P
     * @return array
     */
    public static function generatePage(ServiceManager $serviceManager, \HaaseIT\HCSF\Page $P)
    {
        $requesturi = $serviceManager->get('request')->getRequestTarget();

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
            $aP = SHelper::handleItemPage($serviceManager, $P, $aP);
        }

        $aP['content'] = $P->oPayload->cl_html;

        $aP['content'] = str_replace('@', '&#064;', $aP['content']); // Change @ to HTML Entity -> maybe less spam mails

        $aP['lang_available'] = HelperConfig::$core['lang_available'];
        $aP['lang_detection_method'] = HelperConfig::$core['lang_detection_method'];
        $aP['lang_by_domain'] = HelperConfig::$core['lang_by_domain'];

        if (HelperConfig::$core['debug']) {
            self::getDebug($aP, $P);
            $aP['debugdata'] = Tools::$sDebug;
        }

        return $aP;
    }

    /**
     * @param array $aP
     * @param Page $P
     */
    private static function getDebug($aP, $P)
    {
        if (!empty($_POST)) {
            Tools::debug($_POST, '$_POST');
        } elseif (!empty($_REQUEST)) {
            Tools::debug($_REQUEST, '$_REQUEST');
        }
        if (!empty($_SESSION)) {
            Tools::debug($_SESSION, '$_SESSION');
        }
        Tools::debug($aP, '$aP');
        //Tools::debug($P, '$P');
    }

    /**
     * @return int|mixed|string
     */
    public static function getLanguage()
    {
        $langavailable = HelperConfig::$core['lang_available'];
        if (
            HelperConfig::$core['lang_detection_method'] === 'domain'
            && isset(HelperConfig::$core['lang_by_domain'])
            && is_array(HelperConfig::$core['lang_by_domain'])
        ) { // domain based language detection
            foreach (HelperConfig::$core['lang_by_domain'] as $sKey => $sValue) {
                if ($_SERVER['SERVER_NAME'] == $sValue || $_SERVER['SERVER_NAME'] == 'www.'.$sValue) {
                    $sLang = $sKey;
                    break;
                }
            }
        } elseif (HelperConfig::$core['lang_detection_method'] === 'legacy') { // legacy language detection
            $sLang = key($langavailable);
            if (isset($_GET['language']) && array_key_exists($_GET['language'], $langavailable)) {
                $sLang = strtolower($_GET['language']);
                setcookie('language', strtolower($_GET['language']), 0, '/');
            } elseif (isset($_COOKIE['language']) && array_key_exists($_COOKIE['language'], $langavailable)) {
                $sLang = strtolower($_COOKIE['language']);
            } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && array_key_exists(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2), $langavailable)) {
                $sLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            }
        }
        if (!isset($sLang)) {
            $sLang = key($langavailable);
        }

        return $sLang;
    }

    /**
     * @param string $purpose
     * @return bool|\HTMLPurifier
     */
    public static function getPurifier($purpose)
    {
        $purifier_config = \HTMLPurifier_Config::createDefault();
        $purifier_config->set('Core.Encoding', 'UTF-8');
        $purifier_config->set('Cache.SerializerPath', PATH_PURIFIERCACHE);
        $purifier_config->set('HTML.Doctype', HelperConfig::$core['purifier_doctype']);

        if ($purpose === 'textcat') {
            $configkey = 'textcat';
            $configsection = 'core';
        } elseif ($purpose === 'page') {
            $configkey = 'pagetext';
            $configsection = 'core';
        } elseif ($purpose === 'item') {
            $configkey = 'itemtext';
            $configsection = 'shop';
        } elseif ($purpose === 'itemgroup') {
            $configkey = 'itemgrouptext';
            $configsection = 'shop';
        } else {
            return false;
        }

        if (!empty(HelperConfig::${$configsection}[$configkey.'_unsafe_html_whitelist'])) {
            $purifier_config->set('HTML.Allowed', HelperConfig::${$configsection}[$configkey.'_unsafe_html_whitelist']);
        }
        if (!empty(HelperConfig::${$configsection}[$configkey.'_loose_filtering'])) {
            $purifier_config->set('HTML.Trusted', true);
            $purifier_config->set('Attr.EnableID', true);
            $purifier_config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);
        }

        return new \HTMLPurifier($purifier_config);
    }

    /**
     * @param $callback
     * @param $parameters
     * @return bool|mixed
     */
    public static function twigCallback($callback, $parameters)
    {
        $callbacks = [
            'renderItemStatusIcon' => '\HaaseIT\HCSF\Shop\Helper::renderItemStatusIcon',
            'shopadminMakeCheckbox' => '\HaaseIT\HCSF\Shop\Helper::shopadminMakeCheckbox',
        ];

        if (!isset($callbacks[$callback])) {
            return false;
        }
        
        return call_user_func($callbacks[$callback], $parameters);
    }
}