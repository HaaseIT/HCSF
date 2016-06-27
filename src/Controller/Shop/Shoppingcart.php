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

class Shoppingcart extends Base
{
    private $twig;
    public function __construct($C, $DB, $sLang, $twig, $oItem)
    {
        parent::__construct($C, $DB, $sLang);
        $this->twig = $twig;

        $this->P->cb_pagetype = 'contentnosubnav';

        if ($C["show_pricesonlytologgedin"] && !\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_notloggedin");
        } else {
            //require_once PATH_BASEDIR . 'src/shop/functions.shoppingcart.php';

            $this->P->cb_customcontenttemplate = 'shop/shoppingcart';
            $this->P->oPayload->cl_html = '';

            // ----------------------------------------------------------------------------
            // Check if there is a message to display above the shoppingcart
            // ----------------------------------------------------------------------------
            if (isset($_GET["msg"]) && trim($_GET["msg"]) != '') {
                if (
                    ($_GET["msg"] == 'updated' && isset($_GET["cartkey"]) && isset($_GET["amount"]))
                    || ($_GET["msg"] == 'removed')
                    && isset($_GET["cartkey"])
                ) {
                    $this->P->oPayload->cl_html .= \HaaseIT\Textcat::T("shoppingcart_msg_" . $_GET["msg"] . "_1") . ' ';
                    if (isset($C["custom_order_fields"]) && mb_strpos($_GET["cartkey"], '|') !== false) {
                        $mCartkeys = explode('|', $_GET["cartkey"]);
                        //debug($mCartkeys);
                        foreach ($mCartkeys as $sKey => $sValue) {
                            if ($sKey == 0) {
                                $this->P->oPayload->cl_html .= $sValue . ', ';
                            } else {
                                $TMP = explode(':', $sValue);
                                $this->P->oPayload->cl_html .= \HaaseIT\Textcat::T("shoppingcart_item_" . $TMP[0]) . ' ' . $TMP[1] . ', ';
                                unset($TMP);
                            }
                        }
                        $this->P->oPayload->cl_html = \HaaseIT\Tools::cutStringend($this->P->oPayload->cl_html, 2);
                    } else {
                        $this->P->oPayload->cl_html .= $_GET["cartkey"];
                    }
                    $this->P->oPayload->cl_html .= ' ' . \HaaseIT\Textcat::T("shoppingcart_msg_" . $_GET["msg"] . "_2");
                    if ($_GET["msg"] == 'updated') {
                        $this->P->oPayload->cl_html .= ' ' . $_GET["amount"];
                    }
                    $this->P->oPayload->cl_html .= '<br><br>';
                }
            }

            // ----------------------------------------------------------------------------
            // Display the shoppingcart
            // ----------------------------------------------------------------------------
            if (isset($_SESSION["cart"]) && count($_SESSION["cart"]) >= 1) {
                $aErr = [];
                if (isset($_POST["doCheckout"]) && $_POST["doCheckout"] == 'yes') {
                    $aErr = \HaaseIT\HCSF\Customer\Helper::validateCustomerForm($C, $sLang, $aErr, true);
                    if (!\HaaseIT\HCSF\Customer\Helper::getUserData() && (!isset($_POST["tos"]) || $_POST["tos"] != 'y')) {
                        $aErr["tos"] = true;
                    }
                    if (!\HaaseIT\HCSF\Customer\Helper::getUserData() && (!isset($_POST["cancellationdisclaimer"]) || $_POST["cancellationdisclaimer"] != 'y')) {
                        $aErr["cancellationdisclaimer"] = true;
                    }
                    if (!isset($_POST["paymentmethod"]) || array_search($_POST["paymentmethod"], $C["paymentmethods"]) === false) {
                        $aErr["paymentmethod"] = true;
                    }
                }
                $aShoppingcart = \HaaseIT\HCSF\Shop\Helper::buildShoppingCartTable($_SESSION["cart"], $sLang, $C, false, '', $aErr);
            }

            // ----------------------------------------------------------------------------
            // Checkout
            // ----------------------------------------------------------------------------
            if (!isset($aShoppingcart)) {
                $this->P->oPayload->cl_html .= \HaaseIT\Textcat::T("shoppingcart_empty");
            } else {
                if (isset($_POST["doCheckout"]) && $_POST["doCheckout"] == 'yes') {
                    if (count($aErr) == 0) {
                        try {
                            $DB->beginTransaction();
                            $aDataOrder = [
                                'o_custno' => filter_var(trim(\HaaseIT\Tools::getFormfield("custno")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_email' => filter_var(trim(\HaaseIT\Tools::getFormfield("email")), FILTER_SANITIZE_EMAIL),
                                'o_corpname' => filter_var(trim(\HaaseIT\Tools::getFormfield("corpname")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_name' => filter_var(trim(\HaaseIT\Tools::getFormfield("name")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_street' => filter_var(trim(\HaaseIT\Tools::getFormfield("street")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_zip' => filter_var(trim(\HaaseIT\Tools::getFormfield("zip")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_town' => filter_var(trim(\HaaseIT\Tools::getFormfield("town")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_phone' => filter_var(trim(\HaaseIT\Tools::getFormfield("phone")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_cellphone' => filter_var(trim(\HaaseIT\Tools::getFormfield("cellphone")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_fax' => filter_var(trim(\HaaseIT\Tools::getFormfield("fax")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_country' => filter_var(trim(\HaaseIT\Tools::getFormfield("country")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_group' => trim(\HaaseIT\HCSF\Customer\Helper::getUserData('cust_group')),
                                'o_remarks' => filter_var(trim(\HaaseIT\Tools::getFormfield("remarks")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_tos' => ((isset($_POST["tos"]) && $_POST["tos"] == 'y' || \HaaseIT\HCSF\Customer\Helper::getUserData()) ? 'y' : 'n'),
                                'o_cancellationdisclaimer' => ((isset($_POST["cancellationdisclaimer"]) && $_POST["cancellationdisclaimer"] == 'y' || \HaaseIT\HCSF\Customer\Helper::getUserData()) ? 'y' : 'n'),
                                'o_paymentmethod' => filter_var(trim(\HaaseIT\Tools::getFormfield("paymentmethod")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_sumvoll' => $_SESSION["cartpricesums"]["sumvoll"],
                                'o_sumerm' => $_SESSION["cartpricesums"]["sumerm"],
                                'o_sumnettoall' => $_SESSION["cartpricesums"]["sumnettoall"],
                                'o_taxvoll' => $_SESSION["cartpricesums"]["taxvoll"],
                                'o_taxerm' => $_SESSION["cartpricesums"]["taxerm"],
                                'o_sumbruttoall' => $_SESSION["cartpricesums"]["sumbruttoall"],
                                'o_mindermenge' => (isset($_SESSION["cartpricesums"]["mindergebuehr"]) ? $_SESSION["cartpricesums"]["mindergebuehr"] : ''),
                                'o_shippingcost' => \HaaseIT\HCSF\Shop\Helper::getShippingcost($C, $sLang),
                                'o_orderdate' => date("Y-m-d"),
                                'o_ordertimestamp' => time(),
                                'o_authed' => ((\HaaseIT\HCSF\Customer\Helper::getUserData()) ? 'y' : 'n'),
                                'o_sessiondata' => serialize($_SESSION),
                                'o_postdata' => serialize($_POST),
                                'o_remote_address' => $_SERVER["REMOTE_ADDR"],
                                'o_ordercompleted' => 'n',
                                'o_paymentcompleted' => 'n',
                                'o_srv_hostname' => $_SERVER["HTTP_HOST"],
                                'o_vatfull' => $C["vat"]["full"],
                                'o_vatreduced' => $C["vat"]["reduced"],
                            ];
                            $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aDataOrder, 'orders');
                            //die($sQ);
                            $hResult = $DB->prepare($sQ);
                            foreach ($aDataOrder as $sKey => $sValue) {
                                $hResult->bindValue(':' . $sKey, $sValue);
                            }
                            $hResult->execute();
                            $iInsertID = $DB->lastInsertId();

                            $aDataOrderItems = [];
                            $aImagesToSend = [];
                            if (isset($_SESSION["cart"]) && count($_SESSION["cart"]) >= 1) {
                                foreach ($_SESSION["cart"] as $sK => $aV) {
                                    // base64 encode img and prepare for db
                                    //echo $aV["img"];
                                    // image/png image/jpeg image/gif
                                    // data:{mimetype};base64,XXXX
                                    if ($C['email_orderconfirmation_embed_itemimages_method'] == 'glide') {
                                        $sPathToImage = '/'.$C['directory_images'].'/'.$C['directory_images_items'].'/';
                                        $sImageroot = PATH_BASEDIR . $this->C['directory_glide_master'];

                                        if (
                                            is_file($sImageroot.substr($sPathToImage.$aV["img"], strlen($this->C['directory_images']) + 1))
                                            && $aImgInfo = getimagesize($sImageroot.substr($sPathToImage.$aV["img"], strlen($this->C['directory_images']) + 1))
                                        ) {
                                            $glideserver = \League\Glide\ServerFactory::create([
                                                'source' => $sImageroot,
                                                'cache' => PATH_GLIDECACHE,
                                                'max_image_size' => $this->C['glide_max_imagesize'],
                                            ]);
                                            $glideserver->setBaseUrl('/' . $this->C['directory_images'] . '/');
                                            $base64Img = $glideserver->getImageAsBase64($sPathToImage.$aV["img"], $C['email_orderconfirmation_embed_itemimages_glideparams']);
                                            $TMP = explode(',', $base64Img);
                                            $binImg = base64_decode($TMP[1]);
                                            unset($TMP);
                                        } else {
                                            $base64Img = false;
                                            $binImg = false;
                                        }
                                    } else {
                                        $sPathToImage = PATH_DOCROOT.$C['directory_images'].'/'.$C['directory_images_items'].'/'.$C['directory_images_items_email'].'/';
                                        if ($aImgInfo = getimagesize($sPathToImage.$aV["img"])) {
                                            $binImg = file_get_contents($sPathToImage.$aV["img"]);
                                            $base64Img = 'data:' . $aImgInfo["mime"] . ';base64,';
                                            $base64Img .= base64_encode($binImg);
                                        } else {
                                            $base64Img = false;
                                            $binImg = false;
                                        }
                                    }
                                    if (isset($C["email_orderconfirmation_embed_itemimages"]) && $C["email_orderconfirmation_embed_itemimages"]) {
                                        $aImagesToSend[$aV["img"]] = $binImg;
                                    }
                                    unset($binImg, $aImgInfo);
                                    //echo $base64Img;

                                    $aDataOrderItems[] = [
                                        'oi_o_id' => $iInsertID,
                                        'oi_cartkey' => $sK,
                                        'oi_amount' => $aV["amount"],
                                        'oi_price_netto_list' => $aV["price"]["netto_list"],
                                        'oi_price_brutto_list' => $aV["price"]["brutto_list"],
                                        'oi_price_netto_use' => $aV["price"]["netto_use"],
                                        'oi_price_brutto_use' => $aV["price"]["brutto_use"],
                                        'oi_price_netto_sale' => isset($aV["price"]["netto_sale"]) ? $aV["price"]["netto_sale"] : '',
                                        'oi_price_brutto_sale' => isset($aV["price"]["brutto_sale"]) ? $aV["price"]["brutto_sale"] : '',
                                        'oi_price_netto_rebated' => isset($aV["price"]["netto_rebated"]) ? $aV["price"]["netto_rebated"] : '',
                                        'oi_price_brutto_rebated' => isset($aV["price"]["brutto_rebated"]) ? $aV["price"]["brutto_rebated"] : '',
                                        'oi_vat' => $C["vat"][$aV["vat"]],
                                        'oi_rg' => $aV["rg"],
                                        'oi_rg_rebate' => isset($C["rebate_groups"][$aV["rg"]][trim(\HaaseIT\HCSF\Customer\Helper::getUserData('cust_group'))]) ? $C["rebate_groups"][$aV["rg"]][trim(\HaaseIT\HCSF\Customer\Helper::getUserData('cust_group'))] : '',
                                        'oi_itemname' => $aV["name"],
                                        'oi_img' => $base64Img,
                                    ];
                                    unset($base64Img);
                                }
                            }
                            foreach ($aDataOrderItems as $aV) {
                                $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aV, 'orders_items');
                                $hResult = $DB->prepare($sQ);
                                foreach ($aV as $sKey => $sValue) {
                                    $hResult->bindValue(':' . $sKey, $sValue);
                                }
                                $hResult->execute();
                            }
                            $DB->commit();
                        } catch (Exception $e) {
                            // If something raised an exception in our transaction block of statements,
                            // roll back any work performed in the transaction
                            print '<p>Unable to complete transaction!</p>';
                            print $e;
                            $DB->rollBack();
                        }
                        //die(debug($aDataOrderClean, true).debug($aDataOrderItemsClean, true));
                        $sMailbody_us = $this->buildOrderMailBody(false, $iInsertID);
                        $sMailbody_they = $this->buildOrderMailBody(true, $iInsertID);

                        // write to file
                        $fp = fopen(PATH_LOGS . 'shoplog_' . date("Y-m-d") . '.html', 'a');
                        // Write $somecontent to our opened file.
                        fwrite($fp, $sMailbody_us . "\n\n-------------------------------------------------------------------------\n\n");
                        fclose($fp);

                        if (isset($C["email_orderconfirmation_attachment_cancellationform_" . $sLang]) && file_exists(PATH_DOCROOT.$C['directory_emailattachments'].'/'.$C["email_orderconfirmation_attachment_cancellationform_".$sLang])) {
                            $aFilesToSend[] = PATH_DOCROOT.$C['directory_emailattachments'].'/'.$C["email_orderconfirmation_attachment_cancellationform_".$sLang];
                        } else $aFilesToSend = [];

                        // Send Mails
                        \HaaseIT\HCSF\Helper::mailWrapper($C, $_POST["email"], \HaaseIT\Textcat::T("shoppingcart_mail_subject") . ' ' . $iInsertID, $sMailbody_they, $aImagesToSend, $aFilesToSend);
                        \HaaseIT\HCSF\Helper::mailWrapper($C, $C["email_sender"], 'Bestellung im Webshop Nr: ' . $iInsertID, $sMailbody_us, $aImagesToSend);

                        if (isset($_SESSION["cart"])) unset($_SESSION["cart"]);
                        if (isset($_SESSION["cartpricesums"])) unset($_SESSION["cartpricesums"]);
                        if (isset($_SESSION["sondercart"])) unset($_SESSION["sondercart"]);

                        if (isset($_POST["paymentmethod"]) && $_POST["paymentmethod"] == 'paypal' && array_search('paypal', $C["paymentmethods"]) !== false && isset($C["paypal_interactive"]) && $C["paypal_interactive"]) {
                            header('Location: /_misc/paypal.html?id=' . $iInsertID);
                        } elseif (isset($_POST["paymentmethod"]) && $_POST["paymentmethod"] == 'sofortueberweisung' && array_search('sofortueberweisung', $C["paymentmethods"]) !== false) {
                            header('Location: /_misc/sofortueberweisung.html?id=' . $iInsertID);
                        } else {
                            header('Location: /_misc/checkedout.html?id=' . $iInsertID);
                        }
                        die();
                    }
                } // endif $_POST["doCheckout"] == 'yes'
            }

            if (isset($aShoppingcart)) {
                $this->P->cb_customdata = $aShoppingcart;
            }
        }
    }

    private function buildOrderMailBody($bCust = true, $iId = 0)
    {
        $aSHC = \HaaseIT\HCSF\Shop\Helper::buildShoppingCartTable($_SESSION["cart"], $this->sLang, $this->C, true);

        $aData = [
            'customerversion' => $bCust,
            //'shc_css' => file_get_contents(PATH_DOCROOT.'screen-shc.css'),
            'datetime' => date("d.m.Y - H:i"),
            'custno' => (isset($_POST["custno"]) && strlen(trim($_POST["custno"])) >= $this->C["minimum_length_custno"] ? $_POST["custno"] : ''),
            'corpname' => (isset($_POST["corpname"]) && trim($_POST["corpname"]) != '' ? $_POST["corpname"] : ''),
            'name' => (isset($_POST["name"]) && trim($_POST["name"]) != '' ? $_POST["name"] : ''),
            'street' => (isset($_POST["street"]) && trim($_POST["street"]) != '' ? $_POST["street"] : ''),
            'zip' => (isset($_POST["zip"]) && trim($_POST["zip"]) != '' ? $_POST["zip"] : ''),
            'town' => (isset($_POST["town"]) && trim($_POST["town"]) != '' ? $_POST["town"] : ''),
            'phone' => (isset($_POST["phone"]) && trim($_POST["phone"]) != '' ? $_POST["phone"] : ''),
            'cellphone' => (isset($_POST["cellphone"]) && trim($_POST["cellphone"]) != '' ? $_POST["cellphone"] : ''),
            'fax' => (isset($_POST["fax"]) && trim($_POST["fax"]) != '' ? $_POST["fax"] : ''),
            'email' => (isset($_POST["email"]) && trim($_POST["email"]) != '' ? $_POST["email"] : ''),
            'country' => (isset($_POST["country"]) && trim($_POST["country"]) != '' ? (isset($this->C["countries_".$this->sLang][$_POST["country"]]) ? $this->C["countries_".$this->sLang][$_POST["country"]] : $_POST["country"]) : ''),
            'remarks' => (isset($_POST["remarks"]) && trim($_POST["remarks"]) != '' ? $_POST["remarks"] : ''),
            'tos' => (isset($_POST["tos"]) && trim($_POST["tos"]) != '' ? $_POST["tos"] : ''),
            'cancellationdisclaimer' => (isset($_POST["cancellationdisclaimer"]) && trim($_POST["cancellationdisclaimer"]) != '' ? $_POST["cancellationdisclaimer"] : ''),
            'paymentmethod' => (isset($_POST["paymentmethod"]) && trim($_POST["paymentmethod"]) != '' ? $_POST["paymentmethod"] : ''),
            'shippingcost' => (!isset($_SESSION["shippingcost"]) || $_SESSION["shippingcost"] == 0 ? false : $_SESSION["shippingcost"]),
            'paypallink' => (isset($_POST["paymentmethod"]) && $_POST["paymentmethod"] == 'paypal' ?  $_SERVER["HTTP_HOST"].'/_misc/paypal.html?id='.$iId : ''),
            'sofortueberweisunglink' => (isset($_POST["paymentmethod"]) && $_POST["paymentmethod"] == 'sofortueberweisung' ?  $_SERVER["HTTP_HOST"].'/_misc/sofortueberweisung.html?id='.$iId : ''),
            'SESSION' => (!$bCust ? \HaaseIT\Tools::debug($_SESSION, '$_SESSION', true, true) : ''),
            'POST' => (!$bCust ? \HaaseIT\Tools::debug($_POST, '$_POST', true, true) : ''),
            'orderid' => $iId,
        ];

        $aM["customdata"] = $aSHC;
        $aM['currency'] = $this->C["waehrungssymbol"];
        if (isset($this->C["custom_order_fields"])) $aM["custom_order_fields"] = $this->C["custom_order_fields"];
        $aM["customdata"]["mail"] = $aData;

        $sH = $this->twig->render('shop/mail-order-html.twig', $aM);

        return $sH;
    }

}