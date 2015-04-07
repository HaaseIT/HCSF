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

function admin_prepareItemlistsearchform()
{
    $aData["searchcats"] = array(
        'nummer|Artikelnummer',
        'name|Artikelname',
        'index|Artikelindex',
    );
    $aData["orderbys"] = array(
        'nummer|Artikelnummer',
        'name|Artikelname',
    );

    if (isset($_GET["searchcat"])) {
        $aData["searchcat"] = $_GET["searchcat"];
        $_SESSION["itemadmin_searchcat"] = $_GET["searchcat"];
    } elseif (isset($_SESSION["itemadmin_searchcat"])) $aData["searchcat"] = $_SESSION["itemadmin_searchcat"];

    if (isset($_GET["orderby"])) {
        $aData["orderby"] = $_GET["orderby"];
        $_SESSION["itemadmin_orderby"] = $_GET["orderby"];
    } elseif (isset($_SESSION["itemadmin_orderby"])) $aData["orderby"] = $_SESSION["itemadmin_orderby"];

    return $aData;
}

function admin_getItemlist($DB, $sLang)
{
    $sSearchstring = \filter_input(INPUT_GET, 'searchstring', FILTER_SANITIZE_SPECIAL_CHARS);
    $sSearchstring = str_replace('*', '%', $sSearchstring);

    $sQ = "SELECT " . DB_ITEMFIELD_NUMBER . ", " . DB_ITEMFIELD_NAME;
    $sQ .= " FROM " . DB_ITEMTABLE_BASE;
    $sQ .= " LEFT OUTER JOIN " . DB_ITEMTABLE_TEXT . " ON ";
    $sQ .= DB_ITEMTABLE_BASE . "." . DB_ITEMTABLE_BASE_PKEY . " = " . DB_ITEMTABLE_TEXT . "." . DB_ITEMTABLE_TEXT_PARENTPKEY;
    $sQ .= " AND " . DB_ITEMTABLE_TEXT . "." . DB_ITEMFIELD_LANGUAGE . " = :lang";
    $sQ .= " WHERE ";
    if ($_REQUEST["searchcat"] == 'name') {
        $sQ .= DB_ITEMFIELD_NAME . " LIKE :searchstring ";
    } elseif ($_REQUEST["searchcat"] == 'nummer') {
        $sQ .= DB_ITEMFIELD_NUMBER . " LIKE :searchstring ";
    } elseif ($_REQUEST["searchcat"] == 'index') {
        $sQ .= DB_ITEMFIELD_INDEX . " LIKE :searchstring ";
    } else exit;

    if ($_REQUEST["orderby"] == 'name') $sQ .= "ORDER BY " . DB_ITEMFIELD_NAME;
    elseif ($_REQUEST["orderby"] == 'nummer') $sQ .= " ORDER BY " . DB_ITEMFIELD_NUMBER;
    //HaaseIT\Tools::debug($sQ);

    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':searchstring', $sSearchstring);
    $hResult->bindValue(':lang', $sLang);
    $hResult->execute();

    //HaaseIT\Tools::debug($DB->error());

    $aItemlist["numrows"] = $hResult->rowCount();

    if ($aItemlist["numrows"] != 0) {
        while ($aRow = $hResult->fetch()) $aItemlist["data"][] = $aRow;
        return $aItemlist;
    } else return false;
}

function admin_prepareItemlist($aItemlist, $twig)
{
    $aList = array(
        array('title' => 'Art. Nr.', 'key' => 'itemno', 'width' => 100, 'linked' => false,),
        array('title' => 'Name', 'key' => 'name', 'width' => 350, 'linked' => false,),
        array('title' => 'edit', 'key' => 'itemno', 'width' => 30, 'linked' => true, 'ltarget' => $_SERVER["PHP_SELF"], 'lkeyname' => 'itemno', 'lgetvars' => array('action' => 'showitem'),),
    );
    foreach ($aItemlist["data"] as $aValue) {
        $aData[] = array(
            'itemno' => $aValue[DB_ITEMFIELD_NUMBER],
            'name' => $aValue[DB_ITEMFIELD_NAME],
        );
    }
    $aLData = array(
        'numrows' => $aItemlist["numrows"],
        'listtable' => \HaaseIT\Tools::makeListTable($aList, $aData, $twig),
    );

    return $aLData;
}

function admin_getItem($sItemno = '', $DB, $sLang)
{
    if (isset($_REQUEST["itemno"]) && $_REQUEST["itemno"] != '') $sItemno = $_REQUEST["itemno"];
    elseif ($sItemno == '') return false;

    $sItemno = \filter_var($sItemno, FILTER_SANITIZE_SPECIAL_CHARS);

    $sQ = "SELECT * FROM ".DB_ITEMTABLE_BASE." WHERE ".DB_ITEMFIELD_NUMBER." = :itemno";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':itemno', $sItemno);
    $hResult->execute();
    //HaaseIT\Tools::debug($sQ);
    //HaaseIT\Tools::debug($DB->error());
    $aItemdata["base"] = $hResult->fetch();

    $sQ = "SELECT * FROM " . DB_ITEMTABLE_TEXT;
    $sQ .= " WHERE " . DB_ITEMTABLE_TEXT_PARENTPKEY . " = :parentpkey";
    $sQ .= " AND " . DB_ITEMFIELD_LANGUAGE . " = :lang";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':parentpkey', $aItemdata["base"][DB_ITEMTABLE_BASE_PKEY]);
    $hResult->bindValue(':lang', $sLang);
    $hResult->execute();
    //HaaseIT\Tools::debug($sQ);
    if ($hResult->rowCount() != 0) $aItemdata["text"] = $hResult->fetch();

    //HaaseIT\Tools::debug($aItemdata);
    return $aItemdata;
}

