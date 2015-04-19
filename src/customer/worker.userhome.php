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

if (!getUserData()) {
    $P = array(
        'base' => array(
            'cb_pagetype' => 'content',
            'cb_pageconfig' => '',
            'cb_subnav' => '',
        ),
        'lang' => array(
            'cl_lang' => $sLang,
            'cl_html' => \HaaseIT\Textcat::T("denied_notloggedin"),
        ),
    );
} else {
    function handleUserhomePage($C, $sLang, $DB) {
        $P = array(
            'base' => array(
                'cb_pagetype' => 'content',
                'cb_pageconfig' => '',
                'cb_subnav' => '',
                'cb_customcontenttemplate' => 'customer/customerhome',
            ),
            'lang' => array(
                'cl_lang' => $sLang,
                'cl_html' => '',
            ),
        );

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

                $hResult = $DB->prepare($sQ);
                $hResult->bindValue(':id', $_SESSION["user"][DB_CUSTOMERTABLE_PKEY], PDO::PARAM_INT);
                $hResult->bindValue(':email', trim($_POST["email"]), PDO::PARAM_STR);
                $hResult->execute();
                //debug($sQ);
                $iRows = $hResult->rowCount();
                if ($iRows == 1) $sErr .= \HaaseIT\Textcat::T("userprofile_emailalreadyinuse") . '<br>';
                $sErr = validateCustomerForm($C, $sLang, $sErr, true);

                if ($sErr == '') {
                    if ($C["allow_edituserprofile"]) {
                        $aData = array(
                            //DB_CUSTOMERFIELD_EMAIL => trim($_POST["email"]), // disabled until renwewd email verification implemented
                            DB_CUSTOMERFIELD_CORP => trim($_POST["corpname"]),
                            DB_CUSTOMERFIELD_NAME => trim($_POST["name"]),
                            DB_CUSTOMERFIELD_STREET => trim($_POST["street"]),
                            DB_CUSTOMERFIELD_ZIP => trim($_POST["zip"]),
                            DB_CUSTOMERFIELD_TOWN => trim($_POST["town"]),
                            DB_CUSTOMERFIELD_PHONE => trim($_POST["phone"]),
                            DB_CUSTOMERFIELD_CELLPHONE => trim($_POST["cellphone"]),
                            DB_CUSTOMERFIELD_FAX => trim($_POST["fax"]),
                            DB_CUSTOMERFIELD_COUNTRY => trim($_POST["country"]),
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
            $P["base"]["cb_customdata"]["customerform"] = buildCustomerForm($C, $sLang, 'editprofile', $sErr);
            //if ($C["allow_edituserprofile"]) $P["lang"]["cl_html"] .= '<br>'.\HaaseIT\Textcat::T("userprofile_infoeditemail"); // Future implementation
        } else {
            $P["base"]["cb_customdata"]["customerform"] = buildCustomerForm($C, $sLang, 'userhome');
        }
        $aPData["showprofilelinks"] = false;
        if (!isset($_GET["editprofile"])) {
            $aPData["showprofilelinks"] = true;
        }
        if (isset($aPData) && count($aPData)) {
            $P["base"]["cb_customdata"]["userhome"] = $aPData;
        }

        return $P;
    }

    $P = handleUserhomePage($C, $sLang, $DB);
}
