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
                $this->config->getShop('show_pricesonlytologgedin')
                && !\HaaseIT\HCSF\Customer\Helper::getUserData()
            )
            || filter_input(INPUT_SERVER, 'HTTP_REFERER') === null
        ) {
            $this->P->oPayload->cl_html = $this->serviceManager->get('textcats')->T('denied_default');
        } else {
            $iAmount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_INT);
            $postitemno = filter_input(INPUT_POST, 'itemno', FILTER_SANITIZE_SPECIAL_CHARS);

            if (empty($postitemno) || !is_numeric($iAmount)) {
                $this->replyToCartUpdate('noitemnooramount');
            } else {
                $iAmount = floor($iAmount);

                // Check if this item exists
                $aData = $this->serviceManager->get('oItem')->sortItems('', $postitemno);
                if (!isset($aData)) {
                    $this->replyToCartUpdate('itemnotfound');
                } else {
                    // are there additional items to this item, if so, check if they are valid, too.
                    $postadditionalitems = filter_input(INPUT_POST, 'additionalitems', FILTER_SANITIZE_SPECIAL_CHARS);
                    if (!empty($postadditionalitems)) {

                        if (strpos($postadditionalitems, '~') !== false) {
                            $postadditionalitems = explode('~', $postadditionalitems);
                        } else {
                            $postadditionalitems = [$postadditionalitems];
                        }

                        $additionaldata = $this->serviceManager->get('oItem')->sortItems('', $postadditionalitems);

                        if (count($postadditionalitems) != count($additionaldata['item'])) {
                            $this->replyToCartUpdate('itemnotfound');
                        }
                    }

                    // build the key for this item for the shoppingcart
                    $sItemno = $aData['item'][$postitemno]['itm_no'];
                    $sCartKey = $sItemno;

                    if (!empty($this->config->getShop('custom_order_fields'))) {
                        foreach ($this->config->getShop('custom_order_fields') as $sValue) {
                            if (isset($aData['item'][$sItemno]['itm_data'][$sValue])) {
                                $aOptions = [];
                                $TMP = explode('|', $aData['item'][$sItemno]['itm_data'][$sValue]);
                                foreach ($TMP as $sTMPValue) {
                                    if (!empty($sTMPValue)) {
                                        $aOptions[] = $sTMPValue;
                                    }
                                }
                                unset($sTMP);

                                $currentpost = filter_input(INPUT_POST, $sValue);
                                if ($currentpost !== null && in_array($currentpost, $aOptions)) {
                                    $sCartKey .= '|'.$sValue.':'.$currentpost;
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

                    if (filter_input(INPUT_POST, 'action') === 'add') {
                        $this->addItemToCart($sCartKey, $aItem);

                        if (!empty($postadditionalitems)) {
                            foreach ($postadditionalitems as $additionalitem) {
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
        if (filter_input(INPUT_GET, 'ajax') !== null) {
            $aAR = [
                'cart' => $_SESSION['cart'],
                'reply' => $sReply,
                'cartsums' => \HaaseIT\HCSF\Shop\Helper::calculateCartItems($_SESSION['cart']),
                'currency' => $this->config->getShop('waehrungssymbol'),
                'numberformat_decimals' => $this->config->getCore('numberformat_decimals'),
                'numberformat_decimal_point' => $this->config->getCore('numberformat_decimal_point'),
                'numberformat_thousands_seperator' => $this->config->getCore('numberformat_thousands_seperator'),
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
            header('Location: '.\HaaseIT\Toolbox\Tools::makeLinkHRefWithAddedGetVars(filter_input(INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_URL), $aMSG, true, false));
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
