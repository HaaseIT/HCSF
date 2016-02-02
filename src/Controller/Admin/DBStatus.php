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

class DBStatus extends Base
{
    public function __construct($C, $DB, $sLang, $twig)
    {
        parent::__construct($C, $DB, $sLang);
        $this->P->cb_customcontenttemplate = 'DBStatus';

        if (isset($_GET['clearorphanedtextcats'])) $DB->exec('DELETE FROM textcat_lang WHERE tcl_tcid NOT IN (SELECT tc_id FROM textcat_base)');
        $hResult = $DB->query('SELECT * FROM textcat_lang WHERE tcl_tcid NOT IN (SELECT tc_id FROM textcat_base)');
        $this->P->cb_customdata['rows_textcat_lang'] = $hResult->rowCount();
        if ($this->P->cb_customdata['rows_textcat_lang'] > 0) {
            $aListSetting = [
                ['title' => 'tcl_id', 'key' => 'tcl_id', 'width' => '7%', 'linked' => false,],
                ['title' => 'tcl_tcid', 'key' => 'tcl_tcid', 'width' => '7%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                ['title' => 'tcl_lang', 'key' => 'tcl_lang', 'width' => '7%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                ['title' => 'tcl_text', 'key' => 'tcl_text', 'width' => '79%', 'linked' => false, 'escapehtmlspecialchars' => true,],
            ];
            $aData = $hResult->fetchAll();
            $this->P->cb_customdata['rows_textcat_lang_list'] = \HaaseIT\Tools::makeListtable(
                $aListSetting,
                $aData,
                $twig
            );
        }

        if (isset($_GET['clearorphanedtextcatsarchive'])) $DB->exec('DELETE FROM textcat_lang_archive WHERE tcl_tcid NOT IN (SELECT tc_id FROM textcat_base)');
        $hResult = $DB->query('SELECT * FROM textcat_lang_archive WHERE tcl_tcid NOT IN (SELECT tc_id FROM textcat_base)');
        $this->P->cb_customdata['rows_textcat_lang_archive'] = $hResult->rowCount();
        if ($this->P->cb_customdata['rows_textcat_lang_archive'] > 0) {
            $aListSetting = [
                [
                    'title' => 'tcla_timestamp',
                    'key' => 'tcla_timestamp',
                    'width' => '15%',
                    'linked' => false,
                ],
                [
                    'title' => 'tcla_id',
                    'key' => 'tcla_id',
                    'width' => '7%',
                    'linked' => false,
                ],
                [
                    'title' => 'tcl_id',
                    'key' => 'tcl_id',
                    'width' => '7%',
                    'linked' => false,
                ],
                [
                    'title' => 'tcl_tcid',
                    'key' => 'tcl_tcid',
                    'width' => '7%',
                    'linked' => false,
                    'escapehtmlspecialchars' => true,
                ],
                [
                    'title' => 'tcl_lang',
                    'key' => 'tcl_lang',
                    'width' => '7%',
                    'linked' => false,
                    'escapehtmlspecialchars' => true,
                ],
                [
                    'title' => 'tcl_text',
                    'key' => 'tcl_text',
                    'width' => '57%',
                    'linked' => false,
                    'escapehtmlspecialchars' => true,
                ],
            ];
            $aData = $hResult->fetchAll();
            $this->P->cb_customdata['rows_textcat_lang_archive_list'] = \HaaseIT\Tools::makeListtable($aListSetting,
                $aData, $twig);
        }

        if (isset($_GET['clearorphanedcontent'])) $DB->exec('DELETE FROM content_lang WHERE cl_cb NOT IN (SELECT cb_id FROM content_base)');
        $hResult = $DB->query('SELECT * FROM content_lang WHERE cl_cb NOT IN (SELECT cb_id FROM content_base)');
        $this->P->cb_customdata['rows_content_lang'] = $hResult->rowCount();

        if (isset($_GET['clearorphanedcontentarchive'])) $DB->exec('DELETE FROM content_lang_archive WHERE cl_cb NOT IN (SELECT cb_id FROM content_base)');
        $hResult = $DB->query('SELECT * FROM content_lang_archive WHERE cl_cb NOT IN (SELECT cb_id FROM content_base)');
        $this->P->cb_customdata['rows_content_lang_archive'] = $hResult->rowCount();

        if (isset($_GET['clearorphaneditems'])) $DB->exec('DELETE FROM item_lang WHERE itml_pid NOT IN (SELECT itm_id FROM item_base)');
        $hResult = $DB->query('SELECT * FROM item_lang WHERE itml_pid NOT IN (SELECT itm_id FROM item_base)');
        $this->P->cb_customdata['rows_item_lang'] = $hResult->rowCount();

        if (isset($_GET['clearorphaneditemgroups'])) $DB->exec('DELETE FROM itemgroups_text WHERE itmgt_pid NOT IN (SELECT itmg_id FROM itemgroups_base)');
        $hResult = $DB->query('SELECT * FROM itemgroups_text WHERE itmgt_pid NOT IN (SELECT itmg_id FROM itemgroups_base)');
        $this->P->cb_customdata['rows_itemgroups_text'] = $hResult->rowCount();

        if (isset($_GET['clearorphanedorderitems'])) $DB->exec('DELETE FROM orders_items WHERE oi_o_id  NOT IN (SELECT o_id FROM orders)');
        $hResult = $DB->query('SELECT * FROM orders_items WHERE oi_o_id  NOT IN (SELECT o_id FROM orders)');
        $this->P->cb_customdata['rows_orders_items'] = $hResult->rowCount();
    }
}