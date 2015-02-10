<?php

/*
    Contanto - A modular CMS and Shopsystem
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

define("PATH_ORDERLOG", PATH_DOCROOT.'_admin/orderlogs/');
define("PATH_PAYPALLOG", PATH_DOCROOT.'_admin/ipnlogs/');
define("FILE_PAYPALLOG", PATH_DOCROOT.'ipnlog.txt');

$TMP = array(
    'email_orderconfirmation_attachment_cancellationform_de' => '',
    'email_orderconfirmation_embed_itemimages' => true,

    'items_orderdirection_default' => 'ASC',

    'paypal' => array(
        'business' => 'paypalseller@domain.tld',
        'url' => 'https://www.sandbox.paypal.com/de/cgi-bin/webscr',
        'auth_token' => 'XXXXXXXXXXXXX',
        'currency_id' => 'EUR',
    ),
    'paypal_notify' => 'http://'.$_SERVER["HTTP_HOST"].'/_misc/paypal_notify.html',
    'paypal_return' => 'http://'.$_SERVER["HTTP_HOST"].'/_misc/paypal_return.html',
    'paypal_interactive' => true,

    'sofortueberweisung' => array(
        'user_id' => '27471',
        'project_id' => '83464',
        'currency_id' => 'EUR',
    ),

    'paymentmethods' => array(
        'prepay',
        'paypal',
        //'sofortueberweisung',
        // 'debit',
        // 'invoice',
    ),
    'interactive_paymentmethods_redirect_immediately' => false,

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
        'full' => 19,
        'reduced' => 7,
        // 'none' => 0, // if vat is disabled please uncomment this!
    ),
);

if (isset($TMP["vat_disable"]) && $TMP["vat_disable"]) {
    $TMP["vat"] = array("full" => 0, "reduced" => 0);
}

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
