<?php

function admin_showItemAddForm($sErr = '') {
    global $FORM;
    $sH = '';
    if ($sErr != '') $sH .= $sErr.'<br>';
    $sH .= 'Artikelnummer:<br>';
    $FORM->sFormmethod = 'POST';
    $FORM->sFormaction = Tools::makeLinkHRefWithAddedGetVars($_SERVER["PHP_SELF"]);
    $sH .= $FORM->openForm('additem');
    $sH .= $FORM->makeHidden('additem', 'do');
    $sH .= $FORM->makeText('itemno', Tools::getFormfield('itemno', ''));
    $sH .= ' ';
    $sH .= $FORM->makeSubmit();
    $sH .= $FORM->closeForm();

    return $sH;
}

function admin_showItemlistsearchform() { // no query
    global $FORM;

    $aSearchcats = array(
        'nummer|Artikelnummer',
        'name|Artikelname',
        'index|Artikelindex',
    );
    $aOrderby = array(
        'nummer|Artikelnummer',
        'name|Artikelname',
    );

    if (isset($_REQUEST["searchcat"])) {
        $sSearchcat = $_REQUEST["searchcat"];
        $_SESSION["searchcat"] = $_REQUEST["searchcat"];
    } elseif (isset($_SESSION["searchcat"])) $sSearchcat = $_SESSION["searchcat"];
    else $sSearchcat = '';

    if (isset($_REQUEST["orderby"])) {
        $sOrderby = $_REQUEST["orderby"];
        $_SESSION["orderby"] = $_REQUEST["orderby"];
    } elseif (isset($_SESSION["orderby"])) $sOrderby = $_SESSION["orderby"];
    else $sOrderby = '';

    $FORM->sFormmethod = 'GET';
    $sH = '<table>';
    $sH .= $FORM->openForm().$FORM->makeHidden('action', 'search');
    $sH .= '<tr><td>';
    $sH .= 'Search for:<br>';
    $sH .= $FORM->makeText('searchstring', ((isset($_REQUEST["searchstring"])) ? $_REQUEST["searchstring"] : ''), 200).'&nbsp;';
    $sH .= '</td><td>';
    $sH .= 'Search in:<br>';
    $sH .= $FORM->makeSelect('searchcat', $aSearchcats, $sSearchcat, 120).'&nbsp;';
    $sH .= '</td><td>';
    $sH .= 'Order by:<br>';
    $sH .= $FORM->makeSelect('orderby', $aOrderby, $sOrderby, 120).'&nbsp;';
    $sH .= '</td><td style="vertical-align: bottom;">';
    $sH .= $FORM->makeSubmit('', 'Submit', 100);
    $sH .= '</td></tr>';
    $sH .= $FORM->closeForm();
    $sH .= '</table>';
    $sH .= '<br>';

    return $sH;
}

function admin_getItemlist() { // input filtered
    global $DB, $C, $sLang;

    $sSearchstring = Tools::cED($_REQUEST["searchstring"]);
    $sSearchstring = str_replace('*', '%', $sSearchstring);

    $sQ = "SELECT ".DB_ITEMFIELD_NUMBER.", ".DB_ITEMFIELD_NAME;
    $sQ .= " FROM ".DB_ITEMTABLE_BASE;
    $sQ .= " LEFT OUTER JOIN ".DB_ITEMTABLE_TEXT." ON ";
    $sQ .= DB_ITEMTABLE_BASE.".".DB_ITEMTABLE_BASE_PKEY." = ".DB_ITEMTABLE_TEXT.".".DB_ITEMTABLE_TEXT_PARENTPKEY;
    $sQ .= " AND ".DB_ITEMTABLE_TEXT.".".DB_ITEMFIELD_LANGUAGE." = :lang";
    $sQ .= " WHERE ";
    if ($_REQUEST["searchcat"] == 'name') {
        $sQ .= DB_ITEMFIELD_NAME." LIKE :searchstring ";
    } elseif ($_REQUEST["searchcat"] == 'nummer') {
        $sQ .= DB_ITEMFIELD_NUMBER." LIKE :searchstring ";
    } elseif ($_REQUEST["searchcat"] == 'index') {
        $sQ .= DB_ITEMFIELD_INDEX." LIKE :searchstring ";
    } else exit;

    if ($_REQUEST["orderby"] == 'name') $sQ .= "ORDER BY ".DB_ITEMFIELD_NAME;
    elseif ($_REQUEST["orderby"] == 'nummer') $sQ .= " ORDER BY ".DB_ITEMFIELD_NUMBER;
    //debug($sQ);

    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':searchstring', $sSearchstring);
    $hResult->bindValue(':lang', $sLang);
    $hResult->execute();

    //debug($DB->error());

    $aItemlist["numrows"] = $hResult->rowCount();

    if ($aItemlist["numrows"] != 0) {
        while ($aRow = $hResult->fetch()) $aItemlist["data"][] = $aRow;
        return $aItemlist;
    } else return false;
}

