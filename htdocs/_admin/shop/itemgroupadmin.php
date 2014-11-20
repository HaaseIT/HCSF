<?php
/*
$P = array(
'head_scripts' => '<script type="text/javascript" src="/jquery.js"></script>
<script type="text/javascript" src="/_admin/_tinymce/tinymce.min.js"></script>
<script type="text/javascript">
tinymce.init({
selector: "textarea",
language : "de",
content_css: "/screen-global.css",
theme : "modern",
plugins: [
"advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker",
"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
"save table contextmenu directionality emoticons template paste textcolor"
],
templates : [
{
title: "2-Spaltige Tabelle 50/50",
url: "/_admin/_tinymce/templates/table5050.html",
description: "2-Spaltige Tabelle 50/50"
}
]
});
</script>',

// append new values here
'' => '',
);
*/

include_once('base.inc.php');
include_once('shop/functions.admin.itemgroups.inc.php');

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
    //debug($sQ);
    $iNumRowsLang = $hResult->rowCount();

    //debug($iNumRowsBasis.' / '.$iNumRowsLang);

    if ($iNumRowsBasis == 1 && $iNumRowsLang == 0) {
        $aData = array(
            DB_ITEMGROUPTABLE_TEXT_PARENTPKEY => $_REQUEST["gid"],
            DB_ITEMGROUPFIELD_LANGUAGE => $sLang,
        );
        //debug($aData);
        $sQ = buildPSInsertQuery($aData, DB_ITEMGROUPTABLE_TEXT);
        //echo debug($sQ);
        $hResult = $DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
        header('Location: '.$_SERVER["PHP_SELF"]."?gid=".$_REQUEST["gid"].'&action=editgroup');
        die();
    }
    //echo debug($aItemdata, false);
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'editgroup') {
    if (isset($_REQUEST["do"]) && $_REQUEST["do"] == 'true') {
        $sUpdatestatus = admin_updateGroup();
        if ($sUpdatestatus == 'success') {
            $sH .= '<div class="small"><b>Die Gruppe wurde aktualisiert.</b></div><br>';
        } elseif ($sUpdatestatus == 'duplicateno') {
            $sH .= '<div class="small"><b>Diese Gruppennummer wird bereits für eine andere Gruppe verwendet.</b></div><br>';
        } else {
            $sH .= '<div class="small"><b>Beim Aktualisieren der Gruppe ist ein Fehler aufgetreten,<br>bitte wenden Sie sich an den Systemadministrator.</b></div><br>';
        }
    }
    $aGroup = admin_getItemgroups($_REQUEST["gid"]);
    if (isset($_REQUEST["added"])) $sH .= 'Die Gruppe wurde hinzugefügt.<br><br>';
    $sH .= admin_showGroupForm('', 'edit', $aGroup[0]);
    //debug($aGroup);

} elseif (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'delete') {
    $sH .= 'Delete Not implemented yet.';
} elseif (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'addgroup') {
    $sErr = '';
    if (isset($_REQUEST["do"]) && $_REQUEST["do"] == 'true') {
        if (strlen($_REQUEST["name"]) < 3) $sErr .= 'Der Name ist zu kurz.<br>';
        if (strlen($_REQUEST["no"]) < 3) $sErr .= 'Die Gruppennummer ist zu kurz.<br>';
        if ($sErr == '') {
            $sQ = "SELECT ".DB_ITEMGROUPFIELD_NUMBER." FROM ".DB_ITEMGROUPTABLE_BASE;
            $sQ .= " WHERE ".DB_ITEMGROUPFIELD_NUMBER." = :no";
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':no', $_REQUEST["no"]);
            $hResult->execute();
            if ($hResult->rowCount() > 0) $sErr .= 'Diese Gruppennummer ist bereits vergeben.<br>';
        }
        if ($sErr == '') {
            $aData = array(
                DB_ITEMGROUPFIELD_NAME => $_REQUEST["name"],
                DB_ITEMGROUPFIELD_NUMBER => $_REQUEST["no"],
                DB_ITEMGROUPFIELD_IMG => $_REQUEST["img"],
            );
            $sQ = buildPSInsertQuery($aData, DB_ITEMGROUPTABLE_BASE);
            //debug($sQ);
            $hResult = $DB->prepare($sQ);
            foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
            $hResult->execute();
            $iLastInsertID = $DB->lastInsertId();
            header('Location: '.$_SERVER["PHP_SELF"].'?action=editgroup&added&gid='.$iLastInsertID);
        } else $sH .= admin_showGroupForm($sErr, 'add');
    } else $sH .= admin_showGroupForm('', 'add');
} else {
    $sH .= '<a href="'.$_SERVER["PHP_SELF"].'?action=addgroup">Click here to add a new group</a><br><br>';
    $sH .= admin_showItemgroups(admin_getItemgroups());
}

$P = array(
    'base' => array(
        'cb_pagetype' => 'content',
        'cb_pageconfig' => '',
        'cb_subnav' => 'admin',
    ),
    'lang' => array(
        'cl_lang' => $sLang,
        'cl_html' => $sH,
    ),
);

$aP = generatePage($C, $P, $sLang, $FORM);
$aP["debug"] = true;

echo $twig->render($C["template_base"], $aP);
