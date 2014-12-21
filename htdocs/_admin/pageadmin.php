<?php
/*
14.9.2009
- moved update-queries to buildUpdateQuery()
- filtered input for all select queries
*/

//error_reporting(E_ALL);
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
);
*/

include_once('base.inc.php');
include_once('functions.admin.pages.inc.php');

$P = array(
    'base' => array(
        'cb_pagetype' => 'content',
        'cb_pageconfig' => '',
        'cb_subnav' => 'admin',
        'cb_customcontenttemplate' => 'pageadmin',
    ),
    'lang' => array(
        'cl_lang' => $sLang,
    ),
);

$sH = '';

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'insert_lang') {
    $aPage = admin_getPage($_REQUEST["page_id"], $DB, $sLang);

    if (isset($aPage["base"]) && !isset($aPage["text"])) {
        $aData = array(
            DB_CONTENTTABLE_LANG_PARENTPKEY => $aPage["base"][DB_CONTENTTABLE_BASE_PKEY],
            DB_CONTENTFIELD_LANG => $sLang,
        );
        //debug($aData);
        $sQ = \HaaseIT\Tools::buildInsertQuery($aData, DB_CONTENTTABLE_LANG);
        //echo debug($sQ, false);
        $DB->exec($sQ);
        header('Location: '.$_SERVER["PHP_SELF"]."?page_id=".$_REQUEST["page_id"].'&action=edit');
        die();
    }
    //echo debug($aItemdata, false);
}

if (!isset($_REQUEST["action"])) {
    $P["base"]["cb_customdata"]["pageselect"] = showPageselect($DB, $C);
} elseif ($_REQUEST["action"] == 'edit' && isset($_REQUEST["page_id"]) && $_REQUEST["page_id"] != '') {
    if (admin_getPage($_REQUEST["page_id"], $DB, $sLang)) {
        if (isset($_REQUEST["action_a"]) && $_REQUEST["action_a"] == 'true') $P["base"]["cb_customdata"]["updated"] = updatePage($DB, $C, $sLang);
        $P["base"]["cb_customdata"]["page"] = admin_getPage($_REQUEST["page_id"], $DB, $sLang);
        $P["base"]["cb_customdata"]["page"]["admin_page_types"] = $C["admin_page_types"];
        $P["base"]["cb_customdata"]["page"]["admin_page_groups"] = $C["admin_page_groups"];
        $aOptions = array('');
        foreach ($C["navstruct"] as $sKey => $aValue) $aOptions[] = $sKey;
        $P["base"]["cb_customdata"]["page"]["subnavarea_options"] = $aOptions;
        unset($aOptions);
    } else {

    }
} elseif ($_REQUEST["action"] == 'addpage') {
    $sErr = '';
    if (isset($_POST["addpage"]) && $_POST["addpage"] == 'do') {
        if (strlen($_POST["pagekey"]) < 4) $sErr .= 'Bitte verwenden Sie mindestens 4 Zeichen für die Artikelnummer.<br>';
        else {
            $sQ = "SELECT ".DB_CONTENTFIELD_BASE_KEY." FROM ".DB_CONTENTTABLE_BASE." WHERE ".DB_CONTENTFIELD_BASE_KEY." = '";
            $sQ .= \HaaseIT\Tools::cED(trim($_POST["pagekey"]))."'";
            $hResult = $DB->query($sQ);
            $iRows = $hResult->rowCount();
            if ($iRows > 0) {
                $sErr .= 'Dieser Seitenschlüssel ist bereits vergeben.<br>';
            } else {
                $aData = array(DB_CONTENTFIELD_BASE_KEY => trim($_POST["pagekey"]),);
                $sQ = \HaaseIT\Tools::buildInsertQuery($aData, DB_CONTENTTABLE_BASE);
                //debug($sQ);
                $hResult = $DB->exec($sQ);
                $iInsertID = $DB->lastInsertId();
                $sQ = "SELECT ".DB_CONTENTTABLE_BASE_PKEY." FROM ".DB_CONTENTTABLE_BASE." WHERE ".DB_CONTENTTABLE_BASE_PKEY." = '".$iInsertID."'";
                $hResult = $DB->query($sQ);
                $aRow = $hResult->fetch();
                header('Location: '.$_SERVER["PHP_SELF"].'?page_id='.$aRow[DB_CONTENTTABLE_BASE_PKEY].'&action=edit');
            }
        }
    }
    $sH .= admin_showPageAddForm($sErr);
}

$P["lang"]["cl_html"] = $sH;

$aP = generatePage($C, $P, $sLang);

echo $twig->render($C["template_base"], $aP);
