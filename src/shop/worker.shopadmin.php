<?php

require_once __DIR__.'/../../src/shop/functions.admin.shop.php';
require_once __DIR__.'/../../src/shop/functions.shoppingcart.php';

$P = new \HaaseIT\HCSF\CorePage($C, $sLang);
$P->cb_pagetype = 'content';
$P->cb_subnav = 'admin';
$P->cb_customcontenttemplate = 'shop/shopadmin';

$sH = '';

if (isset($_POST["change"])) {
    $iID = filter_var(trim(\HaaseIT\Tools::getFormfield("id")), FILTER_SANITIZE_NUMBER_INT);
    $aData = array(
        'o_lastedit_timestamp' => time(),
        'o_remarks_internal' => filter_var(trim(\HaaseIT\Tools::getFormfield("remarks_internal")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
        'o_transaction_no' => filter_var(trim(\HaaseIT\Tools::getFormfield("transaction_no")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
        'o_paymentcompleted' => filter_var(trim(\HaaseIT\Tools::getFormfield("order_paymentcompleted")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
        'o_ordercompleted' => filter_var(trim(\HaaseIT\Tools::getFormfield("order_completed")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
        'o_lastedit_user' => ((isset($_SERVER["REMOTE_USER"])) ? $_SERVER["REMOTE_USER"] : ''),
        'o_shipping_service' => filter_var(trim(\HaaseIT\Tools::getFormfield("order_shipping_service")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
        'o_shipping_trackingno' => filter_var(trim(\HaaseIT\Tools::getFormfield("order_shipping_trackingno")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
        'o_id' => $iID,
    );

    $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_ORDERTABLE, 'o_id');
    //HaaseIT\Tools::debug($sQ);
    $hResult = $DB->prepare($sQ);
    foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
    $hResult->execute();
    header('Location: /_admin/shopadmin.html?action=edit&id='.$iID);
    die();
}

$aPData = [
    'searchform_type' => \HaaseIT\Tools::getFormfield('type', 'openinwork'),
    'searchform_fromday' => \HaaseIT\Tools::getFormfield('fromday', '01'),
    'searchform_frommonth' => \HaaseIT\Tools::getFormfield('frommonth', '01'),
    'searchform_fromyear' => \HaaseIT\Tools::getFormfield('fromyear', '2014'),
    'searchform_today' => \HaaseIT\Tools::getFormfield('today', date("d")),
    'searchform_tomonth' => \HaaseIT\Tools::getFormfield('tomonth', date("m")),
    'searchform_toyear' => \HaaseIT\Tools::getFormfield('toyear', date("Y")),
];

$CSA = array(
    'list_orders' => array(
        array('title' => 'Besteller', 'key' => 'o_cust', 'width' => 280, 'linked' => false,),
        array('title' => 'Netto', 'key' => 'o_sumnettoall', 'width' => 75, 'linked' => false,),
        array('title' => 'Status', 'key' => 'o_order_status', 'width' => 80, 'linked' => false,),
        array('title' => 'Zeit/VorgNr', 'key' => 'o_ordertime_number', 'width' => 100, 'linked' => false,),
        array('title' => '', 'key' => 'o_order_host_payment', 'width' => 140, 'linked' => false,),
        array(
            'title' => 'bearb.',
            'key' => 'o_id',
            'width' => 45,
            'linked' => true,
            'ltarget' => '/_admin/shopadmin.html',
            'lkeyname' => 'id',
            'lgetvars' => array(
                'action' => 'edit',
            ),
        ),
    ),
    'list_orderitems' => array(
        array('title' => 'Art Nr', 'key' => 'oi_itemno', 'width' => 95, 'linked' => false,),
        array('title' => 'Art Name', 'key' => 'oi_itemname', 'width' => 350, 'linked' => false,),
        array('title' => 'Menge', 'key' => 'oi_amount', 'width' => 50, 'linked' => false, 'style-data' => 'text-align: center;',),
        array('title' => 'Netto', 'key' => 'oi_price_netto', 'width' => 70, 'linked' => false,),
        array('title' => 'Ges. Netto', 'key' => 'ges_netto', 'width' => 75, 'linked' => false,),
    ),
);

$aShopadmin = handleShopAdmin($CSA, $twig, $DB, $C, $sLang);

$P->cb_customdata = array_merge($aPData, $aShopadmin);

$sH .= $aShopadmin["html"];

$P->oPayload->cl_html = $sH;
unset($sH);