function admin_showItemlist($aItemlist) { // no query
    global $C;

    $aList = array(
        array('title' => 'Art. Nr.', 'key' => 'itemno', 'width' => 100, 'linked' => false,),
        array('title' => 'Name', 'key' => 'name', 'width' => 350, 'linked' => false,),
        array('title' => 'edit', 'key' => 'itemno', 'width' => 30, 'linked' => true, 'ltarget' => $_SERVER["PHP_SELF"], 'lkeyname' => 'itemno', 'lgetvars' => array('action' => 'showitem'),),
    );
    foreach ($aItemlist["data"] as $aValue) {
        $aData[] = array(
            'itemno' => $aValue[DB_ITEMFIELD_NUMBER],
            'name' => $aValue[DB_ITEMFIELD_NAME],
        );
    }
    $sH = 'Displaying '.$aItemlist["numrows"].' results:<br><br>';
    $sH .= makeListTable($aList, $aData);

    return $sH;
}

function admin_getItem($sItemno = '') { // input filtered
    global $DB, $C, $sLang;

    if (isset($_REQUEST["itemno"]) && $_REQUEST["itemno"] != '') $sItemno = $_REQUEST["itemno"];
    elseif ($sItemno == '') return false;

    $sItemno = Tools::cED($sItemno);

    $sQ = "SELECT * FROM ".DB_ITEMTABLE_BASE." WHERE ".DB_ITEMFIELD_NUMBER." = :itemno";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':itemno', $sItemno);
    $hResult->execute();
    //echo debug($sQ, true);
    //echo debug($DB->error(), true);
    $aItemdata["base"] = $hResult->fetch();

    $sQ = "SELECT * FROM ".DB_ITEMTABLE_TEXT;
    $sQ .= " WHERE ".DB_ITEMTABLE_TEXT_PARENTPKEY." = :parentpkey";
    $sQ .= " AND ".DB_ITEMFIELD_LANGUAGE." = :lang";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':parentpkey', $aItemdata["base"][DB_ITEMTABLE_BASE_PKEY]);
    $hResult->bindValue(':lang', $sLang);
    $hResult->execute();
    //echo debug($sQ, true);
    if ($hResult->rowCount() != 0) $aItemdata["text"] = $hResult->fetch();

    //debug($aItemdata);
    return $aItemdata;
}

