<?php

require_once __DIR__.'/../../src/shop/functions.admin.items.php';
require_once __DIR__.'/../../src/shop/functions.admin.itemgroups.php';

$P = new \HaaseIT\HCSF\CorePage($C, $sLang);
$P->cb_pagetype = 'content';
$P->cb_subnav = 'admin';
$P->cb_customcontenttemplate = 'shop/itemadmin';

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'insert_lang') {
    $aItemdata = admin_getItem('', $DB, $sLang);

    if (isset($aItemdata["base"]) && !isset($aItemdata["text"])) {
        $aData = array(
            DB_ITEMTABLE_TEXT_PARENTPKEY => $aItemdata["base"][DB_ITEMTABLE_BASE_PKEY],
            DB_ITEMFIELD_LANGUAGE => $sLang,
        );
        //HaaseIT\Tools::debug($aData);

        $sQ = \HaaseIT\DBTools::buildInsertQuery($aData, DB_ITEMTABLE_TEXT);
        //HaaseIT\Tools::debug($sQ);
        $DB->exec($sQ);

        header('Location: '.$_SERVER["PHP_SELF"]."?itemno=".$_REQUEST["itemno"].'&action=showitem');
        die();
    }
    //HaaseIT\Tools::debug($aItemdata);
}
//HaaseIT\Tools::debug($_GET);
$P->cb_customdata["searchform"] = admin_prepareItemlistsearchform();

if (isset($_REQUEST["action"])) {
    if ($_REQUEST["action"] == 'search') {
        $P->cb_customdata["searchresult"] = true;
        if ($aItemlist = admin_getItemlist($DB, $sLang)) {
            if (count($aItemlist["data"]) == 1) {
                $aItemdata = admin_getItem($aItemlist["data"][0][DB_ITEMFIELD_NUMBER], $DB, $sLang);
                $P->cb_customdata["item"] = admin_prepareItem($aItemdata, $C, $DB, $sLang);
            } else {
                $P->cb_customdata["itemlist"] = admin_prepareItemlist($aItemlist, $twig);
            }
        }
    } elseif (isset($_REQUEST["doaction"]) && $_REQUEST["doaction"] == 'edititem') {
        admin_updateItem($C, $DB);
        $P->cb_customdata["itemupdated"] = true;

        $aItemdata = admin_getItem('', $DB, $sLang);
        $P->cb_customdata["item"] = admin_prepareItem($aItemdata, $C, $DB, $sLang);
    } elseif ($_REQUEST["action"] == 'showitem') {
        $aItemdata = admin_getItem('', $DB, $sLang);
        $P->cb_customdata["item"] = admin_prepareItem($aItemdata, $C, $DB, $sLang);
    } elseif ($_GET["action"] == 'additem') {
        $aErr = array();
        if (isset($_POST["additem"]) && $_POST["additem"] == 'do') {
            if (strlen($_POST["itemno"]) < 4) $aErr["itemnotooshort"] = true;
            else {
                $sQ = "SELECT ".DB_ITEMFIELD_NUMBER." FROM ".DB_ITEMTABLE_BASE." WHERE ".DB_ITEMFIELD_NUMBER." = '";
                $sQ .= \trim(\filter_input(INPUT_POST, 'itemno', FILTER_SANITIZE_SPECIAL_CHARS))."'";
                $hResult = $DB->query($sQ);
                $iRows = $hResult->rowCount();
                if ($iRows > 0) {
                    $aErr["itemnoalreadytaken"] = true;
                } else {
                    $aData = array(DB_ITEMFIELD_NUMBER => trim(\filter_input(INPUT_POST, 'itemno', FILTER_SANITIZE_SPECIAL_CHARS)),);
                    $sQ = \HaaseIT\DBTools::buildInsertQuery($aData, DB_ITEMTABLE_BASE);
                    //HaaseIT\Tools::debug($sQ);
                    $hResult = $DB->exec($sQ);
                    $iInsertID = $DB->lastInsertId();
                    $sQ = "SELECT ".DB_ITEMFIELD_NUMBER." FROM ".DB_ITEMTABLE_BASE." WHERE ".DB_ITEMTABLE_BASE_PKEY." = '".$iInsertID."'";
                    $hResult = $DB->query($sQ);
                    $aRow = $hResult->fetch();
                    header('Location: '.$_SERVER["PHP_SELF"].'?itemno='.$aRow[DB_ITEMFIELD_NUMBER].'&action=showitem');
                    die();
                }
            }
        }
        $P->cb_customdata["showaddform"] = true;
        $P->cb_customdata["err"] = $aErr;
    }
}
