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


use HaaseIT\HCSF\HardcodedText;
use HaaseIT\HCSF\HelperConfig;
use HaaseIT\Toolbox\Tools;
use Zend\Diactoros\ServerRequest;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Itemadmin
 * @package HaaseIT\HCSF\Controller\Admin\Shop
 */
class Itemadmin extends Base
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $dbal;

    /**
     * @var array
     */
    protected $get;

    /**
     * @var array
     */
    protected $post;

    /**
     * @var ServerRequest;
     */
    protected $request;

    /**
     * Itemadmin constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->dbal = $serviceManager->get('dbal');
        $this->request = $serviceManager->get('request');
        $this->get = $this->request->getQueryParams();
        $this->post = $this->request->getParsedBody();
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        $this->P->cb_customcontenttemplate = 'shop/itemadmin';

        if (isset($this->get['action']) && $this->get['action'] === 'insert_lang') {
            $aItemdata = $this->admin_getItem();

            if (isset($aItemdata['base']) && !isset($aItemdata['text'])) {
                $querybuilder = $this->dbal->createQueryBuilder();
                $querybuilder
                    ->insert('item_lang')
                    ->setValue('itml_pid', '?')
                    ->setValue('itml_lang', '?')
                    ->setParameter(0, $aItemdata['base']['itm_id'])
                    ->setParameter(1, HelperConfig::$lang)
                ;
                $querybuilder->execute();

                \HaaseIT\HCSF\Helper::redirectToPage('/_admin/itemadmin.html?itemno='.$this->get['itemno'].'&action=showitem');
            }
        }
        $this->P->cb_customdata['searchform'] = $this->admin_prepareItemlistsearchform();

        if (isset($this->get['action'])) {
            if ($this->get['action'] === 'search') {
                $this->P->cb_customdata['searchresult'] = true;
                if ($aItemlist = $this->admin_getItemlist()) {
                    if (count($aItemlist['data']) == 1) {
                        $aItemdata = $this->admin_getItem($aItemlist['data'][0]['itm_no']);
                        $this->P->cb_customdata['item'] = $this->admin_prepareItem($aItemdata);
                    } else {
                        $this->P->cb_customdata['itemlist'] = $this->admin_prepareItemlist($aItemlist);
                    }
                }
            } elseif (isset($this->post['doaction']) && $this->post['doaction'] === 'edititem') {
                $this->admin_updateItem();
                $this->P->cb_customdata['itemupdated'] = true;

                $aItemdata = $this->admin_getItem();
                $this->P->cb_customdata['item'] = $this->admin_prepareItem($aItemdata);
            } elseif ($this->get['action'] === 'showitem') {
                $aItemdata = $this->admin_getItem();
                $this->P->cb_customdata['item'] = $this->admin_prepareItem($aItemdata);
            } elseif ($this->get['action'] === 'additem') {
                $aErr = [];
                if (isset($this->post['additem']) && $this->post['additem'] === 'do') {
                    if (strlen($this->post['itemno']) < 4) {
                        $aErr['itemnotooshort'] = true;
                    } else {
                        $querybuilder = $this->dbal->createQueryBuilder();
                        $querybuilder
                            ->select('itm_no')
                            ->from('item_base')
                            ->where('itm_no = ?')
                            ->setParameter(0, trim(filter_input(INPUT_POST, 'itemno', FILTER_SANITIZE_SPECIAL_CHARS)))
                        ;
                        $stmt = $querybuilder->execute();

                        if ($stmt->rowCount() > 0) {
                            $aErr['itemnoalreadytaken'] = true;
                        } else {
                            $querybuilder = $this->dbal->createQueryBuilder();
                            $querybuilder
                                ->insert('item_base')
                                ->setValue('itm_no', '?')
                                ->setParameter(0, trim(filter_input(INPUT_POST, 'itemno', FILTER_SANITIZE_SPECIAL_CHARS)))
                            ;

                            $querybuilder->execute();
                            $iInsertID = $this->dbal->lastInsertId();

                            $queryBuilder = $this->dbal->createQueryBuilder();
                            $queryBuilder
                                ->select('itm_no')
                                ->from('item_base')
                                ->where('itm_id = '.$queryBuilder->createNamedParameter($iInsertID))
                            ;
                            $statement = $queryBuilder->execute();
                            $row = $statement->fetch();

                            \HaaseIT\HCSF\Helper::redirectToPage('/_admin/itemadmin.html?itemno='.$row['itm_no'].'&action=showitem');
                        }
                    }
                }
                $this->P->cb_customdata['showaddform'] = true;
                $this->P->cb_customdata['err'] = $aErr;
            }
        }
    }

    /**
     * @return mixed
     */
    private function admin_prepareItemlistsearchform()
    {
        $aData['searchcats'] = [
            'nummer|'.HardcodedText::get('itemadmin_search_itemno'),
            'name|'.HardcodedText::get('itemadmin_search_itemname'),
            'index|'.HardcodedText::get('itemadmin_search_itemindex'),
        ];
        $aData['orderbys'] = [
            'nummer|'.HardcodedText::get('itemadmin_search_itemno'),
            'name|'.HardcodedText::get('itemadmin_search_itemname'),
        ];

        if (isset($this->get['searchcat'])) {
            $aData['searchcat'] = $this->get['searchcat'];
            $_SESSION['itemadmin_searchcat'] = $this->get['searchcat'];
        } elseif (isset($_SESSION['itemadmin_searchcat'])) {
            $aData['searchcat'] = $_SESSION['itemadmin_searchcat'];
        }

        if (isset($this->get['orderby'])) {
            $aData['orderby'] = $this->get['orderby'];
            $_SESSION['itemadmin_orderby'] = $this->get['orderby'];
        } elseif (isset($_SESSION['itemadmin_orderby'])) {
            $aData['orderby'] = $_SESSION['itemadmin_orderby'];
        }

        return $aData;
    }

    /**
     * @return bool
     */
    private function admin_getItemlist()
    {
        $sSearchstring = filter_input(INPUT_GET, 'searchstring', FILTER_SANITIZE_SPECIAL_CHARS);
        $sSearchstring = str_replace('*', '%', $sSearchstring);

        $querybuilder = $this->dbal->createQueryBuilder();
        $querybuilder
            ->select('itm_no, itm_name, itm_index')
            ->from('item_base', 'b')
            ->leftJoin('b', 'item_lang', 'l', 'b.itm_id = l.itml_pid AND l.itml_lang = :lang')
        ;

        if ($this->get['searchcat'] === 'name') {
            $querybuilder->where('itm_name LIKE :searchstring');
        } elseif ($this->get['searchcat'] === 'nummer') {
            $querybuilder->where('itm_no LIKE :searchstring');
        } elseif ($this->get['searchcat'] === 'index') {
            $querybuilder->where('itm_index LIKE :searchstring');
        } else {
            \HaaseIT\HCSF\Helper::terminateScript();
        }

        if ($this->get['orderby'] === 'name') {
            $querybuilder->orderBy('itm_name');
        } elseif ($this->get['orderby'] === 'nummer') {
            $querybuilder->orderBy('itm_no');
        }

        $querybuilder
            ->setParameter(':searchstring', $sSearchstring)
            ->setParameter(':lang', HelperConfig::$lang)
        ;

        $stmt = $querybuilder->execute();

        $aItemlist['numrows'] = $stmt->rowCount();

        if ($aItemlist['numrows'] !== 0) {
            while ($aRow = $stmt->fetch()) {
                $aItemlist['data'][] = $aRow;
            }
            return $aItemlist;
        }

        return false;
    }

    /**
     * @param $aItemlist
     * @return array
     */
    private function admin_prepareItemlist($aItemlist)
    {
        $aList = [
            ['title' => HardcodedText::get('itemadmin_list_active'), 'key' => 'itemindex', 'width' => 30, 'linked' => false, 'callback' => 'renderItemStatusIcon',],
            ['title' => HardcodedText::get('itemadmin_list_itemno'), 'key' => 'itemno', 'width' => 100, 'linked' => false,],
            ['title' => HardcodedText::get('itemadmin_list_name'), 'key' => 'name', 'width' => 350, 'linked' => false,],
            ['title' => HardcodedText::get('itemadmin_list_edit'), 'key' => 'itemno', 'width' => 30, 'linked' => true, 'ltarget' => '/_admin/itemadmin.html', 'lkeyname' => 'itemno', 'lgetvars' => ['action' => 'showitem'],],
        ];
        $aData = [];
        foreach ($aItemlist['data'] as $aValue) {
            $aData[] = [
                'itemindex' => $aValue['itm_index'],
                'itemno' => $aValue['itm_no'],
                'name' => $aValue['itm_name'],
            ];
        }

        return [
            'numrows' => $aItemlist['numrows'],
            'listtable' => Tools::makeListtable($aList, $aData, $this->serviceManager->get('twig')),
        ];
    }

    /**
     * @param string $sItemno
     * @return bool
     */
    private function admin_getItem($sItemno = '')
    {
        if ($sItemno === '') {
            if (empty($this->get['itemno'])) {
                return false;
            }
            $sItemno = filter_var($this->get['itemno'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        }

        $querybuilder = $this->dbal->createQueryBuilder();
        $querybuilder
            ->select('*')
            ->from('item_base')
            ->where('itm_no = ?')
            ->setParameter(0, $sItemno)
        ;
        $stmt = $querybuilder->execute();

        $aItemdata['base'] = $stmt->fetch();

        $querybuilder = $this->dbal->createQueryBuilder();
        $querybuilder
            ->select('*')
            ->from('item_lang')
            ->where('itml_pid = ? AND itml_lang = ?')
            ->setParameter(0, $aItemdata['base']['itm_id'])
            ->setParameter(1, HelperConfig::$lang)
        ;
        $stmt = $querybuilder->execute();

        if ($stmt->rowCount() != 0) {
            $aItemdata['text'] = $stmt->fetch();
        }

        return $aItemdata;
    }

    /**
     * @param $aItemdata
     * @return array
     */
    private function admin_prepareItem($aItemdata)
    {
        $aData = [
            'form' => ['action' => Tools::makeLinkHRefWithAddedGetVars('/_admin/itemadmin.html', ['action' => 'showitem', 'itemno' => $aItemdata['base']['itm_no']]),],
            'id' => $aItemdata['base']['itm_id'],
            'itemno' => $aItemdata['base']['itm_no'],
            'name' => $aItemdata['base']['itm_name'],
            'img' => $aItemdata['base']['itm_img'],
            'price' => $aItemdata['base']['itm_price'],
            'vatid' => $aItemdata['base']['itm_vatid'],
            'rg' => $aItemdata['base']['itm_rg'],
            'index' => $aItemdata['base']['itm_index'],
            'prio' => $aItemdata['base']['itm_order'],
            'group' => $aItemdata['base']['itm_group'],
            'data' => $aItemdata['base']['itm_data'],
            'weight' => $aItemdata['base']['itm_weight'],
        ];

        if (!HelperConfig::$shop['vat_disable']) {
            $aOptions[] = '|';
            foreach (HelperConfig::$shop['vat'] as $sKey => $sValue) {
                $aOptions[] = $sKey.'|'.$sValue;
            }
            $aData['vatoptions'] = $aOptions;
            unset($aOptions);
        }
        $aData['rgoptions'][] = '';
        foreach (HelperConfig::$shop['rebate_groups'] as $sKey => $aValue) {
            $aData['rgoptions'][] = $sKey;
        }

        $aGroups = $this->admin_getItemgroups('');
        $aData['groupoptions'][] = '';
        foreach ($aGroups as $aValue) {
            $aData['groupoptions'][] = $aValue['itmg_id'] . '|' . $aValue['itmg_no'] . ' - ' . $aValue['itmg_name'];
        }
        unset($aGroups);

        if (isset($aItemdata['text'])) {
            $aData['lang'] = [
                'textid' => $aItemdata['text']['itml_id'],
                'nameoverride' => $aItemdata['text']['itml_name_override'],
                'text1' => $aItemdata['text']['itml_text1'],
                'text2' => $aItemdata['text']['itml_text2'],
            ];
        }

        return $aData;
    }

    /**
     * @return bool
     */
    private function admin_updateItem()
    {
        $purifier = false;
        if (HelperConfig::$shop['itemtext_enable_purifier']) {
            $purifier = \HaaseIT\HCSF\Helper::getPurifier('item');
        }

        $querybuilder = $this->dbal->createQueryBuilder();
        $querybuilder
            ->update('item_base')
            ->set('itm_name', ':itm_name')
            ->set('itm_group', ':itm_group')
            ->set('itm_img', ':itm_img')
            ->set('itm_index', ':itm_index')
            ->set('itm_order', ':itm_order')
            ->set('itm_price', ':itm_price')
            ->set('itm_rg', ':itm_rg')
            ->set('itm_data', ':itm_data')
            ->set('itm_weight', ':itm_weight')
            ->set('itm_vatid', ':itm_vatid')
            ->where('itm_id = :itm_id')
            ->setParameter(':itm_name', filter_var($this->post['name'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
            ->setParameter(':itm_group', filter_var($this->post['group'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
            ->setParameter(':itm_img', filter_var($this->post['bild'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
            ->setParameter(':itm_index', filter_var($this->post['index'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
            ->setParameter(':itm_order', filter_var($this->post['prio'], FILTER_SANITIZE_NUMBER_INT))
            ->setParameter(':itm_price', filter_var($this->post['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION))
            ->setParameter(':itm_rg', filter_var($this->post['rg'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
            ->setParameter(':itm_data', filter_var($this->post['data'], FILTER_UNSAFE_RAW))
            ->setParameter(':itm_weight', filter_var($this->post['weight'], FILTER_SANITIZE_NUMBER_INT))
            ->setParameter(':itm_id', filter_var($this->post['id'], FILTER_SANITIZE_NUMBER_INT))
        ;

        if (!HelperConfig::$shop['vat_disable']) {
            $querybuilder->setParameter(':itm_vatid', filter_var($this->post['vatid'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW));
        } else {
            $querybuilder->setParameter(':itm_vatid', 'full');
        }
        $querybuilder->execute();

        if (isset($this->post['textid'])) {
            $querybuilder = $this->dbal->createQueryBuilder();
            $querybuilder
                ->update('item_lang')
                ->set('itml_text1', ':itml_text1')
                ->set('itml_text2', ':itml_text2')
                ->set('itml_name_override', ':itml_name_override')
                ->where('itml_id = :itml_id')
                ->setParameter(':itml_text1', !empty($this->purifier) ? $purifier->purify($this->post['text1']) : $this->post['text1'])
                ->setParameter(':itml_text2', !empty($this->purifier) ? $purifier->purify($this->post['text2']) : $this->post['text2'])
                ->setParameter(':itml_name_override', filter_var($this->post['name_override'], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW))
                ->setParameter(':itml_id', filter_var($this->post['textid'], FILTER_SANITIZE_NUMBER_INT))
            ;
            $querybuilder->execute();
        }

        return true;
    }

    /**
     * @param string $iGID
     * @return mixed
     */
    private function admin_getItemgroups($iGID = '') // this function should be outsourced, a duplicate is used in admin itemgroups!
    {
        $querybuilder = $this->dbal->createQueryBuilder();
        $querybuilder
            ->select('*')
            ->from('itemgroups_base', 'b')
            ->leftJoin('b', 'itemgroups_text', 't', 'b.itmg_id = t.itmgt_pid AND t.itmgt_lang = ?')
            ->setParameter(0, HelperConfig::$lang)
            ->orderBy('itmg_no')
        ;

        if ($iGID != '') {
            $querybuilder
                ->where('itmg_id = :gid')
                ->setParameter(1, $iGID)
            ;
        }
        $stmt = $querybuilder->execute();

        return $stmt->fetchAll();
    }
}