function admin_prepareItem($aItemdata, $C, $DB, $sLang)
{
    $aData = array(
        'form' => array('action' => \HaaseIT\Tools::makeLinkHRefWithAddedGetVars($_SERVER["PHP_SELF"], array('action' => 'showitem', 'itemno' => $aItemdata["base"][DB_ITEMFIELD_NUMBER])),),
        'id' => $aItemdata["base"][DB_ITEMTABLE_BASE_PKEY],
        'itemno' => $aItemdata["base"][DB_ITEMFIELD_NUMBER],
        'name' => htmlspecialchars($aItemdata["base"][DB_ITEMFIELD_NAME]),
        'img' => $aItemdata["base"][DB_ITEMFIELD_IMG],
        'price' => $aItemdata["base"][DB_ITEMFIELD_PRICE],
        'vatid' => $aItemdata["base"][DB_ITEMFIELD_VAT],
        'rg' => $aItemdata["base"][DB_ITEMFIELD_RG],
        'index' => $aItemdata["base"][DB_ITEMFIELD_INDEX],
        'prio' => $aItemdata["base"][DB_ITEMFIELD_ORDER],
        'group' => $aItemdata["base"][DB_ITEMFIELD_GROUP],
        'data' => $aItemdata["base"][DB_ITEMFIELD_DATA],
        'weight' => $aItemdata["base"][DB_ITEMFIELD_WEIGHT],
    );

    if (!$C["vat_disable"]) {
        $aOptions[] = '|';
        foreach ($C["vat"] as $sKey => $sValue) $aOptions[] = $sKey.'|'.$sValue;
        $aData["vatoptions"] = $aOptions;
        unset($aOptions);
    }
    $aData["rgoptions"][] = '';
    foreach ($C["rebate_groups"] as $sKey => $aValue) $aData["rgoptions"][] = $sKey;

    $aGroups = admin_getItemgroups('', $DB, $sLang);
    $aData["groupoptions"][] = '';
    foreach ($aGroups as $aValue) $aData["groupoptions"][] = $aValue[DB_ITEMGROUPTABLE_BASE_PKEY] . '|' . $aValue[DB_ITEMGROUPFIELD_NUMBER] . ' - ' . $aValue[DB_ITEMGROUPFIELD_NAME];
    unset($aGroups);

    if (isset($aItemdata["text"])) {
        $aData["lang"] = array(
            'textid' => $aItemdata["text"][DB_ITEMTABLE_TEXT_PKEY],
            'nameoverride' => htmlspecialchars($aItemdata["text"][DB_ITEMFIELD_NAME_OVERRIDE]),
            'text1' => $aItemdata["text"][DB_ITEMFIELD_TEXT1],
            'text2' => $aItemdata["text"][DB_ITEMFIELD_TEXT2],
        );
    }

    return $aData;
}

function admin_updateItem($C, $DB)
{
    $aData = array(
        DB_ITEMFIELD_NAME => $_REQUEST["name"],
        DB_ITEMFIELD_GROUP => $_REQUEST["group"],
        DB_ITEMFIELD_IMG => $_REQUEST["bild"],
        DB_ITEMFIELD_INDEX => $_REQUEST["index"],
        DB_ITEMFIELD_ORDER => $_REQUEST["prio"],
        DB_ITEMFIELD_PRICE => $_REQUEST["price"],
        DB_ITEMFIELD_RG => $_REQUEST["rg"],
        DB_ITEMFIELD_DATA => $_REQUEST["data"],
        DB_ITEMFIELD_WEIGHT => $_REQUEST["weight"],
        DB_ITEMTABLE_BASE_PKEY => $_REQUEST["id"],
    );
    if (!$C["vat_disable"]) $aData[DB_ITEMFIELD_VAT] = $_REQUEST["vatid"];
    else $aData[DB_ITEMFIELD_VAT] = 'full';
    $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_ITEMTABLE_BASE, DB_ITEMTABLE_BASE_PKEY);
    //echo $sQ."\n";
    $hResult = $DB->prepare($sQ);
    foreach ($aData as $sKey => $sValue) $hResult->bindValue(':' . $sKey, $sValue);
    $hResult->execute();
    if (isset($_REQUEST["textid"])) {
        $aData = array(
            DB_ITEMFIELD_TEXT1 => $_REQUEST["text1"],
            DB_ITEMFIELD_TEXT2 => $_REQUEST["text2"],
            DB_ITEMFIELD_NAME_OVERRIDE => $_REQUEST["name_override"],
            DB_ITEMTABLE_TEXT_PKEY => $_REQUEST["textid"],
        );
        $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_ITEMTABLE_TEXT, DB_ITEMTABLE_TEXT_PKEY);
        //echo $sQ."\n";
        //HaaseIT\Tools::debug($DB->error());
        $hResult = $DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':' . $sKey, $sValue);
        $hResult->execute();
    }

    return true;
}
