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

class Register extends Base
{
    public function __construct($C, $DB, $sLang, $twig, $oItem)
    {
        parent::__construct($C, $DB, $sLang);

        if (getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
        } else {
            $this->P->cb_customcontenttemplate = 'customer/register';

            $aErr = array();
            if (isset($_POST["doRegister"]) && $_POST["doRegister"] == 'yes') {
                $aErr = validateCustomerForm($C, $sLang, $aErr);
                if (count($aErr) == 0) {
                    $sQ = "SELECT " . DB_CUSTOMERFIELD_EMAIL . " FROM " . DB_CUSTOMERTABLE;
                    $sQ .= " WHERE " . DB_CUSTOMERFIELD_EMAIL . " = :email";

                    $sEmail = filter_var(trim(\HaaseIT\Tools::getFormfield("email")), FILTER_SANITIZE_EMAIL);
                    $hResult = $DB->prepare($sQ);
                    $hResult->bindValue(':email', $sEmail, \PDO::PARAM_STR);
                    $hResult->execute();
                    $iRows = $hResult->rowCount();

                    if ($iRows == 0) {
                        $sEmailVerificationcode = md5($_POST["email"] . time());
                        $aData = array(
                            DB_CUSTOMERFIELD_EMAIL => $sEmail,
                            DB_CUSTOMERFIELD_CORP => filter_var(trim(\HaaseIT\Tools::getFormfield("corpname")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            DB_CUSTOMERFIELD_NAME => filter_var(trim(\HaaseIT\Tools::getFormfield("name")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            DB_CUSTOMERFIELD_STREET => filter_var(trim(\HaaseIT\Tools::getFormfield("street")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            DB_CUSTOMERFIELD_ZIP => filter_var(trim(\HaaseIT\Tools::getFormfield("zip")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            DB_CUSTOMERFIELD_TOWN => filter_var(trim(\HaaseIT\Tools::getFormfield("town")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            DB_CUSTOMERFIELD_PHONE => filter_var(trim(\HaaseIT\Tools::getFormfield("phone")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            DB_CUSTOMERFIELD_CELLPHONE => filter_var(trim(\HaaseIT\Tools::getFormfield("cellphone")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            DB_CUSTOMERFIELD_FAX => filter_var(trim(\HaaseIT\Tools::getFormfield("fax")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            DB_CUSTOMERFIELD_COUNTRY => filter_var(trim(\HaaseIT\Tools::getFormfield("country")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            DB_CUSTOMERFIELD_PASSWORD => crypt($_POST["pwd"], $C["blowfish_salt"]),
                            DB_CUSTOMERFIELD_TOSACCEPTED => ((isset($_POST["tos"]) && $_POST["tos"] == 'y') ? 'y' : 'n'),
                            DB_CUSTOMERFIELD_CANCELLATIONDISCLAIMERACCEPTED => ((isset($_POST["cancellationdisclaimer"]) && $_POST["cancellationdisclaimer"] == 'y') ? 'y' : 'n'),
                            DB_CUSTOMERFIELD_EMAILVERIFIED => 'n',
                            DB_CUSTOMERFIELD_EMAILVERIFICATIONCODE => $sEmailVerificationcode,
                            DB_CUSTOMERFIELD_ACTIVE => (($C["register_require_manual_activation"]) ? 'n' : 'y'),
                            DB_CUSTOMERFIELD_REGISTRATIONTIMESTAMP => time(),
                        );
                        //debug( $aData );
                        $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aData, DB_CUSTOMERTABLE);
                        //debug( $sQ );

                        $hResult = $DB->prepare($sQ);
                        foreach ($aData as $sKey => $sValue) {
                            $hResult->bindValue(':' . $sKey, $sValue, \PDO::PARAM_STR);
                        }
                        $hResult->execute();

                        sendVerificationMail($sEmailVerificationcode, $sEmail, $C);
                        sendVerificationMail($sEmailVerificationcode, $sEmail, $C, true);
                        $aPData["showsuccessmessage"] = true;
                    } else {
                        $aErr["emailalreadyexists"] = true;
                        $this->P->cb_customdata["customerform"] = buildCustomerForm($C, $sLang, 'register', $aErr);
                    }
                } else {
                    $this->P->cb_customdata["customerform"] = buildCustomerForm($C, $sLang, 'register', $aErr);
                }
            } else {
                $this->P->cb_customdata["customerform"] = buildCustomerForm($C, $sLang, 'register');
            }
            if (isset($aPData) && count($aPData)) {
                $this->P->cb_customdata["register"] = $aPData;
            }
        }
    }
}