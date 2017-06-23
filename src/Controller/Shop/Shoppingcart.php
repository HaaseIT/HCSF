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
use HaaseIT\Toolbox\Tools;
use HaaseIT\HCSF\Helper;
use HaaseIT\HCSF\Customer\Helper as CHelper;
use HaaseIT\HCSF\Shop\Helper as SHelper;

class Shoppingcart extends Base
{
    /**
     * @var \HaaseIT\Toolbox\Textcat
     */
    private $textcats;

    /**
     * @var array
     */
    private $imagestosend = [];

    /**
     * Shoppingcart constructor.
     * @param \Zend\ServiceManager\ServiceManager $serviceManager
     */
    public function __construct(\Zend\ServiceManager\ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->textcats = $this->serviceManager->get('textcats');
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'contentnosubnav';

        if (HelperConfig::$shop['show_pricesonlytologgedin'] && !CHelper::getUserData()) {
            $this->P->oPayload->cl_html = $this->textcats->T('denied_notloggedin');
        } else {
            $this->P->cb_customcontenttemplate = 'shop/shoppingcart';

            // Check if there is a message to display above the shoppingcart
            $this->P->oPayload->cl_html = $this->getNotification();

            // Display the shoppingcart
            if (isset($_SESSION['cart']) && count($_SESSION['cart']) >= 1) {
                $aErr = [];
                if (filter_input(INPUT_POST, 'doCheckout') === 'yes') {
                    $aErr = $this->validateCheckout($aErr);
                    if (count($aErr) === 0) {
                        \HaaseIT\HCSF\Helper::redirectToPage($this->doCheckout());
                    }
                }

                $aShoppingcart = SHelper::buildShoppingCartTable($_SESSION['cart'], false, '', $aErr);

                $this->P->cb_customdata = $aShoppingcart;
            } else {
                $this->P->oPayload->cl_html .= $this->textcats->T('shoppingcart_empty');
            }
        }
    }

