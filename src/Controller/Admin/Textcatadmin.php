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

namespace HaaseIT\HCSF\Controller\Admin;

use HaaseIT\HCSF\HardcodedText;
use HaaseIT\HCSF\HelperConfig;
use HaaseIT\Tools;

class Textcatadmin extends Base
{
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->container);
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        $this->P->cb_customcontenttemplate = 'textcatadmin';

        $return = '';

        if (!isset($_REQUEST["action"]) || $_REQUEST["action"] == '') {
            $aData = $this->container['textcats']->getCompleteTextcatForCurrentLang();

            $aListSetting = [
                ['title' => HardcodedText::get('textcatadmin_list_title_key'), 'key' => 'tc_key', 'width' => '20%', 'linked' => false,],
                ['title' => HardcodedText::get('textcatadmin_list_title_text'), 'key' => 'tcl_text', 'width' => '80%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                [
                    'title' => HardcodedText::get('textcatadmin_list_title_edit'),
                    'key' => 'tc_id',
                    'width' => 35,
                    'linked' => true,
                    'ltarget' => '/_admin/textcatadmin.html',
                    'lkeyname' => 'id',
                    'lgetvars' => [
                        'action' => 'edit',
                    ],
                ],
            ];
            $return .= Tools::makeListtable($aListSetting, $aData, $this->container['twig']);
        } elseif ($_GET["action"] == 'edit' || $_GET["action"] == 'delete') {
            if ($_GET["action"] == 'delete' && isset($_POST["delete"]) && $_POST["delete"] == 'do') {
                $this->container['textcats']->deleteText($_GET["id"]);
                $this->P->cb_customdata["deleted"] = true;
            } else {
                $this->P->cb_customdata["edit"] = true;

                $this->container['textcats']->initTextIfVoid($_GET["id"]);

                // if post:edit is set, update
                if (isset($_POST["edit"]) && $_POST["edit"] == 'do') {
                    if (HelperConfig::$core['textcat_enable_purifier']) {
                        $this->container['textcats']->purifier = \HaaseIT\HCSF\Helper::getPurifier('textcat');
                    } else {
                        $this->container['textcats']->purifier = false;
                    }
                    $this->container['textcats']->saveText($_POST["lid"], $_POST["text"]);
                    $this->P->cb_customdata["updated"] = true;
                }

                $aData = $this->container['textcats']->getSingleTextByID($_GET["id"]);
                $this->P->cb_customdata["editform"] = [
                    'id' => $aData["tc_id"],
                    'lid' => $aData["tcl_id"],
                    'key' => $aData["tc_key"],
                    'lang' => $aData["tcl_lang"],
                    'text' => $aData["tcl_text"],
                ];

                // show archived versions of this textcat
                $hResult = $this->container['db']->query(
                    'SELECT * FROM textcat_lang_archive WHERE tcl_id = '.$aData["tcl_id"]." AND tcl_lang = '".HelperConfig::$lang."' ORDER BY tcla_timestamp DESC"
                );
                $iArchivedRows = $hResult->rowCount();
                if ($iArchivedRows > 0) {
                    $aListSetting = [
                        ['title' => 'tcla_timestamp', 'key' => 'tcla_timestamp', 'width' => '15%', 'linked' => false,],
                        ['title' => 'tcl_text', 'key' => 'tcl_text', 'width' => '85%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                    ];
                    $aData = $hResult->fetchAll();
                    $this->P->cb_customdata['archived_list'] = Tools::makeListtable($aListSetting,
                        $aData, $this->container['twig']);
                }
            }
        } elseif ($_GET["action"] == 'add') {
            $this->P->cb_customdata["add"] = true;
            if (isset($_POST["add"]) && $_POST["add"] == 'do') {
                $this->P->cb_customdata["err"] = $this->container['textcats']->verifyAddTextKey($_POST["key"]);

                if (count($this->P->cb_customdata["err"]) == 0) {
                    $this->P->cb_customdata["addform"] = [
                        'key' => $_POST["key"],
                        'id' => $this->container['textcats']->addTextKey($_POST["key"]),
                    ];
                }
            }
        }

        $this->P->oPayload->cl_html = $return;
    }
}