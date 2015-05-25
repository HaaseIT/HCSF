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

if (getUserData()) {
    $P = array(
        'base' => array(
            'cb_pagetype' => 'content',
            'cb_pageconfig' => '',
            'cb_subnav' => '',
        ),
        'lang' => array(
            'cl_lang' => $sLang,
            'cl_html' => \HaaseIT\Textcat::T("denied_default"),
        ),
    );
} else {
    function handleRegisterPage($C, $sLang, $DB) {
        $P = array(
            'base' => array(
                'cb_pagetype' => 'content',
                'cb_pageconfig' => '',
                'cb_subnav' => '',
                'cb_customcontenttemplate' => 'customer/register',
            ),
            'lang' => array(
                'cl_lang' => $sLang,
                'cl_html' => '',
            ),
        );

        $aErr = array();
        if (isset($_POST["doRegister"]) && $_POST["doRegister"] == 'yes') {
            $aErr = validateCustomerForm($C, $sLang, $aErr);
            if (count($aErr) == 0) {
                $sQ = "SELECT " . DB_CUSTOMERFIELD_EMAIL . " FROM " . DB_CUSTOMERTABLE;
                $sQ .= " WHERE " . DB_CUSTOMERFIELD_EMAIL . " = :email";

                $hResult = $DB->prepare($sQ);
                $hResult->bindValue(':email', trim($_POST["email"]), PDO::PARAM_STR);
                $hResult->execute();
                $iRows = $hResult->rowCount();

                if ($iRows == 0) {
                    $sEmailVerificationcode = md5($_POST["email"] . time());
                    $aData = array(
                        DB_CUSTOMERFIELD_EMAIL => trim($_POST["email"]),
                        DB_CUSTOMERFIELD_CORP => trim($_POST["corpname"]),
                        DB_CUSTOMERFIELD_NAME => trim($_POST["name"]),
                        DB_CUSTOMERFIELD_STREET => trim($_POST["street"]),
                        DB_CUSTOMERFIELD_ZIP => trim($_POST["zip"]),
                        DB_CUSTOMERFIELD_TOWN => trim($_POST["town"]),
                        DB_CUSTOMERFIELD_PHONE => trim($_POST["phone"]),
                        DB_CUSTOMERFIELD_CELLPHONE => trim($_POST["cellphone"]),
                        DB_CUSTOMERFIELD_FAX => trim($_POST["fax"]),
                        DB_CUSTOMERFIELD_COUNTRY => trim($_POST["country"]),
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
                        $hResult->bindValue(':' . $sKey, $sValue, PDO::PARAM_STR);
                    }
                    $hResult->execute();

                    sendVerificationMail($sEmailVerificationcode, $_POST["email"], $C);
                    sendVerificationMail($sEmailVerificationcode, $_POST["email"], $C, true);
                    $aPData["showsuccessmessage"] = true;
                } else {
                    $aErr["emailalreadyexists"] = true;
                    $P["base"]["cb_customdata"]["customerform"] = buildCustomerForm($C, $sLang, 'register', $aErr);
                }
            } else {
                $P["base"]["cb_customdata"]["customerform"] = buildCustomerForm($C, $sLang, 'register', $aErr);
            }
        } else {
            $P["base"]["cb_customdata"]["customerform"] = buildCustomerForm($C, $sLang, 'register');
        }
        if (isset($aPData) && count($aPData)) {
            $P["base"]["cb_customdata"]["register"] = $aPData;
        }

        return $P;
    }

    $P = handleRegisterPage($C, $sLang, $DB);
}