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


class Helper
{

    public static function validateCustomerForm($C, $sLang, $aErr = array(), $bEdit = false)
    {
        if (!isset($_POST["email"]) || !\filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) $aErr["email"] = true;
        if ($C["validate_corpname"] && (!isset($_POST["corpname"]) || strlen(trim($_POST["corpname"])) < 3)) $aErr["corpname"] = true;
        if ($C["validate_name"] && (!isset($_POST["name"]) || strlen(trim($_POST["name"])) < 3)) $aErr["name"] = true;
        if ($C["validate_street"] && (!isset($_POST["street"]) || strlen(trim($_POST["street"])) < 3)) $aErr["street"] = true;
        if ($C["validate_zip"] && (!isset($_POST["zip"]) || strlen(trim($_POST["zip"])) < 4)) $aErr["zip"] = true;
        if ($C["validate_town"] && (!isset($_POST["town"]) || strlen(trim($_POST["town"])) < 3)) $aErr["town"] = true;
        if ($C["validate_phone"] && (!isset($_POST["phone"]) || strlen(trim($_POST["phone"])) < 6)) $aErr["phone"] = true;
        if ($C["validate_cellphone"] && (!isset($_POST["cellphone"]) || strlen(trim($_POST["cellphone"])) < 11)) $aErr["cellphone"] = true;
        if ($C["validate_fax"] && (!isset($_POST["fax"]) || strlen(trim($_POST["fax"]))) < 6) $aErr["fax"] = true;
        if ($C["validate_country"] && (!isset($_POST["country"]) || !isset($C["countries_".$sLang][$_POST["country"]]))) $aErr["country"] = true;
        if (!$bEdit && (!isset($_POST["tos"]) || $_POST["tos"] != 'y')) $aErr["tos"] = true;
        if (!$bEdit && (!isset( $_POST["cancellationdisclaimer"] ) || $_POST["cancellationdisclaimer"] != 'y')) $aErr["cancellationdisclaimer"] = true;

        if (!$bEdit || (isset($_POST["pwd"]) && trim($_POST["pwd"]) != '')) {
            if (strlen($_POST["pwd"]) < $C["minimum_length_password"] || strlen($_POST["pwd"]) > $C["maximum_length_password"]) $aErr["passwordlength"] = true;
            if ($_POST["pwd"] != $_POST["pwdc"]) $aErr["passwordmatch"] = true;
        }

        return $aErr;
    }

    public static function getDefaultCountryByConfig($C, $sLang) {
        if (isset($C["defaultcountrybylang"][$sLang])) {
            $sDefaultCountryByConfig = $C["defaultcountrybylang"][$sLang];
        } else {
            $sDefaultCountryByConfig = '';
        }
        return $sDefaultCountryByConfig;
    }

    public static function getCustomerFormDefaultValue($sKeyConfig, $sKeyForm, $aUserData) {
        $sDefaultValue = self::getUserData($sKeyConfig, $aUserData);
        if (!$sDefaultValue && isset($_SESSION["formsave_addrform"][$sKeyForm])) $sDefaultValue = $_SESSION["formsave_addrform"][$sKeyForm];

        return $sDefaultValue;
    }

