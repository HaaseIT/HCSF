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


use HaaseIT\HCSF\Customer\Helper as CHelper;
use HaaseIT\HCSF\HelperConfig;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Items
 * @package HaaseIT\HCSF\Shop
 */
class Items
{
    /**
     * @var \PDO
     */
    private $db;

    // Initialize Class
    public function __construct(ServiceManager $serviceManager)
    {
        $this->db = $serviceManager->get('db');
    }

    public function getItemPathTree()
    {
        $itemindexpathtree = [];
        $aItemoverviewpages = [];
        $sql = "SELECT * FROM content_base WHERE cb_pagetype = 'itemoverview' OR cb_pagetype = 'itemoverviewgrpd'";
        $oQuery = $this->db->query($sql);
        while ($aRow = $oQuery->fetch()) {
            $aItemoverviewpages[] = [
                'path' => $aRow['cb_key'],
                'pageconfig' => json_decode($aRow['cb_pageconfig']),
            ];
        }
        foreach ($aItemoverviewpages as $aValue) {
            if (isset($aValue['pageconfig']->itemindex)) {
                if (is_array($aValue['pageconfig']->itemindex)) {
                    foreach ($aValue['pageconfig']->itemindex as $sIndexValue) {
                        if (!isset($itemindexpathtree[$sIndexValue])) {
                            $itemindexpathtree[$sIndexValue] = mb_substr($aValue['path'], 0, mb_strlen($aValue['path']) - 10).'item/';
                        }
                    }
                } else {
                    if (!isset($itemindexpathtree[$aValue['pageconfig']->itemindex])) {
                        $itemindexpathtree[$aValue['pageconfig']->itemindex] = mb_substr($aValue['path'], 0, mb_strlen($aValue['path']) - 10).'item/';
                    }
                }
            }
        }

        return $itemindexpathtree;
    }

