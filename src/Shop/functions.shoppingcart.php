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

function showOrderStatusText($sStatusShort)
{
    if ($sStatusShort == 'y') {
        $sStatus = \HaaseIT\Textcat::T("order_status_completed");
    } elseif ($sStatusShort == 'n') {
        $sStatus = \HaaseIT\Textcat::T("order_status_open");
    } elseif ($sStatusShort == 'i') {
        $sStatus = \HaaseIT\Textcat::T("order_status_inwork");
    } elseif ($sStatusShort == 's') {
        $sStatus = \HaaseIT\Textcat::T("order_status_canceled");
    } elseif ($sStatusShort == 'd') {
        $sStatus = \HaaseIT\Textcat::T("order_status_deleted");
    }

    return $sStatus;
}

function showMyOrders($COList, $twig, $DB)
{
    $sH = '';
    $sQ = "SELECT * FROM ".DB_ORDERTABLE." WHERE ";
    $sQ .= "o_custno = :custno ";
    $sQ .= "ORDER BY o_ordertimestamp DESC";

    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':custno', \HaaseIT\HCSF\Customer\Helper::getUserData('cust_no'));
    $hResult->execute();

    if ($hResult->rowCount() >= 1) {
        while ($aRow = $hResult->fetch()) {
            $sStatus = showOrderStatusText($aRow["o_ordercompleted"]);

            if ($aRow["o_paymentmethod"] == 'prepay') $sPaymentmethod = \HaaseIT\Textcat::T("order_paymentmethod_prepay");
            elseif ($aRow["o_paymentmethod"] == 'paypal') $sPaymentmethod = \HaaseIT\Textcat::T("order_paymentmethod_paypal");
            elseif ($aRow["o_paymentmethod"] == 'debit') $sPaymentmethod = \HaaseIT\Textcat::T("order_paymentmethod_debit");
            elseif ($aRow["o_paymentmethod"] == 'invoice') $sPaymentmethod = \HaaseIT\Textcat::T("order_paymentmethod_invoice");
            else $sPaymentmethod = ucwords($aRow["o_paymentmethod"]);

            if ($aRow["o_paymentcompleted"] == 'y') $sPaymentstatus = ucwords(\HaaseIT\Textcat::T("misc_yes"));
            else $sPaymentstatus = ucwords(\HaaseIT\Textcat::T("misc_no"));

            $aData[] = array(
                'o_id' => $aRow["o_id"],
                'o_order_status' => $sStatus,
                'o_ordertime' => date("d.m.y H:i", $aRow["o_ordertimestamp"]),
                'o_paymentmethod' => $sPaymentmethod,
                'o_paymentcompleted' => $sPaymentstatus,
                'o_shipping_service' => $aRow["o_shipping_service"],
                'o_shipping_trackingno' => $aRow["o_shipping_trackingno"],
           );
        }
        $sH .= \HaaseIT\Tools::makeListtable($COList, $aData, $twig);
        //HaaseIT\Tools::debug($aData);
    } else $sH .= \HaaseIT\Textcat::T("myorders_no_orders_to_display");

    return $sH;
}

function calculateTotalFromDB($aOrder)
{
    $fGesamtnetto = $aOrder["o_sumnettoall"];
    $fVoll = $aOrder["o_sumvoll"];
    $fSteuervoll = $aOrder["o_taxvoll"];
    $fGesamtbrutto = $aOrder["o_sumbruttoall"];
    $fSteuererm = $aOrder["o_taxerm"];

    if ($aOrder["o_mindermenge"] > 0) {
        $fVoll += $aOrder["o_mindermenge"];
        $fGesamtnetto += $aOrder["o_mindermenge"];
        $fSteuervoll = ($fVoll * $aOrder["o_vatfull"] / 100);
        $fGesamtbrutto = $fGesamtnetto + $fSteuervoll + $fSteuererm; 
    }
    if ($aOrder["o_shippingcost"] > 0) {
        $fVoll += $aOrder["o_shippingcost"];
        $fGesamtnetto += $aOrder["o_shippingcost"];
        $fSteuervoll = ($fVoll * $aOrder["o_vatfull"] / 100);
        $fGesamtbrutto = $fGesamtnetto + $fSteuervoll + $fSteuererm;
    }

    return $fGesamtbrutto;
}

