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

use HaaseIT\HCSF\HelperConfig;
use HaaseIT\Toolbox\Tools;
use HaaseIT\HCSF\HardcodedText;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Helper
 * @package HaaseIT\HCSF\Customer
 */
class Helper
{
    /**
     * @param string $sLang
     * @param array $aErr
     * @param bool $bEdit
     * @return array
     */
    public static function validateCustomerForm($sLang, $aErr = [], $bEdit = false)
    {
        if (empty(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL))) {
            $aErr['email'] = true;
        }
        $postcorpname = filter_input(INPUT_POST, 'corpname', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (HelperConfig::$customer['validate_corpname'] && (empty($postcorpname) || strlen(trim($postcorpname)) < 3)) {
            $aErr['corpname'] = true;
        }
        $postname = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (HelperConfig::$customer['validate_name'] && (empty($postname) || strlen(trim($postname)) < 3)) {
            $aErr['name'] = true;
        }
        $poststreet = filter_input(INPUT_POST, 'street', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (HelperConfig::$customer['validate_street'] && (empty($poststreet) || strlen(trim($poststreet)) < 3)) {
            $aErr['street'] = true;
        }
        $postzip = filter_input(INPUT_POST, 'zip', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (HelperConfig::$customer['validate_zip'] && (empty($postzip) || strlen(trim($postzip)) < 4)) {
            $aErr['zip'] = true;
        }
        $posttown = filter_input(INPUT_POST, 'town', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (HelperConfig::$customer['validate_town'] && (empty($posttown) || strlen(trim($posttown)) < 3)) {
            $aErr['town'] = true;
        }
        $postphone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (HelperConfig::$customer['validate_phone'] && (empty($postphone) || strlen(trim($postphone)) < 6)) {
            $aErr['phone'] = true;
        }
        $postcellphone = filter_input(INPUT_POST, 'cellphone', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (HelperConfig::$customer['validate_cellphone'] && (empty($postcellphone) || strlen(trim($postcellphone)) < 11)) {
            $aErr['cellphone'] = true;
        }
        $postfax = filter_input(INPUT_POST, 'fax', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (HelperConfig::$customer['validate_fax'] && (empty($postfax) || strlen(trim($postfax)) < 6)) {
            $aErr['fax'] = true;
        }
        $postcountry = filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (HelperConfig::$customer['validate_country'] && (empty($postcountry) || !isset(HelperConfig::$countries['countries_' .$sLang][$postcountry]))) {
            $aErr['country'] = true;
        }
        $posttos = filter_input(INPUT_POST, 'tos');
        if (!$bEdit && $posttos !== 'y') {
            $aErr['tos'] = true;
        }
        $postcancellationdisclaimer = filter_input(INPUT_POST, 'cancellationdisclaimer');
        if (!$bEdit && $postcancellationdisclaimer !== 'y') {
            $aErr['cancellationdisclaimer'] = true;
        }

        $postpwd = filter_input(INPUT_POST, 'pwd');
        $postpwdc = filter_input(INPUT_POST, 'pwdc');
        if (!$bEdit || !empty($postpwd)) {
            if (strlen($postpwd) < HelperConfig::$customer['minimum_length_password']) {
                $aErr['passwordlength'] = true;
            }
            if ($postpwd !== $postpwdc) {
                $aErr['passwordmatch'] = true;
            }
        }

        return $aErr;
    }

    /**
     * @param string $sLang
     * @return string
     */
    public static function getDefaultCountryByConfig($sLang) {
        if (isset(HelperConfig::$core['defaultcountrybylang'][$sLang])) {
            return HelperConfig::$core['defaultcountrybylang'][$sLang];
        }
        return '';
    }

    /**
     * @param string $sKeyConfig
     * @param string $sKeyForm
     * @param array|bool $aUserData
     * @return bool
     */
    public static function getCustomerFormDefaultValue($sKeyConfig, $sKeyForm, $aUserData) {
        $sDefaultValue = self::getUserData($sKeyConfig, $aUserData);
        if (!$sDefaultValue && isset($_SESSION['formsave_addrform'][$sKeyForm])) {
            $sDefaultValue = $_SESSION['formsave_addrform'][$sKeyForm];
        }

        return $sDefaultValue;
    }

    /**
     * @param string $sLang
     * @param string $sPurpose
     * @param array $aErr
     * @param bool $aUserData
     * @return array
     */
    public static function buildCustomerForm($sLang, $sPurpose = 'none', $aErr = [], $aUserData = false)
    {
        $sDefaultCountry = self::getCustomerFormDefaultValue('cust_country', 'country', $aUserData);

        // Purposes: shoppingcart, userhome, shopadmin, editprofile, register
        // fv = field_value, fr = field_required
        $aData = [
            'purpose' => $sPurpose,
            'errormessage' => $aErr,
            'readonlycustno' => $sPurpose === 'shopadmin' ? true : false,
            'readonly' =>
                $sPurpose === 'shopadmin'
                || $sPurpose === 'userhome'
                || ($sPurpose === 'editprofile' && !HelperConfig::$customer['allow_edituserprofile'])
                || ($sPurpose === 'shoppingcart' && self::getUserData())
            ,
            'fv_custno' => Tools::getFormfield(
                'custno',
                self::getCustomerFormDefaultValue('cust_no', 'custno', $aUserData),
                true
            ),
            'fv_email' => Tools::getFormfield(
                'email',
                self::getCustomerFormDefaultValue('cust_email', 'email', $aUserData),
                true
            ),
            'fv_corpname' => Tools::getFormfield(
                'corpname',
                self::getCustomerFormDefaultValue('cust_corp', 'corpname', $aUserData),
                true
            ),
            'fr_corpname' => HelperConfig::$customer['validate_corpname'],
            'fv_name' => Tools::getFormfield(
                'name',
                self::getCustomerFormDefaultValue('cust_name', 'name', $aUserData),
                true
            ),
            'fr_name' => HelperConfig::$customer['validate_name'],
            'fv_street' => Tools::getFormfield(
                'street',
                self::getCustomerFormDefaultValue('cust_street', 'street', $aUserData),
                true
            ),
            'fr_street' => HelperConfig::$customer['validate_street'],
            'fv_zip' => Tools::getFormfield(
                'zip',
                self::getCustomerFormDefaultValue('cust_zip', 'zip', $aUserData),
                true
            ),
            'fr_zip' => HelperConfig::$customer['validate_zip'],
            'fv_town' => Tools::getFormfield(
                'town',
                self::getCustomerFormDefaultValue('cust_town', 'town', $aUserData),
                true
            ),
            'fr_town' => HelperConfig::$customer['validate_town'],
            'fv_phone' => Tools::getFormfield(
                'phone',
                self::getCustomerFormDefaultValue('cust_phone', 'phone', $aUserData),
                true
            ),
            'fr_phone' => HelperConfig::$customer['validate_phone'],
            'fv_cellphone' => Tools::getFormfield(
                'cellphone',
                self::getCustomerFormDefaultValue('cust_cellphone', 'cellphone', $aUserData),
                true
            ),
            'fr_cellphone' => HelperConfig::$customer['validate_cellphone'],
            'fv_fax' => Tools::getFormfield(
                'fax',
                self::getCustomerFormDefaultValue('cust_fax', 'fax', $aUserData),
                true
            ),
            'fr_fax' => HelperConfig::$customer['validate_fax'],
            'fv_country' => Tools::getFormfield(
                'country',
                ($sDefaultCountry ? $sDefaultCountry : self::getDefaultCountryByConfig($sLang)),
                true
            ),
            'fr_country' => HelperConfig::$customer['validate_country'],
        ];

        if ($sPurpose === 'admin') {
            $aData['fv_custgroups'] = HelperConfig::$customer['customer_groups'];
            $aData['fv_custgroup_selected'] = Tools::getFormfield('custgroup', self::getUserData('cust_group', $aUserData), true);
        } elseif ($sPurpose === 'shopadmin') {
            $aData['fv_custgroup'] = '';
            if (isset(HelperConfig::$customer['customer_groups'][self::getUserData('cust_group', $aUserData)])) {
                $aData['fv_custgroup'] = HelperConfig::$customer['customer_groups'][self::getUserData('cust_group', $aUserData)];
            }
        }

        if ($sPurpose === 'admin' || $sPurpose === 'register' || $sPurpose === 'editprofile') {
            $aData['fv_pwd'] = (($sPurpose === 'admin' || $sPurpose === 'editprofile') ? '' : Tools::getFormfield('pwd', ''));
            $aData['fv_pwdc'] = (($sPurpose === 'admin' || $sPurpose === 'editprofile') ? '' : Tools::getFormfield('pwdc', ''));
        }

        if ($sPurpose === 'shoppingcart') {
            $sRememberedRemarks = '';
            if (isset($_SESSION['formsave_addrform']['remarks'])) {
                $sRememberedRemarks = $_SESSION['formsave_addrform']['remarks'];
            }
            $aData['fv_remarks'] = Tools::getFormfield('remarks', $sRememberedRemarks, true);
        }

        if ($sPurpose === 'shoppingcart' || $sPurpose === 'register') {
            if (!self::getUserData()) {
                $aData['fv_tos'] = Tools::getCheckbox('tos', 'y');
                $aData['fv_cancellationdisclaimer'] = Tools::getCheckbox('cancellationdisclaimer', 'y');
            }
        }

        if ($sPurpose === 'shoppingcart') {
            $aData['fv_paymentmethods'] = HelperConfig::$shop['paymentmethods'];
            $aData['fv_paymentmethod'] = Tools::getFormfield('paymentmethod', '');
        }

        if ($sPurpose === 'admin') {
            $aData['fv_active'] = self::getUserData('cust_active', $aUserData) === 'y';
            $aData['fv_emailverified'] = self::getUserData('cust_emailverified', $aUserData) === 'y';
        }
        return $aData;
    }

    /**
     * @param $sEmailVerificationcode
     * @param $sTargetAddress
     * @param ServiceManager $serviceManager
     * @param bool $bCust
     */
    public static function sendVerificationMail($sEmailVerificationcode, $sTargetAddress, ServiceManager $serviceManager, $bCust = false)
    {
        if ($bCust) {
            $sSubject = $serviceManager->get('textcats')->T('register_mail_emailverification_subject');

            $serverhttps = filter_input(INPUT_SERVER, 'HTTPS');
            $servername = filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_URL);
            $aP['link'] = 'http'.($serverhttps === 'on' ? 's' : '').'://';
            $aP['link'] .= $servername.'/_misc/verifyemail.html?key='.$sEmailVerificationcode;

            $sMessage = $serviceManager->get('twig')->render('customer/sendverificationmail.twig', $aP);
        } else {
            $sSubject = HardcodedText::get('newcustomerregistration_mail_subject');
            $sMessage = HardcodedText::get('newcustomerregistration_mail_text1').' ';
            $sMessage .= $sTargetAddress.HardcodedText::get(
                'newcustomerregistration_mail_text2').' '.date(HelperConfig::$core['locale_format_date_time']
                );
            $sTargetAddress = HelperConfig::$core['email_sender'];
        }

        \HaaseIT\HCSF\Helper::mailWrapper($sTargetAddress, $sSubject, $sMessage);
    }

    /**
     * @param string $sField
     * @param bool $aUserdata
     * @return bool
     */
    public static function getUserData($sField = '', $aUserdata = false)
    {
        if (!$aUserdata) {
            if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
                return false;
            } elseif ($sField == '') {
                return true;
            }

            if ($sField != '' && isset($_SESSION['user'][$sField]) && $_SESSION['user'][$sField] != '') {
                return $_SESSION['user'][$sField];
            }
        } else {
            if (isset($aUserdata[$sField])) {
                return $aUserdata[$sField];
            } elseif ($sField = '') {
                return false;
            }
        }
    }
}
