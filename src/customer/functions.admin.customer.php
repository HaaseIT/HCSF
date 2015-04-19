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

$CUA = array(
    array('title' => 'Nr.', 'key' => DB_CUSTOMERFIELD_NUMBER, 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',),
    array('title' => 'Firma', 'key' => DB_CUSTOMERFIELD_CORP, 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',),
    array('title' => 'Name', 'key' => DB_CUSTOMERFIELD_NAME, 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',),
    array('title' => 'Ort', 'key' => DB_CUSTOMERFIELD_TOWN, 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',),
    array('title' => 'Aktiv', 'key' => DB_CUSTOMERFIELD_ACTIVE, 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',),
    //	array('title' => '', 'key' => $C[""], 'width' => 100, 'linked' => false,),
    array(
        'title' => 'bearb.',
        'key' => DB_CUSTOMERTABLE_PKEY,
        'width' => '16%',
        'linked' => true,
        'ltarget' => $_SERVER["PHP_SELF"],
        'lkeyname' => 'id',
        'lgetvars' => array(
            'action' => 'edit',
        ),
    ),
);

function handleCustomerAdmin($CUA, $twig, $DB, $C, $sLang)
{
    $sType = 'all';
    if (isset($_REQUEST["type"])) {
        if ($_REQUEST["type"] == 'active') {
            $sType = 'active';
        } elseif ($_REQUEST["type"] == 'inactive') {
            $sType = 'inactive';
        }
    }
    $sH = '';
    if (!isset($_GET["action"])) {
        $sQ = "SELECT " . DB_ADDRESSFIELDS . " FROM " . DB_CUSTOMERTABLE;
        if ($sType == 'active') {
            $sQ .= " WHERE " . DB_CUSTOMERFIELD_ACTIVE . " = 'y'";
        } elseif ($sType == 'inactive') {
            $sQ .= " WHERE " . DB_CUSTOMERFIELD_ACTIVE . " = 'n'";
        }
        $sQ .= " ORDER BY " . DB_CUSTOMERFIELD_NUMBER . " ASC";
        //HaaseIT\Tools::debug($sQ);
        $hResult = $DB->query($sQ);
        //HaaseIT\Tools::debug($DB->error());
        //HaaseIT\Tools::debug($hResult->rowCount());
        if ($hResult->rowCount() != 0) {
            $aData = $hResult->fetchAll();
            //HaaseIT\Tools::debug($aData);
            $sH .= \HaaseIT\Tools::makeListtable($CUA, $aData, $twig);
        } else {
            $aInfo["nodatafound"] = true;
        }
    } elseif (isset($_GET["action"]) && $_GET["action"] == 'edit') {
        $iId = \filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $aErr = array();
        if (isset($_POST["doEdit"]) && $_POST["doEdit"] == 'yes') {
            if (strlen(trim($_POST["custno"])) < $C["minimum_length_custno"]) {
                $aErr["custnoinvalid"] = true;
            } else {
                $sQ = "SELECT " . DB_ADDRESSFIELDS . " FROM " . DB_CUSTOMERTABLE;
                $sQ .= " WHERE " . DB_CUSTOMERTABLE_PKEY . " != :id";
                $sQ .= " AND " . DB_CUSTOMERFIELD_NUMBER . " = :custno";
                $hResult = $DB->prepare($sQ);
                $hResult->bindValue(':id', $iId);
                $hResult->bindValue(':custno', trim($_POST["custno"]));
                $hResult->execute();
                //HaaseIT\Tools::debug($sQ);
                $iRows = $hResult->rowCount();
                if ($iRows == 1) {
                    $aErr["custnoalreadytaken"] = true;
                }
                $sQ = "SELECT " . DB_ADDRESSFIELDS . " FROM " . DB_CUSTOMERTABLE;
                $sQ .= " WHERE " . DB_CUSTOMERTABLE_PKEY . " != :id";
                $sQ .= " AND " . DB_CUSTOMERFIELD_EMAIL . " = :email";
                $hResult = $DB->prepare($sQ);
                $hResult->bindValue(':id', $iId);
                $hResult->bindValue(':email', \filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
                $hResult->execute();
                //HaaseIT\Tools::debug($sQ);
                $iRows = $hResult->rowCount();
                if ($iRows == 1) {
                    $aErr["emailalreadytaken"] = true;
                }
                $aErr = validateCustomerForm($C, $sLang, $aErr, true);
                if (count($aErr) == 0) {
                    $aData = array(
                        DB_CUSTOMERFIELD_NUMBER => $_POST["custno"],
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
                        DB_CUSTOMERFIELD_GROUP => trim($_POST["custgroup"]),
                        DB_CUSTOMERFIELD_EMAILVERIFIED => ((isset($_POST["emailverified"]) && $_POST["emailverified"] == 'y') ? 'y' : 'n'),
                        DB_CUSTOMERFIELD_ACTIVE => ((isset($_POST["active"]) && $_POST["active"] == 'y') ? 'y' : 'n'),
                        DB_CUSTOMERTABLE_PKEY => $iId,
                    );
                    if (isset($_POST["pwd"]) && $_POST["pwd"] != '') {
                        $aData[DB_CUSTOMERFIELD_PASSWORD] = crypt($_POST["pwd"], $C["blowfish_salt"]);
                        $aInfo["passwordchanged"] = true;
                    }
                    //HaaseIT\Tools::debug($aData);
                    $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_CUSTOMERTABLE, DB_CUSTOMERTABLE_PKEY);
                    //HaaseIT\Tools::debug($sQ);
                    $hResult = $DB->prepare($sQ);
                    foreach ($aData as $sKey => $sValue) $hResult->bindValue(':' . $sKey, $sValue);
                    $hResult->execute();
                    //HaaseIT\Tools::debug($hResult->errorInfo());
                    $aInfo["changeswritten"] = true;
                }
            }
        }
        $sQ = "SELECT " . DB_ADDRESSFIELDS . " FROM " . DB_CUSTOMERTABLE;
        $sQ .= " WHERE " . DB_CUSTOMERTABLE_PKEY . " = :id";
        $hResult = $DB->prepare($sQ);
        $hResult->bindValue(':id', $iId);
        $hResult->execute();
        if ($hResult->rowCount() == 1) {
            $aUser = $hResult->fetch();
            //HaaseIT\Tools::debug($aUser);
            $aPData["customerform"] = buildCustomerForm($C, $sLang, 'admin', $aErr, $aUser);
        } else {
            $aInfo["nosuchuserfound"] = true;
        }
    }
    $aPData["customeradmin"]["text"] = $sH;
    $aPData["customeradmin"]["type"] = $sType;
    if (isset($aInfo)) $aPData["customeradmin"]["info"] = $aInfo;
    return $aPData;
}
