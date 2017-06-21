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

use HaaseIT\HCSF\HelperConfig;

/**
 * Class Updatecart
 * @package HaaseIT\HCSF\Controller\Shop
 */
class Updatecart extends Base
{
    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        if (
            (
                HelperConfig::$shop['show_pricesonlytologgedin']
                && !\HaaseIT\HCSF\Customer\Helper::getUserData()
            )
            || !isset($_SERVER['HTTP_REFERER'])
        ) {
            $this->P->oPayload->cl_html = $this->serviceManager->get('textcats')->T('denied_default');
        } else {
            $iAmount = '';
            if (isset($_REQUEST['amount'])) {
                $iAmount = $_REQUEST['amount'];
            }

            if (empty($_REQUEST['itemno']) || !is_numeric($iAmount)) {
                $this->replyToCartUpdate('noitemnooramount');
            } else {
                $iAmount = floor($iAmount);

                // Check if this item exists
                $aData = $this->serviceManager->get('oItem')->sortItems('', $_REQUEST['itemno']);
                if (!isset($aData)) {
                    $this->replyToCartUpdate('itemnotfound');
                } else {
                    // are there additional items to this item, if so, check if they are valid, too.
                    if (!empty($_POST['additionalitems'])) {
                        $additionalitems = filter_input(INPUT_POST, 'additionalitems', FILTER_SANITIZE_SPECIAL_CHARS);

                        if (strpos($additionalitems, '~') !== false) {
                            $additionalitems = explode('~', $additionalitems);
                        } else {
                            $additionalitems = [$additionalitems];
                        }

                        $additionaldata = $this->serviceManager->get('oItem')->sortItems('', $additionalitems);

                        if (count($additionalitems) != count($additionaldata['item'])) {
                            $this->replyToCartUpdate('itemnotfound');
                        }
                    }

                    // build the key for this item for the shoppingcart
                    $sItemno = $aData['item'][$_REQUEST['itemno']]['itm_no'];
                    $sCartKey = $sItemno;

                    if (isset(HelperConfig::$shop['custom_order_fields'])) {
                        foreach (HelperConfig::$shop['custom_order_fields'] as $sValue) {
                            if (isset($aData['item'][$sItemno]['itm_data'][$sValue])) {
                                $aOptions = [];
                                $TMP = explode('|', $aData['item'][$sItemno]['itm_data'][$sValue]);
                                foreach ($TMP as $sTMPValue) {
                                    if (trim($sTMPValue) != '') {
                                        $aOptions[] = $sTMPValue;
                                    }
                                }
                                unset($sTMP);

                                if (isset($_REQUEST[$sValue]) && in_array($_REQUEST[$sValue], $aOptions)) {
                                    $sCartKey .= '|' . $sValue . ':' . $_REQUEST[$sValue];
                                } else {
                                    $this->replyToCartUpdate('requiredfieldmissing');
                                }
                            }
                        }
                    }
                    // if this Items is not in cart and amount is 0, no need to do anything, return to referer
                    if ($iAmount == 0 && !isset($_SESSION['cart'][$sCartKey])) {
                        $this->replyToCartUpdate('noactiontaken');
                    }
                    $aItem = [
                        'amount' => $iAmount,
                        'price' => $this->serviceManager->get('oItem')->calcPrice($aData['item'][$sItemno]),
                        'rg' => $aData['item'][$sItemno]['itm_rg'],
                        'vat' => $aData['item'][$sItemno]['itm_vatid'],
                        'name' => $aData['item'][$sItemno]['itm_name'],
                        'img' => $aData['item'][$sItemno]['itm_img'],
                    ];
                    if (!empty($_POST['action']) && $_POST['action'] === 'add') {
                        $this->addItemToCart($sCartKey, $aItem);

                        if (!empty($additionalitems)) {
                            foreach ($additionalitems as $additionalitem) {
                                $this->addItemToCart(
                                    $additionalitem,
                                    [
                                        'amount' => $iAmount,
                                        'price' => $this->serviceManager->get('oItem')->calcPrice($additionaldata['item'][$additionalitem]),
                                        'rg' => $additionaldata['item'][$additionalitem]['itm_rg'],
                                        'vat' => $additionaldata['item'][$additionalitem]['itm_vatid'],
                                        'name' => $additionaldata['item'][$additionalitem]['itm_name'],
                                        'img' => $additionaldata['item'][$additionalitem]['itm_img'],
                                    ]
                                );
                            }
                        }
                    } else {
                        if (isset($_SESSION['cart'][$sCartKey])) { // if this item is already in cart, update amount
                            if ($iAmount == 0) { // new amount == 0 -> remove from cart
                                unset($_SESSION['cart'][$sCartKey]);
                                if (count($_SESSION['cart']) == 0) { // once the last cart item is unset, we no longer need cartpricesums
                                    unset($_SESSION['cartpricesums']);
                                }
                                $this->replyToCartUpdate('removed', ['cartkey' => $sCartKey]);
                            } else { // update amount
                                $_SESSION['cart'][$sCartKey]['amount'] = $iAmount;
                                $this->replyToCartUpdate('updated', ['cartkey' => $sCartKey, 'amount' => $iAmount]);
                            }
                        } else { // if this item is not in the cart yet, add it
                            $_SESSION['cart'][$sCartKey] = $aItem;
                        }
                    }
                    $this->replyToCartUpdate('added', ['cartkey' => $sCartKey, 'amount' => $iAmount]);
                }
            }
            \HaaseIT\HCSF\Helper::terminateScript();
        }
    }

    /**
     * @param string $sReply
     * @param array $aMore
     */
    private function replyToCartUpdate($sReply, $aMore = []) {
        if (isset($_REQUEST['ajax'])) {
            $aAR = [
                'cart' => $_SESSION['cart'],
                'reply' => $sReply,
                'cartsums' => \HaaseIT\HCSF\Shop\Helper::calculateCartItems($_SESSION['cart']),
                'currency' => HelperConfig::$shop['waehrungssymbol'],
                'numberformat_decimals' => HelperConfig::$core['numberformat_decimals'],
                'numberformat_decimal_point' => HelperConfig::$core['numberformat_decimal_point'],
                'numberformat_thousands_seperator' => HelperConfig::$core['numberformat_thousands_seperator'],
            ];
            if (count($aMore)) {
                $aAR = array_merge($aAR, $aMore);
            }
            echo $this->serviceManager->get('twig')->render('shop/update-cart.twig', $aAR);
        } else {
            $aMSG['msg'] =  $sReply;
            if (count($aMore)) {
                $aMSG = array_merge($aMSG, $aMore);
            }
            header('Location: '.\HaaseIT\Toolbox\Tools::makeLinkHRefWithAddedGetVars($_SERVER['HTTP_REFERER'], $aMSG, true, false));
        }
        \HaaseIT\HCSF\Helper::terminateScript();
    }

    protected function addItemToCart($cartkey, $item)
    {
        if (isset($_SESSION['cart'][$cartkey])) { // if this item is already in cart, add to amount
            $_SESSION['cart'][$cartkey]['amount'] += $item['amount'];
        } else {
            $_SESSION['cart'][$cartkey] = $item;
        }

        return true;
    }
}