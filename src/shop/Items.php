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

namespace HaaseIT\HCSF\Shop;

class Items
{
    private $C;
    //private $P;
    private $DB;
    private $sLang;

    // Initialize Class
    function __construct($C, $DB, $sLang)
    {
        $this->C = $C;
        $this->DB = $DB;
        $this->sLang = $sLang;
    }

    function queryItem($mItemIndex = '', $mItemno = '', $sOrderby = '')
    {
        $sQ = "SELECT ";
        $sQ .= DB_ITEMFIELDS." FROM ".DB_ITEMTABLE_BASE;
        $sQ .= " LEFT OUTER JOIN ".DB_ITEMTABLE_TEXT." ON ";
        $sQ .= DB_ITEMTABLE_BASE.".".DB_ITEMTABLE_BASE_PKEY." = ";
        $sQ .= DB_ITEMTABLE_TEXT.".".DB_ITEMTABLE_TEXT_PARENTPKEY;
        $sQ .= " AND ".DB_ITEMFIELD_LANGUAGE." = :lang";
        $sQ .= $this->queryItemWhereClause($mItemIndex, $mItemno);
        $sQ .= " ORDER BY ".(($sOrderby == '') ? DB_ITEMFIELD_ORDER.", ".DB_ITEMFIELD_NUMBER : $sOrderby)." ".$this->C["items_orderdirection_default"];

        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':lang', $this->sLang, \PDO::PARAM_STR);
        if ($mItemno != '') {
            if (!is_array($mItemno)) {
                $hResult->bindValue(':itemno', $mItemno, \PDO::PARAM_STR);
            }
        }
        elseif (isset($_REQUEST["searchtext"]) && strlen($_REQUEST["searchtext"]) > 2) {
            $sSearchtext = filter_var(trim($_REQUEST["searchtext"]), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            if (isset($_REQUEST["artnoexact"])) {
                $hResult->bindValue(':searchtext', $sSearchtext, \PDO::PARAM_STR);
            }
            $hResult->bindValue(':searchtextwild1', '%'.$sSearchtext.'%', \PDO::PARAM_STR);
            $hResult->bindValue(':searchtextwild2', '%'.$sSearchtext.'%', \PDO::PARAM_STR);
            $hResult->bindValue(':searchtextwild3', '%'.$sSearchtext.'%', \PDO::PARAM_STR);
            $hResult->bindValue(':searchtextwild4', '%'.$sSearchtext.'%', \PDO::PARAM_STR);
            $hResult->bindValue(':searchtextwild5', '%'.$sSearchtext.'%', \PDO::PARAM_STR);
        }
        $hResult->execute();
        //HaaseIT\Tools::debug($hResult->errorinfo());

        return $hResult;
    }

