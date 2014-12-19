<?php

/*
2014-12-19
- removed functions: generateRandomString, makeLinkHRefWithAddedGetVars, calculateImagesizeToBox, resizeImage,
  dateAddLeadingZero, validateEmail, array_search_recursive, buildInsertQuery, buildPSInsertQuery, buildUpdateQuery,
  buildPSUpdateQuery, cED, cEDA, cutString, cutStringend, getCheckboxaval, makeOptionsArrayFromString, getOptionname,
  getFormfield, getCheckbox - moved to class Tools
- removed function: makeCheckboxtable - obsolete
2014-11-19
- removed function is_blank(), it was instancing the variable to test, this had some strange effects
- removed function mail_utf8(), obsolete, use phpmailer library instead
2014-10-10
- changed function debug() to use var_dump instead of print_r
- removed function usefulChars(), getWherefound(), getWherefoundWalker(), rHTC(), handleMail(), href()
2014-10-02
- added parameter $bMakeAmpersandHTMLEntity to makeLinkHRefWithAddedGetVars()
2014-08-19
- Added -f parameter to mail_utf8() sending for sender address
- Added display of seconds to showClienttime()
2014-07-27
- removed add line link in makeListtable()
2014-07-23
- added possibility to add a label to debug()
2014-07-15
- Changed code formating to comply with php-fig.org's PSR
2014-07-14
- added functionality for makeLinkHRefWithAddedGetVars() to parse the supplied url for getvars to use
- added function is_blank, courtesy of FBOES
2014-06-16
- removed function escapeHTMLSpecialchars() and popupLink()
2014-06-13
- function makeListtable() 1.4 - new css needed to work as before! Also: see function's version history
- function debug() now escapes htmlspecialchars
2014-06-12
- mail_utf8(): added possibility to send plaintext mails too
2013-12-02
- added makeOptionsArrayFromString(), loadTextcats(), T()
2013-08-19
- added resizeImage() and calculateImagesizeToBox() from PSS
2011-11-18
- added escapeHTMLSpecialChars()
2009-09-14
- cED und cEDA -> fixed single-quotes, added @ - MH
- buildUpdateQuery() argument 3 and 4 are now optional (pkey + pkeyvalue), if empty, no where-clause will be included!!!
- buildUpdateQuery() $sPValue was not input-filtered, now it is
- added usefulChars()
*/

function T($sTextkey, $bReturnFalseIfNotAvailable = false)
{
    global $T, $sLang, $C;
    $sDefaultlang = key($C["lang_available"]);
    //debug($T[$sDefaultlang]);
    if (isset($_GET["showtextkeys"])) {
        $sH = '['.$sTextkey.']';
    } else {
        if (isset($T[$sLang][$sTextkey]["tcl_text"]) && trim($T[$sLang][$sTextkey]["tcl_text"]) != '') {
            $sH = trim($T[$sLang][$sTextkey]["tcl_text"]);
        } elseif (isset($T[$sDefaultlang][$sTextkey]["tcl_text"]) && trim($T[$sDefaultlang][$sTextkey]["tcl_text"]) != '') {
            $sH = trim($T[$sDefaultlang][$sTextkey]["tcl_text"]);
        }
        if (!isset($sH) || $sH == '') {
            if ($bReturnFalseIfNotAvailable) return false;
            else $sH = 'Missing Text: '.$sTextkey;
        }
    }

    return $sH;
}

function loadTextcats()
{
    global $sLang, $C, $DB;

    $sQ = "SELECT * FROM textcat_base LEFT JOIN textcat_lang ON textcat_base.tc_id = textcat_lang.tcl_tcid && tcl_lang = :lang";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':lang', $sLang, PDO::PARAM_STR);
    $hResult->execute();
    while ($aRow = $hResult->fetch()) {
        $aTextcat[$sLang][$aRow["tc_key"]] = $aRow;
    }

    $sDefaultlang = key($C["lang_available"]);
    if ($sLang != $sDefaultlang) {
        $hResult = $DB->prepare($sQ);
        $hResult->bindValue(':lang', $sDefaultlang, PDO::PARAM_STR);
        $hResult->execute();
        while ($aRow = $hResult->fetch()) $aTextcat[$sDefaultlang][$aRow["tc_key"]] = $aRow;
    }

    if (isset($aTextcat)) {
        return $aTextcat;
    }
}

