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
    private $DB, $sLang, $itemindexpathtree;
    // make method getItemPathByIndex()
    // if itemindexpathtree not set, load from db

    // Initialize Class
    public function __construct($C, $DB, $sLang)
    {
        $this->C = $C;
        $this->DB = $DB;
        $this->sLang = $sLang;
    }

    public function getItemPathTree()
    {
        $itemindexpathtree = [];
        $aItemoverviewpages = [];
        $sQ = "SELECT * FROM content_base WHERE cb_pagetype = 'itemoverview' OR cb_pagetype = 'itemoverviewgrpd'";
        $oQuery = $this->DB->query($sQ);
        while ($aRow = $oQuery->fetch()) {
            $aItemoverviewpages[] = [
                'path' => $aRow['cb_key'],
                'pageconfig' => json_decode($aRow["cb_pageconfig"]),
            ];
        }
        //HaaseIT\Tools::debug($aItemoverviewpages, '$aItemoverviewpages');
        foreach ($aItemoverviewpages as $aValue) {
            if (isset($aValue["pageconfig"]->itemindex)) {
                if (is_array($aValue["pageconfig"]->itemindex)) {
                    foreach ($aValue["pageconfig"]->itemindex as $sIndexValue) {
                        if (!isset($itemindexpathtree[$sIndexValue])) {
                            $itemindexpathtree[$sIndexValue] = mb_substr($aValue["path"], 0, mb_strlen($aValue["path"]) - 10).'item/';
                        }
                    }
                } else {
                    if (!isset($itemindexpathtree[$aValue["pageconfig"]->itemindex])) {
                        $itemindexpathtree[$aValue["pageconfig"]->itemindex] = mb_substr($aValue["path"], 0, mb_strlen($aValue["path"]) - 10).'item/';
                    }
                }
            }
        }
        //HaaseIT\Tools::debug($itemindexpathtree, '$itemindexpathtree');
        //HaaseIT\Tools::debug($aP["pageconfig"]->itemindex, '$aP["pageconfig"]->itemindex');

        return $itemindexpathtree;
    }

    public function queryItem($mItemIndex = '', $mItemno = '', $sOrderby = '')
    {
        $sQ = 'SELECT '.DB_ITEMFIELDS.' FROM item_base';
        $sQ .= ' LEFT OUTER JOIN item_lang ON item_base.itm_id = item_lang.itml_pid AND itml_lang = :lang';
        $sQ .= $this->queryItemWhereClause($mItemIndex, $mItemno);
        $sQ .= ' ORDER BY '.(($sOrderby == '') ? 'itm_order, itm_no' : $sOrderby).' '.$this->C["items_orderdirection_default"];

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

    public function queryItemWhereClause($mItemIndex = '', $mItemno = '')
    {
        $sQ = " WHERE ";
        if ($mItemno != '') {
            if (\is_array($mItemno)) {
                $sItemno = "'".\implode("','", \filter_var_array($mItemno, FILTER_SANITIZE_SPECIAL_CHARS))."'";
                $sQ .= 'item_base.itm_no IN ('.$sItemno.')';
            } else {
                $sQ .= 'item_base.itm_no = :itemno';
            }
        } elseif (isset($_REQUEST["searchtext"]) && \strlen($_REQUEST["searchtext"]) > 2) {
            if (isset($_REQUEST["artnoexact"])) $sQ .= 'item_base.itm_no = :searchtext';
            else {
                $sQ .= '(item_base.itm_no LIKE :searchtextwild1 OR itm_name LIKE :searchtextwild2';
                $sQ .= ' OR itml_name_override LIKE :searchtextwild3 OR itml_text1 LIKE :searchtextwild4';
                $sQ .= ' OR itml_text2 LIKE :searchtextwild5)';
            }
        } else {
            if (\is_array($mItemIndex)) {
                $sQ .= "(";
                foreach ($mItemIndex as $sAIndex) $sQ .= "itm_index LIKE '%".\filter_var($sAIndex, FILTER_SANITIZE_SPECIAL_CHARS)."%' OR ";
                $sQ = \HaaseIT\Tools::cutStringend($sQ, 4);
                $sQ .= ")";
            } else {
                $sQ .= "itm_index LIKE '%".\filter_var($mItemIndex, FILTER_SANITIZE_SPECIAL_CHARS)."%'";
            }
        }
        $sQ .= ' AND itm_index NOT LIKE \'%!%\' AND itm_index NOT LIKE \'%AL%\'';

        return $sQ;
    }

    public function sortItems($mItemIndex = '', $mItemno = '', $bEnableItemGroups = false)
    {
        if ($mItemno != '') {
            if (\is_array($mItemno)) {
                foreach ($mItemno as $sKey => $sValue) {
                    $TMP[$sKey] = \filter_var(\trim($sValue), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                }
                $mItemno = $TMP;
                unset($TMP);
            } else {
                $mItemno = \filter_var(\trim($mItemno), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            }
        }

        $hResult = $this->queryItem($mItemIndex, $mItemno);

        while ($aRow = $hResult->fetch()) {
            if (isset($aRow['itm_data'])) {
                $aRow['itm_data'] = \json_decode($aRow['itm_data'], true);
            }
            $aRow["pricedata"] = $this->calcPrice($aRow);

            if (\trim($aRow['itm_group']) == 0 || !$bEnableItemGroups) {
                $aAssembly["item"][$aRow['itm_no']] = $aRow;
            } else {
                if (isset($aAssembly["groups"]["ITEMGROUP-".$aRow['itm_group']])) {
                    $aAssembly["groups"]["ITEMGROUP-".$aRow['itm_group']][$aRow['itm_no']] = $aRow;
                } else {
                    $aAssembly["item"]["ITEMGROUP-".$aRow['itm_group']]["group"] = "ITEMGROUP-".$aRow['itm_group'];
                    $aAssembly["groups"]["ITEMGROUP-".$aRow['itm_group']]["ITEMGROUP-DATA"] = $this->getGroupdata($aRow['itm_group']);
                    $aAssembly["groups"]["ITEMGROUP-".$aRow['itm_group']][$aRow['itm_no']] = $aRow;
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

    public function getGroupdata($sGroup)
    {
        $sQ = 'SELECT '.DB_ITEMGROUPFIELDS.' FROM itemgroups_base'
            . ' LEFT OUTER JOIN itemgroups_text ON itemgroups_base.itmg_id = itemgroups_text.itmgt_pid'
            . ' AND itmgt_lang = :lang'
            . ' WHERE itmg_id = :group';

        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':lang', $this->sLang, \PDO::PARAM_STR);
        $hResult->bindValue(':group', $sGroup, \PDO::PARAM_INT);
        $hResult->execute();

        $aRow = $hResult->fetch();
        $aRow["type"] = 'itemgroupdata';
        return $aRow;
    }

    public function calcPrice($aData)
    {
        $aPrice = [];
        if ($aData['itm_vatid'] != 'reduced') {
            $aData['itm_vatid'] = 'full';
        }

        if(is_numeric($aData['itm_price']) && $aData['itm_price'] > 0) {
            $aPrice["netto_list"] = $aData['itm_price'];
            if (
                isset($aData["itm_data"]["sale"]["start"]) && isset($aData["itm_data"]["sale"]["end"])
                && isset($aData["itm_data"]["sale"]["price"])
            ) {
                $iToday = date("Ymd");
                if ($iToday >= $aData["itm_data"]["sale"]["start"] && $iToday <= $aData["itm_data"]["sale"]["end"]) {
                    $aPrice["netto_sale"] = $aData["itm_data"]["sale"]["price"];
                }
            }
            if (
                $aData['itm_rg'] != ''
                && isset($this->C["rebate_groups"][$aData['itm_rg']][\HaaseIT\HCSF\Customer\Helper::getUserData('cust_group')])
            ) {
                $aPrice["netto_rebated"] =
                    $aData['itm_price'] * (100 - $this->C["rebate_groups"][$aData['itm_rg']][\HaaseIT\HCSF\Customer\Helper::getUserData('cust_group')])
                    / 100;
            }
        } else {
            return false;
        }

        $aPrice["netto_use"] = $aPrice["netto_list"];

        if (isset($aPrice["netto_rebated"]) && $aPrice["netto_rebated"] < $aPrice["netto_use"]) {
            $aPrice["netto_use"] = $aPrice["netto_rebated"];
        }
        if (isset($aPrice["netto_sale"]) && $aPrice["netto_sale"] < $aPrice["netto_use"]) {
            $aPrice["netto_use"] = $aPrice["netto_sale"];
        }

        $aPrice["brutto_use"] = ($aPrice["netto_use"] * $this->C['vat'][$aData['itm_vatid']] / 100) + $aPrice["netto_use"];

        return $aPrice;
    }
}
