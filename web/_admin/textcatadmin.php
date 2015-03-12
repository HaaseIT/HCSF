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

include_once(__DIR__.'/../../app/init.php');

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
} elseif ($_GET["action"] == 'edit') {
    $P["base"]["cb_customdata"]["edit"] = true;
    //\HaaseIT\Tools::debug($_REQUEST);

    \HaaseIT\Textcat::initTextIfVoid($_GET["id"]);

    // if post:edit is set, update
    if (isset($_POST["edit"]) && $_POST["edit"] == 'do') {
        \HaaseIT\Textcat::saveText($_POST["lid"], $_POST["text"]);
        $P["base"]["cb_customdata"]["updated"] = true;
    }

    $aData = \HaaseIT\Textcat::getSingleTextByID($_GET["id"]);
    //HaaseIT\Tools::debug($aData);
    $P["base"]["cb_customdata"]["editform"] = array(
        'id' => $aData["tc_id"],
        'lid' => $aData["tcl_id"],
        'key' => $aData["tc_key"],
        'lang' => $aData["tcl_lang"],
        'text' => $aData["tcl_text"],
    );
} elseif ($_GET["action"] == 'add') {
    $P["base"]["cb_customdata"]["add"] = true;
    if (isset($_POST["add"]) && $_POST["add"] == 'do') {
        $P["base"]["cb_customdata"]["err"] = \HaaseIT\Textcat::verifyAddTextKey($_POST["key"]);

        if (count($P["base"]["cb_customdata"]["err"]) == 0) {
            $P["base"]["cb_customdata"]["addform"] = array(
                'key' => $_POST["key"],
                'id' => \HaaseIT\Textcat::addTextKey($_POST["key"]),
            );
        }
    }
}

$P["lang"]["cl_html"] = $sH;

$aP = generatePage($C, $P, $sLang, $DB, $oItem);

echo $twig->render($C["template_base"], $aP);
