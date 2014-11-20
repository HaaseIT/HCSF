<?php

/*
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

function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

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

function makeLinkHRefWithAddedGetVars($sHRef, $aGetvarstoadd = array(), $bUseGetVarsFromSuppliedHRef = false, $bMakeAmpersandHTMLEntity = true)
{
    if ($bUseGetVarsFromSuppliedHRef) {
        $aHRef = parse_url($sHRef);
        if(isset($aHRef["query"])) {
            $aHRef["query"] = str_replace('&amp;', '&', $aHRef["query"]);
            $aQuery = explode('&', $aHRef["query"]);
            foreach ($aQuery as $sValue) {
                $aGetvarsraw = explode('=', $sValue);
                $aGetvars[$aGetvarsraw[0]] = $aGetvarsraw[1];
            }
        }
        $sH = '';
        if (isset($aHRef["scheme"])) {
            $sH .= $aHRef["scheme"].'://';
        }
        if (isset($aHRef["host"])) {
            $sH .= $aHRef["host"];
        }
        if (isset($aHRef["user"])) {
            $sH .= $aHRef["user"];
        }
        if (isset($aHRef["path"])) {
            $sH .= $aHRef["path"];
        }
    } else {
        $sH = $sHRef;
        if (isset($_GET) && count($_GET)) {
            $aGetvars = $_GET;
        }
    }
    $bFirstGetVar = true;

    if (count($aGetvarstoadd)) {
        foreach ($aGetvarstoadd as $sKey => $sValue) {
            if ($bFirstGetVar) {
                $sH .= '?';
                $bFirstGetVar = false;
            } else {
                if ($bMakeAmpersandHTMLEntity) {
                    $sH .= '&amp;';
                } else {
                    $sH .= '&';
                }
            }
            $sH .= $sKey.'='.$sValue;
        }
    }
    if (isset($aGetvars) && count($aGetvars)) {
        foreach ($aGetvars as $sKey => $sValue) {
            if (array_key_exists($sKey, $aGetvarstoadd)) {
                continue;
            }
            if ($bFirstGetVar) {
                $sH .= '?';
                $bFirstGetVar = false;
            } else {
                if ($bMakeAmpersandHTMLEntity) {
                    $sH .= '&amp;';
                } else {
                    $sH .= '&';
                }
            }
            $sH .= $sKey.'='.$sValue;
        }
    }

    return $sH;
}

function calculateImagesizeToBox($sImage, $iBoxWidth, $iBoxHeight)
{
    $aImagedata = GetImageSize($sImage);

    if($aImagedata[0] > $iBoxWidth && $aImagedata[1] > $iBoxHeight) {
        $iWidth = $iBoxWidth;
        $iHeight = $aImagedata[1] / $aImagedata[0] * $iBoxWidth;

        if($iHeight > $iBoxHeight) {
            $iHeight = $iBoxHeight;
            $iWidth = $aImagedata[0] / $aImagedata[1] * $iBoxHeight;
        }
    } elseif($aImagedata[0] > $iBoxWidth) {
        $iWidth = $iBoxWidth;
        $iHeight = $aImagedata[1] / $aImagedata[0] * $iBoxWidth;
    } elseif($aImagedata[1] > $iBoxHeight) {
        $iHeight = $iBoxHeight;
        $iWidth = $aImagedata[0] / $aImagedata[1] * $iBoxHeight;
    } elseif($aImagedata[0] <= $iBoxWidth && $aImagedata[1] <= $iBoxHeight) {
        $iWidth = $aImagedata[0];
        $iHeight = $aImagedata[1];
    }

    $aData = array(
        'width' => $aImagedata[0],
        'height' => $aImagedata[1],
        'newwidth' => round($iWidth),
        'newheight' => round($iHeight),
    );

    if($aData["width"] != $aData["newwidth"]) {
        $aData["resize"] = true;
    } else {
        $aData["resize"] = false;
    }

    return $aData;
}

function resizeImage($sImage, $sNewimage, $iNewwidth, $iNewheight, $sJPGquality = 75)
{
    $aImagedata = GetImageSize($sImage);

    if ($aImagedata[2] == 1) { // gif
        $img_old = imagecreatefromgif($sImage);
        $img_new = imagecreate($iNewwidth, $iNewheight);
        imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $iNewwidth, $iNewheight, $aImagedata[0], $aImagedata[1]);
        imagedestroy($img_old);
        imagegif($img_new, $sNewimage);
        imagedestroy($img_new);
    } elseif ($aImagedata[2] == 2) { // jpg
        $img_old = imagecreatefromjpeg($sImage);
        $img_new = imagecreatetruecolor($iNewwidth, $iNewheight);
        imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $iNewwidth, $iNewheight, $aImagedata[0], $aImagedata[1]);
        imagedestroy($img_old);
        imagejpeg($img_new, $sNewimage, $sJPGquality);
        imagedestroy($img_new);
    } elseif ($aImagedata[2] == 3) { // png
        $img_old = imagecreatefrompng($sImage);
        $img_new = imagecreatetruecolor($iNewwidth, $iNewheight);
        imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $iNewwidth, $iNewheight, $aImagedata[0], $aImagedata[1]);
        imagedestroy($img_old);
        imagepng($img_new, $sNewimage);
        imagedestroy($img_new);
    }

    return file_exists($sNewimage);
}

function dateAddLeadingZero($sDate)
{
    switch ($sDate) {
        case '0':
            return '01';
            break;
        case '1':
            return '01';
            break;
        case '2':
            return '02';
            break;
        case '3':
            return '03';
            break;
        case '4':
            return '04';
            break;
        case '5':
            return '05';
            break;
        case '6':
            return '06';
            break;
        case '7':
            return '07';
            break;
        case '8':
            return '08';
            breal;
        case '9':
            return '09';
            break;
    }
    return $sDate;
}

function validateEmail($sEmail)
{
    if(preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $sEmail)) return true;
    else return false;
}

function array_search_recursive($needle, $haystack, $nodes=array())
{
    foreach ($haystack as $key1=>$value1) {
        if (is_array($value1)) {
            $nodes = array_search_recursive($needle, $value1, $nodes);
        } elseif ($key1 == $needle || $value1 == $needle) {
            $nodes[] = array($key1=>$value1);
        }
    }
    return $nodes;
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

function buildInsertQuery($aData, $sTable, $bKeepAT = false)
{
    $sFields = '';
    $sValues = '';
    foreach ($aData as $sKey => $sValue) {
        $sFields .= $sKey.', ';
        $sValues .= "'".cED($sValue, $bKeepAT)."', ";
    }
    $sQ = "INSERT INTO ".$sTable." (";
    $sQ .= cutStringend($sFields, 2);
    $sQ .= ") VALUES (";
    $sQ .= cutStringend($sValues, 2);
    $sQ .= ")";
    return $sQ;
}

function buildPSInsertQuery($aData, $sTable)
{
    $sFields = '';
    $sValues = '';
    foreach ($aData as $sKey => $sValue) {
        $sFields .= $sKey.', ';
        $sValues .= ":".$sKey.", ";
    }
    $sQ = "INSERT INTO ".$sTable." (".cutStringend($sFields, 2).") VALUES (".cutStringend($sValues, 2).")";
    return $sQ;
}

function buildUpdateQuery($aData, $sTable, $sPKey = '', $sPValue = '', $bKeepAT = false)
{
    $sQ = "UPDATE ".$sTable." SET ";
    foreach ($aData as $sKey => $sValue) {
        $sQ .= $sKey." = '".cED($sValue, $bKeepAT)."', ";
    }
    $sQ = cutStringend($sQ, 2);
    if ($sPKey == '') {
        $sQ .= ' ';
    } else {
        $sQ .= " WHERE ".$sPKey." = '".cED($sPValue, $bKeepAT)."'";
    }
    return $sQ;
}

function buildPSUpdateQuery($aData, $sTable, $sPKey = '')
{
    $sQ = "UPDATE ".$sTable." SET ";
    foreach ($aData as $sKey => $sValue) {
        if ($sPKey != '' && $sKey == $sPKey) {
            continue;
        }
        $sQ .= $sKey." = :".$sKey.", ";
    }
    $sQ = cutStringend($sQ, 2);
    if ($sPKey == '') {
        $sQ .= ' ';
    } else {
        $sQ .= " WHERE ".$sPKey." = :".$sPKey;
    }
    return $sQ;
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

function cED($sString, $bKeepAT = false)
{ // Cleanup External Data
    $sString = str_replace("'", "&#39;", $sString);
    //$sString = str_replace('"', "&#34;", $sString);
    if (!$bKeepAT) {
        $sString = str_replace("@", "&#064;", $sString);
    }
    return $sString;
}

function cEDA($aInput)
{ // Cleanup External Data Array (one dimensional)
    $aOutput = array();
    foreach ($aInput as $sKey => $sValue) {
        $aOutput[$sKey] = str_replace("'", "&#39;", $sValue);
        $aOutput[$sKey] = str_replace("@", "&#064;", $sValue);
    }
    return $aOutput;
}

function cutString($string, $length="35")
{
    if(mb_strlen($string) > $length + 3) {
        $string = mb_substr($string, 0, $length);
        $string = trim($string)."...";
    }
    return $string;
}

function cutStringend($sString, $iLength)
{
    $sString = mb_substr($sString, 0, mb_strlen($sString) - $iLength);
    return $sString;
}

function makeCheckboxtable($sString1, $sString2, $sStyle = 'main')
{
    $sH = '<table valign="top">';
    $sH .= '<tr>';
    $sH .= '<td class="'.$sStyle.'">'.$sString1.'</td>';
    $sH .= '<td class="'.$sStyle.'">&nbsp;</td>';
    $sH .= '<td class="'.$sStyle.'">'.$sString2.'</td>';
    $sH .= '</tr>';
    $sH .= '</table>';
    return $sH;
}

function getCheckbox($sKey, $sBoxvalue)
{
    if(isset($_REQUEST[$sKey]) && $_REQUEST[$sKey] == $sBoxvalue) {
        return true;
    } else {
        return false;
    }
}

// Beispiel: $FORM->makeCheckbox('fil_status[A]', 'A', getCheckboxaval('fil_status', 'A'))
// das array muss benannte schlüssel haben da sonst der erste (0) wie false behandelt wird!
function getCheckboxaval($sKey, $sBoxvalue)
{
    if(isset($_REQUEST[$sKey]) && array_search($sBoxvalue, $_REQUEST[$sKey])) {
        return true;
    } else {
        return false;
    }
}

// Expects list of options, one option per line
function makeOptionsArrayFromString($sString)
{
    $sString = str_replace("\r", "", $sString);
    $aOptions = explode("\n", $sString);
    return $aOptions;
}

function getOptionname($aOptions, $sSelected)
{
    foreach ($aOptions as $sValue) {
        $aTMP = explode('|', $sValue);
        if ($aTMP[0] == $sSelected) {
            return $aTMP[1];
        }
    }
}

function getFormfield($sKey, $sDefault = '', $bEmptyisvalid = false)
{
    if(isset($_REQUEST[$sKey])) {
        if ($bEmptyisvalid && $_REQUEST[$sKey] == '') {
            return '';
        } elseif ($_REQUEST[$sKey] != '') {
            return $_REQUEST[$sKey];
        } else {
            return $sDefault;
        }
    } else {
        return $sDefault;
    }
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
