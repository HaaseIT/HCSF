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

$P = array(
    'base' => array(
        'cb_pagetype' => 'content',
        'cb_pageconfig' => '',
        'cb_subnav' => '',
    ),
    'lang' => array(
        'cl_lang' => $sLang,
        'cl_html' => '',
    ),
);

if (($C["show_pricesonlytologgedin"] && !getUserData()) || !isset($_SERVER["HTTP_REFERER"])) {
    $P = array(
        'base' => array(
            'cb_pagetype' => 'content',
            'cb_pageconfig' => '',
            'cb_subnav' => '',
        ),
        'lang' => array(
            'cl_lang' => $sLang,
            'cl_html' => \HaaseIT\Textcat::T("denied_default"),
        ),
    );
} else {
    function replyToCartUpdate($C, $twig, $sReply, $aMore = []) {
        if (isset($_REQUEST["ajax"])) {
            $aAR = [
                'cart' => $_SESSION["cart"],
                'reply' => $sReply,
                'cartsums' => calculateCartItems($C, $_SESSION["cart"]),
                'currency' => $C["waehrungssymbol"],
            ];
            if (count($aMore)) $aAR = array_merge($aAR, $aMore);
            echo $twig->render('shop/update-cart.twig', $aAR);
        } else {
            $aMSG["msg"] =  $sReply;
            if (count($aMore)) $aMSG = array_merge($aMSG, $aMore);
            header('Location: '.\HaaseIT\Tools::makeLinkHRefWithAddedGetVars($_SERVER["HTTP_REFERER"], $aMSG, true, false));
        }
        die();
    }

    $iAmount = '';
    if (isset($_REQUEST["amount"])) {
        $iAmount = $_REQUEST["amount"];
    }

    if (!isset($_REQUEST["itemno"]) || $_REQUEST["itemno"] == '' || !is_numeric($iAmount)) {
        replyToCartUpdate($C, $twig, 'noitemnooramount');
    } else {
        $iAmount = floor($iAmount);
        //die($_REQUEST["itemno"]);

        // Check if this item exists
        $aData = $oItem->sortItems('', $_REQUEST["itemno"]);
        if (!isset($aData)) {
            replyToCartUpdate($C, $twig, 'itemnotfound');
        } else {
            // build the key for this item for the shoppingcart
            $sItemno = $aData["item"][$_REQUEST["itemno"]][DB_ITEMFIELD_NUMBER];
            $sCartKey = $sItemno;

            if (isset($C["custom_order_fields"])) {
                foreach ($C["custom_order_fields"] as $sValue) {
                    if (isset($aData["item"][$sItemno]["itm_data"][$sValue])) {
                        $aOptions = array();
                        $TMP = explode('|', $aData["item"][$sItemno]["itm_data"][$sValue]);
                        foreach ($TMP as $sTMPValue) {
                            if (trim($sTMPValue) != '') {
                                $aOptions[] = $sTMPValue;
                            }
                        }
                        unset($sTMP);

                        if (isset($_REQUEST[$sValue]) && in_array($_REQUEST[$sValue], $aOptions)) {
                            $sCartKey .= '|' . $sValue . ':' . $_REQUEST[$sValue];
                        } else {
                            replyToCartUpdate($C, $twig, 'requiredfieldmissing');
                        }
                    }
                }
            }
            // if this Items is not in cart and amount is 0, no need to do anything, return to referer
            if (!isset($_SESSION["cart"][$sCartKey]) && $iAmount == 0) {
                replyToCartUpdate($C, $twig, 'noactiontaken');
            }
            $aItem = array(
                'amount' => $iAmount,
                'price' => $oItem->calcPrice($aData["item"][$sItemno]),
                'rg' => $aData["item"][$sItemno][DB_ITEMFIELD_RG],
                'vat' => $aData["item"][$sItemno][DB_ITEMFIELD_VAT],
                'name' => $aData["item"][$sItemno][DB_ITEMFIELD_NAME],
                'img' => $aData["item"][$sItemno][DB_ITEMFIELD_IMG],
            );
            //debug($aItem);
            if (isset($_SESSION["cart"][$sCartKey])) { // if this item is already in cart, update amount
                if ($iAmount == 0) { // new amount == 0 -> remove from cart
                    unset($_SESSION["cart"][$sCartKey]);
                    if (count($_SESSION["cart"]) == 0) { // once the last cart item is unset, we no longer need cartpricesums
                        unset($_SESSION["cartpricesums"]);
                    }
                    replyToCartUpdate($C, $twig, 'removed', ['cartkey' => $sCartKey]);
                } else { // update amount
                    $_SESSION["cart"][$sCartKey]["amount"] = $iAmount;
                    replyToCartUpdate($C, $twig, 'updated', ['cartkey' => $sCartKey, 'amount' => $iAmount]);
                }
            } else { // if this item is not in the cart yet, add it
                $_SESSION["cart"][$sCartKey] = $aItem;
            }
            //debug($_SESSION);
            replyToCartUpdate($C, $twig, 'added', ['cartkey' => $sCartKey, 'amount' => $iAmount]);
        }
    }
    die();
}