function addAdditionalCostsToItems($C, $sLang, $aSumme, $iVATfull, $iVATreduced)
{
    $fGesamtnetto = $aSumme["sumvoll"] + $aSumme["sumerm"];
    $fSteuervoll = $aSumme["sumvoll"] * $iVATfull / 100;
    $fSteuererm = $aSumme["sumerm"] * $iVATreduced / 100;
    $fGesamtbrutto = $fGesamtnetto + $fSteuervoll + $fSteuererm;

    $aOrder = array(
        'sumvoll' => $aSumme["sumvoll"],
        'sumerm' => $aSumme["sumerm"],
        'sumnettoall' => $fGesamtnetto,
        'taxvoll' => $fSteuervoll,
        'taxerm' => $fSteuererm,
        'sumbruttoall' => $fGesamtbrutto,
   );

    $fGesamtnettoitems = $aOrder["sumnettoall"];
    $aOrder["fVoll"] = $aOrder["sumvoll"];
    $aOrder["fErm"] = $aOrder["sumerm"];
    $aOrder["fGesamtnetto"] = $aOrder["sumnettoall"];
    $aOrder["fSteuervoll"] = $aOrder["taxvoll"];
    $aOrder["fSteuererm"] = $aOrder["taxerm"];
    $aOrder["fGesamtbrutto"] = $aOrder["sumbruttoall"];

    $aOrder["bMindesterreicht"] = true;
    $aOrder["fMindergebuehr"] = 0;
    $aOrder["iMindergebuehr_id"] = 0;
    if ($fGesamtnettoitems < $C["minimumorderamountnet"]) {
        $aOrder["bMindesterreicht"] = false;
        $aOrder["iMindergebuehr_id"] = 0;
    } elseif ($fGesamtnettoitems < $C["reducedorderamountnet1"]) {
        $aOrder["fVoll"] += $C["reducedorderamountfee1"];
        $aOrder["fGesamtnetto"] += $C["reducedorderamountfee1"];
        $aOrder["fSteuervoll"] = $aOrder["fVoll"] * $iVATfull / 100;
        $aOrder["fGesamtbrutto"] = $aOrder["fGesamtnetto"] + $aOrder["fSteuervoll"] + $aOrder["fSteuererm"];
        $aOrder["iMindergebuehr_id"] = 1;
        $aOrder["fMindergebuehr"] = $C["reducedorderamountfee1"];
    } elseif($fGesamtnettoitems < $C["reducedorderamountnet2"]) {
        $aOrder["fVoll"] += $C["reducedorderamountfee2"];
        $aOrder["fGesamtnetto"] += $C["reducedorderamountfee2"];
        $aOrder["fSteuervoll"] = $aOrder["fVoll"] * $iVATfull / 100;
        $aOrder["fGesamtbrutto"] = $aOrder["fGesamtnetto"] + $aOrder["fSteuervoll"] + $aOrder["fSteuererm"];
        $aOrder["iMindergebuehr_id"] = 2;
        $aOrder["fMindergebuehr"] = $C["reducedorderamountfee2"];
    }

    if (isset($C["shippingcoststandardrate"]) && $C["shippingcoststandardrate"] != 0 &&
    ((!isset($C["mindestbetragversandfrei"]) || !$C["mindestbetragversandfrei"]) || $fGesamtnettoitems < $C["mindestbetragversandfrei"]))  {
        $aOrder["fVersandkostennetto"] = getShippingcost($C, $sLang);
        $aOrder["fVersandkostenvat"] = $aOrder["fVersandkostennetto"] * $iVATfull / 100;
        $aOrder["fVersandkostenbrutto"] = $aOrder["fVersandkostennetto"] + $aOrder["fVersandkostenvat"];

        $aOrder["fSteuervoll"] = ($aOrder["fVoll"] * $iVATfull / 100) + $aOrder["fVersandkostenvat"];
        $aOrder["fVoll"] += $aOrder["fVersandkostennetto"];
        $aOrder["fGesamtnetto"] += $aOrder["fVersandkostennetto"];
        $aOrder["fGesamtbrutto"] = $aOrder["fGesamtnetto"] + $aOrder["fSteuervoll"] + $aOrder["fSteuererm"];
    } else $aOrder["fVersandkosten"] = 0;

    return $aOrder;
}

