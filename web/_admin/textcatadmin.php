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

include_once($_SERVER['DOCUMENT_ROOT'].'/../app/init.php');

$P = array(
    'base' => array(
        'cb_pagetype' => 'content',
        'cb_pageconfig' => '',
        'cb_subnav' => 'admin',
        'cb_customcontenttemplate' => 'textcatadmin',
    ),
    'lang' => array(
        'cl_lang' => $sLang,
    ),
);

$sH = '';

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

    $sH .= \HaaseIT\Tools::makeListtable($aListSetting, $aData, $twig);
} elseif ($_GET["action"] == 'edit') {
    $P["base"]["cb_customdata"]["edit"] = true;
    //debug($_REQUEST);
    $sQ = "SELECT * FROM textcat_lang WHERE tcl_tcid = :id AND tcl_lang = :lang";
    //echo $sQ;

    // Check if this textkey already has a child in the language table, if not, insert one
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':id', $_GET["id"]);
    $hResult->bindValue(':lang', $sLang);
    $hResult->execute();
    if ($hResult->rowCount() == 0) {
        $aData = array(
            'tcl_tcid' => $_GET["id"],
            'tcl_lang' => $sLang
        );
        $sQ = \HaaseIT\Tools::buildPSInsertQuery($aData, 'textcat_lang');
        //echo $sQ;
        $hResult = $DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
    }
    // if post:edit is set, update
    if (isset($_POST["edit"]) && $_POST["edit"] == 'do') {
        $aData = array(
            'tcl_text' => $_POST["text"],
            'tcl_id' => $_POST["lid"],
        );
        $sQ = \HaaseIT\Tools::buildPSUpdateQuery($aData, 'textcat_lang', 'tcl_id');
        //debug($sQ);
        $hResult = $DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
        $P["base"]["cb_customdata"]["updated"] = true;
    }

    $sQ = "SELECT * FROM textcat_base LEFT JOIN textcat_lang ON textcat_base.tc_id = textcat_lang.tcl_tcid && ";
    $sQ .= "tcl_lang = :lang WHERE tc_id = :id";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':id', $_GET["id"]);
    $hResult->bindValue(':lang', $sLang);
    $hResult->execute();
    $aData = $hResult->fetch();
    //debug($aData);
    $P["base"]["cb_customdata"]["editform"] = array(
        'id' => $_REQUEST["id"],
        'lid' => $aData["tcl_id"],
        'key' => $aData["tc_key"],
        'lang' => $sLang,
        'text' => $aData["tcl_text"],
    );
} elseif ($_GET["action"] == 'add') {
    $P["base"]["cb_customdata"]["add"] = true;
    $aErr = array();
    if (isset($_POST["add"]) && $_POST["add"] == 'do') {
        if (strlen($_POST["key"]) < 3) $aErr["keytooshort"] = true;
        if (count($aErr) == 0) {
            $sQ = "SELECT tc_key FROM textcat_base WHERE tc_key = :key";
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':key', $_POST["key"]);
            $hResult->execute();
            $iRows = $hResult->rowCount();
            if ($iRows > 0) $aErr["keyalreadyexists"] = true;
        }
        if (count($aErr) == 0) {
            $aData = array('tc_key' => trim($_POST["key"]),);
            $sQ = \HaaseIT\Tools::buildInsertQuery($aData, 'textcat_base');
            //debug($sQ);
            $DB->exec($sQ);
            $iId = $DB->lastInsertId();
            $P["base"]["cb_customdata"]["addform"] = array(
                'key' => $_POST["key"],
                'id' => $iId,
            );
        }
        $P["base"]["cb_customdata"]["err"] = $aErr;
    }
}

$P["lang"]["cl_html"] = $sH;

$aP = generatePage($C, $P, $sLang, $DB, $oItem);

echo $twig->render($C["template_base"], $aP);