function debug($mixed, $bQuiet = false, $sLabel = '')
{
    if (!$bQuiet) global $sDebug;
    if (!isset($sDebug)) $sDebug = '';
    $sDebug .= '<pre class="debug">';
    if ($sLabel != '') {
        $sDebug .= $sLabel."\n\n";
    }
    ob_start();
    var_dump($mixed);
    $sDebug .= htmlspecialchars(ob_get_contents());
    ob_end_clean();
    $sDebug .= '</pre>';
    return $sDebug;
}

function showPagesnav($iPages, $iPage, $aGetvars = array())
{
    $sH = '';
    // links: << <
    if ($iPage == 1) {
        $sH .= '&lt;&lt;&nbsp;&nbsp;';
    } else {
        // < link (one page back)
        $aGetvars["page"] = $iPage - 1;
        $sH .= href('', $aGetvars);
        $sH .= '&lt;&lt;</a>&nbsp;&nbsp;';
    }
    // page-number links
    for ($i = 1; $i <= $iPages; $i++) {
        $aGetvars["page"] = $i;
        $sH .= href('', $aGetvars);
        if ($i == $iPage) {
            $sH .= '<strong><span style="Text-Decoration: overline underline;">'.$i.'</span></strong></a> ';
        } else {
            $sH .= $i.'</a> ';
        }
    }
    $sH .= '&nbsp;';
    // links: > >>
    if ($iPage == $iPages) {
        $sH .= '&gt;&gt;';
    } else {
        $aGetvars["page"] = $iPage + 1;
        $sH .= href('', $aGetvars);
        $sH .= '&gt;&gt;</a> ';
    }
    return $sH;
}

function showClienttime()
{
    $sH = '<script type="text/javascript">
    <!--
    var Jetzt = new Date();
    var Tag = Jetzt.getDate();
    var Monat = Jetzt.getMonth() + 1;
    var Jahr = Jetzt.getYear();
    var Stunden = Jetzt.getHours();
    var Minuten = Jetzt.getMinutes();
    var Sekunden = Jetzt.getSeconds();
    var NachVollMinuten  = ((Minuten < 10) ? ":0" : ":");
    var NachVollSekunden  = ((Sekunden < 10) ? ":0" : ":");
    if (Jahr<2000) Jahr=Jahr+1900;
    document.write(Tag + "." + Monat + "." + Jahr + "  " + Stunden + NachVollMinuten + Minuten + NachVollSekunden + Sekunden);
    //-->
    </script>';
    return $sH;
}

