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

/*
14.9.2009
- moved update-queries to buildUpdateQuery()
- filtered input for all select queries
*/

require_once __DIR__.'/../../app/init.php';
require_once __DIR__.'/../../src/functions.admin.pages.php';

$P = new \HaaseIT\HCSF\CorePage($C, $sLang);
$P->cb_pagetype = 'content';
$P->cb_subnav = 'admin';
$P->cb_customcontenttemplate = 'pageadmin';

function showPageselect($DB, $C) {
    $sQ = "SELECT * FROM content_base ORDER BY cb_key";
    $hResult = $DB->query($sQ);
    foreach ($C["admin_page_groups"] as $sValue) {
        $TMP = explode('|', $sValue);
        $aGroupkeys[] = $TMP[0];
    }
    unset($TMP);

    while ($aResult = $hResult->fetch()) {
        $bGrouped = false;
        foreach ($aGroupkeys as $sValue) {
            if ($aResult["cb_group"] == $sValue) {
                $aTree[$sValue][] = $aResult;
                $bGrouped = true;
            }
        }
        if (!$bGrouped) $aTree["_"][] = $aResult;
    }

    foreach ($C["admin_page_groups"] as $sValue) {
        $TMP = explode('|', $sValue);
        if (isset ($aTree[$TMP[0]]) && count($aTree[$TMP[0]]) >= 1) {
            $aOptions_g[] = $TMP[0].'|'.$TMP[1];
        }
    }
    unset($TMP);

    $aSData = array(
        'options_groups' => isset($aOptions_g) ? $aOptions_g : array(),
        'tree' => isset($aTree) ? $aTree : array(),
    );

    return $aSData;
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'insert_lang') {
    $aPage = admin_getPage($_REQUEST["page_id"], $DB, $sLang);

    if (isset($aPage["base"]) && !isset($aPage["text"])) {
        $aData = array(
            'cl_cb' => $aPage["base"]["cb_id"],
            'cl_lang' => $sLang,
        );
        //HaaseIT\Tools::debug($aData);
        $sQ = \HaaseIT\DBTools::buildInsertQuery($aData, 'content_lang');
        //HaaseIT\Tools::debug($sQ);
        $DB->exec($sQ);
        header('Location: '.$_SERVER["PHP_SELF"]."?page_id=".$_REQUEST["page_id"].'&action=edit');
        die();
    }
    //HaaseIT\Tools::debug($aItemdata);
}

if (!isset($_GET["action"])) {
    $P->cb_customdata["pageselect"] = showPageselect($DB, $C);
} elseif (($_GET["action"] == 'edit' || $_GET["action"] == 'delete') && isset($_REQUEST["page_key"]) && $_REQUEST["page_key"] != '') {
    if ($_GET["action"] == 'delete' && isset($_POST["delete"]) && $_POST["delete"] == 'do') {
        // delete and put message in customdata
        // delete children
        $sQ = "DELETE FROM content_lang WHERE cl_cb = '".\filter_var($_GET["page_id"], FILTER_SANITIZE_NUMBER_INT)."'";
        $DB->exec($sQ);

        // then delete base row
        $sQ = "DELETE FROM content_base WHERE cb_id = '".\filter_var($_GET["page_id"], FILTER_SANITIZE_NUMBER_INT)."'";
        $DB->exec($sQ);

        $P->cb_customdata["deleted"] = true;
    } else { // edit or update page
        if (isset($_REQUEST["page_key"]) && $Ptoedit = new \HaaseIT\HCSF\UserPage($C, $sLang, $DB, $_REQUEST["page_key"], true)) {
            if (isset($_REQUEST["action_a"]) && $_REQUEST["action_a"] == 'true') {

                $Ptoedit->cb_pagetype = $_POST['page_type'];
                $Ptoedit->cb_group = $_POST['page_group'];
                $Ptoedit->cb_pageconfig = $_POST['page_config'];
                $Ptoedit->cb_subnav = $_POST['page_subnav'];
                $bBaseupdated = $Ptoedit->write();

                if ($Ptoedit->oPayload->cl_id != NULL) {
                    $Ptoedit->oPayload->cl_html = $_POST['page_html'];
                    $Ptoedit->oPayload->cl_title = $_POST['page_title'];
                    $Ptoedit->oPayload->cl_description = $_POST['page_description'];
                    $Ptoedit->oPayload->cl_keywords = $_POST['page_keywords'];
                    $bPayloadupdated = $Ptoedit->oPayload->write();
                }

                $P->cb_customdata["updated"] = true;
            }
            $P->cb_customdata["page"] = $Ptoedit;
            $P->cb_customdata["admin_page_types"] = $C["admin_page_types"];
            $P->cb_customdata["admin_page_groups"] = $C["admin_page_groups"];
            $aOptions = array('');
            foreach ($C["navstruct"] as $sKey => $aValue) {
                if ($sKey == 'admin') {
                    continue;
                }
                $aOptions[] = $sKey;
            }
            $P->cb_customdata["subnavarea_options"] = $aOptions;
            unset($aOptions);
        } else {
            die('Page selected not found error.');
        }


    }
} elseif ($_GET["action"] == 'addpage') {
    $aErr = array();
    if (isset($_POST["addpage"]) && $_POST["addpage"] == 'do') {
        if (mb_substr($_POST["pagekey"], 0, 2) == '/_') {
            $aErr["reservedpath"] = true;
        } elseif (strlen($_POST["pagekey"]) < 4) {
            $aErr["keytooshort"] = true;
        } else {
            $sQ = "SELECT cb_key FROM content_base WHERE cb_key = '";
            $sQ .= \trim(\filter_input(INPUT_POST, 'pagekey', FILTER_SANITIZE_SPECIAL_CHARS))."'";
            $hResult = $DB->query($sQ);
            $iRows = $hResult->rowCount();
            if ($iRows > 0) {
                $aErr["keyalreadyinuse"] = true;
            } else {
                $aData = array('cb_key' => trim(\filter_input(INPUT_POST, 'pagekey', FILTER_SANITIZE_SPECIAL_CHARS)),);
                $sQ = \HaaseIT\DBTools::buildInsertQuery($aData, 'content_base');
                //HaaseIT\Tools::debug($sQ);
                $hResult = $DB->exec($sQ);
                $iInsertID = $DB->lastInsertId();
                $sQ = "SELECT cb_id FROM content_base WHERE cb_id = '".$iInsertID."'";
                $hResult = $DB->query($sQ);
                $aRow = $hResult->fetch();
                header('Location: '.$_SERVER["PHP_SELF"].'?page_key='.$aRow["cb_key"].'&action=edit');
            }
        }
        $P->cb_customdata["err"] = $aErr;
        unset($aErr);
    }
    $P->cb_customdata["showaddform"] = true;
}

$aP = generatePage($C, $P, $sLang, $DB, $oItem);

echo $twig->render($C["template_base"], $aP);
