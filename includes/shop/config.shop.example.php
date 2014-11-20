<?php

define("DB_ITEMTABLE_BASE", 'item_base');
define("DB_ITEMTABLE_BASE_PKEY", 'itm_id');
define("DB_ITEMFIELD_NUMBER", 'itm_no');
define("DB_ITEMFIELD_NAME", 'itm_name');
define("DB_ITEMFIELD_GROUP", 'itm_group');
define("DB_ITEMFIELD_INDEX", 'itm_index');
define("DB_ITEMFIELD_PRICE", 'itm_price');
define("DB_ITEMFIELD_VAT", 'itm_vatid');
define("DB_ITEMFIELD_RG", 'itm_rg');
define("DB_ITEMFIELD_ORDER", 'itm_order');
define("DB_ITEMFIELD_IMG", 'itm_img');
define("DB_ITEMFIELD_DATA", 'itm_data');

define("DB_ITEMTABLE_TEXT", 'item_lang');
define("DB_ITEMTABLE_TEXT_PKEY", 'itml_id');
define("DB_ITEMTABLE_TEXT_PARENTPKEY", 'itml_pid');
define("DB_ITEMFIELD_LANGUAGE", 'itml_lang');
define("DB_ITEMFIELD_NAME_OVERRIDE", 'itml_name_override');
define("DB_ITEMFIELD_TEXT1", 'itml_text1');
define("DB_ITEMFIELD_TEXT2", 'itml_text2');
define("DB_ITEMFIELDS", 'itm_no, itm_name, itm_price, itm_vatid, itm_rg, itm_img, itm_group, itm_data, itml_name_override, itml_text1, itml_text2, itm_index');

define("DB_ITEMGROUPTABLE_BASE", 'itemgroups_base');
define("DB_ITEMGROUPTABLE_BASE_PKEY", 'itmg_id');
define("DB_ITEMGROUPFIELD_NUMBER", 'itmg_no');
define("DB_ITEMGROUPFIELD_NAME", 'itmg_name');
define("DB_ITEMGROUPFIELD_IMG", 'itmg_img');

define("DB_ITEMGROUPTABLE_TEXT", 'itemgroups_text');
define("DB_ITEMGROUPTABLE_TEXT_PKEY", 'itmgt_id');
define("DB_ITEMGROUPTABLE_TEXT_PARENTPKEY", 'itmgt_pid');
define("DB_ITEMGROUPFIELD_SHORTTEXT", 'itmgt_shorttext');
define("DB_ITEMGROUPFIELD_DETAILS", 'itmgt_details');
define("DB_ITEMGROUPFIELD_LANGUAGE", 'itmgt_lang');
define("DB_ITEMGROUPFIELDS", 'itmg_no, itmg_name, itmg_imgsm, itmg_imglg, itmgt_shorttext, itmgt_details');

define("DB_ORDERTABLE", 'orders');
define("DB_ORDERTABLE_PKEY", 'o_id');
define("DB_ORDERFIELD_PAYMENTMETHOD", 'o_paymentmethod');
define("DB_ORDERTABLE_ITEMS", 'orders_items');

define("PATH_ORDERLOG", '/_admin/orderlogs/');

$TMP = array(
    'email_orderconfirmation_attachment_cancellationform_de' => '',
    'email_orderconfirmation_embed_itemimages' => true,

    'items_orderdirection_default' => 'ASC',

    'paypal' => array('business' => 'paypalseller@domain.tld', 'url' => 'https://www.sandbox.paypal.com/de/cgi-bin/webscr', 'auth_token' => 'XXXXXXXXXXXXX'),
    'paypal_notify' => 'http://'.$_SERVER["HTTP_HOST"].'/_misc/paypal_notify.html',
    'paypal_return' => 'http://'.$_SERVER["HTTP_HOST"].'/_misc/paypal_return.html',
    'paypal_log' => '/_admin/ipnlogs/ipnlog.txt',
    'paypal_interactive' => true,

    'paymentmethods' => array(
        'prepay',
        'paypal',
        // 'debit',
        // 'invoice',
    ),

    'shipping_services' => array(
        'DHL',
        'UPS',
        'DPD',
        'GLS',
    ),

    'orderamounts' => array(1,2,3,4,5,6,7,8,9,10),

    'show_pricesonlytologgedin' => false,

    'custom_order_fields' => array(
        'size'
    ),

    'itemdetail_suggestions' => 8, // set to 0 to disable

    'minimumorderamountnet' => 0,
    'reducedorderamountnet1' => 0,
    'reducedorderamountnet2' => 0,
    'reducedorderamountfee1' => 0,
    'reducedorderamountfee2' => 0,
    'minimumamountforfreeshipping' => 0,
    'waehrungssymbol' => '&euro;',
    'shippingcoststandardrate' => 6.713,
    'shippingcosts' => array(
        array(
            'cost' => 3.35,
            'countries' => array(
                'DE' => 'DE'
            ),
        ),
        array(
            'cost' => 4.19,
            'countries' => array(
                'BE' => 'BE',
                'FR' => 'FR',
                'LI' => 'LI',
                'LU' => 'LU',
                'MC' => 'MC',
                'NL' => 'NL',
                'CH' => 'CH',
                'AT' => 'AT',
                'GG' => 'GG',
                'JE' => 'JE',
                'AX' => 'AX',
                'DK' => 'DK',
                'EE' => 'EE',
                'FI' => 'FI',
                'FO' => 'FO',
                'GG' => 'GG',
                'IE' => 'IE',
                'IS' => 'IS',
                'IM' => 'IM',
                'JE' => 'JE',
                'LV' => 'LV',
                'LT' => 'LT',
                'NO' => 'NO',
                'SE' => 'SE',
                'SJ' => 'SJ',
                'GB' => 'GB',
                'BW' => 'BW',
                'LS' => 'LS',
                'NA' => 'NA',
                'SZ' => 'SZ',
                'ZA' => 'ZA',
                'BY' => 'BY',
                'BG' => 'BG',
                'PL' => 'PL',
                'MD' => 'MD',
                'RO' => 'RO',
                'RU' => 'RU',
                'SK' => 'SK',
                'CZ' => 'CZ',
                'UA' => 'UA',
                'HU' => 'HU',
                'AL' => 'AL',
                'AD' => 'AD',
                'BA' => 'BA',
                'GI' => 'GI',
                'GR' => 'GR',
                'IT' => 'IT',
                'HR' => 'HR',
                'MT' => 'MT',
                'MK' => 'MK',
                'ME' => 'ME',
                'PT' => 'PT',
                'SM' => 'SM',
                'RS' => 'RS',
                'CS' => 'CS',
                'SI' => 'SI',
                'ES' => 'ES',
                'VA' => 'VA',
            ),
        ),
    ),
    'rebate_groups' => array(
        '01' => array(
            'grosskunde' => 10,
            'wiederverkaeufer' => 15,
        ),
        '02' => array(
            'grosskunde' => 7,
            'wiederverkaeufer' => 11,
        ),
    ),

    'vat_disable' => true,
    'vat' => array( // default vat of country first!!
        '19' => '0',
        '7' => '0',
    ),
);

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
            'ltarget' => $_SERVER["PHP_SELF"],
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

$C = array_merge($C, $TMP);
unset($TMP);
