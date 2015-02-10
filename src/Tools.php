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

namespace HaaseIT;

class Tools
{

    public static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[\rand(0, \strlen($characters) - 1)];
        }
        return $randomString;
    }

    public static function makeLinkHRefWithAddedGetVars($sHRef, $aGetvarstoadd = array(), $bUseGetVarsFromSuppliedHRef = false, $bMakeAmpersandHTMLEntity = true)
    {
        if ($bUseGetVarsFromSuppliedHRef) {
            $aHRef = \parse_url($sHRef);
            if (isset($aHRef["query"])) {
                $aHRef["query"] = \str_replace('&amp;', '&', $aHRef["query"]);
                $aQuery = \explode('&', $aHRef["query"]);
                foreach ($aQuery as $sValue) {
                    $aGetvarsraw = \explode('=', $sValue);
                    $aGetvars[$aGetvarsraw[0]] = $aGetvarsraw[1];
                }
            }
            $sH = '';
            if (isset($aHRef["scheme"])) {
                $sH .= $aHRef["scheme"] . '://';
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
            if (isset($_GET) && \count($_GET)) {
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
                $sH .= $sKey . '=' . $sValue;
            }
        }
        if (isset($aGetvars) && \count($aGetvars)) {
            foreach ($aGetvars as $sKey => $sValue) {
                if (\array_key_exists($sKey, $aGetvarstoadd)) {
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
                $sH .= $sKey . '=' . $sValue;
            }
        }

        return $sH;
    }

    public static function calculateImagesizeToBox($sImage, $iBoxWidth, $iBoxHeight)
    {
        $aImagedata = \GetImageSize($sImage);

        if ($aImagedata[0] > $iBoxWidth && $aImagedata[1] > $iBoxHeight) {
            $iWidth = $iBoxWidth;
            $iHeight = $aImagedata[1] / $aImagedata[0] * $iBoxWidth;

            if ($iHeight > $iBoxHeight) {
                $iHeight = $iBoxHeight;
                $iWidth = $aImagedata[0] / $aImagedata[1] * $iBoxHeight;
            }
        } elseif ($aImagedata[0] > $iBoxWidth) {
            $iWidth = $iBoxWidth;
            $iHeight = $aImagedata[1] / $aImagedata[0] * $iBoxWidth;
        } elseif ($aImagedata[1] > $iBoxHeight) {
            $iHeight = $iBoxHeight;
            $iWidth = $aImagedata[0] / $aImagedata[1] * $iBoxHeight;
        } elseif ($aImagedata[0] <= $iBoxWidth && $aImagedata[1] <= $iBoxHeight) {
            $iWidth = $aImagedata[0];
            $iHeight = $aImagedata[1];
        }

        $aData = array(
            'width' => $aImagedata[0],
            'height' => $aImagedata[1],
            'newwidth' => \round($iWidth),
            'newheight' => \round($iHeight),
        );

        if ($aData["width"] != $aData["newwidth"]) {
            $aData["resize"] = true;
        } else {
            $aData["resize"] = false;
        }

        return $aData;
    }

    public static function resizeImage($sImage, $sNewimage, $iNewwidth, $iNewheight, $sJPGquality = 75)
    {
        $aImagedata = \GetImageSize($sImage);

        if ($aImagedata[2] == 1) { // gif
            $img_old = \imagecreatefromgif($sImage);
            $img_new = \imagecreate($iNewwidth, $iNewheight);
            \imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $iNewwidth, $iNewheight, $aImagedata[0], $aImagedata[1]);
            \imagedestroy($img_old);
            \imagegif($img_new, $sNewimage);
            \imagedestroy($img_new);
        } elseif ($aImagedata[2] == 2) { // jpg
            $img_old = \imagecreatefromjpeg($sImage);
            $img_new = \imagecreatetruecolor($iNewwidth, $iNewheight);
            \imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $iNewwidth, $iNewheight, $aImagedata[0], $aImagedata[1]);
            \imagedestroy($img_old);
            \imagejpeg($img_new, $sNewimage, $sJPGquality);
            \imagedestroy($img_new);
        } elseif ($aImagedata[2] == 3) { // png
            $img_old = \imagecreatefrompng($sImage);
            $img_new = \imagecreatetruecolor($iNewwidth, $iNewheight);
            \imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $iNewwidth, $iNewheight, $aImagedata[0], $aImagedata[1]);
            \imagedestroy($img_old);
            \imagepng($img_new, $sNewimage);
            \imagedestroy($img_new);
        }

        return \file_exists($sNewimage);
    }

    public static function dateAddLeadingZero($sDate)
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
                break;
            case '9':
                return '09';
                break;
        }
        return $sDate;
    }

    public static function validateEmail($sEmail)
    {
        if (\preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $sEmail)) return true;
        else return false;
    }

    public static function array_search_recursive($needle, $haystack, $nodes = array())
    {
        foreach ($haystack as $key1 => $value1) {
            if (\is_array($value1)) {
                $nodes = self::array_search_recursive($needle, $value1, $nodes);
            } elseif ($key1 == $needle || $value1 == $needle) {
                $nodes[] = array($key1 => $value1);
            }
        }
        return $nodes;
    }

    public static function buildInsertQuery($aData, $sTable, $bKeepAT = false)
    {
        $sFields = '';
        $sValues = '';
        foreach ($aData as $sKey => $sValue) {
            $sFields .= $sKey . ", ";
            $sValues .= "'" . self::cED($sValue, $bKeepAT) . "', ";
        }
        $sQ = "INSERT INTO " . $sTable . " (" . self::cutStringend($sFields, 2) . ") ";
        $sQ .= "VALUES (" . self::cutStringend($sValues, 2) . ")";
        return $sQ;
    }

    public static function buildPSInsertQuery($aData, $sTable)
    {
        $sFields = '';
        $sValues = '';
        foreach ($aData as $sKey => $sValue) {
            $sFields .= $sKey . ', ';
            $sValues .= ":" . $sKey . ", ";
        }
        $sQ = "INSERT INTO " . $sTable . " (" . self::cutStringend($sFields, 2) . ") VALUES (" . self::cutStringend($sValues, 2) . ")";
        return $sQ;
    }

    public static function buildUpdateQuery($aData, $sTable, $sPKey = '', $sPValue = '', $bKeepAT = false)
    {
        $sQ = "UPDATE " . $sTable . " SET ";
        foreach ($aData as $sKey => $sValue) {
            $sQ .= $sKey . " = '" . self::cED($sValue, $bKeepAT) . "', ";
        }
        $sQ = self::cutStringend($sQ, 2);
        if ($sPKey == '') {
            $sQ .= ' ';
        } else {
            $sQ .= " WHERE " . $sPKey . " = '" . self::cED($sPValue, $bKeepAT) . "'";
        }
        return $sQ;
    }

    public static function buildPSUpdateQuery($aData, $sTable, $sPKey = '')
    {
        $sQ = "UPDATE " . $sTable . " SET ";
        foreach ($aData as $sKey => $sValue) {
            if ($sPKey != '' && $sKey == $sPKey) {
                continue;
            }
            $sQ .= $sKey . " = :" . $sKey . ", ";
        }
        $sQ = self::cutStringend($sQ, 2);
        if ($sPKey == '') {
            $sQ .= ' ';
        } else {
            $sQ .= " WHERE " . $sPKey . " = :" . $sPKey;
        }
        return $sQ;
    }

    public static function cED($sString, $bKeepAT = false)
    { // Cleanup External Data
        $sString = \str_replace("'", "&#39;", $sString);
        //$sString = str_replace('"', "&#34;", $sString);
        if (!$bKeepAT) {
            $sString = \str_replace("@", "&#064;", $sString);
        }
        return $sString;
    }

    public static function cEDA($aInput, $bKeepAT = false)
    { // Cleanup External Data Array (one-dimensional)
        $aOutput = array();
        foreach ($aInput as $sKey => $sValue) {
            $aOutput[$sKey] = \str_replace("'", "&#39;", $sValue);
            if (!$bKeepAT) {
                $aOutput[$sKey] = \str_replace("@", "&#064;", $sValue);
            }
        }
        return $aOutput;
    }

    public static function cutString($string, $length = "35")
    {
        if (\mb_strlen($string) > $length + 3) {
            $string = \mb_substr($string, 0, $length);
            $string = \trim($string) . "...";
        }
        return $string;
    }

    public static function cutStringend($sString, $iLength)
    {
        return \mb_substr($sString, 0, \mb_strlen($sString) - $iLength);
    }

    public static function getCheckbox($sKey, $sBoxvalue)
    {
        if (isset($_REQUEST[$sKey]) && $_REQUEST[$sKey] == $sBoxvalue) {
            return true;
        } else {
            return false;
        }
    }

    // Verify: ist das Beispiel im folgenden Kommentar noch korrekt? Da jetzt auf !== false geprüft wird
    // Beispiel: $FORM->makeCheckbox('fil_status[A]', 'A', getCheckboxaval('fil_status', 'A'))
    // das array muss benannte schlüssel haben da sonst der erste (0) wie false behandelt wird!
    public static function getCheckboxaval($sKey, $sBoxvalue)
    {
        if (isset($_REQUEST[$sKey]) && \array_search($sBoxvalue, $_REQUEST[$sKey]) !== false) {
            return true;
        } else {
            return false;
        }
    }

    // Expects list of options, one option per line
    public static function makeOptionsArrayFromString($sString)
    {
        $sString = \str_replace("\r", "", $sString);
        $aOptions = \explode("\n", $sString);
        return $aOptions;
    }

    public static function getOptionname($aOptions, $sSelected)
    {
        foreach ($aOptions as $sValue) {
            $aTMP = \explode('|', $sValue);
            if ($aTMP[0] == $sSelected) {
                return $aTMP[1];
            }
        }
    }

    public static function getFormfield($sKey, $sDefault = '', $bEmptyisvalid = false)
    {
        if (isset($_REQUEST[$sKey])) {
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

    protected static $COUNTER_makeListtable;
    public static function makeListtable($aC, $aData, $twig)
    {
        // v 1.5
        /*
        Changes in 1.5 2014-12-21:
        moved function to class Tools
        changed function to use twig template

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

        if (is_int(self::$COUNTER_makeListtable)) {
            self::$COUNTER_makeListtable++;
        } else {
            self::$COUNTER_makeListtable = 1;
        }

        $aLData["C"] = $aC;

        if (is_array($aC)) {
            $aLData["rows"] = $aData;
            $aLData["counter_listtable"] = self::$COUNTER_makeListtable;
        }

        return $twig->render('listtable.twig', $aLData);
    }
}
