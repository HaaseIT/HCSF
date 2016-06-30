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

class Forgotpassword extends Base
{
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->C, $this->sLang);
        $this->P->cb_pagetype = 'content';

        if (\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
        } else {
            $this->P->cb_customcontenttemplate = 'customer/forgotpassword';

            $aErr = [];
            if (isset($_POST["doSend"]) && $_POST["doSend"] == 'yes') {
                $aErr = $this->handleForgotPassword($this->DB, $this->C, $aErr);
                if (count($aErr) == 0) {
                    $this->P->cb_customdata["forgotpw"]["showsuccessmessage"] = true;
                } else {
                    $this->P->cb_customdata["forgotpw"]["errors"] = $aErr;
                }
            }
        }
    }

    private function handleForgotPassword($aErr) {
        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            $aErr[] = 'emailinvalid';
        } else {
            $sQ = 'SELECT * FROM customer WHERE cust_email = :email';

            $sEmail = filter_var(trim(\HaaseIT\Tools::getFormfield("email")), FILTER_SANITIZE_EMAIL);

            $hResult = $this->DB->prepare($sQ);
            $hResult->bindValue(':email', $sEmail, \PDO::PARAM_STR);
            $hResult->execute();
            if ($hResult->rowCount() != 1) {
                $aErr[] = 'emailunknown';
            } else {
                $aResult = $hResult->fetch();
                $iTimestamp = time();
                if ($iTimestamp - HOUR < $aResult['cust_pwresettimestamp']) { // 1 hour delay between requests
                    $aErr[] = 'pwresetstilllocked';
                } else {
                    $sResetCode = md5($aResult['cust_email'].$iTimestamp);
                    $aData = [
                        'cust_pwresetcode' => $sResetCode,
                        'cust_pwresettimestamp' => $iTimestamp,
                        'cust_id' => $aResult['cust_id'],
                    ];
                    $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'customer', 'cust_id');
                    $hResult = $this->DB->prepare($sQ);
                    foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
                    $hResult->execute();

                    $sTargetAddress = $aResult['cust_email'];
                    $sSubject = \HaaseIT\Textcat::T("forgotpw_mail_subject");
                    $sMessage = \HaaseIT\Textcat::T("forgotpw_mail_text1");
                    $sMessage .= "<br><br>".'<a href="http'.(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on' ? 's' : '').'://';
                    $sMessage .= $_SERVER["HTTP_HOST"].'/_misc/rp.html?key='.$sResetCode.'&amp;email='.$sTargetAddress.'">';
                    $sMessage .= 'http'.(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on' ? 's' : '').'://';
                    $sMessage .= $_SERVER["HTTP_HOST"].'/_misc/rp.html?key='.$sResetCode.'&amp;email='.$sTargetAddress.'</a>';
                    $sMessage .= '<br><br>'.\HaaseIT\Textcat::T("forgotpw_mail_text2");

                    \HaaseIT\HCSF\Helper::mailWrapper($this->C, $sTargetAddress, $sSubject, $sMessage);
                }
            }
        }

        return $aErr;
    }

}