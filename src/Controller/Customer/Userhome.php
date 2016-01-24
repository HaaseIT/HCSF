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

class Userhome extends Base
{
    public function __construct($C, $DB, $sLang, $twig, $oItem)
    {
        parent::__construct($C, $DB, $sLang);

        if (!\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_notloggedin");
        } else {
            $this->P->cb_customcontenttemplate = 'customer/customerhome';

            //debug($_SESSION["user"]);
            $aPData["display_logingreeting"] = false;
            if (isset($_GET["login"]) && $_GET["login"]) {
                $aPData["display_logingreeting"] = true;
            }
            if (isset($_GET["editprofile"])) {
                $sErr = '';

                if (isset($_POST["doEdit"]) && $_POST["doEdit"] == 'yes') {
                    $sQ = "SELECT " . DB_ADDRESSFIELDS . " FROM " . DB_CUSTOMERTABLE;
                    $sQ .= " WHERE " . DB_CUSTOMERTABLE_PKEY . " != :id";
                    $sQ .= " AND " . DB_CUSTOMERFIELD_EMAIL . " = :email";

                    $sEmail = filter_var(trim(\HaaseIT\Tools::getFormfield("email")), FILTER_SANITIZE_EMAIL);

                    $hResult = $DB->prepare($sQ);
                    $hResult->bindValue(':id', $_SESSION["user"][DB_CUSTOMERTABLE_PKEY], \PDO::PARAM_INT);
                    $hResult->bindValue(':email', $sEmail, \PDO::PARAM_STR);
                    $hResult->execute();
                    //debug($sQ);
                    $iRows = $hResult->rowCount();
                    if ($iRows == 1) $sErr .= \HaaseIT\Textcat::T("userprofile_emailalreadyinuse") . '<br>';
                    $sErr = \HaaseIT\HCSF\Customer\Helper::validateCustomerForm($C, $sLang, $sErr, true);

                    if ($sErr == '') {
                        if ($C["allow_edituserprofile"]) {
                            $aData = array(
                                //DB_CUSTOMERFIELD_EMAIL => $sEmail, // disabled until renwewd email verification implemented
                                DB_CUSTOMERFIELD_CORP => filter_var(trim(\HaaseIT\Tools::getFormfield("corpname")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                DB_CUSTOMERFIELD_NAME => filter_var(trim(\HaaseIT\Tools::getFormfield("name")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                DB_CUSTOMERFIELD_STREET => filter_var(trim(\HaaseIT\Tools::getFormfield("street")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                DB_CUSTOMERFIELD_ZIP => filter_var(trim(\HaaseIT\Tools::getFormfield("zip")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                DB_CUSTOMERFIELD_TOWN => filter_var(trim(\HaaseIT\Tools::getFormfield("town")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                DB_CUSTOMERFIELD_PHONE => filter_var(trim(\HaaseIT\Tools::getFormfield("phone")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                DB_CUSTOMERFIELD_CELLPHONE => filter_var(trim(\HaaseIT\Tools::getFormfield("cellphone")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                DB_CUSTOMERFIELD_FAX => filter_var(trim(\HaaseIT\Tools::getFormfield("fax")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                DB_CUSTOMERFIELD_COUNTRY => filter_var(trim(\HaaseIT\Tools::getFormfield("country")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            );
                        }
                        if (isset($_POST["pwd"]) && $_POST["pwd"] != '') {
                            $aData[DB_CUSTOMERFIELD_PASSWORD] = crypt($_POST["pwd"], $C["blowfish_salt"]);
                            $aPData["infopasswordchanged"] = true;
                        }
                        //debug($aData);
                        $aData[DB_CUSTOMERTABLE_PKEY] = $_SESSION["user"][DB_CUSTOMERTABLE_PKEY];

                        if (count($aData) > 1) {
                            $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_CUSTOMERTABLE, DB_CUSTOMERTABLE_PKEY);
                            //debug($sQ);
                            $hResult = $DB->prepare($sQ);
                            foreach ($aData as $sKey => $sValue) {
                                $hResult->bindValue(':' . $sKey, $sValue);
                            }
                            $hResult->execute();
                            $aPData["infochangessaved"] = true;
                        } else {
                            $aPData["infonothingchanged"] = true;
                        }
                    }
                }
                $this->P->cb_customdata["customerform"] = \HaaseIT\HCSF\Customer\Helper::buildCustomerForm($C, $sLang, 'editprofile', $sErr);
                //if ($C["allow_edituserprofile"]) $P["lang"]["cl_html"] .= '<br>'.\HaaseIT\Textcat::T("userprofile_infoeditemail"); // Future implementation
            } else {
                $this->P->cb_customdata["customerform"] = \HaaseIT\HCSF\Customer\Helper::buildCustomerForm($C, $sLang, 'userhome');
            }
            $aPData["showprofilelinks"] = false;
            if (!isset($_GET["editprofile"])) {
                $aPData["showprofilelinks"] = true;
            }
            if (isset($aPData) && count($aPData)) {
                $this->P->cb_customdata["userhome"] = $aPData;
            }
        }
    }
}