<?php

function admin_updateGroup() {
    global $C, $DB, $sLang;

    $sQ = "SELECT * FROM ".DB_ITEMGROUPTABLE_BASE." WHERE ".DB_ITEMGROUPTABLE_BASE_PKEY." != :id AND ";
    $sQ .= DB_ITEMGROUPFIELD_NUMBER." = :gno";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':id', $_REQUEST["gid"]);
    $hResult->bindValue(':gno', $_REQUEST["no"]);
    $hResult->execute();
    $iNumRows = $hResult->rowCount();

    if ($iNumRows > 0) return 'duplicateno';

    $aData = array(
        DB_ITEMGROUPFIELD_NAME => $_REQUEST["name"],
        DB_ITEMGROUPFIELD_NUMBER => $_REQUEST["no"],
        DB_ITEMGROUPFIELD_IMG => $_REQUEST["img"],
        DB_ITEMGROUPTABLE_BASE_PKEY => $_REQUEST["gid"],
    );

    $sQ = buildPSUpdateQuery($aData, DB_ITEMGROUPTABLE_BASE, DB_ITEMGROUPTABLE_BASE_PKEY);
    //debug($sQ);
    $hResult = $DB->prepare($sQ);
    foreach ($aData as $sKey => $sValue) {
        $hResult->bindValue(':'.$sKey, $sValue);
    }
    $hResult->execute();

    $sQ = "SELECT ".DB_ITEMGROUPTABLE_TEXT_PKEY." FROM ".DB_ITEMGROUPTABLE_TEXT;
    $sQ .= " WHERE ".DB_ITEMGROUPTABLE_TEXT_PARENTPKEY." = :gid";
    $sQ .= " AND ".DB_ITEMGROUPFIELD_LANGUAGE." = :lang";
    //debug($sQ);
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':gid', $_REQUEST["gid"]);
    $hResult->bindValue(':lang', $sLang, PDO::PARAM_STR);
    $hResult->execute();

    $iNumRows = $hResult->rowCount();

    if ($iNumRows == 1) {
        $aRow = $hResult->fetch();
        //debug($aRow);
        $aData = array(
            DB_ITEMGROUPFIELD_SHORTTEXT => $_REQUEST["shorttext"],
            DB_ITEMGROUPFIELD_DETAILS => $_REQUEST["details"],
            DB_ITEMGROUPTABLE_TEXT_PKEY => $aRow[DB_ITEMGROUPTABLE_TEXT_PKEY],
        );
        $sQ = buildPSUpdateQuery($aData, DB_ITEMGROUPTABLE_TEXT, DB_ITEMGROUPTABLE_TEXT_PKEY);
        //debug($sQ);
        $hResult = $DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
    }

    return 'success';
}

function admin_showGroupForm($sErr = '', $sPurpose = 'none', $aData = array()) { // no query
    global $FORM, $C, $sLang;

    $sH = '';
    //debug($aData);
    //$sH = '<strong>Artikelgruppe hinzufügen</strong><br /><br />';
    if ($sErr != '') $sH .= '<div style="color: red; font-weight: bold;">'.$sErr.'</div><br />';
    $sH .= '<table border="0" cellspacing="0" cellpadding="0">';

    $FORM->sFormmethod = 'POST';
    $FORM->sFormaction = makeLinkHRefWithAddedGetVars($_SERVER["PHP_SELF"]);
    $sH .= $FORM->openForm();
    $sH .= $FORM->makeHidden('do', 'true');

    $sH .= '<tr><td>Gruppenname:<br />';
    $sH .= $FORM->makeText('name', getFormField('name', ((isset($aData[DB_ITEMGROUPFIELD_NAME])) ? $aData[DB_ITEMGROUPFIELD_NAME] : '')), 540, 0);
    $sH .= '</td></tr>';
    $sH .= '<tr><td class="main">Gruppennummer:<br />';
    $sH .= $FORM->makeText('no', getFormField('no', ((isset($aData[DB_ITEMGROUPFIELD_NUMBER])) ? $aData[DB_ITEMGROUPFIELD_NUMBER] : '')), 540);
    $sH .= '</tr>';
    $sH .= '<tr><td><table border="0" cellspacing="0" cellpadding="0"><tr><td class="main">Bild:<br>';
    $sH .= $FORM->makeText('img', getFormField('img', ((isset($aData[DB_ITEMGROUPFIELD_IMG])) ? $aData[DB_ITEMGROUPFIELD_IMG] : '')), 265, 0, false, 'formtext_globalsetting');
    $sH .= '</td></tr></table></td></tr>';

    if ($sPurpose == 'edit') {
        if ($aData[DB_ITEMGROUPTABLE_TEXT_PKEY] != '') {
            $sH .= '<tr><td class="main">Kurztext:<br>';
            $sH .= $FORM->makeTextarea('shorttext', getFormField('shorttext', ((isset($aData[DB_ITEMGROUPFIELD_SHORTTEXT])) ? $aData[DB_ITEMGROUPFIELD_SHORTTEXT] : '')), 540, 150);
            $sH .= '<tr><td class="main">Details:<br>';
            $sH .= $FORM->makeTextarea('details', getFormField('details', ((isset($aData[DB_ITEMGROUPFIELD_DETAILS])) ? $aData[DB_ITEMGROUPFIELD_DETAILS] : '')), 540, 150);
            $sH .= '</td></tr>';
        } else {
            $sH .= '<tr><td>In dieser Sprache sind noch keine Texte angelegt!';
            $sH .= '<a href="'.$_SERVER["PHP_SELF"].'?action=insert_lang&gid='.$aData[DB_ITEMGROUPTABLE_BASE_PKEY].'">[Anlegen]</a></td></tr>';
        }
    }
    $sH .= '<tr><td>';
    $sH .= $FORM->makeSubmit('', 'Submit', 540);
    $sH .= '</td></tr>';
    $sH .= '</table>';

    return $sH;
}

