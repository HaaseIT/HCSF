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
use HaaseIT\Tools;
use HaaseIT\HCSF\Helper;
use HaaseIT\HCSF\Customer\Helper as CHelper;
use HaaseIT\HCSF\Shop\Helper as SHelper;
use HaaseIT\DBTools;

class Shoppingcart extends Base
{
    /**
     * @var \Zend\Diactoros\ServerRequest
     */
    private $request;

    /**
     * @var \HaaseIT\Textcat
     */
    private $textcats;

    /**
     * @var array
     */
    private $get;

    /**
     * @var array
     */
    private $post;

    /**
     * Shoppingcart constructor.
     * @param \Zend\ServiceManager\ServiceManager $serviceManager
     */
    public function __construct(\Zend\ServiceManager\ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->request = $this->serviceManager->get('request');
        $this->textcats = $this->serviceManager->get('textcats');
        $this->get = $this->request->getQueryParams();
        $this->post = $this->request->getParsedBody();
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'contentnosubnav';


        if (HelperConfig::$shop["show_pricesonlytologgedin"] && !CHelper::getUserData()) {
            $this->P->oPayload->cl_html = $this->textcats->T("denied_notloggedin");
        } else {
            $this->P->cb_customcontenttemplate = 'shop/shoppingcart';
            $this->P->oPayload->cl_html = '';

            // ----------------------------------------------------------------------------
            // Check if there is a message to display above the shoppingcart
            // ----------------------------------------------------------------------------
            $this->P->oPayload->cl_html = $this->getNotification();

            // ----------------------------------------------------------------------------
            // Display the shoppingcart
            // ----------------------------------------------------------------------------
            $aErr = [];
            if (isset($_SESSION["cart"]) && count($_SESSION["cart"]) >= 1) {
                if (isset($this->post["doCheckout"]) && $this->post["doCheckout"] == 'yes') {
                    $aErr = CHelper::validateCustomerForm(HelperConfig::$lang, $aErr, true);
                    if (!CHelper::getUserData() && (!isset($this->post["tos"]) || $this->post["tos"] != 'y')) {
                        $aErr["tos"] = true;
                    }
                    if (!CHelper::getUserData() && (!isset($this->post["cancellationdisclaimer"]) || $this->post["cancellationdisclaimer"] != 'y')) {
                        $aErr["cancellationdisclaimer"] = true;
                    }
                    if (
                        !isset($this->post["paymentmethod"])
                        || array_search(
                            $this->post["paymentmethod"],
                            HelperConfig::$shop["paymentmethods"]
                        ) === false) {
                        $aErr["paymentmethod"] = true;
                    }
                }
                $aShoppingcart = SHelper::buildShoppingCartTable($_SESSION["cart"], false, '', $aErr);
            }

            // ----------------------------------------------------------------------------
            // Checkout
            // ----------------------------------------------------------------------------
            if (!isset($aShoppingcart)) {
                $this->P->oPayload->cl_html .= $this->textcats->T("shoppingcart_empty");
            } else {
                if (isset($this->post["doCheckout"]) && $this->post["doCheckout"] == 'yes') {
                    if (count($aErr) == 0) {
                        $this->doCheckout();
                    }
                } // endif $this->post["doCheckout"] == 'yes'
            }

            if (isset($aShoppingcart)) {
                $this->P->cb_customdata = $aShoppingcart;
            }
        }
    }

