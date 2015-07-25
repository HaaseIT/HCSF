<?php

require_once __DIR__.'/../../src/shop/functions.admin.itemgroups.php';

$P = new \HaaseIT\HCSF\CorePage($C, $sLang);
$P->cb_pagetype = 'content';
$P->cb_subnav = 'admin';
$P->cb_customcontenttemplate = 'shop/itemgroupadmin';

$sH = '';
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'insert_lang') {
    $sQ = "SELECT ".DB_ITEMGROUPTABLE_BASE_PKEY." FROM ".DB_ITEMGROUPTABLE_BASE." WHERE ".DB_ITEMGROUPTABLE_BASE_PKEY." = :gid";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':gid', $_REQUEST["gid"]);
    $hResult->execute();
    $iNumRowsBasis = $hResult->rowCount();

    $sQ = "SELECT ".DB_ITEMGROUPTABLE_TEXT_PKEY." FROM ".DB_ITEMGROUPTABLE_TEXT;
    $sQ .= " WHERE ".DB_ITEMGROUPTABLE_TEXT_PARENTPKEY." = :gid";
    $sQ .= " AND ".DB_ITEMGROUPFIELD_LANGUAGE." = :lang";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':gid', $_REQUEST["gid"]);
    $hResult->bindValue(':lang', $sLang);
    $hResult->execute();
    //HaaseIT\Tools::debug($sQ);
    $iNumRowsLang = $hResult->rowCount();

    //HaaseIT\Tools::debug($iNumRowsBasis.' / '.$iNumRowsLang);

    if ($iNumRowsBasis == 1 && $iNumRowsLang == 0) {
        $aData = array(
            DB_ITEMGROUPTABLE_TEXT_PARENTPKEY => $_REQUEST["gid"],
            DB_ITEMGROUPFIELD_LANGUAGE => $sLang,
        );
        //HaaseIT\Tools::debug($aData);
        $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aData, DB_ITEMGROUPTABLE_TEXT);
        //HaaseIT\Tools::debug($sQ);
        $hResult = $DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
        header('Location: '.$_SERVER["PHP_SELF"]."?gid=".$_REQUEST["gid"].'&action=editgroup');
        die();
    }
    //HaaseIT\Tools::debug($aItemdata);
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'editgroup') {
    if (isset($_REQUEST["do"]) && $_REQUEST["do"] == 'true') {
        $P->cb_customdata["updatestatus"] = admin_updateGroup($DB, $sLang);
    }
    $aGroup = admin_getItemgroups($_REQUEST["gid"], $DB, $sLang);
    if (isset($_REQUEST["added"])) {
        $P->cb_customdata["groupjustadded"] = true;
    }
    $P->cb_customdata["showform"] = 'edit';
    $P->cb_customdata["group"] = admin_prepareGroup('edit', $aGroup[0]);
    //HaaseIT\Tools::debug($aGroup);
} elseif (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'addgroup') {
    $aErr = array();
    if (isset($_REQUEST["do"]) && $_REQUEST["do"] == 'true') {
        if (strlen($_REQUEST["name"]) < 3) $aErr["nametooshort"] = true;
        if (strlen($_REQUEST["no"]) < 3) $aErr["grouptooshort"] = true;
        if (count($aErr) == 0) {
            $sQ = "SELECT ".DB_ITEMGROUPFIELD_NUMBER." FROM ".DB_ITEMGROUPTABLE_BASE;
            $sQ .= " WHERE ".DB_ITEMGROUPFIELD_NUMBER." = :no";
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':no', $_REQUEST["no"]);
            $hResult->execute();
            if ($hResult->rowCount() > 0) $aErr["duplicateno"] = true;
        }
        if (count($aErr) == 0) {
            $aData = array(
                DB_ITEMGROUPFIELD_NAME => $_REQUEST["name"],
                DB_ITEMGROUPFIELD_NUMBER => $_REQUEST["no"],
                DB_ITEMGROUPFIELD_IMG => $_REQUEST["img"],
            );
            $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aData, DB_ITEMGROUPTABLE_BASE);
            //HaaseIT\Tools::debug($sQ);
            $hResult = $DB->prepare($sQ);
            foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
            $hResult->execute();
            $iLastInsertID = $DB->lastInsertId();
            header('Location: '.$_SERVER["PHP_SELF"].'?action=editgroup&added&gid='.$iLastInsertID);
        } else {
            $P->cb_customdata["err"] = $aErr;
            $P->cb_customdata["showform"] = 'add';
            $P->cb_customdata["group"] = admin_prepareGroup('add');
        }
    } else {
        $P->cb_customdata["showform"] = 'add';
        $P->cb_customdata["group"] = admin_prepareGroup('add');
    }
} else {
    if (!$sH .= admin_showItemgroups(admin_getItemgroups('', $DB, $sLang), $twig)) {
        $P->cb_customdata["err"]["nogroupsavaliable"] = true;
    }

}

$P->oPayload->cl_html = $sH;
unset($sH);