function admin_getItemgroups($iGID = '') { // input filtered
    global $C, $DB, $sLang;

    $sQ = "SELECT * FROM ".DB_ITEMGROUPTABLE_BASE;
    $sQ .= " LEFT OUTER JOIN ".DB_ITEMGROUPTABLE_TEXT." ON ";
    $sQ .= DB_ITEMGROUPTABLE_BASE.".".DB_ITEMGROUPTABLE_BASE_PKEY." = ".DB_ITEMGROUPTABLE_TEXT.".".DB_ITEMGROUPTABLE_TEXT_PARENTPKEY;
    $sQ .= " AND ".DB_ITEMGROUPTABLE_TEXT.".".DB_ITEMGROUPFIELD_LANGUAGE." = :lang";
    if ($iGID != '') $sQ .= " WHERE ".DB_ITEMGROUPTABLE_BASE_PKEY." = :gid";
    $sQ .= " ORDER BY ".DB_ITEMGROUPFIELD_NUMBER;
    //debug($sQ);
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':lang', $sLang);
    if ($iGID != '') $hResult->bindValue(':gid', $iGID);
    $hResult->execute();

    $aGroups = $hResult->fetchAll();
    //debug($aGroups);

    return $aGroups;
}

function admin_showItemgroups($aGroups) { // no query
    global $C;
    //debug($aGroups);
    $aList = array(
        array('title' => 'Gruppe', 'key' => 'gno', 'width' => 80, 'linked' => false, 'style-data' => 'padding: 5px 0;'),
        array('title' => 'Gruppenname', 'key' => 'gname', 'width' => 350, 'linked' => false, 'style-data' => 'padding: 5px 0;'),
        array('title' => 'edit', 'key' => 'gid', 'width' => 30, 'linked' => true, 'ltarget' => $_SERVER["PHP_SELF"], 'lkeyname' => 'gid', 'lgetvars' => array('action' => 'editgroup'), 'style-data' => 'padding: 5px 0;'),
        array('title' => 'Bild', 'key' => 'gimgsm', 'width' => 105, 'linked' => false, 'style-data' => 'padding: 5px 0; text-align: right;'),
    );
    if (count($aGroups) > 0) {
        foreach ($aGroups as $aValue) {
            //$iChildren = admin_getItemGroupChildren($aValue["ag_id"]);
            $aData[] = array(
                'gid' => $aValue[DB_ITEMGROUPTABLE_BASE_PKEY],
                'gno' => $aValue[DB_ITEMGROUPFIELD_NUMBER],
                'gname' => $aValue[DB_ITEMGROUPFIELD_NAME],
                'gimg' => ((isset($aValue[DB_ITEMGROUPFIELD_IMG]) && $aIData = @getImageSize(PATH_DOCROOT.$aValue[DB_ITEMGROUPFIELD_IMG])) ? '<img src="'.$aValue[DB_ITEMGROUPFIELD_IMG].'" '.$aIData[3].' alt="" />' : ''),
                //	'children' => $iChildren,
            );
        }
        $sH = makeListTable($aList, $aData);
    } else {
        $sH = 'Zur Zeit sind keine Gruppen angelegt.';
    }

    return $sH;
}

function admin_getItemGroupChildren($iGID) {
    global $C, $DB;
    $sQ = "SELECT ".DB_ITEMFIELD_NUMBER." FROM ".DB_ITEMTABLE_BASE;
    $sQ .= " WHERE ".DB_ITEMFIELD_GROUP." = :gid";
    $sQ .= " AND ".DB_ITEMFIELD_INDEX." NOT LIKE '%AL%' AND ".DB_ITEMFIELD_INDEX." NOT LIKE '%!%'";
    //debug($sQ);
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':gid', $iGID);
    $hResult->execute();
    //debug($DB->error());
    $iNumRows = $hResult->rowCount();

    return $iNumRows;
}
