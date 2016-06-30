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
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->C, $this->sLang);
        $this->P->cb_pagetype = 'content';

        if (!\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_notloggedin");
        } else {
            $this->P->cb_customcontenttemplate = 'customer/customerhome';

            $aPData["display_logingreeting"] = false;
            if (isset($_GET["login"]) && $_GET["login"]) {
                $aPData["display_logingreeting"] = true;
            }
            if (isset($_GET["editprofile"])) {
                $sErr = '';

                if (isset($_POST["doEdit"]) && $_POST["doEdit"] == 'yes') {
                    $sQ = 'SELECT '.DB_ADDRESSFIELDS.' FROM customer WHERE cust_id != :id AND cust_email = :email';

                    $sEmail = filter_var(trim(\HaaseIT\Tools::getFormfield("email")), FILTER_SANITIZE_EMAIL);

                    $hResult = $this->DB->prepare($sQ);
                    $hResult->bindValue(':id', $_SESSION["user"]['cust_id'], \PDO::PARAM_INT);
                    $hResult->bindValue(':email', $sEmail, \PDO::PARAM_STR);
                    $hResult->execute();
                    $iRows = $hResult->rowCount();
                    if ($iRows == 1) $sErr .= \HaaseIT\Textcat::T("userprofile_emailalreadyinuse") . '<br>';
                    $sErr = \HaaseIT\HCSF\Customer\Helper::validateCustomerForm($this->C, $this->sLang, $sErr, true);

                    if ($sErr == '') {
                        if ($this->C["allow_edituserprofile"]) {
                            $aData = [
                                //'cust_email' => $sEmail, // disabled until renwewd email verification implemented
                                'cust_corp' => filter_var(trim(\HaaseIT\Tools::getFormfield("corpname")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_name' => filter_var(trim(\HaaseIT\Tools::getFormfield("name")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_street' => filter_var(trim(\HaaseIT\Tools::getFormfield("street")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_zip' => filter_var(trim(\HaaseIT\Tools::getFormfield("zip")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_town' => filter_var(trim(\HaaseIT\Tools::getFormfield("town")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_phone' => filter_var(trim(\HaaseIT\Tools::getFormfield("phone")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_cellphone' => filter_var(trim(\HaaseIT\Tools::getFormfield("cellphone")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_fax' => filter_var(trim(\HaaseIT\Tools::getFormfield("fax")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_country' => filter_var(trim(\HaaseIT\Tools::getFormfield("country")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            ];
                        }
                        if (isset($_POST["pwd"]) && $_POST["pwd"] != '') {
                            $aData['cust_password'] = password_hash($_POST["pwd"], PASSWORD_DEFAULT);
                            $aPData["infopasswordchanged"] = true;
                        }
                        $aData['cust_id'] = $_SESSION["user"]['cust_id'];

                        if (count($aData) > 1) {
                            $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'customer', 'cust_id');
                            $hResult = $this->DB->prepare($sQ);
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
                $this->P->cb_customdata["customerform"] = \HaaseIT\HCSF\Customer\Helper::buildCustomerForm($this->C, $this->sLang, 'editprofile', $sErr);
                //if ($this->C["allow_edituserprofile"]) $P["lang"]["cl_html"] .= '<br>'.\HaaseIT\Textcat::T("userprofile_infoeditemail"); // Future implementation
            } else {
                $this->P->cb_customdata["customerform"] = \HaaseIT\HCSF\Customer\Helper::buildCustomerForm($this->C, $this->sLang, 'userhome');
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