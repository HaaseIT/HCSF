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
                $aData = [
                    'itml_pid' => $aItemdata["base"]['itm_id'],
                    'itml_lang' => $sLang,
                ];

                $sQ = \HaaseIT\DBTools::buildInsertQuery($aData, 'item_lang');
                $DB->exec($sQ);

                header('Location: /_admin/itemadmin.html?itemno='.$_REQUEST["itemno"].'&action=showitem');
                die();
            }
        }
        $this->P->cb_customdata["searchform"] = $this->admin_prepareItemlistsearchform();

        if (isset($_REQUEST["action"])) {
            if ($_REQUEST["action"] == 'search') {
                $this->P->cb_customdata["searchresult"] = true;
                if ($aItemlist = $this->admin_getItemlist()) {
                    if (count($aItemlist["data"]) == 1) {
                        $aItemdata = $this->admin_getItem($aItemlist["data"][0]['itm_no']);
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
                $aErr = [];
                if (isset($_POST["additem"]) && $_POST["additem"] == 'do') {
                    if (strlen($_POST["itemno"]) < 4) $aErr["itemnotooshort"] = true;
                    else {
                        $sQ = 'SELECT itm_no FROM item_base WHERE itm_no = \'';
                        $sQ .= \trim(\filter_input(INPUT_POST, 'itemno', FILTER_SANITIZE_SPECIAL_CHARS))."'";
                        $hResult = $DB->query($sQ);
                        $iRows = $hResult->rowCount();
                        if ($iRows > 0) {
                            $aErr["itemnoalreadytaken"] = true;
                        } else {
                            $aData = ['itm_no' => trim(\filter_input(INPUT_POST, 'itemno', FILTER_SANITIZE_SPECIAL_CHARS)),];
                            $sQ = \HaaseIT\DBTools::buildInsertQuery($aData, 'item_base');
                            $DB->exec($sQ);
                            $iInsertID = $DB->lastInsertId();
                            $sQ = 'SELECT itm_no FROM item_base WHERE itm_id = '.$iInsertID;
                            $hResult = $DB->query($sQ);
                            $aRow = $hResult->fetch();
                            header('Location: /_admin/itemadmin.html?itemno='.$aRow['itm_no'].'&action=showitem');
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

        $sQ = 'SELECT itm_no, itm_name FROM item_base'
            . ' LEFT OUTER JOIN item_lang ON item_base.itm_id = item_lang.itml_pid AND item_lang.itml_lang = :lang'
            . ' WHERE ';
        if ($_REQUEST["searchcat"] == 'name') {
            $sQ .= 'itm_name LIKE :searchstring ';
        } elseif ($_REQUEST["searchcat"] == 'nummer') {
            $sQ .= 'itm_no LIKE :searchstring ';
        } elseif ($_REQUEST["searchcat"] == 'index') {
            $sQ .= 'itm_index LIKE :searchstring ';
        } else exit;

        if ($_REQUEST["orderby"] == 'name') $sQ .= 'ORDER BY itm_name';
        elseif ($_REQUEST["orderby"] == 'nummer') $sQ .= ' ORDER BY itm_no';

        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':searchstring', $sSearchstring);
        $hResult->bindValue(':lang', $this->sLang);
        $hResult->execute();

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
                'itemno' => $aValue['itm_no'],
                'name' => $aValue['itm_name'],
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

        $sItemno = filter_var($sItemno, FILTER_SANITIZE_SPECIAL_CHARS);

        $sQ = 'SELECT * FROM item_base WHERE itm_no = :itemno';
        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':itemno', $sItemno);
        $hResult->execute();

        $aItemdata["base"] = $hResult->fetch();

        $sQ = 'SELECT * FROM item_lang WHERE itml_pid = :parentpkey AND itml_lang = :lang';
        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':parentpkey', $aItemdata["base"]['itm_id']);
        $hResult->bindValue(':lang', $this->sLang);
        $hResult->execute();
        if ($hResult->rowCount() != 0) $aItemdata["text"] = $hResult->fetch();

        return $aItemdata;
    }

    private function admin_prepareItem($aItemdata)
    {
        $aData = [
            'form' => ['action' => \HaaseIT\Tools::makeLinkHRefWithAddedGetVars('/_admin/itemadmin.html', ['action' => 'showitem', 'itemno' => $aItemdata["base"]['itm_no']]),],
            'id' => $aItemdata["base"]['itm_id'],
            'itemno' => $aItemdata["base"]['itm_no'],
            'name' => $aItemdata["base"]['itm_name'],
            'img' => $aItemdata["base"]['itm_img'],
            'price' => $aItemdata["base"]['itm_price'],
            'vatid' => $aItemdata["base"]['itm_vatid'],
            'rg' => $aItemdata["base"]['itm_rg'],
            'index' => $aItemdata["base"]['itm_index'],
            'prio' => $aItemdata["base"]['itm_order'],
            'group' => $aItemdata["base"]['itm_group'],
            'data' => $aItemdata["base"]['itm_data'],
            'weight' => $aItemdata["base"]['itm_weight'],
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
        foreach ($aGroups as $aValue) $aData["groupoptions"][] = $aValue['itmg_id'] . '|' . $aValue['itmg_no'] . ' - ' . $aValue['itmg_name'];
        unset($aGroups);

        if (isset($aItemdata["text"])) {
            $aData["lang"] = [
                'textid' => $aItemdata["text"]['itml_id'],
                'nameoverride' => $aItemdata["text"]['itml_name_override'],
                'text1' => $aItemdata["text"]['itml_text1'],
                'text2' => $aItemdata["text"]['itml_text2'],
            ];
        }

        return $aData;
    }

    private function admin_updateItem($purifier)
    {
        $aData = [
            'itm_name' => filter_var($_REQUEST["name"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'itm_group' => filter_var($_REQUEST["group"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'itm_img' => filter_var($_REQUEST["bild"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'itm_index' => filter_var($_REQUEST["index"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'itm_order' => filter_var($_REQUEST["prio"], FILTER_SANITIZE_NUMBER_INT),
            'itm_price' => filter_var($_REQUEST["price"], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'itm_rg' => filter_var($_REQUEST["rg"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'itm_data' => filter_var($_REQUEST["data"], FILTER_UNSAFE_RAW),
            'itm_weight' => filter_var($_REQUEST["weight"], FILTER_SANITIZE_NUMBER_INT),
            'itm_id' => filter_var($_REQUEST["id"], FILTER_SANITIZE_NUMBER_INT),
        ];
        if (!$this->C["vat_disable"]) $aData['itm_vatid'] = filter_var($_REQUEST["vatid"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        else $aData['itm_vatid'] = 'full';
        $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'item_base', 'itm_id');
        $hResult = $this->DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':' . $sKey, $sValue);
        $hResult->execute();
        if (isset($_REQUEST["textid"])) {
            $aData = [
                'itml_text1' => $purifier->purify($_REQUEST["text1"]),
                'itml_text2' => $purifier->purify($_REQUEST["text2"]),
                'itml_name_override' => filter_var($_REQUEST["name_override"], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW),
                'itml_id' => filter_var($_REQUEST["textid"], FILTER_SANITIZE_NUMBER_INT),
            ];
            $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'item_lang', 'itml_id');
            $hResult = $this->DB->prepare($sQ);
            foreach ($aData as $sKey => $sValue) $hResult->bindValue(':' . $sKey, $sValue);
            $hResult->execute();
        }

        return true;
    }

    private function admin_getItemgroups($iGID = '') // this function should be outsourced, a duplicate is used in admin itemgroups!
    {
        $sQ = 'SELECT * FROM itemgroups_base'
            . ' LEFT OUTER JOIN itemgroups_text ON itemgroups_base.itmg_id = itemgroups_text.itmgt_pid'
            . ' AND itemgroups_text.itmgt_lang = :lang';
        if ($iGID != '') $sQ .= ' WHERE itmg_id = :gid';
        $sQ .= ' ORDER BY itmg_no';
        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':lang', $this->sLang);
        if ($iGID != '') $hResult->bindValue(':gid', $iGID);
        $hResult->execute();

        $aGroups = $hResult->fetchAll();

        return $aGroups;
    }

}