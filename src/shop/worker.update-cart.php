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
    if (isset($_REQUEST["amount"])) {
        $iAmount = $_REQUEST["amount"];
    } else {
        $iAmount = '';
    }

    if (isset($_REQUEST["itemno"]) && $_REQUEST["itemno"] != '' && is_numeric($iAmount)) {
        $iAmount = floor($iAmount);
        //die($_REQUEST["itemno"]);

        // Check if this item exists
        $aData = $oItem->sortItems('', $_REQUEST["itemno"]);
        if (!isset($aData)) {
            if (isset($_REQUEST["ajax"])) {
                $aAR = [
                    'cart' => $_SESSION["cart"],
                    'reply' => 'itemnotfound',
                    'cartsums' => calculateCartItems($C, $_SESSION["cart"]),
                    'currency' => $C["waehrungssymbol"],
                ];
                echo $twig->render('shop/update-cart.twig', $aAR);
            } else {
                header('Location: '.\HaaseIT\Tools::makeLinkHRefWithAddedGetVars($_SERVER["HTTP_REFERER"], array('msg' => 'item'), true, false));
            }
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
                            if (isset($_REQUEST["ajax"])) {
                                $aAR = array(
                                    'cart' => $_SESSION["cart"],
                                    'reply' => 'requiredfieldmissing',
                                    'cartsums' => calculateCartItems($C, $_SESSION["cart"]),
                                    'currency' => $C["waehrungssymbol"],
                                );
                                echo $twig->render('shop/update-cart.twig', $aAR);
                            } else {
                                header('Location: ' . $_SERVER["HTTP_REFERER"]);
                            }
                            die();
                        }
                    }
                }
            }
            // if this Items is not in cart and amount is 0, no need to do anything, return to referer
            if (!isset($_SESSION["cart"][$sCartKey]) && $iAmount == 0) {
                if (isset($_REQUEST["ajax"])) {
                    $aAR = array(
                        'cart' => $_SESSION["cart"],
                        'reply' => 'noactiontaken',
                        'cartsums' => calculateCartItems($C, $_SESSION["cart"]),
                        'currency' => $C["waehrungssymbol"],
                    );
                    echo $twig->render('shop/update-cart.twig', $aAR);
                } else {
                    header('Location: ' . $_SERVER["HTTP_REFERER"]);
                }
                die();
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
            if (isset($_SESSION["cart"][$sCartKey])) {
                if ($iAmount == 0) {
                    unset($_SESSION["cart"][$sCartKey]);
                    if (count($_SESSION["cart"]) == 0) {
                        unset($_SESSION["cartpricesums"]);
                    }
                    if (isset($_REQUEST["ajax"])) {
                        $aAR = array(
                            'cart' => $_SESSION["cart"],
                            'reply' => 'removed',
                            'subject' => $sCartKey,
                            'cartsums' => calculateCartItems($C, $_SESSION["cart"]),
                            'currency' => $C["waehrungssymbol"],
                        );
                        echo $twig->render('shop/update-cart.twig', $aAR);
                    } else {
                        header('Location: ' . \HaaseIT\Tools::makeLinkHRefWithAddedGetVars($_SERVER["HTTP_REFERER"], array('msg' => 'removed', 'cartkey' => $sCartKey), true, false));
                    }
                    die();
                } else {
                    $_SESSION["cart"][$sCartKey]["amount"] = $iAmount;
                    if (isset($_REQUEST["ajax"])) {
                        $aAR = array(
                            'cart' => $_SESSION["cart"],
                            'reply' => 'updated',
                            'item' => $sCartKey,
                            'amount' => $iAmount,
                            'cartsums' => calculateCartItems($C, $_SESSION["cart"]),
                            'currency' => $C["waehrungssymbol"],
                        );
                        echo $twig->render('shop/update-cart.twig', $aAR);
                    } else {
                        header('Location: ' . \HaaseIT\Tools::makeLinkHRefWithAddedGetVars($_SERVER["HTTP_REFERER"], array('msg' => 'updated', 'cartkey' => $sCartKey, 'amount' => $iAmount), true, false));
                    }
                    die();
                }
            } else {
                $_SESSION["cart"][$sCartKey] = $aItem;
            }
            //debug($_SESSION);
            if (isset($_REQUEST["ajax"])) {
                $aAR = array(
                    'cart' => $_SESSION["cart"],
                    'reply' => 'added',
                    'item' => $sCartKey,
                    'amount' => $iAmount,
                    'cartsums' => calculateCartItems($C, $_SESSION["cart"]),
                    'currency' => $C["waehrungssymbol"],
                );
                echo $twig->render('shop/update-cart.twig', $aAR);
            } else {
                header('Location: ' . \HaaseIT\Tools::makeLinkHRefWithAddedGetVars($_SERVER["HTTP_REFERER"], array('msg' => 'added', 'cartkey' => $sCartKey, 'amount' => $iAmount), true, false));
            }
        }
    } else {
        if (isset($_REQUEST["ajax"])) {
            $aAR = array(
                'cart' => $_SESSION["cart"],
                'reply' => 'noitemnooramount',
                'cartsums' => calculateCartItems($C, $_SESSION["cart"]),
                'currency' => $C["waehrungssymbol"],
            );
            echo $twig->render('shop/update-cart.twig', $aAR);
        } else {
            header('Location: ' . \HaaseIT\Tools::makeLinkHRefWithAddedGetVars($_SERVER["HTTP_REFERER"], array('msg' => 'amount'), true, false));
        }
    }

    die();
}
