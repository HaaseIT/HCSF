<?php

$P = new \HaaseIT\HCSF\CorePage($C, $sLang);
$P->cb_pagetype = 'content';
$P->cb_subnav = 'admin';
$P->cb_customcontenttemplate = 'textcatadmin';

$sH = '';

if (!isset($_REQUEST["action"]) || $_REQUEST["action"] == '') {
    $aData = \HaaseIT\Textcat::getCompleteTextcatForCurrentLang();
    //HaaseIT\Tools::debug($aData);

    $aListSetting = array(
        array('title' => 'TC Key', 'key' => 'tc_key', 'width' => '20%', 'linked' => false,),
        array('title' => 'TC Text', 'key' => 'tcl_text', 'width' => '80%', 'linked' => false, 'escapehtmlspecialchars' => true,),
        array(
            'title' => 'Edit',
            'key' => 'tc_id',
            'width' => 35,
            'linked' => true,
            'ltarget' => $_SERVER["PHP_SELF"],
            'lkeyname' => 'id',
            'lgetvars' => array(
                'action' => 'edit',
            ),
        ),
    );
    $sH .= \HaaseIT\Tools::makeListtable($aListSetting, $aData, $twig);
} elseif ($_GET["action"] == 'edit' || $_GET["action"] == 'delete') {
    if ($_GET["action"] == 'delete' && isset($_POST["delete"]) && $_POST["delete"] == 'do') {
        \HaaseIT\Textcat::deleteText($_GET["id"]);
        $P->cb_customdata["deleted"] = true;
    } else {
        $P->cb_customdata["edit"] = true;
        //\HaaseIT\Tools::debug($_REQUEST);

        \HaaseIT\Textcat::initTextIfVoid($_GET["id"]);

        // if post:edit is set, update
        if (isset($_POST["edit"]) && $_POST["edit"] == 'do') {
            \HaaseIT\Textcat::saveText($_POST["lid"], $_POST["text"]);
            $P->cb_customdata["updated"] = true;
        }

        $aData = \HaaseIT\Textcat::getSingleTextByID($_GET["id"]);
        //HaaseIT\Tools::debug($aData);
        $P->cb_customdata["editform"] = array(
            'id' => $aData["tc_id"],
            'lid' => $aData["tcl_id"],
            'key' => $aData["tc_key"],
            'lang' => $aData["tcl_lang"],
            'text' => $aData["tcl_text"],
        );
    }
} elseif ($_GET["action"] == 'add') {
    $P->cb_customdata["add"] = true;
    if (isset($_POST["add"]) && $_POST["add"] == 'do') {
        $P->cb_customdata["err"] = \HaaseIT\Textcat::verifyAddTextKey($_POST["key"]);

        if (count($P->cb_customdata["err"]) == 0) {
            $P->cb_customdata["addform"] = array(
                'key' => $_POST["key"],
                'id' => \HaaseIT\Textcat::addTextKey($_POST["key"]),
            );
        }
    }
}

$P->oPayload->cl_html = $sH;