function getShippingcost($C, $sLang) {
    $fShippingcost = $C["shippingcoststandardrate"];

    $sCountry = '';
    if (isset($_SESSION["user"]["cust_country"])) {
        $sCountry = $_SESSION["user"]["cust_country"];
    } elseif (isset($_POST["doCheckout"]) && $_POST["doCheckout"] == 'yes' && isset($_POST["country"])) {
        $sCountry = trim(\HaaseIT\Tools::getFormfield("country"));
    } elseif (isset($_SESSION["formsave_addrform"]["country"])) {
        $sCountry = $_SESSION["formsave_addrform"]["country"];
    } else {
        $sCountry = \HaaseIT\HCSF\Customer\Helper::getDefaultCountryByConfig($C, $sLang);
    }
    //HaaseIT\Tools::debug($sCountry);

    foreach ($C["shippingcosts"] as $aValue) {
        if (isset($aValue["countries"][$sCountry])) {
            $fShippingcost = $aValue["cost"];
            break;
        }
    }

    return $fShippingcost;
}

function buildOrderMailBody($C, $sLang, $twig, $bCust = true, $iId = 0)
{
    $aSHC = buildShoppingCartTable($_SESSION["cart"], $sLang, $C, true);

    $aData = array(
        'customerversion' => $bCust,
        //'shc_css' => file_get_contents(PATH_DOCROOT.'screen-shc.css'),
        'datetime' => date("d.m.Y - H:i"),
        'custno' => (isset($_POST["custno"]) && strlen(trim($_POST["custno"])) >= $C["minimum_length_custno"] ? $_POST["custno"] : ''),
        'corpname' => (isset($_POST["corpname"]) && trim($_POST["corpname"]) != '' ? $_POST["corpname"] : ''),
        'name' => (isset($_POST["name"]) && trim($_POST["name"]) != '' ? $_POST["name"] : ''),
        'street' => (isset($_POST["street"]) && trim($_POST["street"]) != '' ? $_POST["street"] : ''),
        'zip' => (isset($_POST["zip"]) && trim($_POST["zip"]) != '' ? $_POST["zip"] : ''),
        'town' => (isset($_POST["town"]) && trim($_POST["town"]) != '' ? $_POST["town"] : ''),
        'phone' => (isset($_POST["phone"]) && trim($_POST["phone"]) != '' ? $_POST["phone"] : ''),
        'cellphone' => (isset($_POST["cellphone"]) && trim($_POST["cellphone"]) != '' ? $_POST["cellphone"] : ''),
        'fax' => (isset($_POST["fax"]) && trim($_POST["fax"]) != '' ? $_POST["fax"] : ''),
        'email' => (isset($_POST["email"]) && trim($_POST["email"]) != '' ? $_POST["email"] : ''),
        'country' => (isset($_POST["country"]) && trim($_POST["country"]) != '' ? (isset($C["countries_".$sLang][$_POST["country"]]) ? $C["countries_".$sLang][$_POST["country"]] : $_POST["country"]) : ''),
        'remarks' => (isset($_POST["remarks"]) && trim($_POST["remarks"]) != '' ? $_POST["remarks"] : ''),
        'tos' => (isset($_POST["tos"]) && trim($_POST["tos"]) != '' ? $_POST["tos"] : ''),
        'cancellationdisclaimer' => (isset($_POST["cancellationdisclaimer"]) && trim($_POST["cancellationdisclaimer"]) != '' ? $_POST["cancellationdisclaimer"] : ''),
        'paymentmethod' => (isset($_POST["paymentmethod"]) && trim($_POST["paymentmethod"]) != '' ? $_POST["paymentmethod"] : ''),
        'shippingcost' => (!isset($_SESSION["shippingcost"]) || $_SESSION["shippingcost"] == 0 ? false : $_SESSION["shippingcost"]),
        'paypallink' => (isset($_POST["paymentmethod"]) && $_POST["paymentmethod"] == 'paypal' ?  $_SERVER["HTTP_HOST"].'/_misc/paypal.html?id='.$iId : ''),
        'sofortueberweisunglink' => (isset($_POST["paymentmethod"]) && $_POST["paymentmethod"] == 'sofortueberweisung' ?  $_SERVER["HTTP_HOST"].'/_misc/sofortueberweisung.html?id='.$iId : ''),
        'SESSION' => (!$bCust ? HaaseIT\Tools::debug($_SESSION, '$_SESSION', true, true) : ''),
        'POST' => (!$bCust ? HaaseIT\Tools::debug($_POST, '$_POST', true, true) : ''),
        'orderid' => $iId,
    );

    $aM["customdata"] = $aSHC;
    $aM['currency'] = $C["waehrungssymbol"];
    if (isset($C["custom_order_fields"])) $aM["custom_order_fields"] = $C["custom_order_fields"];
    $aM["customdata"]["mail"] = $aData;
    //HaaseIT\Tools::debug($aM, '$aM');
    
    $sH = $twig->render('shop/mail-order-html.twig', $aM);

    return $sH;
}

