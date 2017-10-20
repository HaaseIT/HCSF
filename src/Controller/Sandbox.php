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

namespace HaaseIT\HCSF\Controller;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\PaymentExecution;

class Sandbox extends Base
{
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        \PayPal\Auth\OAuthTokenCredential::$CACHE_PATH = PATH_CACHE;
        \PayPal\Cache\AuthorizationCache::$CACHE_PATH = PATH_CACHE;


        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                $this->config->getShop('paypalrestv1')['clientid'],     // ClientID
                $this->config->getShop('paypalrestv1')['secret']      // ClientSecret
            )
        );

        if (!empty($_GET['execute'])) {
            $paymentId = filter_input(INPUT_GET, 'paymentId');
            $token = filter_input(INPUT_GET, 'token');
            $payerId = filter_input(INPUT_GET, 'payerId');

            try {
                $payment = Payment::get($paymentId, $apiContext);
            } catch (\Exception $e) {
                $this->P->oPayload->cl_html = print_r($e, true);
            }

            $execution = new PaymentExecution();
            $execution->setPayerId($payerId);

            try {
                $result = $payment->execute($execution, $apiContext);
            } catch (\Exception $e) {
                $this->P->oPayload->cl_html = print_r($e, true);
            }

            $this->P->oPayload->cl_html = '<pre>'.print_r($result, true).'</pre>';

        } else {
            if (empty($_GET['success'])) {
                $payer = new Payer();
                $payer->setPaymentMethod("paypal");

                $item1 = new Item();
                $item1->setName('Ground Coffee 40 oz')
                    ->setCurrency('EUR')
                    ->setQuantity(1)
                    ->setSku("123123") // Similar to `item_number` in Classic API
                    ->setPrice('7');

                $itemList = new ItemList();
                $itemList->setItems(array($item1,));

                $details = new Details();
                $details->setSubtotal('7')
                    ->setShipping('1')
                    ->setTax('0')
                ;

                $amount = new Amount();
                $amount->setCurrency("EUR")
                    ->setDetails($details)
                    ->setTotal('8')
                ;

                $transaction = new Transaction();
                $transaction->setAmount($amount)
                    ->setItemList($itemList)
                    ->setDescription("Payment description")
                    ->setInvoiceNumber(uniqid())

                ;

                $baseUrl = 'https://dev13.haase-it.com/_misc/sandbox.html';

                $redirectUrls = new RedirectUrls();
                $redirectUrls->setReturnUrl($baseUrl."?success=true")
                    ->setCancelUrl($baseUrl."?success=false");

                $payment = new Payment();
                $payment->setIntent("sale")
                    ->setPayer($payer)
                    ->setRedirectUrls($redirectUrls)
                    ->setTransactions(array($transaction))
                ;


                try {
                    $payment->create($apiContext);
                } catch (\Exception $e) {
                    $this->P->oPayload->cl_html = print_r($e, true);
                }

                $approvalLink = $payment->getApprovalLink();

                $html = <<< HTML
<script src="https://www.paypalobjects.com/webstatic/ppplus/ppplus.min.js" type="text/javascript"></script>
<div id="ppplus">
</div>
<script type="application/javascript">
var ppp = PAYPAL.apps.PPP({
"approvalUrl": "$approvalLink",
"placeholder": "ppplus",
"mode": "sandbox",
"country": "DE"
});
</script>
HTML;



                $this->P->oPayload->cl_html = $html;



            } else {
                $success = filter_input(INPUT_GET, 'success');
                if ($success === 'true') {
                    $paymentId = filter_input(INPUT_GET, 'paymentId');
                    $token = filter_input(INPUT_GET, 'token');
                    $payerId = filter_input(INPUT_GET, 'PayerID');

                    try {
                        $payment = Payment::get($paymentId, $apiContext);
                    } catch (\Exception $e) {
                        $this->P->oPayload->cl_html = print_r($e, true);
                    }

                    $payer = $payment->getPayer();
                    $transactions = $payment->getTransactions();
                    $payerinfo = $payer->getPayerInfo();
                    $billing = $payerinfo->getBillingAddress();
                    $itemlist = $transactions[0]->getItemList();
                    $shipping = $itemlist->getShippingAddress();
                    $items = $itemlist->getItems();
                    $payee = $transactions[0]->getPayee();
                    $amount = $transactions[0]->getAmount();


                    $debug = '<pre>'
                        .print_r($shipping, true).'<br><br>'
                        .print_r($billing, true).'<br><br>'
                        .print_r($payerinfo, true).'<br><br>'
//                    .print_r($payer, true).'<br><br>'
//                    .print_r($transactions, true).'<br><br>'
//                    .print_r($payment, true).'<br><br>'
                        .print_r($items, true).'<br><br>'
                        .print_r($payee, true).'<br><br>'
                        .print_r($amount, true).'<br><br>'
                        .'</pre>'
                    ;


                    $html = <<< HTML
<a href="/_misc/sandbox.html?execute=true&amp;payerId=$payerId&amp;paymentId=$paymentId" style="background: black; color: white;padding:10px;">Execute Payment</a> 
HTML;


                    $this->P->oPayload->cl_html = $html.'<br><br>'.$debug;

                } else {
                    echo 'false';
                }
            }
        }
    }
}
