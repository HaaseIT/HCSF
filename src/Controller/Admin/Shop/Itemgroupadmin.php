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

class Itemgroupadmin extends Base
{
    public function __construct($C, $DB, $sLang, $twig)
    {
        parent::__construct($C, $DB, $sLang);
        $this->P->cb_customcontenttemplate = 'shop/itemgroupadmin';

        $sH = '';
        if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'insert_lang') {
            $sQ = 'SELECT itmg_id FROM itemgroups_base WHERE itmg_id = :gid';
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':gid', $_REQUEST["gid"]);
            $hResult->execute();
            $iNumRowsBasis = $hResult->rowCount();

            $sQ = 'SELECT itmgt_id FROM itemgroups_text WHERE itmgt_pid = :gid AND itmgt_lang = :lang';
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':gid', $_REQUEST["gid"]);
            $hResult->bindValue(':lang', $sLang);
            $hResult->execute();
            $iNumRowsLang = $hResult->rowCount();

            if ($iNumRowsBasis == 1 && $iNumRowsLang == 0) {
                $iGID = filter_var($_REQUEST["gid"], FILTER_SANITIZE_NUMBER_INT);
                $aData = [
                    'itmgt_pid' => $iGID,
                    'itmgt_lang' => $sLang,
                ];
                $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aData, 'itemgroups_text');
                $hResult = $DB->prepare($sQ);
                foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
                $hResult->execute();
                header('Location: /_admin/itemgroupadmin.html?gid='.$iGID.'&action=editgroup');
                die();
            }
        }

        if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'editgroup') {
            if (isset($_REQUEST["do"]) && $_REQUEST["do"] == 'true') {
                $this->P->cb_customdata["updatestatus"] = $this->admin_updateGroup(\HaaseIT\HCSF\Helper::getPurifier($C, 'itemgroup'));
            }

            $iGID = filter_var($_REQUEST["gid"], FILTER_SANITIZE_NUMBER_INT);
            $aGroup = $this->admin_getItemgroups($iGID);
            if (isset($_REQUEST["added"])) {
                $this->P->cb_customdata["groupjustadded"] = true;
            }
            $this->P->cb_customdata["showform"] = 'edit';
            $this->P->cb_customdata["group"] = $this->admin_prepareGroup('edit', $aGroup[0]);
        } elseif (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'addgroup') {
            $aErr = [];
            if (isset($_REQUEST["do"]) && $_REQUEST["do"] == 'true') {
                $sName = filter_var($_REQUEST["name"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                $sGNo = filter_var($_REQUEST["no"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                $sImg = filter_var($_REQUEST["img"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

                if (strlen($sName) < 3) $aErr["nametooshort"] = true;
                if (strlen($sGNo) < 3) $aErr["grouptooshort"] = true;
                if (count($aErr) == 0) {
                    $sQ = 'SELECT itmg_no FROM itemgroups_base WHERE itmg_no = :no';
                    $hResult = $DB->prepare($sQ);
                    $hResult->bindValue(':no', $sGNo);
                    $hResult->execute();
                    if ($hResult->rowCount() > 0) $aErr["duplicateno"] = true;
                }
                if (count($aErr) == 0) {
                    $aData = [
                        'itmg_name' => $sName,
                        'itmg_no' => $sGNo,
                        'itmg_img' => $sImg,
                    ];
                    $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aData, 'itemgroups_base');
                    $hResult = $DB->prepare($sQ);
                    foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
                    $hResult->execute();
                    $iLastInsertID = $DB->lastInsertId();
                    header('Location: /_admin/itemgroupadmin.html?action=editgroup&added&gid='.$iLastInsertID);
                    die();
                } else {
                    $this->P->cb_customdata["err"] = $aErr;
                    $this->P->cb_customdata["showform"] = 'add';
                    $this->P->cb_customdata["group"] = $this->admin_prepareGroup('add');
                }
            } else {
                $this->P->cb_customdata["showform"] = 'add';
                $this->P->cb_customdata["group"] = $this->admin_prepareGroup('add');
            }
        } else {
            if (!$sH .= $this->admin_showItemgroups($this->admin_getItemgroups(''), $twig)) {
                $this->P->cb_customdata["err"]["nogroupsavaliable"] = true;
            }
        }
        $this->P->oPayload->cl_html = $sH;
    }

    private function admin_updateGroup($purifier)
    {
        $sQ = 'SELECT * FROM itemgroups_base WHERE itmg_id != :id AND itmg_no = :gno';
        $hResult = $this->DB->prepare($sQ);
        $iGID = filter_var($_REQUEST["gid"], FILTER_SANITIZE_NUMBER_INT);
        $sGNo = filter_var($_REQUEST["no"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        $hResult->bindValue(':id', $iGID);
        $hResult->bindValue(':gno', $sGNo);
        $hResult->execute();
        $iNumRows = $hResult->rowCount();

        if ($iNumRows > 0) return 'duplicateno';

        $aData = [
            'itmg_name' => filter_var($_REQUEST["name"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'itmg_no' => $sGNo,
            'itmg_img' => filter_var($_REQUEST["img"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'itmg_id'=> $iGID,
        ];

        $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'itemgroups_base', 'itmg_id');
        $hResult = $this->DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) {
            $hResult->bindValue(':' . $sKey, $sValue);
        }
        $hResult->execute();

        $sQ = 'SELECT itmgt_id FROM itemgroups_text WHERE itmgt_pid = :gid AND itmgt_lang = :lang';
        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':gid', $iGID);
        $hResult->bindValue(':lang', $this->sLang, \PDO::PARAM_STR);
        $hResult->execute();

        $iNumRows = $hResult->rowCount();

        if ($iNumRows == 1) {
            $aRow = $hResult->fetch();
            $aData = [
                'itmgt_shorttext' => $purifier->purify($_REQUEST["shorttext"]),
                'itmgt_details' => $purifier->purify($_REQUEST["details"]),
                'itmgt_id' => $aRow['itmgt_id'],
            ];
            $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'itemgroups_text', 'itmgt_id');
            $hResult = $this->DB->prepare($sQ);
            foreach ($aData as $sKey => $sValue) $hResult->bindValue(':' . $sKey, $sValue);
            $hResult->execute();
        }

        return 'success';
    }

    private function admin_prepareGroup($sPurpose = 'none', $aData = [])
    {
        $aGData = [
            'formaction' => \HaaseIT\Tools::makeLinkHRefWithAddedGetVars('/_admin/itemgroupadmin.html'),
            'id' => isset($aData['itmg_id']) ? $aData['itmg_id'] : '',
            'name' => isset($aData['itmg_name']) ? $aData['itmg_name'] : '',
            'no' => isset($aData['itmg_no']) ? $aData['itmg_no'] : '',
            'img' => isset($aData['itmg_img']) ? $aData['itmg_img'] : '',
        ];

        if ($sPurpose == 'edit') {
            if ($aData['itmgt_id'] != '') {
                $aGData["lang"] = [
                    'shorttext' => isset($aData['itmgt_shorttext']) ? $aData['itmgt_shorttext'] : '',
                    'details' => isset($aData['itmgt_details']) ? $aData['itmgt_details'] : '',
                ];
            }
        }

        return $aGData;
    }

    private function admin_getItemgroups($iGID = '')
    {
        $sQ = 'SELECT * FROM itemgroups_base '
            . 'LEFT OUTER JOIN itemgroups_text ON itemgroups_base.itmg_id = itemgroups_text.itmgt_pid'
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

    private function admin_showItemgroups($aGroups, $twig)
    {
        $aList = [
            ['title' => \HaaseIT\HCSF\HardcodedText::get('itemgroupadmin_list_no'), 'key' => 'gno', 'width' => 80, 'linked' => false, 'style-data' => 'padding: 5px 0;'],
            ['title' => \HaaseIT\HCSF\HardcodedText::get('itemgroupadmin_list_name'), 'key' => 'gname', 'width' => 350, 'linked' => false, 'style-data' => 'padding: 5px 0;'],
            ['title' => \HaaseIT\HCSF\HardcodedText::get('itemgroupadmin_list_edit'), 'key' => 'gid', 'width' => 30, 'linked' => true, 'ltarget' => '/_admin/itemgroupadmin.html', 'lkeyname' => 'gid', 'lgetvars' => ['action' => 'editgroup'], 'style-data' => 'padding: 5px 0;'],
        ];
        if (count($aGroups) > 0) {
            foreach ($aGroups as $aValue) {
                $aData[] = [
                    'gid' => $aValue['itmg_id'],
                    'gno' => $aValue['itmg_no'],
                    'gname' => $aValue['itmg_name'],
                ];
            }
            return \HaaseIT\Tools::makeListTable($aList, $aData, $twig);
        } else {
            return false;
        }
    }

}