function calculateCartItems($C, $aCart)
{
    //HaaseIT\Tools::debug($aCart);
    $fErm = 0;
    $fVoll = 0;
    $fTaxErm = 0;
    $fTaxVoll = 0;
    foreach ($aCart as $aValue) {
        // Hmmmkay, so, if vat is not disabled and there is no vat id or none as vat id set to this item, then
        // use the full vat as default. Only use reduced if it is set. Gotta use something as default or item
        // will not add up to total price
        if ($aValue["vat"] != "reduced") {
            $fVoll += ($aValue["amount"] * $aValue["price"]["netto_use"]);
            $fTaxVoll += ($aValue["amount"] * $aValue["price"]["netto_use"] * ($C["vat"]["full"] / 100));
        } else {
            $fErm += ($aValue["amount"] * $aValue["price"]["netto_use"]);
            $fTaxErm += ($aValue["amount"] * $aValue["price"]["netto_use"] * ($C["vat"]["reduced"] / 100));
        }
    }
    $aSumme = array('sumvoll' => $fVoll, 'sumerm' => $fErm, 'taxvoll' => $fTaxVoll, 'taxerm' => $fTaxErm);

    return $aSumme;
}

function refreshCartItems($C, $oItem) // bei login/logout ändern sich ggf die preise, shoppingcart neu berechnen
{
    if (isset($_SESSION["cart"]) && count($_SESSION["cart"])) {
        foreach ($_SESSION["cart"] as $sKey => $aValue) {
            if (!isset($C["custom_order_fields"])) {
                $sItemkey = $sKey;
            } else {
                $TMP = explode('|', $sKey);
                $sItemkey = $TMP[0];
                unset($TMP);
            }
            $aData = $oItem->sortItems('', $sItemkey);
            //HaaseIT\Tools::debug($aData);
            $_SESSION["cart"][$sKey]["price"] = $aData["item"][$sItemkey]["pricedata"];
        }
    }
}

function buildShoppingCartTable($aCart, $sLang, $C, $bReadonly = false, $sCustomergroup = '', $aErr = '', $iVATfull = '', $iVATreduced = '')
{
    if ($iVATfull == '' && $iVATreduced == '') {
        $iVATfull = $C["vat"]["full"];
        $iVATreduced = $C["vat"]["reduced"];
    }
    $aSumme = calculateCartItems($C, $aCart);
    $aData["shoppingcart"] = array(
        'readonly' => $bReadonly,
        'customergroup' => $sCustomergroup,
        'cart' => $aCart,
        'rebategroups' => $C["rebate_groups"],
        'additionalcoststoitems' => addAdditionalCostsToItems($C, $sLang, $aSumme, $iVATfull, $iVATreduced),
        'minimumorderamountnet' => $C["minimumorderamountnet"],
        'reducedorderamountnet1' => $C["reducedorderamountnet1"],
        'reducedorderamountnet2' => $C["reducedorderamountnet2"],
        'reducedorderamountfee1' => $C["reducedorderamountfee1"],
        'reducedorderamountfee2' => $C["reducedorderamountfee2"],
        'minimumamountforfreeshipping' => $C["minimumamountforfreeshipping"],
    );
    //HaaseIT\Tools::debug($aData["additionalcoststoitems"]);

    if (!$bReadonly) {
        $aCartpricesums = $aData["shoppingcart"]["additionalcoststoitems"];
        //$aCartpricesums["mindergebuehr"] = $aData["shoppingcart"]["additionalcoststoitems"]["fMindergebuehr"];
        //$aCartpricesums["mindergebuehrid"] = $aData["shoppingcart"]["additionalcoststoitems"]["iMindergebuehr_id"];
        //$aCartpricesums["shippingcost"] = $aData["shoppingcart"]["additionalcoststoitems"]["fVersandkosten"];
        $_SESSION["cartpricesums"] = $aCartpricesums;
    }

    if ($aData["shoppingcart"]["additionalcoststoitems"]["bMindesterreicht"] && !$bReadonly) {
        $aData["customerform"] = \HaaseIT\HCSF\Customer\Helper::buildCustomerForm($C, $sLang, 'shoppingcart', $aErr);
    }
    
    return $aData;
}