    public function queryItem($mItemIndex = '', $mItemno = '', $sOrderby = '')
    {
        $sql = 'SELECT '.DB_ITEMFIELDS.' FROM item_base';
        $sql .= ' LEFT OUTER JOIN item_lang ON item_base.itm_id = item_lang.itml_pid AND itml_lang = :lang';
        $sql .= $this->queryItemWhereClause($mItemIndex, $mItemno);
        $sql .= ' ORDER BY '.(($sOrderby === '') ? 'itm_order, itm_no' : $sOrderby).' '.HelperConfig::$shop['items_orderdirection_default'];

        $hResult = $this->db->prepare($sql);
        $hResult->bindValue(':lang', HelperConfig::$lang, \PDO::PARAM_STR);
        $getsearchtext = filter_input(INPUT_GET, 'searchtext', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (!empty($mItemno)) {
            if (!is_array($mItemno)) {
                $hResult->bindValue(':itemno', $mItemno, \PDO::PARAM_STR);
            }
        } elseif (!empty($getsearchtext) && strlen(trim($getsearchtext)) > 2) {
            if (filter_input(INPUT_GET, 'artnoexact') !== null) {
                $hResult->bindValue(':searchtext', $getsearchtext, \PDO::PARAM_STR);
            }
            $hResult->bindValue(':searchtextwild1', '%'.$getsearchtext.'%', \PDO::PARAM_STR);
            $hResult->bindValue(':searchtextwild2', '%'.$getsearchtext.'%', \PDO::PARAM_STR);
            $hResult->bindValue(':searchtextwild3', '%'.$getsearchtext.'%', \PDO::PARAM_STR);
            $hResult->bindValue(':searchtextwild4', '%'.$getsearchtext.'%', \PDO::PARAM_STR);
            $hResult->bindValue(':searchtextwild5', '%'.$getsearchtext.'%', \PDO::PARAM_STR);
        }
        $hResult->execute();

        return $hResult;
    }

    /**
     * @param string|array $mItemIndex
     * @param string|array $mItemno
     * @return string
     */
    public function queryItemWhereClause($mItemIndex = '', $mItemno = '')
    {
        $sql = ' WHERE ';
        $getsearchtext = filter_input(INPUT_GET, 'searchtext', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (!empty($mItemno)) {
            if (is_array($mItemno)) {
                $sItemno = "'".\implode("','", \filter_var_array($mItemno, FILTER_SANITIZE_SPECIAL_CHARS))."'";
                $sql .= 'item_base.itm_no IN ('.$sItemno.')';
            } else {
                $sql .= 'item_base.itm_no = :itemno';
            }
        } elseif (!empty($getsearchtext) && strlen(trim($getsearchtext)) > 2) {
            if (filter_input(INPUT_GET, 'artnoexact') !== null) {
                $sql .= 'item_base.itm_no = :searchtext';
            } else {
                $sql .= '(item_base.itm_no LIKE :searchtextwild1 OR itm_name LIKE :searchtextwild2';
                $sql .= ' OR itml_name_override LIKE :searchtextwild3 OR itml_text1 LIKE :searchtextwild4';
                $sql .= ' OR itml_text2 LIKE :searchtextwild5)';
            }
        } else {
            if (is_array($mItemIndex)) {
                $sql .= '(';
                foreach ($mItemIndex as $sAIndex) {
                    $sql .= "itm_index LIKE '%".filter_var($sAIndex, FILTER_SANITIZE_SPECIAL_CHARS)."%' OR ";
                }
                $sql = \HaaseIT\Toolbox\Tools::cutStringend($sql, 4);
                $sql .= ')';
            } else {
                $sql .= "itm_index LIKE '%".filter_var($mItemIndex, FILTER_SANITIZE_SPECIAL_CHARS)."%'";
            }
        }
        $sql .= ' AND itm_index NOT LIKE \'%!%\' AND itm_index NOT LIKE \'%AL%\'';

        return $sql;
    }

    public function sortItems($mItemIndex = '', $mItemno = '', $bEnableItemGroups = false)
    {
        if ($mItemno != '') {
            if (\is_array($mItemno)) {
                $TMP = [];
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
            $aRow['pricedata'] = $this->calcPrice($aRow);

            if (!$bEnableItemGroups || \trim($aRow['itm_group']) == 0) {
                $aAssembly['item'][$aRow['itm_no']] = $aRow;
            } else {
                if (isset($aAssembly['groups']['ITEMGROUP-' .$aRow['itm_group']])) {
                    $aAssembly['groups']['ITEMGROUP-' .$aRow['itm_group']][$aRow['itm_no']] = $aRow;
                } else {
                    $aAssembly['item']['ITEMGROUP-' .$aRow['itm_group']]['group'] = 'ITEMGROUP-' .$aRow['itm_group'];
                    $aAssembly['groups']['ITEMGROUP-' .$aRow['itm_group']]['ITEMGROUP-DATA'] = $this->getGroupdata($aRow['itm_group']);
                    $aAssembly['groups']['ITEMGROUP-' .$aRow['itm_group']][$aRow['itm_no']] = $aRow;
                }
            }
        }

        if (isset($aAssembly)) {
            $aAssembly['totalitems'] = \count($aAssembly['item']);
            $aAssembly['itemkeys'] = \array_keys($aAssembly['item']);
            $aAssembly['firstitem'] = $aAssembly['itemkeys'][0];
            $aAssembly['lastitem'] = $aAssembly['itemkeys'][$aAssembly['totalitems'] - 1];

            return $aAssembly;
        }

        return false;
    }

    public function getGroupdata($sGroup)
    {
        $sql = 'SELECT '.DB_ITEMGROUPFIELDS.' FROM itemgroups_base'
           .' LEFT OUTER JOIN itemgroups_text ON itemgroups_base.itmg_id = itemgroups_text.itmgt_pid'
           .' AND itmgt_lang = :lang'
           .' WHERE itmg_id = :group';

        $hResult = $this->db->prepare($sql);
        $hResult->bindValue(':lang', HelperConfig::$lang, \PDO::PARAM_STR);
        $hResult->bindValue(':group', $sGroup, \PDO::PARAM_INT);
        $hResult->execute();

        $aRow = $hResult->fetch();
        $aRow['type'] = 'itemgroupdata';
        return $aRow;
    }

    public function calcPrice($aData)
    {
        $aPrice = [];
        if ($aData['itm_vatid'] !== 'reduced') {
            $aData['itm_vatid'] = 'full';
        }

        if(is_numeric($aData['itm_price']) && (float) $aData['itm_price'] > 0) {
            $aPrice['netto_list'] = $aData['itm_price'];
            $aPrice['brutto_list'] = $this->addVat($aPrice['netto_list'], HelperConfig::$shop['vat'][$aData['itm_vatid']]);
            if (
                isset($aData['itm_data']['sale']['start'], $aData['itm_data']['sale']['end'], $aData['itm_data']['sale']['price'])
            ) {
                $iToday = date('Ymd');
                if ($iToday >= $aData['itm_data']['sale']['start'] && $iToday <= $aData['itm_data']['sale']['end']) {
                    $aPrice['netto_sale'] = $aData['itm_data']['sale']['price'];
                    $aPrice['brutto_sale'] = $this->addVat($aPrice['netto_sale'], HelperConfig::$shop['vat'][$aData['itm_vatid']]);
                }
            }
            if (
                $aData['itm_rg'] != ''
                && isset(HelperConfig::$shop['rebate_groups'][$aData['itm_rg']][CHelper::getUserData('cust_group')])
            ) {
                $aPrice['netto_rebated'] =
                    bcmul(
                        $aData['itm_price'],
                        bcdiv(
                            bcsub(
                                '100',
                                (string)HelperConfig::$shop['rebate_groups'][$aData['itm_rg']][CHelper::getUserData('cust_group')]
                            ),
                            '100'
                        )
                    );
                $aPrice['brutto_rebated'] = $this->addVat($aPrice['netto_rebated'], HelperConfig::$shop['vat'][$aData['itm_vatid']]);
            }
        } else {
            return false;
        }

        $aPrice['netto_use'] = $aPrice['netto_list'];

        if (isset($aPrice['netto_rebated']) && $aPrice['netto_rebated'] < $aPrice['netto_use']) {
            $aPrice['netto_use'] = $aPrice['netto_rebated'];
        }
        if (isset($aPrice['netto_sale']) && $aPrice['netto_sale'] < $aPrice['netto_use']) {
            $aPrice['netto_use'] = $aPrice['netto_sale'];
        }

        $aPrice['brutto_use'] = $this->addVat($aPrice['netto_use'], HelperConfig::$shop['vat'][$aData['itm_vatid']]);

        return $aPrice;
    }

    private function addVat($price, $vat)
    {
        return
            bcadd(
                bcdiv(
                    bcmul(
                        $price,
                        (string)$vat
                    ),
                    '100'
                ),
                $price
            );
    }
}
