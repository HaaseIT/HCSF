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

namespace HaaseIT\HCSF\Customer;

use HaaseIT\Tools;
use HaaseIT\HCSF\HardcodedText;

class Helper
{

    public static function validateCustomerForm($C, $sLang, $aErr = [], $bEdit = false)
    {
        if (!isset($_POST["email"]) || !\filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) $aErr["email"] = true;
        if ($C['customer']["validate_corpname"] && (!isset($_POST["corpname"]) || strlen(trim($_POST["corpname"])) < 3)) $aErr["corpname"] = true;
        if ($C['customer']["validate_name"] && (!isset($_POST["name"]) || strlen(trim($_POST["name"])) < 3)) $aErr["name"] = true;
        if ($C['customer']["validate_street"] && (!isset($_POST["street"]) || strlen(trim($_POST["street"])) < 3)) $aErr["street"] = true;
        if ($C['customer']["validate_zip"] && (!isset($_POST["zip"]) || strlen(trim($_POST["zip"])) < 4)) $aErr["zip"] = true;
        if ($C['customer']["validate_town"] && (!isset($_POST["town"]) || strlen(trim($_POST["town"])) < 3)) $aErr["town"] = true;
        if ($C['customer']["validate_phone"] && (!isset($_POST["phone"]) || strlen(trim($_POST["phone"])) < 6)) $aErr["phone"] = true;
        if ($C['customer']["validate_cellphone"] && (!isset($_POST["cellphone"]) || strlen(trim($_POST["cellphone"])) < 11)) $aErr["cellphone"] = true;
        if ($C['customer']["validate_fax"] && (!isset($_POST["fax"]) || strlen(trim($_POST["fax"]))) < 6) $aErr["fax"] = true;
        if ($C['customer']["validate_country"] && (!isset($_POST["country"]) || !isset($C['countries']["countries_".$sLang][$_POST["country"]]))) $aErr["country"] = true;
        if (!$bEdit && (!isset($_POST["tos"]) || $_POST["tos"] != 'y')) $aErr["tos"] = true;
        if (!$bEdit && (!isset( $_POST["cancellationdisclaimer"] ) || $_POST["cancellationdisclaimer"] != 'y')) $aErr["cancellationdisclaimer"] = true;

        if (!$bEdit || (isset($_POST["pwd"]) && trim($_POST["pwd"]) != '')) {
            if (
                strlen($_POST["pwd"]) < $C['customer']["minimum_length_password"]
                || strlen($_POST["pwd"]) > $C['customer']["maximum_length_password"]
            ) $aErr["passwordlength"] = true;
            if ($_POST["pwd"] != $_POST["pwdc"]) $aErr["passwordmatch"] = true;
        }

        return $aErr;
    }

    public static function getDefaultCountryByConfig($C, $sLang) {
        if (isset($C['core']["defaultcountrybylang"][$sLang])) {
            return $C['core']["defaultcountrybylang"][$sLang];
        }
        return '';
    }

    public static function getCustomerFormDefaultValue($sKeyConfig, $sKeyForm, $aUserData) {
        $sDefaultValue = self::getUserData($sKeyConfig, $aUserData);
        if (!$sDefaultValue && isset($_SESSION["formsave_addrform"][$sKeyForm])) $sDefaultValue = $_SESSION["formsave_addrform"][$sKeyForm];

        return $sDefaultValue;
    }

