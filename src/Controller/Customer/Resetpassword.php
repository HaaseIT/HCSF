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
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->container);
        $this->P->cb_pagetype = 'content';

        if (\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = $this->container['textcats']->T("denied_default");
        } else {
            if (!isset($_GET["key"]) || !isset($_GET["email"]) || trim($_GET["key"]) == '' || trim($_GET["email"]) == '' || !\filter_var($_GET["email"], FILTER_VALIDATE_EMAIL)) {
                $this->P->oPayload->cl_html = $this->container['textcats']->T("denied_default");
            } else {
                $sql = 'SELECT * FROM customer WHERE cust_email = :email AND cust_pwresetcode = :pwresetcode AND cust_pwresetcode != \'\'';

                $sEmail = filter_var(trim(\HaaseIT\Tools::getFormfield("email")), FILTER_SANITIZE_EMAIL);

                $hResult = $this->container['db']->prepare($sql);
                $hResult->bindValue(':email', $sEmail, \PDO::PARAM_STR);
                $hResult->bindValue(':pwresetcode', filter_var(trim(\HaaseIT\Tools::getFormfield("key")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW), \PDO::PARAM_STR);
                $hResult->execute();
                if ($hResult->rowCount() != 1) {
                    $this->P->oPayload->cl_html = $this->container['textcats']->T("denied_default");
                } else {
                    $aErr = [];
                    $aResult = $hResult->fetch();
                    $iTimestamp = time();
                    if ($aResult['cust_pwresettimestamp'] < $iTimestamp - DAY) {
                        $this->P->oPayload->cl_html = $this->container['textcats']->T("pwreset_error_expired");
                    } else {
                        $this->P->cb_customcontenttemplate = 'customer/resetpassword';
                        $this->P->cb_customdata["pwreset"]["minpwlength"] = $this->container['conf']['customer']["minimum_length_password"];
                        if (isset($_POST["doSend"]) && $_POST["doSend"] == 'yes') {
                            $aErr = $this->handlePasswordReset($aErr, $aResult['cust_id']);
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
            if (
                strlen($_POST["pwd"]) < $this->container['conf']['customer']["minimum_length_password"]
                || strlen($_POST["pwd"]) > $this->container['conf']['customer']["maximum_length_password"]
            ) $aErr[] = 'pwlength';
            if ($_POST["pwd"] != $_POST["pwdc"]) $aErr[] = 'pwmatch';
            if (count($aErr) == 0) {
                $sEnc = password_hash($_POST["pwd"], PASSWORD_DEFAULT);
                $aData = [
                    'cust_password' => $sEnc,
                    'cust_pwresetcode' => '',
                    'cust_id' => $iID,
                ];
                $sql = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'customer', 'cust_id');
                $hResult = $this->container['db']->prepare($sql);
                foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
                $hResult->execute();
            }
        } else {
            $aErr[] = 'nopw';
        }

        return $aErr;
    }

}