    /**
     * @param $aV
     * @return array
     */
    private function getItemImage($aV)
    {
        // base64 encode img and prepare for db
        // image/png image/jpeg image/gif
        // data:{mimetype};base64,XXXX

        $aImagesToSend = [];
        $base64Img = false;
        $binImg = false;

        if (HelperConfig::$shop['email_orderconfirmation_embed_itemimages_method'] == 'glide') {
            $sPathToImage = '/'.HelperConfig::$core['directory_images'].'/'.HelperConfig::$shop['directory_images_items'].'/';
            $sImageroot = PATH_BASEDIR . HelperConfig::$core['directory_glide_master'];

            if (
                is_file($sImageroot.substr($sPathToImage.$aV["img"], strlen(HelperConfig::$core['directory_images']) + 1))
                && $aImgInfo = getimagesize($sImageroot.substr($sPathToImage.$aV["img"], strlen(HelperConfig::$core['directory_images']) + 1))
            ) {
                $glideserver = \League\Glide\ServerFactory::create([
                    'source' => $sImageroot,
                    'cache' => PATH_GLIDECACHE,
                    'max_image_size' => HelperConfig::$core['glide_max_imagesize'],
                ]);
                $glideserver->setBaseUrl('/' . HelperConfig::$core['directory_images'] . '/');
                $base64Img = $glideserver->getImageAsBase64($sPathToImage.$aV["img"], HelperConfig::$shop['email_orderconfirmation_embed_itemimages_glideparams']);
                $TMP = explode(',', $base64Img);
                $binImg = base64_decode($TMP[1]);
                unset($TMP);
            }
        } else {
            $sPathToImage =
                PATH_DOCROOT.HelperConfig::$core['directory_images'].'/'
                .HelperConfig::$shop['directory_images_items'].'/'
                .HelperConfig::$shop['directory_images_items_email'].'/';
            if ($aImgInfo = getimagesize($sPathToImage.$aV["img"])) {
                $binImg = file_get_contents($sPathToImage.$aV["img"]);
                $base64Img = 'data:' . $aImgInfo["mime"] . ';base64,';
                $base64Img .= base64_encode($binImg);
            }
        }
        if (HelperConfig::$shop["email_orderconfirmation_embed_itemimages"]) {
            $aImagesToSend['binimg'] = $binImg;
        }
        if ($base64Img) {
            $aImagesToSend['base64img'] = $base64Img;
        }
        return $aImagesToSend;
    }