function makeListtable($aC, $aData)
{ // v 1.4
    /*
    Changes in 1.4 2014-06-13:
    changed: css to highlight rows
    added: config option to escape html specialchars in listing
    added: add new value now done inside function

    Changes in 1.3:
    changed: row-highlighting changed from js to css
    added: rows now markable with mouseclick

    Changes in 1.2:
    added: global variable $COUNTER_makeListtable, this counts, how many listtables have been on a page yet so each listtable tr gets an unique css id
    if multiple listtables were on each page, the mouseover effect only ever changes the color in the first listtable.

    Changes in 1.1:
    added: 'style-head' attribute for headline-colum
    added: 'style-data' attribute for data-colums
    added: possibility to attach an event to a linked colum (like "onClick")
    fixed: if more than 1 linked colum is defined, all colums used the 'lgetvars' of the last colum

    Relevant CSS Data:
    .listtable-head{font-weight:700;padding:1px}
    .listtable-data{text-align:left;padding:1px}
    .listtable tr:nth-child(even){background-color:#eaeaea;}
    .listtable tr:nth-child(odd){background-color:#fff;}
    .listtable tr:hover{background-color:#bbb;}
    .listtable-marked{background-color:#b0ffb0 !important;}
    .listtable tr.listtable-marked:hover{background-color:#10ff10 !important;}
    .listtable thead tr{background: #bfbfbf !important;}

    Expected config data (arg: $aC)
    $aListSetting = array(
    array('title' => 'Kd Nr.', 'key' => 'adk_nummer', 'width' => 150, 'linked' => false,),
    array(
    'title' => 'Vorg. Nummer',
    'key' => 'vk_nummer',
    'width' => 150,
    'linked' => false,
    'escapehtmlspecialchars' => false,
    'style-data' => 'text-align: center;',
    'style-head' => 'text-align: center;',
    ),
    array(
    'title' => 'löschen',
    'key' => 'vk_wv_nummer',
    'width' => 60,
    'linked' => true,
    'ltarget' => $_SERVER["PHP_SELF"],
    'lkeyname' => 'id',
    'lgetvars' => array(
    'action' => 'delete',
    ),
    'levents' => 'onClick="return confirm(\'Wirklich löschen?\');"',
    ),
    );
    */

    global $COUNTER_makeListtable;
    if (!isset($COUNTER_makeListtable)) {
        $COUNTER_makeListtable = 1;
    } else {
        $COUNTER_makeListtable++;
    }
    $sH = '';
    $sH .= '<script type="text/javascript">'."\n";
    $sH .= 'function toggleHighlight(sRowID) {'."\n";
    $sH .= 'var a = document.getElementById(sRowID);'."\n";
    $sH .= 'var b = a.className.search(" listtable-marked")'."\n";
    $sH .= 'var c = " listtable-marked";'."\n";
    $sH .= 'if (b == -1) {'."\n";
    $sH .= '	a.className += c;'."\n";
    $sH .= '} else {'."\n";
    //$sH .= 'alert(b);'."\n";
    $sH .= '	var reg = new RegExp(\'(\\\\\\\b)\' + c + \'(\\\\\\\b)\');'."\n";
    $sH .= '	a.className = a.className.replace(reg, "");'."\n";
    $sH .= '}'."\n";
    $sH .= '}'."\n";
    $sH .= '</script>'."\n";
    if (is_array($aC)) {
        $sH .= '<table class="listtable">';

        // Begin table head
        $sH .= '<thead>';
        $sH .= '<tr>';
        foreach ($aC as $aValue) {
            $sH .= '<th class="listtable-head" style="width: '.$aValue["width"].'px;"';
            if (isset($aValue["style-head"]) && $aValue["style-head"] != '') {
                $sH .= ' style="'.$aValue["style-head"].'"';
            }
            $sH .= '>';
            if (!$aValue["linked"]) {
                $sH .= $aValue["title"];
            } else {
                $sH .= '&nbsp;';
            }
            $sH .= '</th>';
        }
        $sH .= '</tr>';
        $sH .= '</thead>';

        // Begin table body
        $sH .= '<tbody>';
        $j = 0;
        foreach ($aData as $aValue) {
            $sH .= '<tr id="listtable_'.$COUNTER_makeListtable.'_tr_'.$j.'"';
            $sH .= ' onClick="toggleHighlight(\'listtable_'.$COUNTER_makeListtable.'_tr_'.$j.'\')" ';
            $sH .= '>';
            foreach ($aC as $aCValue) {
                $sH .= '<td class="listtable-data" valign="top"';
                if (isset($aCValue["style-data"]) && $aCValue["style-data"] != '') {
                    $sH .= ' style="'.$aCValue["style-data"].'"';
                }
                $sH .= '>';
                if (!$aCValue["linked"]) {
                    if (isset($aCValue["escapehtmlspecialchars"]) && $aCValue["escapehtmlspecialchars"] == true) {
                        $sH .= htmlspecialchars($aValue[$aCValue["key"]]);
                    } else {
                        $sH .= $aValue[$aCValue["key"]];
                    }
                } else {
                    $sH .= '<a href="'.$aCValue["ltarget"].'?'.$aCValue["lkeyname"].'='.$aValue[$aCValue["key"]];
                    if (isset($aCValue["lgetvars"])) {
                        foreach ($aCValue["lgetvars"] as $sGVKey => $sGVValue) {
                            $sH .= '&'.$sGVKey.'='.$sGVValue;
                        }
                    }
                    $sH .= '"';
                    if (isset($aCValue["levents"]) && $aCValue["levents"] != '') {
                        $sH .= ' '.$aCValue["levents"];
                    }
                    $sH .= '>';
                    $sH .= $aCValue["title"];
                    $sH .= '</a>';
                }
                $sH .= '</td>';
                //debug($aCValue);
            }
            $sH .= '</tr>';
            $j++;
        }
        $sH .= '</tbody>';
        $sH .= '</table>';
    } else {
        $sH .= '<span style="background:red;color:white;font-weight:bold;padding:2px;">function:makeListtable() -> The configuration array is empty (first argument of the function call).</span>';
    }
    return $sH;
}
