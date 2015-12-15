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

// Password reset after clicking the link from the forgot password email

$P = new \HaaseIT\HCSF\CorePage($C, $sLang);
$P->cb_pagetype = 'content';

if (getUserData()) {
    $P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
} else {
    if (!isset($_GET["key"]) || !isset($_GET["email"]) || trim($_GET["key"]) == '' || trim($_GET["email"]) == '' || !\filter_var($_GET["email"], FILTER_VALIDATE_EMAIL)) {
        $P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
    } else {
        $sQ = "SELECT * FROM ".DB_CUSTOMERTABLE." WHERE ".DB_CUSTOMERFIELD_EMAIL." = :email AND ".DB_CUSTOMERFIELD_PWRESETCODE." = :pwresetcode AND ".DB_CUSTOMERFIELD_PWRESETCODE." != ''";

        $sEmail = filter_var(trim(\HaaseIT\Tools::getFormfield("email")), FILTER_SANITIZE_EMAIL);

        $hResult = $DB->prepare($sQ);
        $hResult->bindValue(':email', $sEmail, PDO::PARAM_STR);
        $hResult->bindValue(':pwresetcode', filter_var(trim(\HaaseIT\Tools::getFormfield("key")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW), PDO::PARAM_STR);
        $hResult->execute();
        if ($hResult->rowCount() != 1) {
            $P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
        } else {
            $aErr = array();
            $aResult = $hResult->fetch();
            $iTimestamp = time();
            if ($aResult[DB_CUSTOMERFIELD_PWRESETTIMESTAMP] < $iTimestamp - DAY) {
                $P->oPayload->cl_html = \HaaseIT\Textcat::T("pwreset_error_expired");
            } else {
                $P->cb_customcontenttemplate = 'customer/resetpassword';
                $P->cb_customdata["pwreset"]["minpwlength"] = $C["minimum_length_password"];
                if (isset($_POST["doSend"]) && $_POST["doSend"] == 'yes') {
                    $aErr = handlePasswordReset($DB, $C, $aErr, $aResult[DB_CUSTOMERTABLE_PKEY]);
                    if (count($aErr) == 0) {
                        $P->cb_customdata["pwreset"]["showsuccessmessage"] = true;
                    } else {
                        $P->cb_customdata["pwreset"]["errors"] = $aErr;
                    }
                }
            }
        }
    }
}