    function queryItemWhereClause($mItemIndex = '', $mItemno = '')
    {
        $sQ = " WHERE ";
        if ($mItemno != '') {
            if (is_array($mItemno)) {
                $sItemno = "'".implode("','", \filter_var($mItemno, FILTER_SANITIZE_SPECIAL_CHARS))."'";
                $sQ .= DB_ITEMTABLE_BASE.".".DB_ITEMFIELD_NUMBER." IN (".$sItemno.")";
            } else {
                $sQ .= DB_ITEMTABLE_BASE.".".DB_ITEMFIELD_NUMBER." = :itemno";
            }
        } elseif (isset($_REQUEST["searchtext"]) && \strlen($_REQUEST["searchtext"]) > 2) {
            if (isset($_REQUEST["artnoexact"])) $sQ .= DB_ITEMTABLE_BASE.'.'.DB_ITEMFIELD_NUMBER." = :searchtext";
            else {
                $sQ .= "(";
                $sQ .= DB_ITEMTABLE_BASE.'.'.DB_ITEMFIELD_NUMBER." LIKE :searchtextwild1";
                $sQ .= " OR ".DB_ITEMFIELD_NAME." LIKE :searchtextwild2";
                $sQ .= " OR ".DB_ITEMFIELD_NAME_OVERRIDE." LIKE :searchtextwild3";
                $sQ .= " OR ".DB_ITEMFIELD_TEXT1." LIKE :searchtextwild4";
                $sQ .= " OR ".DB_ITEMFIELD_TEXT2." LIKE :searchtextwild5";
                $sQ .= ")";
            }
        } else {
            if (\is_array($mItemIndex)) {
                $sQ .= "(";
                foreach ($mItemIndex as $sAIndex) $sQ .= DB_ITEMFIELD_INDEX." LIKE '%".\filter_var($sAIndex, FILTER_SANITIZE_SPECIAL_CHARS)."%' OR ";
                $sQ = \HaaseIT\Tools::cutStringend($sQ, 4);
                $sQ .= ")";
            } else {
                $sQ .= DB_ITEMFIELD_INDEX." LIKE '%".\filter_var($mItemIndex, FILTER_SANITIZE_SPECIAL_CHARS)."%'";
            }
        }
        $sQ .= " AND ".DB_ITEMFIELD_INDEX;
        $sQ .= " NOT LIKE '%!%' AND ".DB_ITEMFIELD_INDEX." NOT LIKE '%AL%'";
        //HaaseIT\Tools::debug($sQ, '$sQ');

        return $sQ;
    }

