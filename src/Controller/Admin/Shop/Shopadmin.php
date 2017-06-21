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
use Zend\ServiceManager\ServiceManager;

/**
 * Class Shopadmin
 * @package HaaseIT\HCSF\Controller\Admin\Shop
 */
class Shopadmin extends Base
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $dbal;

    /**
     * Shopadmin constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->dbal = $serviceManager->get('dbal');
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        $this->P->cb_customcontenttemplate = 'shop/shopadmin';

        if (filter_input(INPUT_POST, 'change') !== null) {
            $iID = filter_var(trim(Tools::getFormfield('id')), FILTER_SANITIZE_NUMBER_INT);
            $serverauthuser = filter_input(INPUT_SERVER, 'PHP_AUTH_USER', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

            $querybuilder = $this->dbal->createQueryBuilder();
            $querybuilder
                ->update('orders')
                ->set('o_lastedit_timestamp', ':o_lastedit_timestamp')
                ->set('o_remarks_internal', ':o_remarks_internal')
                ->set('o_transaction_no', ':o_transaction_no')
                ->set('o_paymentcompleted', ':o_paymentcompleted')
                ->set('o_ordercompleted', ':o_ordercompleted')
                ->set('o_lastedit_user', ':o_lastedit_user')
                ->set('o_shipping_service', ':o_shipping_service')
                ->set('o_shipping_trackingno', ':o_shipping_trackingno')
                ->where('o_id = :o_id')
                ->setParameter(':o_lastedit_timestamp', time())
                ->setParameter(':o_remarks_internal', filter_var(trim(Tools::getFormfield('remarks_internal')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                ->setParameter(':o_transaction_no', filter_var(trim(Tools::getFormfield('transaction_no')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                ->setParameter(':o_paymentcompleted', filter_var(trim(Tools::getFormfield('order_paymentcompleted')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                ->setParameter(':o_ordercompleted', filter_var(trim(Tools::getFormfield('order_completed')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                ->setParameter(':o_lastedit_user', !empty($serverauthuser) ? $serverauthuser : '')
                ->setParameter(':o_shipping_service', filter_var(trim(Tools::getFormfield('order_shipping_service')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                ->setParameter(':o_shipping_trackingno', filter_var(trim(Tools::getFormfield('order_shipping_trackingno')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                ->setParameter(':o_id', $iID)
            ;
            $querybuilder->execute();
            \HaaseIT\HCSF\Helper::redirectToPage('/_admin/shopadmin.html?action=edit&id='.$iID);
        }

        $aPData = [
            'searchform_type' => Tools::getFormfield('type', 'openinwork'),
            'searchform_fromday' => Tools::getFormfield('fromday', '01'),
            'searchform_frommonth' => Tools::getFormfield('frommonth', '01'),
            'searchform_fromyear' => Tools::getFormfield('fromyear', '2014'),
            'searchform_today' => Tools::getFormfield('today', date('d')),
            'searchform_tomonth' => Tools::getFormfield('tomonth', date('m')),
            'searchform_toyear' => Tools::getFormfield('toyear', date('Y')),
        ];

        $CSA = [
            'list_orders' => [
                ['title' => '', 'key' => 'o_id', 'width' => 30, 'linked' => false, 'callback' => 'shopadminMakeCheckbox'],
                ['title' => HardcodedText::get('shopadmin_list_orderid'), 'key' => 'o_id', 'width' => 30, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_customer'), 'key' => 'o_cust', 'width' => 280, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_sumnettoall'), 'key' => 'o_sumnettoall', 'width' => 75, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_orderstatus'), 'key' => 'o_order_status', 'width' => 80, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_ordertimenumber'), 'key' => 'o_ordertime_number', 'width' => 100, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_hostpayment'), 'key' => 'o_order_host_payment', 'width' => 140, 'linked' => false,],
                [
                    'title' => HardcodedText::get('shopadmin_list_edit'),
                    'key' => 'o_id',
                    'width' => 45,
                    'linked' => true,
                    'ltarget' => '/_admin/shopadmin.html',
                    'lkeyname' => 'id',
                    'lgetvars' => [
                        'action' => 'edit',
                    ],
                ],
            ],
            'list_orderitems' => [
                ['title' => HardcodedText::get('shopadmin_list_itemno'), 'key' => 'oi_itemno', 'width' => 95, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_itemname'), 'key' => 'oi_itemname', 'width' => 350, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_itemamount'), 'key' => 'oi_amount', 'width' => 50, 'linked' => false, 'style-data' => 'text-align: center;',],
                ['title' => HardcodedText::get('shopadmin_list_itemnetto'), 'key' => 'oi_price_netto', 'width' => 70, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_itemsumnetto'), 'key' => 'ges_netto', 'width' => 75, 'linked' => false,],
            ],
        ];

        $aShopadmin = $this->handleShopAdmin($CSA);

        $this->P->cb_customdata = array_merge($aPData, $aShopadmin);
    }

    /**
     * @param $CSA
     * @return array
     */
    private function handleShopAdmin($CSA)
    {
        $aSData = [];
        $aData = [];
        $getaction = filter_input(INPUT_GET, 'action');
        if ($getaction === null) {
            $bIgnoreStorno = false;

            $querybuilder = $this->dbal->createQueryBuilder();
            $querybuilder
                ->select('*')
                ->from('orders')
                ->orderBy('o_ordertimestamp', 'DESC')
            ;

            $posttype = filter_input(INPUT_POST, 'type');
            $querybuilder->where('o_ordercompleted = ?');
            if ($posttype !== null) {
                switch ($posttype) {
                    case 'closed':
                        $querybuilder->setParameter(0, 'y');
                        break;
                    case 'open':
                        $querybuilder->setParameter(0, 'n');
                        break;
                    case 'inwork':
                        $querybuilder->setParameter(0, 'i');
                        break;
                    case 'storno':
                        $querybuilder->setParameter(0, 's');
                        break;
                    case 'deleted':
                        $querybuilder->setParameter(0, 'd');
                        break;
                    case 'all':
                        $querybuilder
                            ->where('o_ordercompleted != ?')
                            ->setParameter(0, 'd')
                        ;
                        $bIgnoreStorno = true;
                        break;
                    case 'openinwork':
                    default:
                    $querybuilder
                        ->where('o_ordercompleted = ? OR o_ordercompleted = ?')
                        ->setParameter(0, 'n')
                        ->setParameter(1, 'i')
                    ;
                }
            } else {
                $querybuilder
                    ->where('o_ordercompleted = ? OR o_ordercompleted = ?')
                    ->setParameter(0, 'n')
                    ->setParameter(1, 'i')
                ;
            }

            $sFrom = null;
            $sTo = null;
            if ($posttype === 'deleted' || $posttype === 'all' || $posttype === 'closed') {
                $sFrom = filter_input(INPUT_POST, 'fromyear', FILTER_SANITIZE_NUMBER_INT).'-'
                    .Tools::dateAddLeadingZero(filter_input(INPUT_POST, 'frommonth', FILTER_SANITIZE_NUMBER_INT)).'-'
                    .Tools::dateAddLeadingZero(filter_input(INPUT_POST, 'fromday', FILTER_SANITIZE_NUMBER_INT))
                ;
                $sTo = filter_input(INPUT_POST, 'toyear', FILTER_SANITIZE_NUMBER_INT).'-'
                    .Tools::dateAddLeadingZero(filter_input(INPUT_POST, 'tomonth', FILTER_SANITIZE_NUMBER_INT)).'-'
                    .Tools::dateAddLeadingZero(filter_input(INPUT_POST, 'today', FILTER_SANITIZE_NUMBER_INT));

                $querybuilder
                    ->andWhere('o_orderdate >= :from AND o_orderdate <= :to')
                    ->setParameter(':from', $sFrom)
                    ->setParameter(':to', $sTo)
                ;
            }
            $stmt = $querybuilder->execute();

            if ($stmt->rowCount() !== 0) {
                $i = 0;
                $j = 0;
                $k = 0;
                $fGesamtnetto = 0.0;
                while ($aRow = $stmt->fetch()) {
                    switch ($aRow['o_ordercompleted']) {
                        case 'y':
                            $sStatus = '<span style="color: green; font-weight: bold;">'.HardcodedText::get('shopadmin_orderstatus_completed').'</span>';
                            break;
                        case 'n':
                            $sStatus = '<span style="color: orange; font-weight: bold;">'.HardcodedText::get('shopadmin_orderstatus_open').'</span>';
                            break;
                        case 'i':
                            $sStatus = '<span style="color: orange;">'.HardcodedText::get('shopadmin_orderstatus_inwork').'</span>';
                            break;
                        case 's':
                            $sStatus = '<span style="color: red; font-weight: bold;">'.HardcodedText::get('shopadmin_orderstatus_canceled').'</span>';
                            break;
                        case 'd':
                            $sStatus = HardcodedText::get('shopadmin_orderstatus_deleted');
                            break;
                        default:
                            $sStatus = '';
                    }

                    if ($aRow['o_paymentcompleted'] === 'y') {
                        $sZahlungsmethode = '<span style="color: green;">';
                    } else {
                        $sZahlungsmethode = '<span style="color: red;">';
                    }
                    $mZahlungsmethode = $this->serviceManager->get('textcats')->T('order_paymentmethod_' .$aRow['o_paymentmethod'], true);
                    if ($mZahlungsmethode ) {
                        $sZahlungsmethode .= $mZahlungsmethode;
                    } else {
                        $sZahlungsmethode .= ucwords($aRow['o_paymentmethod']);
                    }
                    $sZahlungsmethode .= '</span>';

                    if (trim($aRow['o_corpname']) == '') {
                        $sName = $aRow['o_name'];
                    } else {
                        $sName = $aRow['o_corpname'];
                    }

                    $aData[] = [
                        'o_id' => $aRow['o_id'],
                        'o_account_no' => $aRow['o_custno'],
                        'o_email' => $aRow['o_email'],
                        'o_cust' => $sName.'<br>'.$aRow['o_zip'].' '.$aRow['o_town'],
                        'o_authed' => $aRow['o_authed'],
                        'o_sumnettoall' => number_format(
                            $aRow['o_sumnettoall'],
                                HelperConfig::$core['numberformat_decimals'],
                                HelperConfig::$core['numberformat_decimal_point'],
                                HelperConfig::$core['numberformat_thousands_seperator']
                            )
                            .' '.HelperConfig::$shop['waehrungssymbol']
                            .(
                                ($aRow['o_mindermenge'] != 0 && $aRow['o_mindermenge'] != '')
                                    ? '<br>+'.number_format(
                                        $aRow['o_mindermenge'],
                                        HelperConfig::$core['numberformat_decimals'],
                                        HelperConfig::$core['numberformat_decimal_point'],
                                        HelperConfig::$core['numberformat_thousands_seperator']
                                    ).' '.HelperConfig::$shop['waehrungssymbol'] : ''),
                        'o_order_status' => $sStatus.((trim($aRow['o_lastedit_user']) != '') ? '<br>'.$aRow['o_lastedit_user'] : ''),
                        'o_ordertime_number' => date(
                                HelperConfig::$core['locale_format_date_time'],
                                $aRow['o_ordertimestamp']
                            )
                            .((trim($aRow['o_transaction_no']) != '') ? '<br>'.$aRow['o_transaction_no'] : ''),
                        'o_order_host_payment' => $sZahlungsmethode.'<br>'.$aRow['o_srv_hostname'],
                    ];
                    if (!($bIgnoreStorno && $aRow['o_ordercompleted'] === 's')) {
                        $fGesamtnetto += $aRow['o_sumnettoall'];
                        $j ++;
                    } else {
                        $k++;
                    }
                    $i++;
                }
                $aSData['listtable_orders'] = Tools::makeListtable($CSA['list_orders'], $aData, $this->serviceManager->get('twig'));
                $aSData['listtable_i'] = $i;
                $aSData['listtable_j'] = $j;
                $aSData['listtable_k'] = $k;
                $aSData['listtable_gesamtnetto'] = $fGesamtnetto;
            } else {
                $aSData['nomatchingordersfound'] = true;
            }
        } elseif ($getaction === 'edit') {
            $iId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $querybuilder = $this->dbal->createQueryBuilder();
            $querybuilder
                ->select('*')
                ->from('orders')
                ->where('o_id = ?')
                ->setParameter(0, $iId)
            ;
            $stmt = $querybuilder->execute();
            if ($stmt->rowCount() === 1) {
                $aSData['orderdata'] = $stmt->fetch();

                $querybuilder = $this->dbal->createQueryBuilder();
                $querybuilder
                    ->select('*')
                    ->from('orders_items')
                    ->where('oi_o_id = ?')
                    ->setParameter(0, $iId)
                ;
                $stmt = $querybuilder->execute();
                $aItems = $stmt->fetchAll();

                $aUserdata = [
                    'cust_no' => $aSData['orderdata']['o_custno'],
                    'cust_email' => $aSData['orderdata']['o_email'],
                    'cust_corp' => $aSData['orderdata']['o_corpname'],
                    'cust_name' => $aSData['orderdata']['o_name'],
                    'cust_street' => $aSData['orderdata']['o_street'],
                    'cust_zip' => $aSData['orderdata']['o_zip'],
                    'cust_town' => $aSData['orderdata']['o_town'],
                    'cust_phone' => $aSData['orderdata']['o_phone'],
                    'cust_cellphone' => $aSData['orderdata']['o_cellphone'],
                    'cust_fax' => $aSData['orderdata']['o_fax'],
                    'cust_country' => $aSData['orderdata']['o_country'],
                    'cust_group' => $aSData['orderdata']['o_group'],
                ];
                $aSData['customerform'] = \HaaseIT\HCSF\Customer\Helper::buildCustomerForm(
                    HelperConfig::$lang,
                    'shopadmin',
                    '',
                    $aUserdata
                );

                $aSData['orderdata']['options_shippingservices'] = [''];
                foreach (HelperConfig::$shop['shipping_services'] as $sValue) {
                    $aSData['orderdata']['options_shippingservices'][] = $sValue;
                }

                $aItemsCarttable = [];
                foreach ($aItems as $aValue) {
                    $aPrice = [
                        'netto_list' => $aValue['oi_price_netto_list'],
                        'netto_sale' => $aValue['oi_price_netto_sale'],
                        'netto_rebated' => $aValue['oi_price_netto_rebated'],
                        'netto_use' => $aValue['oi_price_netto_use'],
                        'brutto_use' => $aValue['oi_price_brutto_use'],
                    ];

                    $aItemsCarttable[$aValue['oi_cartkey']] = [
                        'amount' => $aValue['oi_amount'],
                        'price' => $aPrice,
                        'vat' => $aValue['oi_vat'],
                        'rg' => $aValue['oi_rg'],
                        'rg_rebate' => $aValue['oi_rg_rebate'],
                        'name' => $aValue['oi_itemname'],
                        'img' => $aValue['oi_img'],
                    ];
                }

                $aSData = array_merge(
                    \HaaseIT\HCSF\Shop\Helper::buildShoppingCartTable(
                        $aItemsCarttable,
                        true,
                        $aSData['orderdata']['o_group'],
                        '',
                        $aSData['orderdata']['o_vatfull'],
                        $aSData['orderdata']['o_vatreduced']
                    ),
                    $aSData);
            } else {
                $aSData['ordernotfound'] = true;
            }
        }

        return $aSData;
    }
}
