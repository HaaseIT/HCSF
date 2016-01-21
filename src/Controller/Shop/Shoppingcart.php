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
    public function __construct($C, $DB, $sLang, $twig, $oItem)
    {
        parent::__construct($C, $DB, $sLang);

        $this->P->cb_pagetype = 'contentnosubnav';

        if ($C["show_pricesonlytologgedin"] && !getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_notloggedin");
        } else {
            require_once PATH_BASEDIR . 'src/shop/functions.shoppingcart.php';

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
                $aErr = array();
                if (isset($_POST["doCheckout"]) && $_POST["doCheckout"] == 'yes') {
                    $aErr = validateCustomerForm($C, $sLang, $aErr, true);
                    if (!getUserData() && (!isset($_POST["tos"]) || $_POST["tos"] != 'y')) {
                        $aErr["tos"] = true;
                    }
                    if (!getUserData() && (!isset($_POST["cancellationdisclaimer"]) || $_POST["cancellationdisclaimer"] != 'y')) {
                        $aErr["cancellationdisclaimer"] = true;
                    }
                    if (!isset($_POST["paymentmethod"]) || array_search($_POST["paymentmethod"], $C["paymentmethods"]) === false) {
                        $aErr["paymentmethod"] = true;
                    }
                }
                $aShoppingcart = buildShoppingCartTable($_SESSION["cart"], $sLang, $C, false, '', $aErr);
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
                            $aDataOrder = array(
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
                                'o_group' => trim(getUserData(DB_CUSTOMERFIELD_GROUP)),
                                'o_remarks' => filter_var(trim(\HaaseIT\Tools::getFormfield("remarks")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_tos' => ((isset($_POST["tos"]) && $_POST["tos"] == 'y' || getUserData()) ? 'y' : 'n'),
                                'o_cancellationdisclaimer' => ((isset($_POST["cancellationdisclaimer"]) && $_POST["cancellationdisclaimer"] == 'y' || getUserData()) ? 'y' : 'n'),
                                'o_paymentmethod' => filter_var(trim(\HaaseIT\Tools::getFormfield("paymentmethod")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'o_sumvoll' => $_SESSION["cartpricesums"]["sumvoll"],
                                'o_sumerm' => $_SESSION["cartpricesums"]["sumerm"],
                                'o_sumnettoall' => $_SESSION["cartpricesums"]["sumnettoall"],
                                'o_taxvoll' => $_SESSION["cartpricesums"]["taxvoll"],
                                'o_taxerm' => $_SESSION["cartpricesums"]["taxerm"],
                                'o_sumbruttoall' => $_SESSION["cartpricesums"]["sumbruttoall"],
                                'o_mindermenge' => (isset($_SESSION["cartpricesums"]["mindergebuehr"]) ? $_SESSION["cartpricesums"]["mindergebuehr"] : ''),
                                'o_shippingcost' => getShippingcost($C, $sLang),
                                'o_orderdate' => date("Y-m-d"),
                                'o_ordertimestamp' => time(),
                                'o_authed' => ((getUserData()) ? 'y' : 'n'),
                                'o_sessiondata' => serialize($_SESSION),
                                'o_postdata' => serialize($_POST),
                                'o_remote_address' => $_SERVER["REMOTE_ADDR"],
                                'o_ordercompleted' => 'n',
                                'o_paymentcompleted' => 'n',
                                'o_srv_hostname' => $_SERVER["HTTP_HOST"],
                                'o_vatfull' => $C["vat"]["full"],
                                'o_vatreduced' => $C["vat"]["reduced"],
                            );
                            $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aDataOrder, DB_ORDERTABLE);
                            //die($sQ);
                            $hResult = $DB->prepare($sQ);
                            foreach ($aDataOrder as $sKey => $sValue) {
                                $hResult->bindValue(':' . $sKey, $sValue);
                            }
                            $hResult->execute();
                            $iInsertID = $DB->lastInsertId();

                            $aDataOrderItems = array();
                            $aImagesToSend = array();
                            if (isset($_SESSION["cart"]) && count($_SESSION["cart"]) >= 1) {
                                foreach ($_SESSION["cart"] as $sK => $aV) {
                                    // base64 encode img and prepare for db
                                    //echo $aV["img"];
                                    // image/png image/jpeg image/gif
                                    // data:{mimetype};base64,XXXX
                                    $sPathToImage = PATH_DOCROOT.$C['directory_images'].'/'.$C['directory_images_items'].'/'.$C['directory_images_items_email'].'/';
                                    $binImg = file_get_contents($sPathToImage.$aV["img"]);
                                    $aImgInfo = getimagesize($sPathToImage.$aV["img"]);
                                    //echo debug($aImgInfo);
                                    $base64Img = 'data:' . $aImgInfo["mime"] . ';base64,';
                                    $base64Img .= base64_encode($binImg);
                                    if (isset($C["email_orderconfirmation_embed_itemimages"]) && $C["email_orderconfirmation_embed_itemimages"]) $aImagesToSend[] = $aV["img"];
                                    unset($binImg, $aImgInfo);
                                    //echo $base64Img;

                                    $aDataOrderItems[] = array(
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
                                        'oi_rg_rebate' => isset($C["rebate_groups"][$aV["rg"]][trim(getUserData(DB_CUSTOMERFIELD_GROUP))]) ? $C["rebate_groups"][$aV["rg"]][trim(getUserData(DB_CUSTOMERFIELD_GROUP))] : '',
                                        'oi_itemname' => $aV["name"],
                                        'oi_img' => $base64Img,
                                    );
                                    //$_SESSION["cart"][$sK]["img"] = $base64Img;
                                    unset($base64Img);
                                }
                            }
                            foreach ($aDataOrderItems as $aV) {
                                $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aV, DB_ORDERTABLE_ITEMS);
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
                        $sMailbody_us = buildOrderMailBody($C, $sLang, $twig, false, $iInsertID);
                        $sMailbody_they = buildOrderMailBody($C, $sLang, $twig, true, $iInsertID);

                        // write to file
                        $fp = fopen(PATH_LOGS . 'shoplog_' . date("Y-m-d") . '.html', 'a');
                        // Write $somecontent to our opened file.
                        fwrite($fp, $sMailbody_us . "\n\n-------------------------------------------------------------------------\n\n");
                        fclose($fp);

                        if (isset($C["email_orderconfirmation_attachment_cancellationform_" . $sLang]) && file_exists(PATH_DOCROOT.$C['directory_emailattachments'].'/'.$C["email_orderconfirmation_attachment_cancellationform_".$sLang])) {
                            $aFilesToSend[] = PATH_DOCROOT.$C['directory_emailattachments'].'/'.$C["email_orderconfirmation_attachment_cancellationform_".$sLang];
                        } else $aFilesToSend = array();

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
}