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

namespace HaaseIT\HCSF\Controller\Admin\Shop;
use \HaaseIT\HCSF\HardcodedText;

class Itemadmin extends Base
{
    public function __construct($C, $DB, $sLang, $twig)
    {
        parent::__construct($C, $DB, $sLang);

        $this->P->cb_customcontenttemplate = 'shop/itemadmin';

        if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'insert_lang') {
            $aItemdata = $this->admin_getItem();

            if (isset($aItemdata["base"]) && !isset($aItemdata["text"])) {
                $aData = array(
                    DB_ITEMTABLE_TEXT_PARENTPKEY => $aItemdata["base"][DB_ITEMTABLE_BASE_PKEY],
                    DB_ITEMFIELD_LANGUAGE => $sLang,
                );
                //HaaseIT\Tools::debug($aData);

                $sQ = \HaaseIT\DBTools::buildInsertQuery($aData, DB_ITEMTABLE_TEXT);
                //HaaseIT\Tools::debug($sQ);
                $DB->exec($sQ);

                header('Location: /_admin/itemadmin.html?itemno='.$_REQUEST["itemno"].'&action=showitem');
                die();
            }
            //HaaseIT\Tools::debug($aItemdata);
        }
        //HaaseIT\Tools::debug($_GET);
        $this->P->cb_customdata["searchform"] = $this->admin_prepareItemlistsearchform();