    public static function buildCustomerForm($C, $sLang, $sPurpose = 'none', $sErr = '', $aUserData = false)
    {
        $sDefaultCountry = self::getCustomerFormDefaultValue('cust_country', "country", $aUserData);

        // Purposes: shoppingcart, userhome, shopadmin, editprofile, register
        // fv = field_value, fr = field_required
        $aData = [
            'purpose' => $sPurpose,
            'errormessage' => $sErr,
            'readonlycustno' => ($sPurpose == 'shopadmin' ? true : false),
            'readonly' => (
                $sPurpose == 'shopadmin'
                || $sPurpose == 'userhome'
                || ($sPurpose == 'editprofile' && !$C['customer']["allow_edituserprofile"])
                || ($sPurpose == 'shoppingcart' && self::getUserData())
                    ? true
                    : false
            ),
            'fv_custno' => Tools::getFormfield(
                'custno',
                self::getCustomerFormDefaultValue('cust_no', "custno", $aUserData),
                true
            ),
            'fv_email' => Tools::getFormfield(
                'email',
                self::getCustomerFormDefaultValue('cust_email', "email", $aUserData),
                true
            ),
            'fv_corpname' => Tools::getFormfield(
                'corpname',
                self::getCustomerFormDefaultValue('cust_corp', "corpname", $aUserData),
                true
            ),
            'fr_corpname' => $C['customer']["validate_corpname"],
            'fv_name' => Tools::getFormfield(
                'name',
                self::getCustomerFormDefaultValue('cust_name', "name", $aUserData),
                true
            ),
            'fr_name' => $C['customer']["validate_name"],
            'fv_street' => Tools::getFormfield(
                'street',
                self::getCustomerFormDefaultValue('cust_street', "street", $aUserData),
                true
            ),
            'fr_street' => $C['customer']["validate_street"],
            'fv_zip' => Tools::getFormfield(
                'zip',
                self::getCustomerFormDefaultValue('cust_zip', "zip", $aUserData),
                true
            ),
            'fr_zip' => $C['customer']["validate_zip"],
            'fv_town' => Tools::getFormfield(
                'town',
                self::getCustomerFormDefaultValue('cust_town', "town", $aUserData),
                true
            ),
            'fr_town' => $C['customer']["validate_town"],
            'fv_phone' => Tools::getFormfield(
                'phone',
                self::getCustomerFormDefaultValue('cust_phone', "phone", $aUserData),
                true
            ),
            'fr_phone' => $C['customer']["validate_phone"],
            'fv_cellphone' => Tools::getFormfield(
                'cellphone',
                self::getCustomerFormDefaultValue('cust_cellphone', "cellphone", $aUserData),
                true
            ),
            'fr_cellphone' => $C['customer']["validate_cellphone"],
            'fv_fax' => Tools::getFormfield(
                'fax',
                self::getCustomerFormDefaultValue('cust_fax', "fax", $aUserData),
                true
            ),
            'fr_fax' => $C['customer']["validate_fax"],
            'fv_country' => Tools::getFormfield(
                'country',
                ($sDefaultCountry ? $sDefaultCountry : self::getDefaultCountryByConfig($C, $sLang)),
                true
            ),
            'fr_country' => $C['customer']["validate_country"],
        ];

        if ($sPurpose == 'admin') {
            $aData["fv_custgroups"] = $C['customer']["customer_groups"];
            $aData["fv_custgroup_selected"] = Tools::getFormfield('custgroup', self::getUserData('cust_group', $aUserData), true);
        } elseif ($sPurpose == 'shopadmin') {
            $aData["fv_custgroup"] = '';
            if (isset($C['customer']["customer_groups"][self::getUserData('cust_group', $aUserData)])) {
                $aData["fv_custgroup"] = $C['customer']["customer_groups"][self::getUserData('cust_group', $aUserData)];
            }
        }

        if ($sPurpose == 'admin' || $sPurpose == 'register' || $sPurpose == 'editprofile') {
            $aData["fv_pwd"] = (($sPurpose == 'admin' || $sPurpose == 'editprofile') ? '' : Tools::getFormfield('pwd', ''));
            $aData["fv_pwdc"] = (($sPurpose == 'admin' || $sPurpose == 'editprofile') ? '' : Tools::getFormfield('pwdc', ''));
        }

        if ($sPurpose == 'shoppingcart') {
            $sRememberedRemarks = '';
            if (isset($_SESSION["formsave_addrform"]["remarks"])) {
                $sRememberedRemarks = $_SESSION["formsave_addrform"]["remarks"];
            }
            $aData["fv_remarks"] = Tools::getFormfield('remarks', $sRememberedRemarks, true);
        }

        if ($sPurpose == 'shoppingcart' || $sPurpose == 'register') {
            if (!self::getUserData()) {
                $aData["fv_tos"] = Tools::getCheckbox('tos', 'y');
                $aData["fv_cancellationdisclaimer"] = Tools::getCheckbox('cancellationdisclaimer', 'y');
            }
        }

        if ($sPurpose == 'shoppingcart') {
            $aData["fv_paymentmethods"] = $C['shop']["paymentmethods"];
            $aData["fv_paymentmethod"] = Tools::getFormfield('paymentmethod', '');
        }

        if ($sPurpose == 'admin') {
            $aData["fv_active"] = ((self::getUserData('cust_active', $aUserData) == 'y') ? true : false);
            $aData["fv_emailverified"] = ((self::getUserData('cust_emailverified', $aUserData) == 'y') ? true : false);
        }
        return $aData;
    }

    public static function sendVerificationMail($sEmailVerificationcode, $sTargetAddress, $container, $bCust = false)
    {
        if ($bCust) {
            $sSubject = $container['textcats']->T("register_mail_emailverification_subject");

            $aP['link'] = 'http'.(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on' ? 's' : '').'://';
            $aP['link'] .= $_SERVER["SERVER_NAME"].'/_misc/verifyemail.html?key='.$sEmailVerificationcode;

            $sMessage = $container['twig']->render('customer/sendverificationmail.twig', $aP);
        }
        else {
            $sSubject = HardcodedText::get('newcustomerregistration_mail_subject');
            $sMessage = HardcodedText::get('newcustomerregistration_mail_text1').' ';
            $sMessage .= $sTargetAddress.HardcodedText::get(
                'newcustomerregistration_mail_text2').' '.date($container['conf']['core']['locale_format_date_time']
                );
            $sTargetAddress = $container['conf']['core']["email_sender"];
        }

        \HaaseIT\HCSF\Helper::mailWrapper($container['conf'], $sTargetAddress, $sSubject, $sMessage);
    }

    public static function getUserData($sField = '', $aUserdata = false)
    {
        if (!$aUserdata) {
            if (!isset($_SESSION["user"]) || !is_array($_SESSION["user"])) return false;
            elseif ($sField == '') return true;

            if ($sField != '' && isset($_SESSION["user"][$sField]) && $_SESSION["user"][$sField] != '') return $_SESSION["user"][$sField];
        } else {
            if (isset($aUserdata[$sField])) return $aUserdata[$sField];
            elseif ($sField = '') return false;
        }
    }
}