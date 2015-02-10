<?php

/*
    Contanto - A modular CMS and Shopsystem
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
selector: "textarea.wysiwyg",
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
include_once($_SERVER['DOCUMENT_ROOT'].'/../src/shop/functions.admin.items.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/../src/shop/functions.admin.itemgroups.php');

$P = array(
    'base' => array(
        'cb_pagetype' => 'content',
        'cb_pageconfig' => '',
        'cb_subnav' => 'admin',
        'cb_customcontenttemplate' => 'shop/itemadmin',
    ),
    'lang' => array(
        'cl_lang' => $sLang,
        'cl_html' => '',
    ),
);

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'insert_lang') {
    $aItemdata = admin_getItem('', $DB, $sLang);

    if (isset($aItemdata["base"]) && !isset($aItemdata["text"])) {
        $aData = array(
            DB_ITEMTABLE_TEXT_PARENTPKEY => $aItemdata["base"][DB_ITEMTABLE_BASE_PKEY],
            DB_ITEMFIELD_LANGUAGE => $sLang,
        );
        //debug($aData);

        $sQ = \HaaseIT\Tools::buildInsertQuery($aData, DB_ITEMTABLE_TEXT);
        //echo debug($sQ, false);
        $DB->exec($sQ);

        header('Location: '.$_SERVER["PHP_SELF"]."?itemno=".$_REQUEST["itemno"].'&action=showitem');
        die();
    }
    //echo debug($aItemdata, false);
}
//debug($_GET);
$P["base"]["cb_customdata"]["searchform"] = admin_prepareItemlistsearchform();

if (isset($_REQUEST["action"])) {
    if ($_REQUEST["action"] == 'search') {
        $P["base"]["cb_customdata"]["searchresult"] = true;
        if ($aItemlist = admin_getItemlist($DB, $sLang)) {
            if (count($aItemlist["data"]) == 1) {
                $aItemdata = admin_getItem($aItemlist["data"][0][DB_ITEMFIELD_NUMBER], $DB, $sLang);
                $P["base"]["cb_customdata"]["item"] = admin_prepareItem($aItemdata, $C, $DB, $sLang);
            } else {
                $P["base"]["cb_customdata"]["itemlist"] = admin_prepareItemlist($aItemlist, $twig);
            }
        }
    } elseif (isset($_REQUEST["doaction"]) && $_REQUEST["doaction"] == 'edititem') {
        admin_updateItem($C, $DB);
        $P["base"]["cb_customdata"]["itemupdated"] = true;

        $aItemdata = admin_getItem('', $DB, $sLang);
        $P["base"]["cb_customdata"]["item"] = admin_prepareItem($aItemdata, $C, $DB, $sLang);
    } elseif ($_REQUEST["action"] == 'showitem') {
        $aItemdata = admin_getItem('', $DB, $sLang);
        $P["base"]["cb_customdata"]["item"] = admin_prepareItem($aItemdata, $C, $DB, $sLang);
    } elseif ($_GET["action"] == 'additem') {
        $aErr = array();
        if (isset($_POST["additem"]) && $_POST["additem"] == 'do') {
            if (strlen($_POST["itemno"]) < 4) $aErr["itemnotooshort"] = true;
            else {
                $sQ = "SELECT ".DB_ITEMFIELD_NUMBER." FROM ".DB_ITEMTABLE_BASE." WHERE ".DB_ITEMFIELD_NUMBER." = '";
                $sQ .= \HaaseIT\Tools::cED(trim($_POST["itemno"]))."'";
                $hResult = $DB->query($sQ);
                $iRows = $hResult->rowCount();
                if ($iRows > 0) {
                    $aErr["itemnoalreadytaken"] = true;
                } else {
                    $aData = array(DB_ITEMFIELD_NUMBER => trim($_POST["itemno"]),);
                    $sQ = \HaaseIT\Tools::buildInsertQuery($aData, DB_ITEMTABLE_BASE);
                    //debug($sQ);
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
        $P["base"]["cb_customdata"]["showaddform"] = true;
        $P["base"]["cb_customdata"]["err"] = $aErr;
    }
}

$aP = generatePage($C, $P, $sLang, $DB, $oItem);

echo $twig->render($C["template_base"], $aP);
