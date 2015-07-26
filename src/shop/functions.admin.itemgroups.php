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

function admin_updateGroup($DB, $sLang, $purifier)
{
    $sQ = "SELECT * FROM " . DB_ITEMGROUPTABLE_BASE . " WHERE " . DB_ITEMGROUPTABLE_BASE_PKEY . " != :id AND ";
    $sQ .= DB_ITEMGROUPFIELD_NUMBER . " = :gno";
    $hResult = $DB->prepare($sQ);
    $iGID = filter_var($_REQUEST["gid"], FILTER_SANITIZE_NUMBER_INT);
    $sGNo = filter_var($_REQUEST["no"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
    $hResult->bindValue(':id', $iGID);
    $hResult->bindValue(':gno', $sGNo);
    $hResult->execute();
    $iNumRows = $hResult->rowCount();

    if ($iNumRows > 0) return 'duplicateno';

    $aData = array(
        DB_ITEMGROUPFIELD_NAME => filter_var($_REQUEST["name"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
        DB_ITEMGROUPFIELD_NUMBER => $sGNo,
        DB_ITEMGROUPFIELD_IMG => filter_var($_REQUEST["img"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
        DB_ITEMGROUPTABLE_BASE_PKEY => $iGID,
    );

    $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_ITEMGROUPTABLE_BASE, DB_ITEMGROUPTABLE_BASE_PKEY);
    //HaaseIT\Tools::debug($sQ);
    $hResult = $DB->prepare($sQ);
    foreach ($aData as $sKey => $sValue) {
        $hResult->bindValue(':' . $sKey, $sValue);
    }
    $hResult->execute();

    $sQ = "SELECT " . DB_ITEMGROUPTABLE_TEXT_PKEY . " FROM " . DB_ITEMGROUPTABLE_TEXT;
    $sQ .= " WHERE " . DB_ITEMGROUPTABLE_TEXT_PARENTPKEY . " = :gid";
    $sQ .= " AND " . DB_ITEMGROUPFIELD_LANGUAGE . " = :lang";
    //HaaseIT\Tools::debug($sQ);
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':gid', $iGID);
    $hResult->bindValue(':lang', $sLang, PDO::PARAM_STR);
    $hResult->execute();

    $iNumRows = $hResult->rowCount();

    if ($iNumRows == 1) {
        $aRow = $hResult->fetch();
        //HaaseIT\Tools::debug($aRow);
        $aData = array(
            DB_ITEMGROUPFIELD_SHORTTEXT => $purifier->purify($_REQUEST["shorttext"]),
            DB_ITEMGROUPFIELD_DETAILS => $purifier->purify($_REQUEST["details"]),
            DB_ITEMGROUPTABLE_TEXT_PKEY => $aRow[DB_ITEMGROUPTABLE_TEXT_PKEY],
        );
        $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_ITEMGROUPTABLE_TEXT, DB_ITEMGROUPTABLE_TEXT_PKEY);
        //HaaseIT\Tools::debug($sQ);
        $hResult = $DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':' . $sKey, $sValue);
        $hResult->execute();
    }

    return 'success';
}

function admin_prepareGroup($sPurpose = 'none', $aData = array())
{
    $aGData = array(
        'formaction' => \HaaseIT\Tools::makeLinkHRefWithAddedGetVars('/_admin/itemgroupadmin.html'),
        'id' => isset($aData[DB_ITEMGROUPTABLE_BASE_PKEY]) ? $aData[DB_ITEMGROUPTABLE_BASE_PKEY] : '',
        'name' => isset($aData[DB_ITEMGROUPFIELD_NAME]) ? $aData[DB_ITEMGROUPFIELD_NAME] : '',
        'no' => isset($aData[DB_ITEMGROUPFIELD_NUMBER]) ? $aData[DB_ITEMGROUPFIELD_NUMBER] : '',
        'img' => isset($aData[DB_ITEMGROUPFIELD_IMG]) ? $aData[DB_ITEMGROUPFIELD_IMG] : '',
    );

    if ($sPurpose == 'edit') {
        if ($aData[DB_ITEMGROUPTABLE_TEXT_PKEY] != '') {
            $aGData["lang"] = array(
                'shorttext' => isset($aData[DB_ITEMGROUPFIELD_SHORTTEXT]) ? $aData[DB_ITEMGROUPFIELD_SHORTTEXT] : '',
                'details' => isset($aData[DB_ITEMGROUPFIELD_DETAILS]) ? $aData[DB_ITEMGROUPFIELD_DETAILS] : '',
            );
        }
    }

    return $aGData;
}

function admin_getItemgroups($iGID = '', $DB, $sLang)
{
    $sQ = "SELECT * FROM " . DB_ITEMGROUPTABLE_BASE;
    $sQ .= " LEFT OUTER JOIN " . DB_ITEMGROUPTABLE_TEXT . " ON ";
    $sQ .= DB_ITEMGROUPTABLE_BASE . "." . DB_ITEMGROUPTABLE_BASE_PKEY . " = " . DB_ITEMGROUPTABLE_TEXT . "." . DB_ITEMGROUPTABLE_TEXT_PARENTPKEY;
    $sQ .= " AND " . DB_ITEMGROUPTABLE_TEXT . "." . DB_ITEMGROUPFIELD_LANGUAGE . " = :lang";
    if ($iGID != '') $sQ .= " WHERE " . DB_ITEMGROUPTABLE_BASE_PKEY . " = :gid";
    $sQ .= " ORDER BY " . DB_ITEMGROUPFIELD_NUMBER;
    //HaaseIT\Tools::debug($sQ);
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':lang', $sLang);
    if ($iGID != '') $hResult->bindValue(':gid', $iGID);
    $hResult->execute();

    $aGroups = $hResult->fetchAll();
    //HaaseIT\Tools::debug($aGroups);

    return $aGroups;
}

function admin_showItemgroups($aGroups, $twig)
{
    //HaaseIT\Tools::debug($aGroups);
    $aList = array(
        array('title' => 'Gruppe', 'key' => 'gno', 'width' => 80, 'linked' => false, 'style-data' => 'padding: 5px 0;'),
        array('title' => 'Gruppenname', 'key' => 'gname', 'width' => 350, 'linked' => false, 'style-data' => 'padding: 5px 0;'),
        array('title' => 'edit', 'key' => 'gid', 'width' => 30, 'linked' => true, 'ltarget' => '/_admin/itemgroupadmin.html', 'lkeyname' => 'gid', 'lgetvars' => array('action' => 'editgroup'), 'style-data' => 'padding: 5px 0;'),
    );
    if (count($aGroups) > 0) {
        foreach ($aGroups as $aValue) {
            $aData[] = array(
                'gid' => $aValue[DB_ITEMGROUPTABLE_BASE_PKEY],
                'gno' => $aValue[DB_ITEMGROUPFIELD_NUMBER],
                'gname' => $aValue[DB_ITEMGROUPFIELD_NAME],
            );
        }
        return \HaaseIT\Tools::makeListTable($aList, $aData, $twig);
    } else {
        return false;
    }
}