        if (isset($_REQUEST["action"])) {
            if ($_REQUEST["action"] == 'search') {
                $this->P->cb_customdata["searchresult"] = true;
                if ($aItemlist = $this->admin_getItemlist()) {
                    if (count($aItemlist["data"]) == 1) {
                        $aItemdata = $this->admin_getItem($aItemlist["data"][0][DB_ITEMFIELD_NUMBER]);
                        $this->P->cb_customdata["item"] = $this->admin_prepareItem($aItemdata);
                    } else {
                        $this->P->cb_customdata["itemlist"] = $this->admin_prepareItemlist($aItemlist, $twig);
                    }
                }
            } elseif (isset($_REQUEST["doaction"]) && $_REQUEST["doaction"] == 'edititem') {
                $this->admin_updateItem(\HaaseIT\HCSF\Helper::getPurifier($C, 'item'));
                $this->P->cb_customdata["itemupdated"] = true;

                $aItemdata = $this->admin_getItem();
                $this->P->cb_customdata["item"] = $this->admin_prepareItem($aItemdata);
            } elseif ($_REQUEST["action"] == 'showitem') {
                $aItemdata = $this->admin_getItem();
                $this->P->cb_customdata["item"] = $this->admin_prepareItem($aItemdata);
            } elseif ($_GET["action"] == 'additem') {
                $aErr = array();
                if (isset($_POST["additem"]) && $_POST["additem"] == 'do') {
                    if (strlen($_POST["itemno"]) < 4) $aErr["itemnotooshort"] = true;
                    else {
                        $sQ = "SELECT ".DB_ITEMFIELD_NUMBER." FROM ".DB_ITEMTABLE_BASE." WHERE ".DB_ITEMFIELD_NUMBER." = '";
                        $sQ .= \trim(\filter_input(INPUT_POST, 'itemno', FILTER_SANITIZE_SPECIAL_CHARS))."'";
                        $hResult = $DB->query($sQ);
                        $iRows = $hResult->rowCount();
                        if ($iRows > 0) {
                            $aErr["itemnoalreadytaken"] = true;
                        } else {
                            $aData = array(DB_ITEMFIELD_NUMBER => trim(\filter_input(INPUT_POST, 'itemno', FILTER_SANITIZE_SPECIAL_CHARS)),);
                            $sQ = \HaaseIT\DBTools::buildInsertQuery($aData, DB_ITEMTABLE_BASE);
                            //HaaseIT\Tools::debug($sQ);
                            $hResult = $DB->exec($sQ);
                            $iInsertID = $DB->lastInsertId();
                            $sQ = "SELECT ".DB_ITEMFIELD_NUMBER." FROM ".DB_ITEMTABLE_BASE." WHERE ".DB_ITEMTABLE_BASE_PKEY." = '".$iInsertID."'";
                            $hResult = $DB->query($sQ);
                            $aRow = $hResult->fetch();
                            header('Location: /_admin/itemadmin.html?itemno='.$aRow[DB_ITEMFIELD_NUMBER].'&action=showitem');
                            die();
                        }
                    }
                }
                $this->P->cb_customdata["showaddform"] = true;
                $this->P->cb_customdata["err"] = $aErr;
            }
        }
    }

    private function admin_prepareItemlistsearchform()
    {
        $aData["searchcats"] = [
            'nummer|'.HardcodedText::get('itemadmin_search_itemno'),
            'name|'.HardcodedText::get('itemadmin_search_itemname'),
            'index|'.HardcodedText::get('itemadmin_search_itemindex'),
        ];
        $aData["orderbys"] = [
            'nummer|'.HardcodedText::get('itemadmin_search_itemno'),
            'name|'.HardcodedText::get('itemadmin_search_itemname'),
        ];

        if (isset($_GET["searchcat"])) {
            $aData["searchcat"] = $_GET["searchcat"];
            $_SESSION["itemadmin_searchcat"] = $_GET["searchcat"];
        } elseif (isset($_SESSION["itemadmin_searchcat"])) $aData["searchcat"] = $_SESSION["itemadmin_searchcat"];

        if (isset($_GET["orderby"])) {
            $aData["orderby"] = $_GET["orderby"];
            $_SESSION["itemadmin_orderby"] = $_GET["orderby"];
        } elseif (isset($_SESSION["itemadmin_orderby"])) $aData["orderby"] = $_SESSION["itemadmin_orderby"];

        return $aData;
    }

    private function admin_getItemlist()
    {
        $sSearchstring = \filter_input(INPUT_GET, 'searchstring', FILTER_SANITIZE_SPECIAL_CHARS);
        $sSearchstring = str_replace('*', '%', $sSearchstring);

        $sQ = "SELECT " . DB_ITEMFIELD_NUMBER . ", " . DB_ITEMFIELD_NAME;
        $sQ .= " FROM " . DB_ITEMTABLE_BASE;
        $sQ .= " LEFT OUTER JOIN " . DB_ITEMTABLE_TEXT . " ON ";
        $sQ .= DB_ITEMTABLE_BASE . "." . DB_ITEMTABLE_BASE_PKEY . " = " . DB_ITEMTABLE_TEXT . "." . DB_ITEMTABLE_TEXT_PARENTPKEY;
        $sQ .= " AND " . DB_ITEMTABLE_TEXT . "." . DB_ITEMFIELD_LANGUAGE . " = :lang";
        $sQ .= " WHERE ";
        if ($_REQUEST["searchcat"] == 'name') {
            $sQ .= DB_ITEMFIELD_NAME . " LIKE :searchstring ";
        } elseif ($_REQUEST["searchcat"] == 'nummer') {
            $sQ .= DB_ITEMFIELD_NUMBER . " LIKE :searchstring ";
        } elseif ($_REQUEST["searchcat"] == 'index') {
            $sQ .= DB_ITEMFIELD_INDEX . " LIKE :searchstring ";
        } else exit;

        if ($_REQUEST["orderby"] == 'name') $sQ .= "ORDER BY " . DB_ITEMFIELD_NAME;
        elseif ($_REQUEST["orderby"] == 'nummer') $sQ .= " ORDER BY " . DB_ITEMFIELD_NUMBER;
        //HaaseIT\Tools::debug($sQ);

        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':searchstring', $sSearchstring);
        $hResult->bindValue(':lang', $this->sLang);
        $hResult->execute();

        //HaaseIT\Tools::debug($DB->error());

        $aItemlist["numrows"] = $hResult->rowCount();

        if ($aItemlist["numrows"] != 0) {
            while ($aRow = $hResult->fetch()) $aItemlist["data"][] = $aRow;
            return $aItemlist;
        } else return false;
    }

    private function admin_prepareItemlist($aItemlist, $twig)
    {
        $aList = [
            ['title' => HardcodedText::get('itemadmin_list_itemno'), 'key' => 'itemno', 'width' => 100, 'linked' => false,],
            ['title' => HardcodedText::get('itemadmin_list_name'), 'key' => 'name', 'width' => 350, 'linked' => false,],
            ['title' => HardcodedText::get('itemadmin_list_edit'), 'key' => 'itemno', 'width' => 30, 'linked' => true, 'ltarget' => '/_admin/itemadmin.html', 'lkeyname' => 'itemno', 'lgetvars' => ['action' => 'showitem'],],
        ];
        foreach ($aItemlist["data"] as $aValue) {
            $aData[] = [
                'itemno' => $aValue[DB_ITEMFIELD_NUMBER],
                'name' => $aValue[DB_ITEMFIELD_NAME],
            ];
        }
        $aLData = [
            'numrows' => $aItemlist["numrows"],
            'listtable' => \HaaseIT\Tools::makeListTable($aList, $aData, $twig),
        ];

        return $aLData;
    }

    private function admin_getItem($sItemno = '')
    {
        if (isset($_REQUEST["itemno"]) && $_REQUEST["itemno"] != '') $sItemno = filter_var($_REQUEST["itemno"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        elseif ($sItemno == '') return false;

        $sItemno = \filter_var($sItemno, FILTER_SANITIZE_SPECIAL_CHARS);

        $sQ = "SELECT * FROM ".DB_ITEMTABLE_BASE." WHERE ".DB_ITEMFIELD_NUMBER." = :itemno";
        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':itemno', $sItemno);
        $hResult->execute();
        //HaaseIT\Tools::debug($sQ);
        //HaaseIT\Tools::debug($DB->error());
        $aItemdata["base"] = $hResult->fetch();

        $sQ = "SELECT * FROM " . DB_ITEMTABLE_TEXT;
        $sQ .= " WHERE " . DB_ITEMTABLE_TEXT_PARENTPKEY . " = :parentpkey";
        $sQ .= " AND " . DB_ITEMFIELD_LANGUAGE . " = :lang";
        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':parentpkey', $aItemdata["base"][DB_ITEMTABLE_BASE_PKEY]);
        $hResult->bindValue(':lang', $this->sLang);
        $hResult->execute();
        //HaaseIT\Tools::debug($sQ);
        if ($hResult->rowCount() != 0) $aItemdata["text"] = $hResult->fetch();

        //HaaseIT\Tools::debug($aItemdata);
        return $aItemdata;
    }

    private function admin_prepareItem($aItemdata)
    {
        $aData = [
            'form' => ['action' => \HaaseIT\Tools::makeLinkHRefWithAddedGetVars('/_admin/itemadmin.html', ['action' => 'showitem', 'itemno' => $aItemdata["base"][DB_ITEMFIELD_NUMBER]]),],
            'id' => $aItemdata["base"][DB_ITEMTABLE_BASE_PKEY],
            'itemno' => $aItemdata["base"][DB_ITEMFIELD_NUMBER],
            'name' => $aItemdata["base"][DB_ITEMFIELD_NAME],
            'img' => $aItemdata["base"][DB_ITEMFIELD_IMG],
            'price' => $aItemdata["base"][DB_ITEMFIELD_PRICE],
            'vatid' => $aItemdata["base"][DB_ITEMFIELD_VAT],
            'rg' => $aItemdata["base"][DB_ITEMFIELD_RG],
            'index' => $aItemdata["base"][DB_ITEMFIELD_INDEX],
            'prio' => $aItemdata["base"][DB_ITEMFIELD_ORDER],
            'group' => $aItemdata["base"][DB_ITEMFIELD_GROUP],
            'data' => $aItemdata["base"][DB_ITEMFIELD_DATA],
            'weight' => $aItemdata["base"][DB_ITEMFIELD_WEIGHT],
        ];

        if (!$this->C["vat_disable"]) {
            $aOptions[] = '|';
            foreach ($this->C["vat"] as $sKey => $sValue) $aOptions[] = $sKey.'|'.$sValue;
            $aData["vatoptions"] = $aOptions;
            unset($aOptions);
        }
        $aData["rgoptions"][] = '';
        foreach ($this->C["rebate_groups"] as $sKey => $aValue) $aData["rgoptions"][] = $sKey;

        $aGroups = $this->admin_getItemgroups('');
        $aData["groupoptions"][] = '';
        foreach ($aGroups as $aValue) $aData["groupoptions"][] = $aValue[DB_ITEMGROUPTABLE_BASE_PKEY] . '|' . $aValue[DB_ITEMGROUPFIELD_NUMBER] . ' - ' . $aValue[DB_ITEMGROUPFIELD_NAME];
        unset($aGroups);

        if (isset($aItemdata["text"])) {
            $aData["lang"] = array(
                'textid' => $aItemdata["text"][DB_ITEMTABLE_TEXT_PKEY],
                'nameoverride' => $aItemdata["text"][DB_ITEMFIELD_NAME_OVERRIDE],
                'text1' => $aItemdata["text"][DB_ITEMFIELD_TEXT1],
                'text2' => $aItemdata["text"][DB_ITEMFIELD_TEXT2],
            );
        }

        return $aData;
    }

    private function admin_updateItem($purifier)
    {
        $aData = [
            DB_ITEMFIELD_NAME => filter_var($_REQUEST["name"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            DB_ITEMFIELD_GROUP => filter_var($_REQUEST["group"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            DB_ITEMFIELD_IMG => filter_var($_REQUEST["bild"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            DB_ITEMFIELD_INDEX => filter_var($_REQUEST["index"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            DB_ITEMFIELD_ORDER => filter_var($_REQUEST["prio"], FILTER_SANITIZE_NUMBER_INT),
            DB_ITEMFIELD_PRICE => filter_var($_REQUEST["price"], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            DB_ITEMFIELD_RG => filter_var($_REQUEST["rg"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            DB_ITEMFIELD_DATA => filter_var($_REQUEST["data"], FILTER_UNSAFE_RAW),
            DB_ITEMFIELD_WEIGHT => filter_var($_REQUEST["weight"], FILTER_SANITIZE_NUMBER_INT),
            DB_ITEMTABLE_BASE_PKEY => filter_var($_REQUEST["id"], FILTER_SANITIZE_NUMBER_INT),
        ];
        if (!$this->C["vat_disable"]) $aData[DB_ITEMFIELD_VAT] = filter_var($_REQUEST["vatid"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        else $aData[DB_ITEMFIELD_VAT] = 'full';
        $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_ITEMTABLE_BASE, DB_ITEMTABLE_BASE_PKEY);
        //echo $sQ."\n";
        $hResult = $this->DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':' . $sKey, $sValue);
        $hResult->execute();
        if (isset($_REQUEST["textid"])) {
            $aData = array(
                DB_ITEMFIELD_TEXT1 => $purifier->purify($_REQUEST["text1"]),
                DB_ITEMFIELD_TEXT2 => $purifier->purify($_REQUEST["text2"]),
                DB_ITEMFIELD_NAME_OVERRIDE => filter_var($_REQUEST["name_override"], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW),
                DB_ITEMTABLE_TEXT_PKEY => filter_var($_REQUEST["textid"], FILTER_SANITIZE_NUMBER_INT),
            );
            $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_ITEMTABLE_TEXT, DB_ITEMTABLE_TEXT_PKEY);
            //echo $sQ."\n";
            //HaaseIT\Tools::debug($DB->error());
            $hResult = $this->DB->prepare($sQ);
            foreach ($aData as $sKey => $sValue) $hResult->bindValue(':' . $sKey, $sValue);
            $hResult->execute();
        }

        return true;
    }

    private function admin_getItemgroups($iGID = '') // this function should be outsourced, a duplicate is used in admin itemgroups!
    {
        $sQ = "SELECT * FROM " . DB_ITEMGROUPTABLE_BASE;
        $sQ .= " LEFT OUTER JOIN " . DB_ITEMGROUPTABLE_TEXT . " ON ";
        $sQ .= DB_ITEMGROUPTABLE_BASE . "." . DB_ITEMGROUPTABLE_BASE_PKEY . " = " . DB_ITEMGROUPTABLE_TEXT . "." . DB_ITEMGROUPTABLE_TEXT_PARENTPKEY;
        $sQ .= " AND " . DB_ITEMGROUPTABLE_TEXT . "." . DB_ITEMGROUPFIELD_LANGUAGE . " = :lang";
        if ($iGID != '') $sQ .= " WHERE " . DB_ITEMGROUPTABLE_BASE_PKEY . " = :gid";
        $sQ .= " ORDER BY " . DB_ITEMGROUPFIELD_NUMBER;
        //HaaseIT\Tools::debug($sQ);
        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':lang', $this->sLang);
        if ($iGID != '') $hResult->bindValue(':gid', $iGID);
        $hResult->execute();

        $aGroups = $hResult->fetchAll();
        //HaaseIT\Tools::debug($aGroups);

        return $aGroups;
    }

}