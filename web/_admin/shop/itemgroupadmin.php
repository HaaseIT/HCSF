<?php

/*
    Contanto - A multilingual CMS and Shopsystem
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

include_once($_SERVER['DOCUMENT_ROOT'].'/../app/init.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/../src/shop/functions.admin.itemgroups.php');

$P = array(
    'base' => array(
        'cb_pagetype' => 'content',
        'cb_pageconfig' => '',
        'cb_subnav' => 'admin',
        'cb_customcontenttemplate' => 'shop/itemgroupadmin',
    ),
    'lang' => array(
        'cl_lang' => $sLang,
        'cl_html' => '',
    ),
);

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
        $sQ = \HaaseIT\Tools::buildPSInsertQuery($aData, DB_ITEMGROUPTABLE_TEXT);
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
        $P["base"]["cb_customdata"]["updatestatus"] = admin_updateGroup($DB, $sLang);
    }
    $aGroup = admin_getItemgroups($_REQUEST["gid"], $DB, $sLang);
    if (isset($_REQUEST["added"])) {
        $P["base"]["cb_customdata"]["groupjustadded"] = true;
    }
    $P["base"]["cb_customdata"]["showform"] = 'edit';
    $P["base"]["cb_customdata"]["group"] = admin_prepareGroup('edit', $aGroup[0]);
    //debug($aGroup);
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
            $sQ = \HaaseIT\Tools::buildPSInsertQuery($aData, DB_ITEMGROUPTABLE_BASE);
            //debug($sQ);
            $hResult = $DB->prepare($sQ);
            foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
            $hResult->execute();
            $iLastInsertID = $DB->lastInsertId();
            header('Location: '.$_SERVER["PHP_SELF"].'?action=editgroup&added&gid='.$iLastInsertID);
        } else {
            $P["base"]["cb_customdata"]["err"] = $aErr;
            $P["base"]["cb_customdata"]["showform"] = 'add';
            $P["base"]["cb_customdata"]["group"] = admin_prepareGroup('add');
        }
    } else {
        $P["base"]["cb_customdata"]["showform"] = 'add';
        $P["base"]["cb_customdata"]["group"] = admin_prepareGroup('add');
    }
} else {
    if (!$sH .= admin_showItemgroups(admin_getItemgroups('', $DB, $sLang), $twig)) {
        $P["base"]["cb_customdata"]["err"]["nogroupsavaliable"] = true;
    }

}

$P["lang"]["cl_html"] = $sH;

$aP = generatePage($C, $P, $sLang, $DB, $oItem);

echo $twig->render($C["template_base"], $aP);