    public static function buildCustomerForm($C, $sLang, $sPurpose = 'none', $sErr = '', $aUserData = false)
    {
        // Purposes: shoppingcart, userhome, shopadmin, editprofile, register
        $aData["purpose"] = $sPurpose;
        $aData["errormessage"] = $sErr;
        $aData["readonly"] = false;
        $aData["readonlycustno"] = false;
        if ($sPurpose == 'shopadmin') {
            $aData["readonly"] = true;
            $aData["readonlycustno"] = true;
        }
        if ($sPurpose == 'userhome') $aData["readonly"] = true;
        if ($sPurpose == 'editprofile' && !$C["allow_edituserprofile"]) $aData["readonly"] = true;
        if ($sPurpose == 'shoppingcart' && self::getUserData()) $aData["readonly"] = true;

        // fv = field_value, fr = field_required
        $sDefaultCustno = self::getCustomerFormDefaultValue('cust_no', "custno", $aUserData);
        $aData["fv_custno"] = \HaaseIT\Tools::getFormField('custno', $sDefaultCustno, true);

        $sDefaultEmail = self::getCustomerFormDefaultValue('cust_email', "email", $aUserData);
        $aData["fv_email"] = \HaaseIT\Tools::getFormField('email', $sDefaultEmail, true);

        $sDefaultCorpname = self::getCustomerFormDefaultValue('cust_corp', "corpname", $aUserData);
        $aData["fv_corpname"] = \HaaseIT\Tools::getFormField('corpname', $sDefaultCorpname, true);
        $aData["fr_corpname"] = $C["validate_corpname"];

        $sDefaultName = self::getCustomerFormDefaultValue('cust_name', "name", $aUserData);
        $aData["fv_name"] = \HaaseIT\Tools::getFormField('name', $sDefaultName, true);
        $aData["fr_name"] = $C["validate_name"];

        $sDefaultStreet = self::getCustomerFormDefaultValue('cust_street', "street", $aUserData);
        $aData["fv_street"] = \HaaseIT\Tools::getFormField('street', $sDefaultStreet, true);
        $aData["fr_street"] = $C["validate_street"];

        $sDefaultZip = self::getCustomerFormDefaultValue('cust_zip', "zip", $aUserData);
        $aData["fv_zip"] = \HaaseIT\Tools::getFormField('zip', $sDefaultZip, true);
        $aData["fr_zip"] = $C["validate_zip"];

        $sDefaultTown = self::getCustomerFormDefaultValue('cust_town', "town", $aUserData);
        $aData["fv_town"] = \HaaseIT\Tools::getFormField('town', $sDefaultTown, true);
        $aData["fr_town"] = $C["validate_town"];

        $sDefaultPhone = self::getCustomerFormDefaultValue('cust_phone', "phone", $aUserData);
        $aData["fv_phone"] = \HaaseIT\Tools::getFormField('phone', $sDefaultPhone, true);
        $aData["fr_phone"] = $C["validate_phone"];

        $sDefaultCellphone = self::getCustomerFormDefaultValue('cust_cellphone', "cellphone", $aUserData);
        $aData["fv_cellphone"] = \HaaseIT\Tools::getFormField('cellphone', $sDefaultCellphone, true);
        $aData["fr_cellphone"] = $C["validate_cellphone"];

        $sDefaultFax = self::getCustomerFormDefaultValue('cust_fax', "fax", $aUserData);
        $aData["fv_fax"] = \HaaseIT\Tools::getFormField('fax', $sDefaultFax, true);
        $aData["fr_fax"] = $C["validate_fax"];

        $sDefaultCountryByConfig = self::getDefaultCountryByConfig($C, $sLang);
        $sDefaultCountry = self::getCustomerFormDefaultValue('cust_country', "country", $aUserData);
        $aData["fv_country"] = \HaaseIT\Tools::getFormField('country', ($sDefaultCountry ? $sDefaultCountry : $sDefaultCountryByConfig), true);
        $aData["fr_country"] = $C["validate_country"];

        if ($sPurpose == 'admin') {
            $aData["fv_custgroups"] = $C["customer_groups"];
            $aData["fv_custgroup_selected"] = \HaaseIT\Tools::getFormField('custgroup', self::getUserData('cust_group', $aUserData), true);
        } elseif ($sPurpose == 'shopadmin') {
            if (isset($C["customer_groups"][self::getUserData('cust_group', $aUserData)])) {
                $aData["fv_custgroup"] = $C["customer_groups"][self::getUserData('cust_group', $aUserData)];
            } else {
                $aData["fv_custgroup"] = '';
            }
        }

        if ($sPurpose == 'admin' || $sPurpose == 'register' || $sPurpose == 'editprofile') {
            $aData["fv_pwd"] = (($sPurpose == 'admin' || $sPurpose == 'editprofile') ? '' : \HaaseIT\Tools::getFormField('pwd', ''));
            $aData["fv_pwdc"] = (($sPurpose == 'admin' || $sPurpose == 'editprofile') ? '' : \HaaseIT\Tools::getFormField('pwdc', ''));
        }

        if ($sPurpose == 'shoppingcart') {
            $sRememberedRemarks = '';
            if (isset($_SESSION["formsave_addrform"]["remarks"])) {
                $sRememberedRemarks = $_SESSION["formsave_addrform"]["remarks"];
            }
            $aData["fv_remarks"] = \HaaseIT\Tools::getFormField('remarks', $sRememberedRemarks, true);
        }

        if ($sPurpose == 'shoppingcart' || $sPurpose == 'register') {
            if (!self::getUserData()) {
                $aData["fv_tos"] = \HaaseIT\Tools::getCheckbox('tos', 'y');
                $aData["fv_cancellationdisclaimer"] = \HaaseIT\Tools::getCheckbox('cancellationdisclaimer', 'y');
            }
        }

        if ($sPurpose == 'shoppingcart') {
            $aData["fv_paymentmethods"] = $C["paymentmethods"];
            $aData["fv_paymentmethod"] = \HaaseIT\Tools::getFormField('paymentmethod', '');
        }

        if ($sPurpose == 'admin') {
            $aData["fv_active"] = ((self::getUserData('cust_active', $aUserData) == 'y') ? true : false);
            $aData["fv_emailverified"] = ((self::getUserData('cust_emailverified', $aUserData) == 'y') ? true : false);
        }
        return $aData;
    }

    public static function sendVerificationMail($sEmailVerificationcode, $sTargetAddress, $C, $twig, $bCust = false)
    {
        if ($bCust) {
            $sSubject = \HaaseIT\Textcat::T("register_mail_emailverification_subject");

            $aP['link'] = 'http'.(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on' ? 's' : '').'://';
            $aP['link'] .= $_SERVER["HTTP_HOST"].'/_misc/verifyemail.html?key='.$sEmailVerificationcode;

            $sMessage = $twig->render('customer/sendverificationmail.twig', $aP);
        }
        else {
            $sSubject = \HaaseIT\HCSF\HardcodedText::get('newcustomerregistration_mail_subject');
            $sMessage = \HaaseIT\HCSF\HardcodedText::get('newcustomerregistration_mail_text1').' ';
            $sMessage .= $sTargetAddress.\HaaseIT\HCSF\HardcodedText::get('newcustomerregistration_mail_text2').' '.date($C['locale_format_date_time']);
            $sTargetAddress = $C["email_sender"];
        }

        \HaaseIT\HCSF\Helper::mailWrapper($C, $sTargetAddress, $sSubject, $sMessage);
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