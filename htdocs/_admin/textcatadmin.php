<?php

include_once('base.inc.php');

$sH = '';

function admin_showAddTextcatForm($sErr = '') {
    global $FORM;

    $sH = '';
    if (trim($sErr) != '') $sH .= $sErr.'<br>';
    $FORM->sFormaction = $_SERVER["PHP_SELF"].'?action=add';
    $sH .= $FORM->openForm('addtext');
    $sH .= $FORM->makeHidden('add', 'do');
    $sH .= 'Neuen Textschlüssel hinzufügen<br>';
    $sH .= $FORM->makeText('key', Tools::getFormfield('key', ''), 350, 64);
    $sH .= $FORM->makeSubmit('submit', 'Submit');
    $sH .= $FORM->closeForm();

    return $sH;
}

if (!isset($_REQUEST["action"]) || $_REQUEST["action"] == '') {
    $sQ = "SELECT * FROM textcat_base LEFT JOIN textcat_lang ON textcat_base.tc_id = textcat_lang.tcl_tcid && tcl_lang = :lang ORDER BY tc_key";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':lang', $sLang);
    $hResult->execute();
    $aData = $hResult->fetchAll();
    //debug($sQ);
    //debug($aData);

    $aListSetting = array(
        array('title' => 'TC Key', 'key' => 'tc_key', 'width' => 275, 'linked' => false,),
        array('title' => 'TC Text', 'key' => 'tcl_text', 'width' => 278, 'linked' => false, 'escapehtmlspecialchars' => true,),
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

    $sH .= '<div style="text-align:right;width:100%;"><a href="?action=add">'.T("misc_add_new_value").'</a></div>';
    $sH .= makeListtable($aListSetting, $aData, true);
} elseif ($_GET["action"] == 'edit') {
    //debug($_REQUEST);
    $sQ = "SELECT * FROM textcat_lang WHERE tcl_tcid = :id AND tcl_lang = :lang";
    //echo $sQ;
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':id', $_GET["id"]);
    $hResult->bindValue(':lang', $sLang);
    $hResult->execute();
    if ($hResult->rowCount() == 0) {
        $aData = array(
            'tcl_tcid' => $_GET["id"],
            'tcl_lang' => $sLang
        );
        $sQ = Tools::buildPSInsertQuery($aData, 'textcat_lang');
        //echo $sQ;
        $hResult = $DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
    }
    if (isset($_POST["edit"]) && $_POST["edit"] == 'do') {
        $aData = array(
            'tcl_text' => $_POST["text"],
            'tcl_id' => $_POST["lid"],
        );
        $sQ = Tools::buildPSUpdateQuery($aData, 'textcat_lang', 'tcl_id');
        //debug($sQ);
        $hResult = $DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
        $sH .= 'Der Wert wurde aktualisiert ('.showClienttime().').';
    }

    $sQ = "SELECT * FROM textcat_base LEFT JOIN textcat_lang ON textcat_base.tc_id = textcat_lang.tcl_tcid && ";
    $sQ .= "tcl_lang = :lang WHERE tc_id = :id";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':id', $_GET["id"]);
    $hResult->bindValue(':lang', $sLang);
    $hResult->execute();
    $aData = $hResult->fetch();
    //debug($aData);
    $FORM->sFormaction = $_SERVER["PHP_SELF"].'?action=edit&amp;id='.$_REQUEST["id"];
    $sH .= $FORM->openForm('edittextcat');
    $sH .= $FORM->makeHidden('edit', 'do');
    $sH .= $FORM->makeHidden('lid', $aData["tcl_id"]);
    $sH .= $FORM->makeText('key', $aData["tc_key"], 350, 0, true);
    $sH .= $FORM->makeText('lang', $sLang, 30, 0, true);
    $sH .= '<br>';
    $sH .= $FORM->makeTextarea('text', $aData["tcl_text"], 578, 150);
    $sH .= '<br>';
    $sH .= $FORM->makeSubmit('submit', 'Submit', 578, 's');
    $sH .= $FORM->closeForm();
} elseif ($_GET["action"] == 'add') {
    $sErr = '';
    if (isset($_POST["add"]) && $_POST["add"] == 'do') {
        if (strlen($_POST["key"]) < 3) $sErr = 'Der Textschlüssel muß aus mindestens 3 Zeichen bestehen.<br>';
        if ($sErr == '') {
            $sQ = "SELECT tc_key FROM textcat_base WHERE tc_key = :key";
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':key', $_POST["key"]);
            $hResult->execute();
            $iRows = $hResult->rowCount();
            if ($iRows > 0) $sErr = 'Dieser Textschlüssel ist bereits angelegt.<br>';
        }
        if ($sErr == '') {
            $aData = array('tc_key' => trim($_POST["key"]),);
            $sQ = Tools::buildInsertQuery($aData, 'textcat_base');
            //debug($sQ);
            $DB->exec($sQ);
            $iId = $DB->lastInsertId();
            $sErr = 'Der Schlüssel "'.$_POST["key"].'" wurde hinzugefügt.<br><a href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.$iId.'">Klicken Sie hier um ihn zu bearbeiten</a><br>';
        }
    }

    $sH .= admin_showAddTextcatForm($sErr);
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

echo $twig->render($C["template_base"], $aP);
