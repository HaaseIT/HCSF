<?php

/*
    Contanto - A modular CMS and Shopsystem
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
2014-12-21
- removed function: makeListtable(), moved to class Tools
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

function loadTextcats($sLang, $C, $DB)
{
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
