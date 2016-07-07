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

namespace HaaseIT\HCSF\Controller\Shop;

class Myorders extends Base
{
    public function __construct($C, $DB, $sLang, $twig)
    {
        parent::__construct($C, $DB, $sLang);
        $this->twig = $twig;
    }

    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->C, $this->sLang);
        $this->P->cb_pagetype = 'content';

        if (!\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_notloggedin");
        } else {
            require_once PATH_BASEDIR . 'src/shop/functions.shoppingcart.php';

            $this->P->cb_customcontenttemplate = 'shop/myorders';

            if (isset($_GET["action"]) && $_GET["action"] == 'show' && isset($_GET["id"])) {
                $iId = \filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

                $sql = "SELECT * FROM " . 'orders WHERE o_id = :id AND o_custno = \'' . $_SESSION['user']['cust_no'] . '\' AND o_ordercompleted != \'d\'';
                $hResult = $this->DB->prepare($sql);
                $hResult->bindValue(':id', $iId);
                $hResult->execute();

                if ($hResult->rowCount() == 1) {
                    $aOrder = $hResult->fetch();

                    $this->P->cb_customdata['orderdata']['ordertimestamp'] = date($this->C["locale_format_date_time"], $aOrder["o_ordertimestamp"]);
                    $this->P->cb_customdata['orderdata']['orderremarks'] = $aOrder["o_remarks"];
                    $this->P->cb_customdata['orderdata']['paymentmethod'] = \HaaseIT\Textcat::T("order_paymentmethod_" . $aOrder["o_paymentmethod"]);
                    $this->P->cb_customdata['orderdata']['paymentcompleted'] = (($aOrder["o_paymentcompleted"] == 'y') ? \HaaseIT\Textcat::T("myorders_paymentstatus_completed") : \HaaseIT\Textcat::T("myorders_paymentstatus_open"));
                    $this->P->cb_customdata['orderdata']['orderstatus'] = \HaaseIT\HCSF\Shop\Helper::showOrderStatusText($aOrder["o_ordercompleted"]);
                    $this->P->cb_customdata['orderdata']['shippingservice'] = $aOrder["o_shipping_service"];
                    $this->P->cb_customdata['orderdata']['trackingno'] = $aOrder["o_shipping_trackingno"];

                    $sql = 'SELECT * FROM orders_items WHERE oi_o_id = :id';
                    $hResult = $this->DB->prepare($sql);
                    $hResult->bindValue(':id', $iId);
                    $hResult->execute();

                    $aItems = $hResult->fetchAll();

                    foreach ($aItems as $aValue) {
                        $aPrice = [
                            'netto_use' => $aValue["oi_price_netto_use"],
                            'brutto_use' => $aValue["oi_price_brutto_use"],
                        ];
                        $aItemsforShoppingcarttable[$aValue["oi_cartkey"]] = [
                            'amount' => $aValue["oi_amount"],
                            'price' => $aPrice,
                            'vat' => $aValue["oi_vat"],
                            //'rg' => $aValue["oi_rg"],
                            'name' => $aValue["oi_itemname"],
                            'img' => $aValue["oi_img"],
                        ];
                    }

                    $aShoppingcart = \HaaseIT\HCSF\Shop\Helper::buildShoppingCartTable(
                        $aItemsforShoppingcarttable,
                        $this->sLang,
                        $this->C,
                        true,
                        '',
                        '',
                        $aOrder["o_vatfull"],
                        $aOrder["o_vatreduced"]
                    );
                } else {
                    $this->P->cb_customdata['ordernotfound'] = true;
                }
            } else {
                $COList = [
                    ['title' => \HaaseIT\Textcat::T("order_head_orderdate"), 'key' => 'o_ordertime', 'width' => 110, 'linked' => false,],
                    ['title' => \HaaseIT\Textcat::T("order_head_paymenthethod"), 'key' => 'o_paymentmethod', 'width' => 125, 'linked' => false,],
                    ['title' => \HaaseIT\Textcat::T("order_head_paid"), 'key' => 'o_paymentcompleted', 'width' => 60, 'linked' => false,],
                    ['title' => \HaaseIT\Textcat::T("order_head_status"), 'key' => 'o_order_status', 'width' => 80, 'linked' => false,],
                    ['title' => \HaaseIT\Textcat::T("order_head_shipping_service"), 'key' => 'o_shipping_service', 'width' => 90, 'linked' => false,],
                    ['title' => \HaaseIT\Textcat::T("order_head_shipping_trackingno"), 'key' => 'o_shipping_trackingno', 'width' => 130, 'linked' => false,],
                    [
                        'title' => \HaaseIT\Textcat::T("order_show"),
                        'key' => 'o_id',
                        'width' => 120,
                        'linked' => true,
                        'ltarget' => '/_misc/myorders.html',
                        'lkeyname' => 'id',
                        'lgetvars' => ['action' => 'show',],
                    ],
                ];

                $this->P->cb_customdata['listmyorders'] = $this->showMyOrders($COList, $this->twig, $this->DB);
            }

            if (isset($aShoppingcart)) {
                $this->P->cb_customdata['shoppingcart'] = $aShoppingcart['shoppingcart'];
            }
        }
    }

    private function showMyOrders($COList)
    {
        $sH = '';
        $sql = 'SELECT * FROM orders WHERE o_custno = :custno ORDER BY o_ordertimestamp DESC';

        $hResult = $this->DB->prepare($sql);
        $hResult->bindValue(':custno', \HaaseIT\HCSF\Customer\Helper::getUserData('cust_no'));
        $hResult->execute();

        if ($hResult->rowCount() >= 1) {
            while ($aRow = $hResult->fetch()) {
                $sStatus = self::showOrderStatusText($aRow["o_ordercompleted"]);

                if ($aRow["o_paymentmethod"] == 'prepay') $sPaymentmethod = \HaaseIT\Textcat::T("order_paymentmethod_prepay");
                elseif ($aRow["o_paymentmethod"] == 'paypal') $sPaymentmethod = \HaaseIT\Textcat::T("order_paymentmethod_paypal");
                elseif ($aRow["o_paymentmethod"] == 'debit') $sPaymentmethod = \HaaseIT\Textcat::T("order_paymentmethod_debit");
                elseif ($aRow["o_paymentmethod"] == 'invoice') $sPaymentmethod = \HaaseIT\Textcat::T("order_paymentmethod_invoice");
                else $sPaymentmethod = ucwords($aRow["o_paymentmethod"]);

                if ($aRow["o_paymentcompleted"] == 'y') $sPaymentstatus = ucwords(\HaaseIT\Textcat::T("misc_yes"));
                else $sPaymentstatus = ucwords(\HaaseIT\Textcat::T("misc_no"));

                $aData[] = [
                    'o_id' => $aRow["o_id"],
                    'o_order_status' => $sStatus,
                    'o_ordertime' => date($this->C['locale_format_date_time'], $aRow["o_ordertimestamp"]),
                    'o_paymentmethod' => $sPaymentmethod,
                    'o_paymentcompleted' => $sPaymentstatus,
                    'o_shipping_service' => $aRow["o_shipping_service"],
                    'o_shipping_trackingno' => $aRow["o_shipping_trackingno"],
                ];
            }
            $sH .= \HaaseIT\Tools::makeListtable($COList, $aData, $this->twig);
        } else $sH .= \HaaseIT\Textcat::T("myorders_no_orders_to_display");

        return $sH;
    }

}