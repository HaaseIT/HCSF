<?php
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

include_once('base.inc.php');
include_once('shop/functions.admin.items.inc.php');
include_once('shop/functions.admin.itemgroups.inc.php');

$sH = '';

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'insert_lang') {
    $aItemdata = admin_getItem();

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
$sH .= admin_showItemlistsearchform();

if (isset($_REQUEST["action"])) {
    if ($_REQUEST["action"] == 'search') {
        if ($aArtikelliste = admin_getItemlist()) {
            if (count($aArtikelliste["data"]) == 1) {
                $aArtikeldaten = admin_getItem($aArtikelliste["data"][0][DB_ITEMFIELD_NUMBER]);
                $sH .= admin_showItem($aArtikeldaten);
            } else {
                $sH .= admin_showItemlist($aArtikelliste);
            }
        } else $sH .= 'No matches found.';
        //$sH .= debug($aArtikelliste);
    } elseif (isset($_REQUEST["doaction"]) && $_REQUEST["doaction"] == 'edititem') {
        if (admin_updateItem()) $sH .= '<div class="small"><b>Der Artikel wurde aktualisiert ('.showClienttime().').</b></div><br>';
        else $sH .= '<div class="small"><b>Beim Aktualisieren des Artikels ist ein Fehler aufgetreten,<br>bitte wenden Sie sich an den Systemadministrator.</b></div><br>';

        $aArtikeldaten = admin_getItem();
        $sH .= admin_showItem($aArtikeldaten);
        //debug($aArtikeldaten);
    } elseif ($_REQUEST["action"] == 'showitem') {
        $aArtikeldaten = admin_getItem();

        $sH .= admin_showItem($aArtikeldaten);
        //$sH .= debug($aArtikeldaten);
    } elseif ($_GET["action"] == 'additem') {
        $sErr = '';
        if (isset($_POST["additem"]) && $_POST["additem"] == 'do') {
            if (strlen($_POST["itemno"]) < 4) $sErr .= 'Bitte verwenden Sie mindestens 4 Zeichen für die Artikelnummer.<br>';
            else {
                $sQ = "SELECT ".DB_ITEMFIELD_NUMBER." FROM ".DB_ITEMTABLE_BASE." WHERE ".DB_ITEMFIELD_NUMBER." = '";
                $sQ .= \HaaseIT\Tools::cED(trim($_POST["itemno"]))."'";
                $hResult = $DB->query($sQ);
                $iRows = $hResult->rowCount();
                if ($iRows > 0) {
                    $sErr .= 'Diese Artikelnummer ist bereits vergeben.<br>';
                } else {
                    $aData = array(
                        DB_ITEMFIELD_NUMBER => trim($_POST["itemno"]),
                    );
                    $sQ = \HaaseIT\Tools::buildInsertQuery($aData, DB_ITEMTABLE_BASE);
                    //debug($sQ);
                    $hResult = $DB->exec($sQ);
                    $iInsertID = $DB->lastInsertId();
                    $sQ = "SELECT ".DB_ITEMFIELD_NUMBER." FROM ".DB_ITEMTABLE_BASE." WHERE ".DB_ITEMTABLE_BASE_PKEY." = '".$iInsertID."'";
                    $hResult = $DB->query($sQ);
                    $aRow = $hResult->fetch();
                    header('Location: '.$_SERVER["PHP_SELF"].'?itemno='.$aRow[DB_ITEMFIELD_NUMBER].'&action=showitem');
                }
            }
        }
        $sH .= admin_showItemAddForm($sErr);
    }
} else {
    $sH .= '<a href="'.$_SERVER["PHP_SELF"].'?action=additem">Artikel hinzufügen</a><br>';
}

$sH .= '<pre>
Zusatzdaten:
{
    "size":"|S|M|L|XL",
    "suggestions":"0011|0012|0001|0002",
    "sale": {
        "price":79.99,
        "start":"20140809",
        "end":"20140826"
    },
    "detailimg":["image1.jpg","image2.jpg"],
    "soldout":false
}
- set soldout to true and the item will not be orderable

Artikelindex:
Bei meherern Indizies, die einzelnen Indizies mit einer | trennen, z.B.: A020|A030
Bei HTML Tags, die Anführungszeichen enthalten unbedingt single quotes (\') verwenden! Beispiel: &lt;img src=\'/_img/gr_damen_t-shirts.jpg\'>
Keine Zeilenumbrüche in Zeichenketten, sonst funktioniert das ganze nicht!
</pre>';

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
