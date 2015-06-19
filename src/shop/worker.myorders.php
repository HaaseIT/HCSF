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

$P = new \HaaseIT\HCSF\CorePage($C, $sLang);
$P->cb_pagetype = 'content';

if (!getUserData()) {
    $P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_notloggedin");
} else {
    require_once __DIR__ . '/../../src/shop/functions.shoppingcart.php';

    $P->cb_customcontenttemplate = 'shop/myorders';

    if (isset($_GET["action"]) && $_GET["action"] == 'show' && isset($_GET["id"])) {
        $iId = \filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

        $sQ = "SELECT * FROM " . DB_ORDERTABLE . " WHERE o_id = :id AND o_custno = '" . $_SESSION["user"]["cust_no"] . "' AND o_ordercompleted != 'd'";
        $hResult = $DB->prepare($sQ);
        $hResult->bindValue(':id', $iId);
        $hResult->execute();

        if ($hResult->rowCount() == 1) {
            $aOrder = $hResult->fetch();
            $P->oPayload->cl_html = '<h1>' . \HaaseIT\Textcat::T("myorders_headline_order") . ' ' . date($C["locale_format_date_time"], $aOrder["o_ordertimestamp"]) . ':</h1><br>';
            if (trim($aOrder["o_remarks"]) != '') {
                $P->oPayload->cl_html .= '<strong>' . \HaaseIT\Textcat::T("myorders_remarks") . '</strong><br>' . $aOrder["o_remarks"] . '<br><br>';
            }
            $P->oPayload->cl_html .= '<strong>Zahlungsmethode:</strong> ' . \HaaseIT\Textcat::T("order_paymentmethod_" . $aOrder["o_paymentmethod"]) . '<br>';
            $P->oPayload->cl_html .= '<strong>' . \HaaseIT\Textcat::T("myorders_paymentstatus") . '</strong> ' . (($aOrder["o_paymentcompleted"] == 'y') ? \HaaseIT\Textcat::T("myorders_paymentstatus_completed") : \HaaseIT\Textcat::T("myorders_paymentstatus_open")) . '<br>';
            $P->oPayload->cl_html .= '<strong>' . \HaaseIT\Textcat::T("myorders_orderstatus") . '</strong> ' . showOrderStatusText($aOrder["o_ordercompleted"]) . '<br>';
            if (trim($aOrder["o_shipping_service"]) != '') {
                $P->oPayload->cl_html .= '<strong>' . \HaaseIT\Textcat::T("myorders_shipping_service") . '</strong> ' . $aOrder["o_shipping_service"] . '<br>';
            }
            if (trim($aOrder["o_shipping_trackingno"]) != '') {
                $P->oPayload->cl_html .= '<strong>' . \HaaseIT\Textcat::T("myorders_shipping_trackingno") . '</strong> ' . $aOrder["o_shipping_trackingno"] . '<br>';
            }
            $P->oPayload->cl_html .= '<br>';

            $sQ = "SELECT * FROM " . DB_ORDERTABLE_ITEMS . " WHERE oi_o_id = :id";
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':id', $iId);
            $hResult->execute();
            //debug($DB->numRows($hResult));

            $aItems = $hResult->fetchAll();

            foreach ($aItems as $aValue) {
                $aPrice = array(
                    'netto_use' => $aValue["oi_price_netto_use"],
                    'brutto_use' => $aValue["oi_price_brutto_use"],
                );
                $aItemsforShoppingcarttable[$aValue["oi_cartkey"]] = array(
                    'amount' => $aValue["oi_amount"],
                    'price' => $aPrice,
                    'vat' => $aValue["oi_vat"],
                    //'rg' => $aValue["oi_rg"],
                    'name' => $aValue["oi_itemname"],
                    'img' => $aValue["oi_img"],
                );
            }

            $aShoppingcart = buildShoppingCartTable(
                $aItemsforShoppingcarttable,
                $sLang,
                $C,
                true,
                '',
                '',
                $aOrder["o_vatfull"],
                $aOrder["o_vatreduced"]
            );
        } else {
            $P->oPayload->cl_html = \HaaseIT\Textcat::T("myorders_order_not_found");
        }
    } else {
        $COList = array(
            array('title' => \HaaseIT\Textcat::T("order_head_orderdate"), 'key' => 'o_ordertime', 'width' => 110, 'linked' => false,),
            array('title' => \HaaseIT\Textcat::T("order_head_paymenthethod"), 'key' => 'o_paymentmethod', 'width' => 125, 'linked' => false,),
            array('title' => \HaaseIT\Textcat::T("order_head_paid"), 'key' => 'o_paymentcompleted', 'width' => 60, 'linked' => false,),
            array('title' => \HaaseIT\Textcat::T("order_head_status"), 'key' => 'o_order_status', 'width' => 80, 'linked' => false,),
            array('title' => \HaaseIT\Textcat::T("order_head_shipping_service"), 'key' => 'o_shipping_service', 'width' => 90, 'linked' => false,),
            array('title' => \HaaseIT\Textcat::T("order_head_shipping_trackingno"), 'key' => 'o_shipping_trackingno', 'width' => 130, 'linked' => false,),
            array(
                'title' => \HaaseIT\Textcat::T("order_show"),
                'key' => 'o_id',
                'width' => 120,
                'linked' => true,
                'ltarget' => '/_misc/myorders.html',
                'lkeyname' => 'id',
                'lgetvars' => array('action' => 'show',),
            ),
        );

        $P->oPayload->cl_html = showMyOrders($COList, $twig, $DB);
    }

    if (isset($aShoppingcart)) {
        $P->cb_customdata = $aShoppingcart;
    }
}