    function sortItems($mItemIndex = '', $mItemno = '', $bEnableItemGroups = false)
    {
        if ($mItemno != '') {
            if (is_array($mItemno)) {
                foreach ($mItemno as $sKey => $sValue) {
                    $TMP[$sKey] = filter_var(trim($sValue), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                }
                $mItemno = $TMP;
                unset($TMP);
            } else {
                $mItemno = filter_var(trim($mItemno), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            }
        }

        $hResult = $this->queryItem($mItemIndex, $mItemno);

        while ($aRow = $hResult->fetch()) {
            if (isset($aRow[DB_ITEMFIELD_DATA])) {
                $aRow[DB_ITEMFIELD_DATA] = \json_decode($aRow[DB_ITEMFIELD_DATA], true);
            }
            $aRow["pricedata"] = $this->calcPrice($aRow);

            if (\trim($aRow[DB_ITEMFIELD_GROUP]) == 0 || !$bEnableItemGroups) {
                $aAssembly["item"][$aRow[DB_ITEMFIELD_NUMBER]] = $aRow;
            } else {
                if (isset($aAssembly["groups"]["ITEMGROUP-".$aRow[DB_ITEMFIELD_GROUP]])) {
                    $aAssembly["groups"]["ITEMGROUP-".$aRow[DB_ITEMFIELD_GROUP]][$aRow[DB_ITEMFIELD_NUMBER]] = $aRow;
                } else {
                    $aAssembly["item"]["ITEMGROUP-".$aRow[DB_ITEMFIELD_GROUP]]["group"] = "ITEMGROUP-".$aRow[DB_ITEMFIELD_GROUP];
                    $aAssembly["groups"]["ITEMGROUP-".$aRow[DB_ITEMFIELD_GROUP]]["ITEMGROUP-DATA"] = $this->getGroupdata($aRow[DB_ITEMFIELD_GROUP]);
                    $aAssembly["groups"]["ITEMGROUP-".$aRow[DB_ITEMFIELD_GROUP]][$aRow[DB_ITEMFIELD_NUMBER]] = $aRow;
                }
            }
        }

        if (isset($aAssembly)) {
            $aAssembly["totalitems"] = \count($aAssembly["item"]);
            $aAssembly["itemkeys"] = \array_keys($aAssembly["item"]);
            $aAssembly["firstitem"] = $aAssembly["itemkeys"][0];
            $aAssembly["lastitem"] = $aAssembly["itemkeys"][$aAssembly["totalitems"] - 1];

            return $aAssembly;
        }
    }

    function getGroupdata($sGroup)
    {
        $sQ = "SELECT ".DB_ITEMGROUPFIELDS;
        $sQ .= " FROM ".DB_ITEMGROUPTABLE_BASE;
        $sQ .= " LEFT OUTER JOIN ".DB_ITEMGROUPTABLE_TEXT." ON ";
        $sQ .= DB_ITEMGROUPTABLE_BASE.".".DB_ITEMGROUPTABLE_BASE_PKEY." = ";
        $sQ .= DB_ITEMGROUPTABLE_TEXT.".".DB_ITEMGROUPTABLE_TEXT_PARENTPKEY;
        $sQ .= " AND ".DB_ITEMGROUPFIELD_LANGUAGE." = :lang";
        $sQ .= " WHERE ".DB_ITEMGROUPTABLE_BASE_PKEY." = :group";
        //HaaseIT\Tools::debug($sQ);

        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':lang', $this->sLang, \PDO::PARAM_STR);
        $hResult->bindValue(':group', $sGroup, \PDO::PARAM_INT);
        $hResult->execute();
        // HaaseIT\Tools::debug($DB->error());

        $aRow = $hResult->fetch();
        $aRow["type"] = 'itemgroupdata';
        return $aRow;
    }

    function calcPrice($aData)
    {
        $aPrice = array();
        $fPrice = $aData[DB_ITEMFIELD_PRICE];
        $sRG = $aData[DB_ITEMFIELD_RG];
        $sMwstart = $aData[DB_ITEMFIELD_VAT];
        if ($sMwstart != 'reduced') {
            $sMwstart = 'full';
        }

        //if ($iMwstart == 0) return false;
        if(\trim($fPrice) != '' && \trim($fPrice) != '0') {
            if (isset($aData["itm_data"]["sale"]["start"]) && isset($aData["itm_data"]["sale"]["end"]) && isset($aData["itm_data"]["sale"]["price"])) {
                $iToday = date("Ymd");
                if ($iToday >= $aData["itm_data"]["sale"]["start"] && $iToday <= $aData["itm_data"]["sale"]["end"]) {
                    $aPrice["netto_sale"] = $aData["itm_data"]["sale"]["price"];
                    $aPrice["brutto_sale"] =  ($aData["itm_data"]["sale"]["price"] * $this->C["vat"][$sMwstart] / 100) + $aData["itm_data"]["sale"]["price"];
                }
            }
            if ($sRG != '' && isset($this->C["rebate_groups"][$sRG][getUserData('cust_group')])) {
                $aPrice["netto_rebated"] = $fPrice * (100 - $this->C["rebate_groups"][$sRG][\getUserData('cust_group')]) / 100;
                $aPrice["brutto_rebated"] = ($aPrice["netto_rebated"] * $this->C["vat"][$sMwstart] / 100) + $aPrice["netto_rebated"];;
            }
            $fBrutto = ($fPrice * $this->C["vat"][$sMwstart] / 100) + $fPrice;
            $aPrice["netto_list"] = $fPrice;
            $aPrice["brutto_list"] = $fBrutto;
        } else return false;
        if (isset($aPrice["netto_list"])) {
            $aPrice["netto_use"] = $aPrice["netto_list"];
            $aPrice["brutto_use"] = $aPrice["brutto_list"];
        }
        if (isset($aPrice["netto_rebated"]) && $aPrice["netto_rebated"] < $aPrice["netto_use"]) {
            $aPrice["netto_use"] = $aPrice["netto_rebated"];
            $aPrice["brutto_use"] = $aPrice["brutto_rebated"];
        }
        if (isset($aPrice["netto_sale"]) && $aPrice["netto_sale"] < $aPrice["netto_use"]) {
            $aPrice["netto_use"] = $aPrice["netto_sale"];
            $aPrice["brutto_use"] = $aPrice["brutto_sale"];
        }

        return $aPrice;
    }
}