    /**
     * @return array
     */
    private function prepareDataOrder()
    {
        return [
            'o_custno' => filter_var(trim(Tools::getFormfield("custno")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_email' => filter_var(trim(Tools::getFormfield("email")), FILTER_SANITIZE_EMAIL),
            'o_corpname' => filter_var(trim(Tools::getFormfield("corpname")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_name' => filter_var(trim(Tools::getFormfield("name")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_street' => filter_var(trim(Tools::getFormfield("street")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_zip' => filter_var(trim(Tools::getFormfield("zip")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_town' => filter_var(trim(Tools::getFormfield("town")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_phone' => filter_var(trim(Tools::getFormfield("phone")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_cellphone' => filter_var(trim(Tools::getFormfield("cellphone")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_fax' => filter_var(trim(Tools::getFormfield("fax")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_country' => filter_var(trim(Tools::getFormfield("country")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_group' => trim(CHelper::getUserData('cust_group')),
            'o_remarks' => filter_var(trim(Tools::getFormfield("remarks")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_tos' => ((isset($this->post["tos"]) && $this->post["tos"] == 'y' || CHelper::getUserData()) ? 'y' : 'n'),
            'o_cancellationdisclaimer' => ((isset($this->post["cancellationdisclaimer"]) && $this->post["cancellationdisclaimer"] == 'y' || CHelper::getUserData()) ? 'y' : 'n'),
            'o_paymentmethod' => filter_var(trim(Tools::getFormfield("paymentmethod")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_sumvoll' => $_SESSION["cartpricesums"]["sumvoll"],
            'o_sumerm' => $_SESSION["cartpricesums"]["sumerm"],
            'o_sumnettoall' => $_SESSION["cartpricesums"]["sumnettoall"],
            'o_taxvoll' => $_SESSION["cartpricesums"]["taxvoll"],
            'o_taxerm' => $_SESSION["cartpricesums"]["taxerm"],
            'o_sumbruttoall' => $_SESSION["cartpricesums"]["sumbruttoall"],
            'o_mindermenge' => (isset($_SESSION["cartpricesums"]["mindergebuehr"]) ? $_SESSION["cartpricesums"]["mindergebuehr"] : ''),
            'o_shippingcost' => SHelper::getShippingcost(),
            'o_orderdate' => date("Y-m-d"),
            'o_ordertimestamp' => time(),
            'o_authed' => ((CHelper::getUserData()) ? 'y' : 'n'),
            'o_sessiondata' => serialize($_SESSION),
            'o_postdata' => serialize($this->post),
            'o_remote_address' => $_SERVER["REMOTE_ADDR"],
            'o_ordercompleted' => 'n',
            'o_paymentcompleted' => 'n',
            'o_srv_hostname' => $_SERVER["SERVER_NAME"],
            'o_vatfull' => HelperConfig::$shop["vat"]["full"],
            'o_vatreduced' => HelperConfig::$shop["vat"]["reduced"],
        ];
    }

    /**
     * @return bool
     */
    private function doCheckout()
    {
        if (empty($_SESSION["cart"])) {
            return false;
        }

        /** @var \PDO $db */
        $db = $this->serviceManager->get('db');

        try {
            $db->beginTransaction();

            $aDataOrder = $this->prepareDataOrder();
            $sql = DBTools::buildPSInsertQuery($aDataOrder, 'orders');
            $hResult = $db->prepare($sql);
            foreach ($aDataOrder as $sKey => $sValue) {
                $hResult->bindValue(':' . $sKey, $sValue);
            }
            $hResult->execute();
            $iInsertID = $db->lastInsertId();

            $aDataOrderItems = [];
            $aImagesToSend = [];
            foreach ($_SESSION["cart"] as $sK => $aV) {

                $aImagesToSend[$aV["img"]] = $this->getItemImage($aV);

                $aDataOrderItems[] = [
                    'oi_o_id' => $iInsertID,
                    'oi_cartkey' => $sK,
                    'oi_amount' => $aV["amount"],
                    'oi_price_netto_list' => $aV["price"]["netto_list"],
                    'oi_price_netto_use' => $aV["price"]["netto_use"],
                    'oi_price_brutto_use' => $aV["price"]["brutto_use"],
                    'oi_price_netto_sale' => isset($aV["price"]["netto_sale"]) ? $aV["price"]["netto_sale"] : '',
                    'oi_price_netto_rebated' => isset($aV["price"]["netto_rebated"]) ? $aV["price"]["netto_rebated"] : '',
                    'oi_vat' => HelperConfig::$shop["vat"][$aV["vat"]],
                    'oi_rg' => $aV["rg"],
                    'oi_rg_rebate' => isset(
                        HelperConfig::$shop["rebate_groups"][$aV["rg"]][trim(CHelper::getUserData('cust_group'))]
                    )
                        ? HelperConfig::$shop["rebate_groups"][$aV["rg"]][trim(CHelper::getUserData('cust_group'))]
                        : '',
                    'oi_itemname' => $aV["name"],
                    'oi_img' => $aImagesToSend[$aV["img"]]['base64img'],
                ];
            }
            foreach ($aDataOrderItems as $aV) {
                $sql = DBTools::buildPSInsertQuery($aV, 'orders_items');
                $hResult = $db->prepare($sql);
                foreach ($aV as $sKey => $sValue) {
                    $hResult->bindValue(':' . $sKey, $sValue);
                }
                $hResult->execute();
            }
            $db->commit();
        } catch (\Exception $e) {
            // If something raised an exception in our transaction block of statements,
            // roll back any work performed in the transaction
            print '<p>Unable to complete transaction!</p>';
            print $e;
            $db->rollBack();
        }
        $sMailbody_us = $this->buildOrderMailBody(false, $iInsertID);
        $sMailbody_they = $this->buildOrderMailBody(true, $iInsertID);

        // write to file
        $this->writeCheckoutToFile($sMailbody_us);

        // Send Mails
        $this->sendCheckoutMails($iInsertID, $sMailbody_us, $sMailbody_they, $aImagesToSend);

        if (isset($_SESSION["cart"])) unset($_SESSION["cart"]);
        if (isset($_SESSION["cartpricesums"])) unset($_SESSION["cartpricesums"]);
        if (isset($_SESSION["sondercart"])) unset($_SESSION["sondercart"]);

        if (
            isset($this->post["paymentmethod"])
            && $this->post["paymentmethod"] == 'paypal'
            && array_search('paypal', HelperConfig::$shop["paymentmethods"]) !== false
            && isset(HelperConfig::$shop["paypal_interactive"]) && HelperConfig::$shop["paypal_interactive"]
        ) {
            header('Location: /_misc/paypal.html?id=' . $iInsertID);
        } elseif (
            isset($this->post["paymentmethod"])
            && $this->post["paymentmethod"] == 'sofortueberweisung'
            && array_search('sofortueberweisung', HelperConfig::$shop["paymentmethods"]) !== false
        ) {
            header('Location: /_misc/sofortueberweisung.html?id=' . $iInsertID);
        } else {
            header('Location: /_misc/checkedout.html?id=' . $iInsertID);
        }
        die();
    }

    /**
     * @param $iInsertID
     * @param $sMailbody_us
     * @param $sMailbody_they
     * @param $aImagesToSend
     */
    private function sendCheckoutMails($iInsertID, $sMailbody_us, $sMailbody_they, $aImagesToSend)
    {
        if (
            isset(HelperConfig::$shop["email_orderconfirmation_attachment_cancellationform_".HelperConfig::$lang])
            && file_exists(
                PATH_DOCROOT.HelperConfig::$core['directory_emailattachments']
                .'/'.HelperConfig::$shop["email_orderconfirmation_attachment_cancellationform_"
                .HelperConfig::$lang]
            )
        ) {
            $aFilesToSend[] =
                PATH_DOCROOT.HelperConfig::$core['directory_emailattachments'].'/'
                .HelperConfig::$shop["email_orderconfirmation_attachment_cancellationform_".HelperConfig::$lang];
        } else $aFilesToSend = [];

        Helper::mailWrapper(
            $this->post["email"],
            $this->textcats->T("shoppingcart_mail_subject") . ' ' . $iInsertID,
            $sMailbody_they,
            $aImagesToSend,
            $aFilesToSend
        );
        Helper::mailWrapper(
            HelperConfig::$core["email_sender"],
            'Bestellung im Webshop Nr: ' . $iInsertID,
            $sMailbody_us,
            $aImagesToSend
        );
    }

    /**
     * @param $sMailbody_us
     */
    private function writeCheckoutToFile($sMailbody_us)
    {
        $fp = fopen(PATH_LOGS . 'shoplog_' . date("Y-m-d") . '.html', 'a');
        // Write $somecontent to our opened file.
        fwrite($fp, $sMailbody_us . "\n\n-------------------------------------------------------------------------\n\n");
        fclose($fp);
    }

    /**
     * @param $field
     * @return string
     */
    private function getPostValue($field)
    {
        return (isset($this->post[$field]) && trim($this->post[$field]) != '' ? $this->post[$field] : '');
    }

    /**
     * @param bool $bCust
     * @param int $iId
     * @return mixed
     */
    private function buildOrderMailBody($bCust = true, $iId = 0)
    {
        $aSHC = SHelper::buildShoppingCartTable($_SESSION["cart"], true);

        $aData = [
            'customerversion' => $bCust,
            //'shc_css' => file_get_contents(PATH_DOCROOT.'screen-shc.css'),
            'datetime' => date("d.m.Y - H:i"),
            'custno' => (
                isset($this->post["custno"])
                && strlen(trim($this->post["custno"])) >= HelperConfig::$customer["minimum_length_custno"]
                    ? $this->post["custno"]
                    : ''
            ),
            'corpname' => $this->getPostValue('corpname'),
            'name' => $this->getPostValue('name'),
            'street' => $this->getPostValue('street'),
            'zip' => $this->getPostValue('zip'),
            'town' => $this->getPostValue('town'),
            'phone' => $this->getPostValue('phone'),
            'cellphone' => $this->getPostValue('cellphone'),
            'fax' => $this->getPostValue('fax'),
            'email' => $this->getPostValue('email'),
            'country' => (
                isset($this->post["country"]) && trim($this->post["country"]) != '' ?
                (
                    isset(
                        HelperConfig::$countries["countries_".HelperConfig::$lang][$this->post["country"]]
                    )
                        ? HelperConfig::$countries["countries_".HelperConfig::$lang][$this->post["country"]]
                        : $this->post["country"])
                : ''
            ),
            'remarks' => $this->getPostValue('remarks'),
            'tos' => $this->getPostValue('tos'),
            'cancellationdisclaimer' => $this->getPostValue('cancellationdisclaimer'),
            'paymentmethod' => $this->getPostValue('paymentmethod'),
            'shippingcost' => (!isset($_SESSION["shippingcost"]) || $_SESSION["shippingcost"] == 0 ? false : $_SESSION["shippingcost"]),
            'paypallink' => (isset($this->post["paymentmethod"]) && $this->post["paymentmethod"] == 'paypal' ?  $_SERVER["SERVER_NAME"].'/_misc/paypal.html?id='.$iId : ''),
            'sofortueberweisunglink' => (isset($this->post["paymentmethod"]) && $this->post["paymentmethod"] == 'sofortueberweisung' ?  $_SERVER["SERVER_NAME"].'/_misc/sofortueberweisung.html?id='.$iId : ''),
            'SESSION' => (!$bCust ? Tools::debug($_SESSION, '$_SESSION', true, true) : ''),
            'POST' => (!$bCust ? Tools::debug($this->post, '$this->post', true, true) : ''),
            'orderid' => $iId,
        ];

        $aM["customdata"] = $aSHC;
        $aM['currency'] = HelperConfig::$shop["waehrungssymbol"];
        if (isset(HelperConfig::$shop["custom_order_fields"])) {
            $aM["custom_order_fields"] = HelperConfig::$shop["custom_order_fields"];
        }
        $aM["customdata"]["mail"] = $aData;

        return $this->serviceManager->get('twig')->render('shop/mail-order-html.twig', $aM);

    }

    /**
     * @return string
     */
    private function getNotification()
    {
        $return = '';
        if (isset($this->get["msg"]) && trim($this->get["msg"]) != '') {
            if (
                ($this->get["msg"] == 'updated' && isset($this->get["cartkey"]) && isset($this->get["amount"]))
                || ($this->get["msg"] == 'removed')
                && isset($this->get["cartkey"])
            ) {
                $return .= $this->textcats->T("shoppingcart_msg_" . $this->get["msg"] . "_1") . ' ';
                if (isset(HelperConfig::$shop["custom_order_fields"]) && mb_strpos($this->get["cartkey"], '|') !== false) {
                    $mCartkeys = explode('|', $this->get["cartkey"]);
                    foreach ($mCartkeys as $sKey => $sValue) {
                        if ($sKey == 0) {
                            $return .= $sValue . ', ';
                        } else {
                            $TMP = explode(':', $sValue);
                            $return .= $this->textcats->T("shoppingcart_item_" . $TMP[0]) . ' ' . $TMP[1] . ', ';
                            unset($TMP);
                        }
                    }
                    $return = Tools::cutStringend($return, 2);
                } else {
                    $return .= $this->get["cartkey"];
                }
                $return.= ' ' . $this->textcats->T("shoppingcart_msg_" . $this->get["msg"] . "_2");
                if ($this->get["msg"] == 'updated') {
                    $return .= ' ' . $this->get["amount"];
                }
                $return .= '<br><br>';
            }
        }

        return $return;
    }
}