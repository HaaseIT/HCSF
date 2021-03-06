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


use HaaseIT\Toolbox\Tools;
use Zend\ServiceManager\ServiceManager;

/**
 * Class DBStatus
 * @package HaaseIT\HCSF\Controller\Admin
 */
class DBStatus extends Base
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * DBStatus constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->db = $serviceManager->get('db');
        $this->twig = $serviceManager->get('twig');
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager, [], 'admin/base.twig');
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        $this->P->cb_customcontenttemplate = 'DBStatus';

        $this->handleTextcats();
        $this->handleTextcatArchive();
        $this->handleContent();
        $this->handleContentArchive();

        if ($this->config->getCore('enable_module_shop')) {
            $this->handleItems();
            $this->handleItemGroups();
            $this->handleOrderItems();
        }
    }

    /**
     *
     */
    private function handleTextcats()
    {
        if (filter_input(INPUT_GET, 'clearorphanedtextcats') !== null) {
            $this->db->exec('DELETE FROM textcat_lang WHERE tcl_tcid NOT IN (SELECT tc_id FROM textcat_base)');
        }
        /** @var \PDOStatement $hResult */
        $hResult = $this->db->query('SELECT * FROM textcat_lang WHERE tcl_tcid NOT IN (SELECT tc_id FROM textcat_base)');
        $this->P->cb_customdata['rows_textcat_lang'] = $hResult->rowCount();
        if ($this->P->cb_customdata['rows_textcat_lang'] > 0) {
            $aListSetting = [
                ['title' => 'tcl_id', 'key' => 'tcl_id', 'width' => '6%', 'linked' => false,],
                ['title' => 'tcl_tcid', 'key' => 'tcl_tcid', 'width' => '6%', 'linked' => false,],
                ['title' => 'tcl_lang', 'key' => 'tcl_lang', 'width' => '6%', 'linked' => false,],
                ['title' => 'tcl_text', 'key' => 'tcl_text', 'width' => '82%', 'linked' => false, 'escapehtmlspecialchars' => true,],
            ];
            $aData = $hResult->fetchAll();
            $this->P->cb_customdata['rows_textcat_lang_list'] = Tools::makeListtable(
                $aListSetting,
                $aData,
                $this->twig
            );
        }
    }

    /**
     *
     */
    private function handleTextcatArchive()
    {
        if (filter_input(INPUT_GET, 'clearorphanedtextcatsarchive') !== null) {
            $this->db->exec('DELETE FROM textcat_lang_archive WHERE tcl_tcid NOT IN (SELECT tc_id FROM textcat_base)');
        }
        /** @var \PDOStatement $hResult */
        $hResult = $this->db->query('SELECT * FROM textcat_lang_archive WHERE tcl_tcid NOT IN (SELECT tc_id FROM textcat_base)');
        $this->P->cb_customdata['rows_textcat_lang_archive'] = $hResult->rowCount();
        if ($this->P->cb_customdata['rows_textcat_lang_archive'] > 0) {
            $aListSetting = [
                ['title' => 'tcla_timestamp', 'key' => 'tcla_timestamp', 'width' => '15%', 'linked' => false,],
                ['title' => 'tcla_id', 'key' => 'tcla_id', 'width' => '6%', 'linked' => false,],
                ['title' => 'tcl_id', 'key' => 'tcl_id', 'width' => '6%', 'linked' => false,],
                ['title' => 'tcl_tcid', 'key' => 'tcl_tcid', 'width' => '6%', 'linked' => false,],
                ['title' => 'tcl_lang', 'key' => 'tcl_lang', 'width' => '6%', 'linked' => false,],
                ['title' => 'tcl_text', 'key' => 'tcl_text', 'width' => '61%', 'linked' => false, 'escapehtmlspecialchars' => true,],
            ];
            $aData = $hResult->fetchAll();
            $this->P->cb_customdata['rows_textcat_lang_archive_list'] = Tools::makeListtable($aListSetting,
                $aData, $this->twig);
        }
    }

    /**
     *
     */
    private function handleContent()
    {
        if (filter_input(INPUT_GET, 'clearorphanedcontent') !== null) {
            $this->db->exec('DELETE FROM content_lang WHERE cl_cb NOT IN (SELECT cb_id FROM content_base)');
        }
        /** @var \PDOStatement $hResult */
        $hResult = $this->db->query('SELECT * FROM content_lang WHERE cl_cb NOT IN (SELECT cb_id FROM content_base)');
        $this->P->cb_customdata['rows_content_lang'] = $hResult->rowCount();
        if ($this->P->cb_customdata['rows_content_lang'] > 0) {
            $aListSetting = [
                ['title' => 'cl_id', 'key' => 'cl_id', 'width' => '6%', 'linked' => false,],
                ['title' => 'cl_cb', 'key' => 'cl_cb', 'width' => '6%', 'linked' => false,],
                ['title' => 'cl_lang', 'key' => 'cl_lang', 'width' => '6%', 'linked' => false,],
                ['title' => 'cl_html', 'key' => 'cl_html', 'width' => '43%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                ['title' => 'cl_keywords', 'key' => 'cl_keywords', 'width' => '13%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                ['title' => 'cl_description', 'key' => 'cl_description', 'width' => '13%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                ['title' => 'cl_title', 'key' => 'cl_title', 'width' => '13%', 'linked' => false, 'escapehtmlspecialchars' => true,],
            ];
            $aData = $hResult->fetchAll();
            $this->P->cb_customdata['rows_content_lang_list'] = Tools::makeListtable($aListSetting,
                $aData, $this->twig);
        }
    }

    /**
     *
     */
    private function handleContentArchive()
    {
        if (filter_input(INPUT_GET, 'clearorphanedcontentarchive') !== null) {
            $this->db->exec('DELETE FROM content_lang_archive WHERE cl_cb NOT IN (SELECT cb_id FROM content_base)');
        }
        /** @var \PDOStatement $hResult */
        $hResult = $this->db->query('SELECT * FROM content_lang_archive WHERE cl_cb NOT IN (SELECT cb_id FROM content_base)');
        $this->P->cb_customdata['rows_content_lang_archive'] = $hResult->rowCount();
        if ($this->P->cb_customdata['rows_content_lang_archive'] > 0) {
            $aListSetting = [
                ['title' => 'cla_timestamp', 'key' => 'cla_timestamp', 'width' => '15%', 'linked' => false,],
                ['title' => 'cla_id', 'key' => 'cla_id', 'width' => '6%', 'linked' => false,],
                ['title' => 'cl_id', 'key' => 'cl_id', 'width' => '6%', 'linked' => false,],
                ['title' => 'cl_cb', 'key' => 'cl_cb', 'width' => '6%', 'linked' => false,],
                ['title' => 'cl_lang', 'key' => 'cl_lang', 'width' => '6%', 'linked' => false,],
                ['title' => 'cl_html', 'key' => 'cl_html', 'width' => '33%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                ['title' => 'cl_keywords', 'key' => 'cl_keywords', 'width' => '10%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                ['title' => 'cl_description', 'key' => 'cl_description', 'width' => '10%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                ['title' => 'cl_title', 'key' => 'cl_title', 'width' => '10%', 'linked' => false, 'escapehtmlspecialchars' => true,],
            ];
            $aData = $hResult->fetchAll();
            $this->P->cb_customdata['rows_content_lang_archive_list'] = Tools::makeListtable($aListSetting,
                $aData, $this->twig);
        }
    }

    /**
     *
     */
    private function handleItems()
    {
        if (filter_input(INPUT_GET, 'clearorphaneditems') !== null) {
            $this->db->exec('DELETE FROM item_lang WHERE itml_pid NOT IN (SELECT itm_id FROM item_base)');
        }
        /** @var \PDOStatement $hResult */
        $hResult = $this->db->query('SELECT * FROM item_lang WHERE itml_pid NOT IN (SELECT itm_id FROM item_base)');
        $this->P->cb_customdata['rows_item_lang'] = $hResult->rowCount();
        if ($this->P->cb_customdata['rows_item_lang'] > 0) {
            $aListSetting = [
                ['title' => 'itml_id', 'key' => 'itml_id', 'width' => '6%', 'linked' => false,],
                ['title' => 'itml_pid', 'key' => 'itml_pid', 'width' => '6%', 'linked' => false,],
                ['title' => 'itml_lang', 'key' => 'itml_lang', 'width' => '6%', 'linked' => false,],
                [
                    'title' => 'itml_name_override',
                    'key' => 'itml_name_override',
                    'width' => '18%',
                    'linked' => false,
                    'escapehtmlspecialchars' => true,
                ],
                [
                    'title' => 'itml_text1',
                    'key' => 'itml_text1',
                    'width' => '32%',
                    'linked' => false,
                    'escapehtmlspecialchars' => true,
                ],
                [
                    'title' => 'itml_text2',
                    'key' => 'itml_text2',
                    'width' => '32%',
                    'linked' => false,
                    'escapehtmlspecialchars' => true,
                ],
            ];
            $aData = $hResult->fetchAll();
            $this->P->cb_customdata['rows_item_lang_list'] = Tools::makeListtable($aListSetting,
                $aData, $this->twig);
        }
    }

    /**
     *
     */
    private function handleItemGroups()
    {
        if (filter_input(INPUT_GET, 'clearorphaneditemgroups') !== null) {
            $this->db->exec('DELETE FROM itemgroups_text WHERE itmgt_pid NOT IN (SELECT itmg_id FROM itemgroups_base)');
        }
        /** @var \PDOStatement $hResult */
        $hResult = $this->db->query('SELECT * FROM itemgroups_text WHERE itmgt_pid NOT IN (SELECT itmg_id FROM itemgroups_base)');
        $this->P->cb_customdata['rows_itemgroups_text'] = $hResult->rowCount();
        if ($this->P->cb_customdata['rows_itemgroups_text'] > 0) {
            $aListSetting = [
                ['title' => 'itmgt_id', 'key' => 'itmgt_id', 'width' => '6%', 'linked' => false,],
                ['title' => 'itmgt_pid', 'key' => 'itmgt_pid', 'width' => '6%', 'linked' => false,],
                ['title' => 'itmgt_lang', 'key' => 'itmgt_lang', 'width' => '6%', 'linked' => false,],
                [
                    'title' => 'itmgt_shorttext',
                    'key' => 'itmgt_shorttext',
                    'width' => '41%',
                    'linked' => false,
                    'escapehtmlspecialchars' => true,
                ],
                [
                    'title' => 'itmgt_details',
                    'key' => 'itmgt_details',
                    'width' => '41%',
                    'linked' => false,
                    'escapehtmlspecialchars' => true,
                ],
            ];
            $aData = $hResult->fetchAll();
            $this->P->cb_customdata['rows_itemgroups_text_list'] = Tools::makeListtable($aListSetting,
                $aData, $this->twig);
        }
    }

    /**
     *
     */
    private function handleOrderItems()
    {
        if (filter_input(INPUT_GET, 'clearorphanedorderitems') !== null) {
            $this->db->exec('DELETE FROM orders_items WHERE oi_o_id  NOT IN (SELECT o_id FROM orders)');
        }
        /** @var \PDOStatement $hResult */
        $hResult = $this->db->query('SELECT * FROM orders_items WHERE oi_o_id  NOT IN (SELECT o_id FROM orders)');
        $this->P->cb_customdata['rows_orders_items'] = $hResult->rowCount();
        if ($this->P->cb_customdata['rows_orders_items'] > 0) {
            $aListSetting = [
                ['title' => 'oi_id', 'key' => 'oi_id', 'width' => '8%', 'linked' => false,],
                ['title' => 'oi_o_id', 'key' => 'oi_o_id', 'width' => '9%', 'linked' => false,],
                ['title' => 'oi_cartkey', 'key' => 'oi_cartkey', 'width' => '13%', 'linked' => false,],
                ['title' => 'oi_amount', 'key' => 'oi_amount', 'width' => '9%', 'linked' => false,],
                ['title' => 'oi_vat', 'key' => 'oi_vat', 'width' => '8%', 'linked' => false,],
                ['title' => 'oi_rg', 'key' => 'oi_rg', 'width' => '8%', 'linked' => false,],
                ['title' => 'oi_rg_rebate', 'key' => 'oi_rg_rebate', 'width' => '9%', 'linked' => false,],
                ['title' => 'oi_price_netto_list',  'key' => 'oi_price_netto_list', 'width' => '9%', 'linked' => false,],
                ['title' => 'oi_price_netto_sale', 'key' => 'oi_price_netto_sale', 'width' => '9%', 'linked' => false,],
                ['title' => 'oi_price_netto_rebated', 'key' => 'oi_price_netto_rebated', 'width' => '9%', 'linked' => false,],
                ['title' => 'oi_price_brutto_use', 'key' => 'oi_price_brutto_use', 'width' => '9%', 'linked' => false,],
                //['title' => 'oi_img', 'key' => 'oi_img', 'width' => '41%', 'linked' => false, 'escapehtmlspecialchars' => true,],

            ];
            $aData = $hResult->fetchAll();
            $this->P->cb_customdata['rows_orders_items_list'] = Tools::makeListtable($aListSetting,
                $aData, $this->twig);
        }
    }
}
