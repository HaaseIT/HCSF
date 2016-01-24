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

namespace HaaseIT\HCSF\Controller\Customer;

class Resetpassword extends Base
{
    public function __construct($C, $DB, $sLang, $twig, $oItem)
    {
        parent::__construct($C, $DB, $sLang);

        if (\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
        } else {
            if (!isset($_GET["key"]) || !isset($_GET["email"]) || trim($_GET["key"]) == '' || trim($_GET["email"]) == '' || !\filter_var($_GET["email"], FILTER_VALIDATE_EMAIL)) {
                $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
            } else {
                $sQ = "SELECT * FROM ".DB_CUSTOMERTABLE." WHERE ".DB_CUSTOMERFIELD_EMAIL." = :email AND ".DB_CUSTOMERFIELD_PWRESETCODE." = :pwresetcode AND ".DB_CUSTOMERFIELD_PWRESETCODE." != ''";

                $sEmail = filter_var(trim(\HaaseIT\Tools::getFormfield("email")), FILTER_SANITIZE_EMAIL);

                $hResult = $DB->prepare($sQ);
                $hResult->bindValue(':email', $sEmail, \PDO::PARAM_STR);
                $hResult->bindValue(':pwresetcode', filter_var(trim(\HaaseIT\Tools::getFormfield("key")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW), \PDO::PARAM_STR);
                $hResult->execute();
                if ($hResult->rowCount() != 1) {
                    $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
                } else {
                    $aErr = array();
                    $aResult = $hResult->fetch();
                    $iTimestamp = time();
                    if ($aResult[DB_CUSTOMERFIELD_PWRESETTIMESTAMP] < $iTimestamp - DAY) {
                        $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("pwreset_error_expired");
                    } else {
                        $this->P->cb_customcontenttemplate = 'customer/resetpassword';
                        $this->P->cb_customdata["pwreset"]["minpwlength"] = $C["minimum_length_password"];
                        if (isset($_POST["doSend"]) && $_POST["doSend"] == 'yes') {
                            $aErr = $this->handlePasswordReset($aErr, $aResult[DB_CUSTOMERTABLE_PKEY]);
                            if (count($aErr) == 0) {
                                $this->P->cb_customdata["pwreset"]["showsuccessmessage"] = true;
                            } else {
                                $this->P->cb_customdata["pwreset"]["errors"] = $aErr;
                            }
                        }
                    }
                }
            }
        }
    }

    private function handlePasswordReset($aErr, $iID) {
        if (isset($_POST["pwd"]) && trim($_POST["pwd"]) != '') {
            if (strlen($_POST["pwd"]) < $this->C["minimum_length_password"] || strlen($_POST["pwd"]) > $C["maximum_length_password"]) $aErr[] = 'pwlength';
            if ($_POST["pwd"] != $_POST["pwdc"]) $aErr[] = 'pwmatch';
            if (count($aErr) == 0) {
                $sEnc = crypt($_POST["pwd"], $this->C["blowfish_salt"]);
                $aData = array(
                    DB_CUSTOMERFIELD_PASSWORD => $sEnc,
                    DB_CUSTOMERFIELD_PWRESETCODE => '',
                    DB_CUSTOMERTABLE_PKEY => $iID,
                );
                $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_CUSTOMERTABLE, DB_CUSTOMERTABLE_PKEY);
                $hResult = $this->DB->prepare($sQ);
                foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
                $hResult->execute();
            }
        } else {
            $aErr[] = 'nopw';
        }

        return $aErr;
    }

}