    /**
     * @param array $aErr
     * @return array
     */
    private function validateCheckout($aErr = [])
    {
        $aErr = CHelper::validateCustomerForm(HelperConfig::$lang, $aErr, true);
        if (!CHelper::getUserData() && filter_input(INPUT_POST, 'tos') !== 'y') {
            $aErr['tos'] = true;
        }
        if (!CHelper::getUserData() && filter_input(INPUT_POST, 'cancellationdisclaimer') !== 'y') {
            $aErr['cancellationdisclaimer'] = true;
        }
        $postpaymentmethod = filter_input(INPUT_POST, 'paymentmethod');
        if (
            $postpaymentmethod === null
            || in_array($postpaymentmethod, HelperConfig::$shop['paymentmethods'], true) === false
        ) {
            $aErr['paymentmethod'] = true;
        }

        return $aErr;
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

        if (HelperConfig::$shop['email_orderconfirmation_embed_itemimages_method'] === 'glide') {
            $sPathToImage = '/'.HelperConfig::$core['directory_images'].'/'.HelperConfig::$shop['directory_images_items'].'/';
            $sImageroot = PATH_BASEDIR.HelperConfig::$core['directory_glide_master'];

            if (
                is_file($sImageroot.substr($sPathToImage.$aV['img'], strlen(HelperConfig::$core['directory_images']) + 1))
                && $aImgInfo = getimagesize($sImageroot.substr($sPathToImage.$aV['img'], strlen(HelperConfig::$core['directory_images']) + 1))
            ) {
                $glideserver = \League\Glide\ServerFactory::create([
                    'source' => $sImageroot,
                    'cache' => PATH_GLIDECACHE,
                    'max_image_size' => HelperConfig::$core['glide_max_imagesize'],
                ]);
                $glideserver->setBaseUrl('/'.HelperConfig::$core['directory_images'].'/');
                $base64Img = $glideserver->getImageAsBase64($sPathToImage.$aV['img'], HelperConfig::$shop['email_orderconfirmation_embed_itemimages_glideparams']);
                $TMP = explode(',', $base64Img);
                $binImg = base64_decode($TMP[1]);
                unset($TMP);
            }
        } else {
            $sPathToImage =
                PATH_DOCROOT.HelperConfig::$core['directory_images'].'/'
                .HelperConfig::$shop['directory_images_items'].'/'
                .HelperConfig::$shop['directory_images_items_email'].'/';
            if ($aImgInfo = getimagesize($sPathToImage.$aV['img'])) {
                $binImg = file_get_contents($sPathToImage.$aV['img']);
                $base64Img = 'data:'.$aImgInfo['mime'].';base64,';
                $base64Img .= base64_encode($binImg);
            }
        }
        if (HelperConfig::$shop['email_orderconfirmation_embed_itemimages']) {
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
        $cartpricesums = $_SESSION['cartpricesums'];
        return [
            'o_custno' => filter_var(trim(Tools::getFormfield('custno')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_email' => filter_var(trim(Tools::getFormfield('email')), FILTER_SANITIZE_EMAIL),
            'o_corpname' => filter_var(trim(Tools::getFormfield('corpname')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_name' => filter_var(trim(Tools::getFormfield('name')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_street' => filter_var(trim(Tools::getFormfield('street')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_zip' => filter_var(trim(Tools::getFormfield('zip')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_town' => filter_var(trim(Tools::getFormfield('town')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_phone' => filter_var(trim(Tools::getFormfield('phone')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_cellphone' => filter_var(trim(Tools::getFormfield('cellphone')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_fax' => filter_var(trim(Tools::getFormfield('fax')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_country' => filter_var(trim(Tools::getFormfield('country')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_group' => trim(CHelper::getUserData('cust_group')),
            'o_remarks' => filter_var(trim(Tools::getFormfield('remarks')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_tos' => (filter_input(INPUT_POST, 'tos') === 'y' || CHelper::getUserData()) ? 'y' : 'n',
            'o_cancellationdisclaimer' => (filter_input(INPUT_POST, 'cancellationdisclaimer') === 'y' || CHelper::getUserData()) ? 'y' : 'n',
            'o_paymentmethod' => filter_var(trim(Tools::getFormfield('paymentmethod')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_sumvoll' => $cartpricesums['sumvoll'],
            'o_sumerm' => $cartpricesums['sumerm'],
            'o_sumnettoall' => $cartpricesums['sumnettoall'],
            'o_taxvoll' => $cartpricesums['taxvoll'],
            'o_taxerm' => $cartpricesums['taxerm'],
            'o_sumbruttoall' => $cartpricesums['sumbruttoall'],
            'o_mindermenge' => isset($cartpricesums['mindergebuehr']) ? $cartpricesums['mindergebuehr'] : '',
            'o_shippingcost' => SHelper::getShippingcost(),
            'o_orderdate' => date('Y-m-d'),
            'o_ordertimestamp' => time(),
            'o_authed' => CHelper::getUserData() ? 'y' : 'n',
            'o_sessiondata' => serialize($_SESSION),
            'o_postdata' => serialize($_POST),
            'o_remote_address' => filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_ordercompleted' => 'n',
            'o_paymentcompleted' => 'n',
            'o_srv_hostname' => filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'o_vatfull' => HelperConfig::$shop['vat']['full'],
            'o_vatreduced' => HelperConfig::$shop['vat']['reduced'],
        ];
    }

    /**
     * @param int $orderid
     * @param string $cartkey
     * @param array $values
     * @return array
     */
    private function buildOrderItemRow($orderid, $cartkey, array $values)
    {
        return [
            'oi_o_id' => $orderid,
            'oi_cartkey' => $cartkey,
            'oi_amount' => $values['amount'],
            'oi_price_netto_list' => $values['price']['netto_list'],
            'oi_price_netto_use' => $values['price']['netto_use'],
            'oi_price_brutto_use' => $values['price']['brutto_use'],
            'oi_price_netto_sale' => isset($values['price']['netto_sale']) ? $values['price']['netto_sale'] : '',
            'oi_price_netto_rebated' => isset($values['price']['netto_rebated']) ? $values['price']['netto_rebated'] : '',
            'oi_vat' => HelperConfig::$shop['vat'][$values['vat']],
            'oi_rg' => $values['rg'],
            'oi_rg_rebate' => isset(
                HelperConfig::$shop['rebate_groups'][$values['rg']][trim(CHelper::getUserData('cust_group'))]
            )
                ? HelperConfig::$shop['rebate_groups'][$values['rg']][trim(CHelper::getUserData('cust_group'))]
                : '',
            'oi_itemname' => $values['name'],
            'oi_img' => $this->imagestosend[$values['img']]['base64img'],
        ];
    }

    private function writeCheckoutToDB()
    {
        /** @var \Doctrine\DBAL\Connection $dbal */
        $dbal = $this->serviceManager->get('dbal');

        try {
            $dbal->beginTransaction();

            $aDataOrder = $this->prepareDataOrder();

            $iInsertID = \HaaseIT\HCSF\Helper::autoInsert($dbal, 'orders', $aDataOrder);

            foreach ($_SESSION['cart'] as $sK => $aV) {
                $this->imagestosend[$aV['img']] = $this->getItemImage($aV);

                \HaaseIT\HCSF\Helper::autoInsert(
                    $dbal,
                    'orders_items',
                    $this->buildOrderItemRow($iInsertID, $sK, $aV)
                );
            }
            $dbal->commit();

            return $iInsertID;
        } catch (\Exception $e) {
            // If something raised an exception in our transaction block of statements,
            // roll back any work performed in the transaction
            print '<p>Unable to complete transaction!</p>';
            error_log($e);
            $dbal->rollBack();
        }
    }

    private function doCheckout()
    {
        $iInsertID = $this->writeCheckoutToDB();

        $sMailbody_us = $this->buildOrderMailBody(false, $iInsertID);
        $sMailbody_they = $this->buildOrderMailBody(true, $iInsertID);

        // write to file
        $this->writeCheckoutToFile($sMailbody_us);

        // Send Mails
        $this->sendCheckoutMails($iInsertID, $sMailbody_us, $sMailbody_they);

        unset($_SESSION['cart'], $_SESSION['cartpricesums'], $_SESSION['sondercart']);

        $postpaymentmethod = filter_input(INPUT_POST, 'paymentmethod');
        if ($postpaymentmethod !== null) {
            if (
                $postpaymentmethod === 'paypal'
                && isset(HelperConfig::$shop['paypal_interactive'])
                && HelperConfig::$shop['paypal_interactive']
            ) {
                return '/_misc/paypal.html?id='.$iInsertID;
            } elseif ($postpaymentmethod === 'sofortueberweisung') {
                return '/_misc/sofortueberweisung.html?id='.$iInsertID;
            }
        }

        return '/_misc/checkedout.html?id='.$iInsertID;
    }

    /**
     * @param int $iInsertID
     * @param string $sMailbody_us
     * @param string $sMailbody_they
     */
    private function sendCheckoutMails($iInsertID, $sMailbody_us, $sMailbody_they)
    {
        if (
            isset(HelperConfig::$shop['email_orderconfirmation_attachment_cancellationform_' .HelperConfig::$lang])
            && file_exists(
                PATH_DOCROOT.HelperConfig::$core['directory_emailattachments']
                .'/'.HelperConfig::$shop['email_orderconfirmation_attachment_cancellationform_'
                .HelperConfig::$lang]
            )
        ) {
            $aFilesToSend[] =
                PATH_DOCROOT.HelperConfig::$core['directory_emailattachments'].'/'
                .HelperConfig::$shop['email_orderconfirmation_attachment_cancellationform_' .HelperConfig::$lang];
        } else {
            $aFilesToSend = [];
        }

        Helper::mailWrapper(
            filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            $this->textcats->T('shoppingcart_mail_subject').' '.$iInsertID,
            $sMailbody_they,
            $this->imagestosend,
            $aFilesToSend
        );
        Helper::mailWrapper(
            HelperConfig::$core['email_sender'],
            'Bestellung im Webshop Nr: '.$iInsertID,
            $sMailbody_us,
            $this->imagestosend
        );
    }

    /**
     * @param string $sMailbody_us
     */
    private function writeCheckoutToFile($sMailbody_us)
    {
        $fp = fopen(PATH_LOGS.'shoplog_'.date('Y-m-d').'.html', 'a');
        // Write $somecontent to our opened file.
        fwrite($fp, $sMailbody_us."\n\n-------------------------------------------------------------------------\n\n");
        fclose($fp);
    }

    /**
     * @param string $field
     * @return string
     */
    private function getPostValue($field)
    {
        $postvalue = filter_input(INPUT_POST, $field);
        return (!empty($postvalue) ? $postvalue : '');
    }

    /**
     * @param bool $bCust
     * @param int $iId
     * @return mixed
     */
    private function buildOrderMailBody($bCust = true, $iId)
    {
        $aM = [
            'customdata' => SHelper::buildShoppingCartTable($_SESSION['cart'], true),
            'currency' => HelperConfig::$shop['waehrungssymbol'],
        ];
        if (isset(HelperConfig::$shop['custom_order_fields'])) {
            $aM['custom_order_fields'] = HelperConfig::$shop['custom_order_fields'];
        }

        $postcustno = trim(filter_input(INPUT_POST, 'custno', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW));
        $postcountry = trim(filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW));
        $postpaymentmethod = filter_input(INPUT_POST, 'paymentmethod', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        $serverservername = filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_URL);
        $aData = [
            'customerversion' => $bCust,
            //'shc_css' => file_get_contents(PATH_DOCROOT.'screen-shc.css'),
            'datetime' => date('d.m.Y - H:i'),
            'custno' => $postcustno !== null && strlen($postcustno) >= HelperConfig::$customer['minimum_length_custno'] ? $postcustno : '',
            'corpname' => $this->getPostValue('corpname'),
            'name' => $this->getPostValue('name'),
            'street' => $this->getPostValue('street'),
            'zip' => $this->getPostValue('zip'),
            'town' => $this->getPostValue('town'),
            'phone' => $this->getPostValue('phone'),
            'cellphone' => $this->getPostValue('cellphone'),
            'fax' => $this->getPostValue('fax'),
            'email' => $this->getPostValue('email'),
            'country' => !empty($postcountry) ?
            (
                isset(
                    HelperConfig::$countries['countries_' .HelperConfig::$lang][$postcountry]
                )
                    ? HelperConfig::$countries['countries_' .HelperConfig::$lang][$postcountry]
                    : $postcountry)
            : '',
            'remarks' => $this->getPostValue('remarks'),
            'tos' => $this->getPostValue('tos'),
            'cancellationdisclaimer' => $this->getPostValue('cancellationdisclaimer'),
            'paymentmethod' => $this->getPostValue('paymentmethod'),
            'shippingcost' => empty($_SESSION['shippingcost']) ? false : $_SESSION['shippingcost'],
            'paypallink' => $postpaymentmethod === 'paypal' ? $serverservername.'/_misc/paypal.html?id='.$iId : '',
            'sofortueberweisunglink' => $postpaymentmethod === 'sofortueberweisung' ?  $serverservername.'/_misc/sofortueberweisung.html?id='.$iId : '',
            'SESSION' => !$bCust ? Tools::debug($_SESSION, '$_SESSION', true, true) : '',
            'POST' => !$bCust ? Tools::debug($_POST, '$_POST', true, true) : '',
            'orderid' => $iId,
        ];

        $aM['customdata']['mail'] = $aData;

        return $this->serviceManager->get('twig')->render('shop/mail-order-html.twig', $aM);
    }

    /**
     * @return string
     */
    private function getNotification()
    {
        $return = '';
        $getmsg = filter_input(INPUT_GET, 'msg');
        $getcartkey = filter_input(INPUT_GET, 'cartkey', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        $getamount = filter_input(INPUT_GET, 'cartkey', FILTER_SANITIZE_NUMBER_INT);
        if (!empty($getmsg)) {
            if (
                ($getmsg === 'updated' && !empty($getcartkey) && !empty($getamount))
                || ($getmsg === 'removed' && !empty($getcartkey))
            ) {
                $return .= $this->textcats->T('shoppingcart_msg_'.$getmsg.'_1').' ';
                if (isset(HelperConfig::$shop['custom_order_fields']) && mb_strpos($getcartkey, '|') !== false) {
                    $mCartkeys = explode('|', $getcartkey);
                    foreach ($mCartkeys as $sKey => $sValue) {
                        if ($sKey == 0) {
                            $return .= $sValue.', ';
                        } else {
                            $TMP = explode(':', $sValue);
                            $return .= $this->textcats->T('shoppingcart_item_'.$TMP[0]).' '.$TMP[1].', ';
                            unset($TMP);
                        }
                    }
                    $return = Tools::cutStringend($return, 2);
                } else {
                    $return .= $getcartkey;
                }
                $return.= ' '.$this->textcats->T('shoppingcart_msg_'.$getmsg.'_2');
                if ($getmsg === 'updated') {
                    $return .= ' '.$getamount;
                }
                $return .= '<br><br>';
            }
        }

        return $return;
    }
}
