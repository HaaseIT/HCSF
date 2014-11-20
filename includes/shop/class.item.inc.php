<?php

class Item
{
    private $C;
    //private $P;
    private $DB;
    private $FORM;
    private $sLang;

    // Initialize Class
    function Item($C, $DB, $FORM, $sLang)
    {
        $this->C = $C;
        $this->DB = $DB;
        $this->FORM = $FORM;
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
        $hResult->bindValue(':lang', $this->sLang, PDO::PARAM_STR);
        if ($mItemno != '') {
            if (!is_array($mItemno)) {
                $hResult->bindValue(':itemno', $mItemno, PDO::PARAM_STR);
            }
        }
        elseif (isset($_REQUEST["searchtext"]) && strlen($_REQUEST["searchtext"]) > 2) {
            $hResult->bindValue(':searchtext', $_REQUEST["searchtext"], PDO::PARAM_STR);
            $hResult->bindValue(':searchtextwild', '%'.$_REQUEST["searchtext"].'%', PDO::PARAM_STR);
        }
        $hResult->execute();
        //debug($hResult->errorinfo());

        return $hResult;
    }

    function queryItemWhereClause($mItemIndex = '', $mItemno = '')
    {
        $sQ = " WHERE ";
        if ($mItemno != '') {
            if (is_array($mItemno)) {
                $sItemno = "'".implode("','", cED($mItemno))."'";
                $sQ .= DB_ITEMTABLE_BASE.".".DB_ITEMFIELD_NUMBER." IN (".$sItemno.")";
            } else {
                $sQ .= DB_ITEMTABLE_BASE.".".DB_ITEMFIELD_NUMBER." = :itemno";
            }
        } elseif (isset($_REQUEST["searchtext"]) && strlen($_REQUEST["searchtext"]) > 2) {
            if (isset($_REQUEST["artnoexact"])) $sQ .= DB_ITEMTABLE_BASE.'.'.DB_ITEMFIELD_NUMBER." = :searchtext";
            else {
                $sQ .= "(";
                $sQ .= DB_ITEMTABLE_BASE.'.'.DB_ITEMFIELD_NUMBER." LIKE :searchtextwild";
                $sQ .= " OR ".DB_ITEMFIELD_NAME." LIKE :searchtextwild";
                $sQ .= " OR ".DB_ITEMFIELD_NAME_OVERRIDE." LIKE :searchtextwild";
                $sQ .= " OR ".DB_ITEMFIELD_TEXT1." LIKE :searchtextwild";
                $sQ .= " OR ".DB_ITEMFIELD_TEXT2." LIKE :searchtextwild";
                $sQ .= ")";
            }
        } else {
            if (is_array($mItemIndex)) {
                $sQ .= "(";
                foreach ($mItemIndex as $sAIndex) $sQ .= DB_ITEMFIELD_INDEX." LIKE '%".cED($sAIndex)."%' OR ";
                $sQ = cutStringend($sQ, 4);
                $sQ .= ")";
            } else {
                $sQ .= DB_ITEMFIELD_INDEX." LIKE '%".cED($mItemIndex)."%'";
            }
        }
        $sQ .= " AND ".DB_ITEMFIELD_INDEX;
        $sQ .= " NOT LIKE '%!%' AND ".DB_ITEMFIELD_INDEX." NOT LIKE '%AL%'";
        //debug($sQ, false, '$sQ');

        return $sQ;
    }

    function sortItems($mItemIndex = '', $mItemno = '')
    {
        $hResult = $this->queryItem($mItemIndex, $mItemno);

        while ($aRow = $hResult->fetch()) {
            if (isset($aRow[DB_ITEMFIELD_DATA])) {
                $aRow[DB_ITEMFIELD_DATA] = json_decode($aRow[DB_ITEMFIELD_DATA], true);
            }
            $aRow["pricedata"] = $this->calcPrice($aRow);
            $aAssembly["item"][$aRow[DB_ITEMFIELD_NUMBER]] = $aRow;
            if (trim($aRow[DB_ITEMFIELD_GROUP]) != 0) {
                $aAssembly["groups"][$aRow[DB_ITEMFIELD_GROUP]][] = $aRow[DB_ITEMFIELD_NUMBER];
            }
        }

        if (isset($aAssembly)) {
            $aAssembly["totalitems"] = count($aAssembly["item"]);
            $aAssembly["itemkeys"] = array_keys($aAssembly["item"]);
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
        //debug($sQ);

        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':lang', $this->sLang, PDO::PARAM_STR);
        $hResult->bindValue(':group', $sGroup, PDO::PARAM_INT);
        $hResult->execute();
        // echo debug($DB->error(), true);

        return $hResult->fetch();
    }

    function calcPrice($aData)
    {
        $aPrice = array();
        $fPrice = $aData[DB_ITEMFIELD_PRICE];
        $iMwstart = $aData[DB_ITEMFIELD_VAT];
        $sRG = $aData[DB_ITEMFIELD_RG];

        //if ($iMwstart == 0) return false;
        if(trim($fPrice) != '' && trim($fPrice) != '0') {
            if (isset($aData["itm_data"]["sale"]["start"]) && isset($aData["itm_data"]["sale"]["end"]) && isset($aData["itm_data"]["sale"]["price"])) {
                $iToday = date("Ymd");
                if ($iToday >= $aData["itm_data"]["sale"]["start"] && $iToday <= $aData["itm_data"]["sale"]["end"]) {
                    $aPrice["netto_sale"] = $aData["itm_data"]["sale"]["price"];
                    $aPrice["brutto_sale"] =  round($aData["itm_data"]["sale"]["price"] * $this->C["vat"][$iMwstart] / 100, 2) + $aData["itm_data"]["sale"]["price"];
                }
            }
            if ($sRG != '' && isset($this->C["rebate_groups"][$sRG][getUserData('cust_group')])) {
                $aPrice["netto_rebated"] = round($fPrice * (100 - $this->C["rebate_groups"][$sRG][getUserData('cust_group')]) / 100, 2);
                $aPrice["brutto_rebated"] = round($aPrice["netto_rebated"] * $this->C["vat"][$iMwstart] / 100, 2) + $aPrice["netto_rebated"];;
            }
            $fBrutto = round($fPrice * $this->C["vat"][$iMwstart] / 100, 2) + $fPrice;
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
