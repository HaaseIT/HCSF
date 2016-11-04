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

class Pageadmin extends Base
{
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->container);
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        $this->P->cb_customcontenttemplate = 'pageadmin';

        // adding language to page here
        if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'insert_lang') {
            $Ptoinsertlang = new \HaaseIT\HCSF\UserPage($this->container, $_REQUEST["page_key"], true);

            if ($Ptoinsertlang->cb_id != NULL && $Ptoinsertlang->oPayload->cl_id == NULL) {
                $Ptoinsertlang->oPayload->insert($Ptoinsertlang->cb_id);
                header('Location: /_admin/pageadmin.html?page_key='.$Ptoinsertlang->cb_key.'&action=edit');
                die();
            } else {
                die(\HaaseIT\HCSF\HardcodedText::get('pageadmin_exception_couldnotinsertlang'));
            }
        }

        if (!isset($_GET["action"])) {
            $this->P->cb_customdata["pageselect"] = $this->showPageselect();
        } elseif (($_GET["action"] == 'edit' || $_GET["action"] == 'delete') && isset($_REQUEST["page_key"]) && $_REQUEST["page_key"] != '') {
            if ($_GET["action"] == 'delete' && isset($_POST["delete"]) && $_POST["delete"] == 'do') {
                // delete and put message in customdata
                $Ptodelete = new \HaaseIT\HCSF\UserPage($this->container, $_GET["page_key"], true);
                if ($Ptodelete->cb_id != NULL) {
                    $Ptodelete->remove();
                } else {
                    die(\HaaseIT\HCSF\HardcodedText::get('pageadmin_exception_pagetodeletenotfound'));
                }
                $this->P->cb_customdata["deleted"] = true;
            } else { // edit or update page
                if (isset($_REQUEST["page_key"]) && $Ptoedit = new \HaaseIT\HCSF\UserPage($this->container, $_REQUEST["page_key"], true)) {
                    if (isset($_REQUEST["action_a"]) && $_REQUEST["action_a"] == 'true') {

                        if ($this->container['conf']['pagetext_enable_purifier']) {
                            $purifier = \HaaseIT\HCSF\Helper::getPurifier($this->container['conf'], 'page');
                        } else {
                            $purifier = false;
                        }

                        $Ptoedit->cb_pagetype = $_POST['page_type'];
                        $Ptoedit->cb_group = $_POST['page_group'];
                        $Ptoedit->cb_pageconfig = $_POST['page_config'];
                        $Ptoedit->cb_subnav = $_POST['page_subnav'];
                        $Ptoedit->purifier = $purifier;
                        $Ptoedit->write();

                        if ($Ptoedit->oPayload->cl_id != NULL) {
                            $Ptoedit->oPayload->cl_html = $_POST['page_html'];
                            $Ptoedit->oPayload->cl_title = $_POST['page_title'];
                            $Ptoedit->oPayload->cl_description = $_POST['page_description'];
                            $Ptoedit->oPayload->cl_keywords = $_POST['page_keywords'];
                            $Ptoedit->oPayload->purifier = $purifier;
                            $Ptoedit->oPayload->write();
                        }

                        $Ptoedit = new \HaaseIT\HCSF\UserPage($this->container, $_REQUEST["page_key"], true);
                        $this->P->cb_customdata["updated"] = true;
                    }
                    $this->P->cb_customdata["page"] = $Ptoedit;
                    $this->P->cb_customdata["admin_page_types"] = $this->container['conf']["admin_page_types"];
                    $this->P->cb_customdata["admin_page_groups"] = $this->container['conf']["admin_page_groups"];
                    $aOptions = [''];
                    foreach ($this->container["navstruct"] as $sKey => $aValue) {
                        if ($sKey == 'admin') {
                            continue;
                        }
                        $aOptions[] = $sKey;
                    }
                    $this->P->cb_customdata["subnavarea_options"] = $aOptions;
                    unset($aOptions);

                    // show archived versions of this page
                    if ($Ptoedit->oPayload->cl_id != NULL) {
                        $hResult = $this->container['db']->query(
                            'SELECT * FROM content_lang_archive WHERE cl_id = '.$Ptoedit->oPayload->cl_id." AND cl_lang = '".$this->container['lang']."' ORDER BY cla_timestamp DESC"
                        );
                        $iArchivedRows = $hResult->rowCount();
                        if ($iArchivedRows > 0) {
                            $aListSetting = [
                                ['title' => 'cla_timestamp', 'key' => 'cla_timestamp', 'width' => '15%', 'linked' => false,],
                                ['title' => 'cl_html', 'key' => 'cl_html', 'width' => '40%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                                ['title' => 'cl_keywords', 'key' => 'cl_keywords', 'width' => '15%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                                ['title' => 'cl_description', 'key' => 'cl_description', 'width' => '15%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                                ['title' => 'cl_title', 'key' => 'cl_title', 'width' => '15%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                            ];
                            $aData = $hResult->fetchAll();
                            $this->P->cb_customdata['archived_list'] = \HaaseIT\Tools::makeListtable($aListSetting,
                                $aData, $this->container['twig']);
                        }
                    }

                } else {
                    die(\HaaseIT\HCSF\HardcodedText::get('pageadmin_exception_pagenotfound'));
                }
            }
        } elseif ($_GET["action"] == 'addpage') {
            $aErr = [];
            if (isset($_POST["addpage"]) && $_POST["addpage"] == 'do') {
                $sPagekeytoadd = \trim(\filter_input(INPUT_POST, 'pagekey', FILTER_SANITIZE_SPECIAL_CHARS));

                if (mb_substr($sPagekeytoadd, 0, 2) == '/_') {
                    $aErr["reservedpath"] = true;
                } elseif (strlen($sPagekeytoadd) < 4) {
                    $aErr["keytooshort"] = true;
                } else {
                    $Ptoadd = new \HaaseIT\HCSF\UserPage($this->container, $sPagekeytoadd, true);
                    if ($Ptoadd->cb_id == NULL) {
                        if ($Ptoadd->insert($sPagekeytoadd)) {
                            header('Location: /_admin/pageadmin.html?page_key='.$sPagekeytoadd.'&action=edit');
                            die();
                        } else {
                            die(\HaaseIT\HCSF\HardcodedText::get('pageadmin_exception_couldnotinsertpage'));
                        }
                    } else {
                        $aErr["keyalreadyinuse"] = true;
                    }
                }
                $this->P->cb_customdata["err"] = $aErr;
                unset($aErr);
            }
            $this->P->cb_customdata["showaddform"] = true;
        }
    }

    private function showPageselect() {
        $hResult = $this->container['db']->query('SELECT * FROM content_base ORDER BY cb_key');

        $aGroups = [];
        foreach ($this->container['conf']["admin_page_groups"] as $sValue) {
            $TMP = explode('|', $sValue);
            $aGroups[$TMP[0]] = $TMP[1];
        }

        while ($aResult = $hResult->fetch()) {
            if (isset($aGroups[$aResult["cb_group"]])) {
                $aTree[$aResult["cb_group"]][] = $aResult;
            } else {
                $aTree["_"][] = $aResult;
            }
        }

        foreach ($aGroups as $sKey => $sValue) {
            if (isset($aTree[$sKey])) {
                $aOptions_g[] = $sKey.'|'.$sValue;
            }
        }

        return [
            'options_groups' => isset($aOptions_g) ? $aOptions_g : [],
            'tree' => isset($aTree) ? $aTree : [],
        ];
    }
}