function admin_showItem($aItemdata) { // no query
    global $FORM, $C, $sLang;

    $FORM->sFormmethod = 'POST';

    $sH = '';
    //debug($aItemdata);

    $FORM->sFormaction = Tools::makeLinkHRefWithAddedGetVars($_SERVER["PHP_SELF"], array('action' => 'showitem', 'itemno' => $aItemdata["base"][DB_ITEMFIELD_NUMBER]));

    $sH .= $FORM->openForm('itemadmin');
    $sH .= $FORM->makeHidden('id', $aItemdata["base"][DB_ITEMTABLE_BASE_PKEY]);
    $sH .= $FORM->makeHidden('doaction', 'edititem');

    $sH .= '<table><tr><td class="main">';
    $sH .= 'Artikelnr:<br>';
    $sH .= $FORM->makeText('itemno', $aItemdata["base"][DB_ITEMFIELD_NUMBER], 120, 0, true, 'formtext_readonly');
    $sH .= '</td><td>';
    $sH .= 'Artikelname:<br>';
    $sH .= $FORM->makeText('name', htmlspecialchars($aItemdata["base"][DB_ITEMFIELD_NAME]), 400, 0);
    $sH .= '</td></tr></table>';
    $sH .= '<table><tr><td class="main">';
    $sH .= 'Artikelbild:<br>';
    $sH .= $FORM->makeText('bild', $aItemdata["base"][DB_ITEMFIELD_IMG], 350, 0, false, 'formtext_globalsetting');
    $sH .= '</td><td>';
    $sH .= 'Artikelpreis:<br>';
    $sH .= $FORM->makeText('price', $aItemdata["base"][DB_ITEMFIELD_PRICE], 80, 0, false, 'formtext_globalsetting');
    $sH .= '</td><td>';
    if (!$C["vat_disable"]) {
        $sH .= 'MwSt:<br>';
        $aOptions[] = '|';
        foreach ($C["vat"] as $sKey => $sValue) $aOptions[] = $sValue . '|' . $sKey;
        $sH .= $FORM->makeSelect('vatid', $aOptions, $aItemdata["base"][DB_ITEMFIELD_VAT], 45);
        $sH .= '</td><td>';
    }
    $aRGselect[] = '';
    foreach ($C["rebate_groups"] as $sKey => $aValue) $aRGselect[] = $sKey;
    $sH .= 'RG:<br>';
    $sH .= $FORM->makeSelect('rg', $aRGselect, $aItemdata["base"][DB_ITEMFIELD_RG], 40, 1, false, '', 'formselect_globalsetting');
    $sH .= '</td></tr></table>';
    $sH .= '<table><tr><td>';
    $sH .= 'Artikelindex:<br>';
    $sH .= $FORM->makeText('index', $aItemdata["base"][DB_ITEMFIELD_INDEX], 220, 0, false, 'formtext_globalsetting');
    $sH .= '</td><td>';
    $sH .= 'Prio:<br>';
    $sH .= $FORM->makeText('prio', $aItemdata["base"][DB_ITEMFIELD_ORDER], 35, 3, false, 'formtext_globalsetting');
    $sH .= '</td><td>';
    $aGroups = admin_getItemgroups();
    $aGroupselect[] = '';
    foreach ($aGroups as $aValue) $aGroupselect[] = $aValue[DB_ITEMGROUPTABLE_BASE_PKEY].'|'.$aValue[DB_ITEMGROUPFIELD_NUMBER].' - '.$aValue[DB_ITEMGROUPFIELD_NAME];
    $sH .= 'Artikelgruppe:<br>';
    $sH .= $FORM->makeSelect('group', $aGroupselect, $aItemdata["base"][DB_ITEMFIELD_GROUP], 300, 1, false, '', 'formselect_globalsetting');
    $sH .= '</td></tr></table>';
    $sH .= 'Zusatzdaten (JSON):<br>';
    $sH .= $FORM->makeTextarea('data', $aItemdata["base"][DB_ITEMFIELD_DATA], 568, 200);

    if (isset($aItemdata["text"])) {
        $sH .= '<br>';
        $sH .= $FORM->makeHidden('textid', $aItemdata["text"][DB_ITEMTABLE_TEXT_PKEY]);
        //$sH .= '<fieldset>';
        //$sH .= '<legend>Sprachspezifische Daten</legend>';
        $sH .= 'Name override:<br>';
        $sH .= $FORM->makeText('name_override', htmlspecialchars($aItemdata["text"][DB_ITEMFIELD_NAME_OVERRIDE]), 568);
        $sH .= '<br>';
        $sH .= 'Text 1:<br>';
        $sH .= $FORM->makeTextarea('text1', $aItemdata["text"][DB_ITEMFIELD_TEXT1], 568, 190, 'wysiwyg');
        $sH .= '<br>';
        $sH .= 'Text 2:<br>';
        $sH .= $FORM->makeTextarea('text2', $aItemdata["text"][DB_ITEMFIELD_TEXT2], 568, 190, 'wysiwyg');
        //$sH .= '</fieldset>';
    } else {
        $sH .= '<br>In dieser Sprache sind noch keine Texte angelegt! <a href="'.$_SERVER["PHP_SELF"].'?itemno=';
        $sH .= $aItemdata["base"][DB_ITEMFIELD_NUMBER].'&action=insert_lang">[Anlegen]</a><br><br>';
    }
    $sH .= $FORM->makeSubmit('', 'Submit', 568);
    $sH .= $FORM->closeForm();

    return $sH;
}

function admin_updateItem() { // query built by funtion
    global $C, $DB, $sLang;

    $aData = array(
        DB_ITEMFIELD_NAME => $_REQUEST["name"],
        DB_ITEMFIELD_GROUP => $_REQUEST["group"],
        DB_ITEMFIELD_IMG => $_REQUEST["bild"],
        DB_ITEMFIELD_INDEX => $_REQUEST["index"],
        DB_ITEMFIELD_ORDER => $_REQUEST["prio"],
        DB_ITEMFIELD_PRICE => $_REQUEST["price"],
        DB_ITEMFIELD_RG => $_REQUEST["rg"],
        DB_ITEMFIELD_DATA => $_REQUEST["data"],
        DB_ITEMTABLE_BASE_PKEY => $_REQUEST["id"],
    );
    if (!$C["vat_disable"]) $aData[DB_ITEMFIELD_VAT] = $_REQUEST["vatid"];
    else $aData[DB_ITEMFIELD_VAT] = '0';
    $sQ = Tools::buildPSUpdateQuery($aData, DB_ITEMTABLE_BASE, DB_ITEMTABLE_BASE_PKEY);
    //echo $sQ."\n";
    $hResult = $DB->prepare($sQ);
    foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
    $hResult->execute();
    if (isset($_REQUEST["textid"])) {
        $aData = array(
            DB_ITEMFIELD_TEXT1 => $_REQUEST["text1"],
            DB_ITEMFIELD_TEXT2 => $_REQUEST["text2"],
            DB_ITEMFIELD_NAME_OVERRIDE => $_REQUEST["name_override"],
            DB_ITEMTABLE_TEXT_PKEY => $_REQUEST["textid"],
        );
        $sQ = Tools::buildPSUpdateQuery($aData, DB_ITEMTABLE_TEXT, DB_ITEMTABLE_TEXT_PKEY);
        //echo $sQ."\n";
        //debug($DB->error());
        $hResult = $DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
    }

    return true;
}
