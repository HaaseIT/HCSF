<?php

function admin_getPage($iPage, $DB, $sLang) {
    $sQ = "SELECT * FROM ".DB_CONTENTTABLE_BASE." WHERE ".DB_CONTENTTABLE_BASE_PKEY." = :pid";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':pid', $iPage);
    $hResult->execute();
    //echo debug($sQ, true);
    //echo debug($DB->error(), true);
    $iNumrows = $hResult->rowCount();
    if ($iNumrows == 1) {
        $aPage["base"] = $hResult->fetch();

        $sQ = "SELECT * FROM " . DB_CONTENTTABLE_LANG;
        $sQ .= " WHERE " . DB_CONTENTTABLE_LANG_PARENTPKEY . " = :parentpkey";
        $sQ .= " AND " . DB_CONTENTFIELD_LANG . " = :lang";
        $hResult = $DB->prepare($sQ);
        $hResult->bindValue(':parentpkey', $iPage);
        $hResult->bindValue(':lang', $sLang);
        $hResult->execute();
        //echo debug($sQ, true);
        if ($hResult->rowCount() != 0) $aPage["text"] = $hResult->fetch();

        return $aPage;
    } else {
        return false;
    }
}

function updatePage($DB, $C, $sLang) {
    if (array_search($_SERVER["REMOTE_USER"], $C["admin_users"]) !== false) {
        $aData = array(
            "cb_pagetype" => $_REQUEST["page_type"],
            "cb_group" => $_REQUEST["page_group"],
            "cb_pageconfig" => $_REQUEST["page_config"],
            "cb_subnav" => $_REQUEST["page_subnav"],
            'cb_id' => $_REQUEST["page_id"],
        );

        $sQ = \HaaseIT\Tools::buildPSUpdateQuery($aData, DB_CONTENTTABLE_BASE, 'cb_id');
        $hResult = $DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
    }
    if (isset($_REQUEST["textid"])) {
        $aData = array(
            "cl_html" => $_REQUEST["page_html"],
            "cl_title" => $_REQUEST["page_title"],
            "cl_description" => $_REQUEST["page_description"],
            "cl_keywords" => $_REQUEST["page_keywords"],
        );

        $sQ = \HaaseIT\Tools::buildPSUpdateQuery($aData, DB_CONTENTTABLE_LANG);
        $sQ .= "WHERE ".DB_CONTENTTABLE_LANG_PARENTPKEY." = :".DB_CONTENTTABLE_LANG_PARENTPKEY;
        $sQ .= " AND cl_lang = :cl_lang AND ".DB_CONTENTTABLE_LANG_PKEY." = :".DB_CONTENTTABLE_LANG_PKEY;
        $hResult = $DB->prepare($sQ);
        $aData["cl_lang"] = $sLang;
        $aData[DB_CONTENTTABLE_LANG_PARENTPKEY] = $_REQUEST["page_id"];
        $aData[DB_CONTENTTABLE_LANG_PKEY] = $_REQUEST["textid"];
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
    }

    return true;
}

function showPageselect($DB, $C) {
    $sQ = "SELECT * FROM ".DB_CONTENTTABLE_BASE." ORDER BY cb_key";
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
        'options_groups' => $aOptions_g,
        'tree' => $aTree,
    );

    return $aSData;
}
