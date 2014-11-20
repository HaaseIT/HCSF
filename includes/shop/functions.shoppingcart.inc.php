<?php

function showOrderStatusText($sStatusShort)
{
    if ($sStatusShort == 'y') {
        $sStatus = T("order_status_completed");
    } elseif ($sStatusShort == 'n') {
        $sStatus = T("order_status_open");
    } elseif ($sStatusShort == 'i') {
        $sStatus = T("order_status_inwork");
    } elseif ($sStatusShort == 's') {
        $sStatus = T("order_status_canceled");
    } elseif ($sStatusShort == 'd') {
        $sStatus = T("order_status_deleted");
    }

    return $sStatus;
}

function showMyOrders($COList)
{
    global $C, $DB;

    $sH = '';
    $sQ = "SELECT * FROM ".DB_ORDERTABLE." WHERE ";
    $sQ .= "o_custno = :custno ";
    $sQ .= "ORDER BY o_ordertimestamp DESC";

    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':custno', getUserData('cust_no'));
    $hResult->execute();

    if ($hResult->rowCount() >= 1) {
        while ($aRow = $hResult->fetch()) {
            $sStatus = showOrderStatusText($aRow["o_ordercompleted"]);

            if ($aRow["o_paymentmethod"] == 'prepay') $sPaymentmethod = T("order_paymentmethod_prepay");
            elseif ($aRow["o_paymentmethod"] == 'paypal') $sPaymentmethod = T("order_paymentmethod_paypal");
            elseif ($aRow["o_paymentmethod"] == 'debit') $sPaymentmethod = T("order_paymentmethod_debit");
            elseif ($aRow["o_paymentmethod"] == 'invoice') $sPaymentmethod = T("order_paymentmethod_invoice");
            else $sPaymentmethod = ucwords($aRow["o_paymentmethod"]);

            if ($aRow["o_paymentcompleted"] == 'y') $sPaymentstatus = ucwords(T("misc_yes"));
            else $sPaymentstatus = ucwords(T("misc_no"));

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
        $sH .= makeListtable($COList, $aData);
        //$sH .= debug($aData, true);
    } else $sH .= T("myorders_no_orders_to_display");

    return $sH;
}

function calculateTotalFromDB($aOrder)
{
    global $C;

    $fGesamtnetto = $aOrder["o_sumnettoall"];
    $fVoll = $aOrder["o_sumvoll"];
    $fSteuervoll = $aOrder["o_taxvoll"];
    $fGesamtbrutto = $aOrder["o_sumbruttoall"];
    $fSteuererm = $aOrder["o_taxerm"];

    if ($aOrder["o_mindermenge"] > 0) {
        $fVoll += $aOrder["o_mindermenge"];
        $fGesamtnetto += $aOrder["o_mindermenge"];
        $fSteuervoll = ($fVoll * $C["vat"]["19"] / 100);
        $fGesamtbrutto = $fGesamtnetto + $fSteuervoll + $fSteuererm; 
    }
    if ($aOrder["o_shippingcost"] > 0) {
        $fVoll += $aOrder["o_shippingcost"];
        $fGesamtnetto += $aOrder["o_shippingcost"];
        $fSteuervoll = ($fVoll * $C["vat"]["19"] / 100);
        $fGesamtbrutto = $fGesamtnetto + $fSteuervoll + $fSteuererm;
    }

    return 	$fGesamtbrutto;
}

function addAdditionalCostsToItems($aSumme) // Benutzt in paypal payment Seite, da hier nur die Daten aus der DB zur verfügung stehen
{
    global $C;
    //debug($aOrder);

    $fGesamtnetto = $aSumme["sumvoll"] + $aSumme["sumerm"];
    $fSteuervoll = round($aSumme["sumvoll"] * $C["vat"]["19"] / 100, 2);
    $fSteuererm = round($aSumme["sumerm"] * $C["vat"]["7"] / 100, 2);
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
        $aOrder["fSteuervoll"] = round($aOrder["fVoll"] * $C["vat"]["19"] / 100, 2);
        $aOrder["fGesamtbrutto"] = $aOrder["fGesamtnetto"] + $aOrder["fSteuervoll"] + $aOrder["fSteuererm"];
        $aOrder["iMindergebuehr_id"] = 1;
        $aOrder["fMindergebuehr"] = $C["reducedorderamountfee1"];
    } elseif($fGesamtnettoitems < $C["reducedorderamountnet2"]) {
        $aOrder["fVoll"] += $C["reducedorderamountfee2"];
        $aOrder["fGesamtnetto"] += $C["reducedorderamountfee2"];
        $aOrder["fSteuervoll"] = round($aOrder["fVoll"] * $C["vat"]["19"] / 100, 2);
        $aOrder["fGesamtbrutto"] = $aOrder["fGesamtnetto"] + $aOrder["fSteuervoll"] + $aOrder["fSteuererm"];
        $aOrder["iMindergebuehr_id"] = 2;
        $aOrder["fMindergebuehr"] = $C["reducedorderamountfee2"];
    }

    if (isset($C["shippingcoststandardrate"]) && $C["shippingcoststandardrate"] != 0 &&
    ((!isset($C["mindestbetragversandfrei"]) || !$C["mindestbetragversandfrei"]) || $fGesamtnettoitems < $C["mindestbetragversandfrei"]))  {
        $aOrder["fVersandkostennetto"] = getShippingcost($C);
        $aOrder["fVersandkostenvat"] = round($aOrder["fVersandkostennetto"] * $C["vat"]["19"] / 100, 2);
        $aOrder["fVersandkostenbrutto"] = $aOrder["fVersandkostennetto"] + $aOrder["fVersandkostenvat"];

        $aOrder["fSteuervoll"] = round($aOrder["fVoll"] * $C["vat"]["19"] / 100, 2) + $aOrder["fVersandkostenvat"];
        $aOrder["fVoll"] += $aOrder["fVersandkostennetto"];
        $aOrder["fGesamtnetto"] += $aOrder["fVersandkostennetto"];
        $aOrder["fGesamtbrutto"] = $aOrder["fGesamtnetto"] + $aOrder["fSteuervoll"] + $aOrder["fSteuererm"];
    } else $aOrder["fVersandkosten"] = 0;

    //debug($aOrder);

    return $aOrder;
}

function getShippingcost($C) {
    global $sLang;
    $fShippingcost = $C["shippingcoststandardrate"];

    $sCountry = '';
    if (isset($_SESSION["user"]["cust_country"])) {
        $sCountry = $_SESSION["user"]["cust_country"];
    } elseif (isset($_POST["doCheckout"]) && $_POST["doCheckout"] == 'yes' && isset($_POST["country"])) {
        $sCountry = trim(getFormfield("country"));
    } elseif (isset($_SESSION["formsave_addrform"]["country"])) {
        $sCountry = $_SESSION["formsave_addrform"]["country"];
    } else {
        $sCountry = getDefaultCountryByConfig($C, $sLang);
    }
    //debug($sCountry);

    foreach ($C["shippingcosts"] as $aValue) {
        if (isset($aValue["countries"][$sCountry])) {
            $fShippingcost = $aValue["cost"];
            break;
        }
    }

    return $fShippingcost;
}

function buildOrderMailBody($twig, $bCust = true, $iId = 0)
{
    global $C;

    $aSHC = buildShoppingCartTable($_SESSION["cart"], true);

    $aData = array(
        'customerversion' => $bCust,
        'shc_css' => file_get_contents(PATH_DOCROOT.'screen-shc.css'),
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
        'country' => (isset($_POST["country"]) && trim($_POST["country"]) != '' ? $_POST["country"] : ''),
        'remarks' => (isset($_POST["remarks"]) && trim($_POST["remarks"]) != '' ? $_POST["remarks"] : ''),
        'tos' => (isset($_POST["tos"]) && trim($_POST["tos"]) != '' ? $_POST["tos"] : ''),
        'cancellationdisclaimer' => (isset($_POST["cancellationdisclaimer"]) && trim($_POST["cancellationdisclaimer"]) != '' ? $_POST["cancellationdisclaimer"] : ''),
        'paymentmethod' => (isset($_POST["paymentmethod"]) && trim($_POST["paymentmethod"]) != '' ? $_POST["paymentmethod"] : ''),
        'shippingcost' => (!isset($_SESSION["shippingcost"]) || $_SESSION["shippingcost"] == 0 ? false : $_SESSION["shippingcost"]),
        'paypallink' => (isset($_POST["paymentmethod"]) && $_POST["paymentmethod"] == 'paypal' ?  $_SERVER["HTTP_HOST"].'/_misc/paypal.html?id='.$iId : ''),
        'SESSION' => (!$bCust ? debug($_SESSION, true) : ''),
        'POST' => (!$bCust ? debug($_POST, true) : ''),
    );

    $aM["customdata"] = $aSHC;
    $aM['currency'] = $C["waehrungssymbol"];
    if (isset($C["custom_order_fields"])) $aM["custom_order_fields"] = $C["custom_order_fields"];
    $aM["customdata"]["mail"] = $aData;
    //debug($aM, false, '$aM');
    
    $sH = $twig->render('shop/mail-order-html.twig', $aM);

    return $sH;
}

function calculateCartItems($aCart)
{
    global $C;
    //debug($aCart);
    $fErm = 0;
    $fVoll = 0;
    $fTaxErm = 0;
    $fTaxVoll = 0;
    foreach ($aCart as $aValue) {
        if ($C["vat_disable"]) {
            $fVoll += ($aValue["amount"] * $aValue["price"]["netto_use"]);
            //$fTaxVoll += 0;
        } else {
            if ($aValue["vat"] == "19") {
                $fVoll += ($aValue["amount"] * $aValue["price"]["netto_use"]);
                $fTaxVoll += ($aValue["amount"] * $aValue["price"]["netto_use"] * ($aValue["vat"] / 100));
            }
            if ($aValue["vat"] == "7") {
                $fErm += ($aValue["amount"] * $aValue["price"]["netto_use"]);
                $fTaxErm += ($aValue["amount"] * $aValue["price"]["netto_use"] * ($aValue["vat"] / 100));
            }
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
            //debug($aData);
            $_SESSION["cart"][$sKey]["price"] = $aData["item"][$sItemkey]["pricedata"];
        }
    }
}

function buildShoppingCartTable($aCart, $bReadonly = false, $sCustomergroup = '', $aErr = '')
{
    global $C;

    $aSumme = calculateCartItems($aCart);
    $aData["shoppingcart"] = array(
        'readonly' => $bReadonly,
        'customergroup' => $sCustomergroup,
        'cart' => $aCart,
        'rebategroups' => $C["rebate_groups"],
        'additionalcoststoitems' => addAdditionalCostsToItems($aSumme),
        'minimumorderamountnet' => $C["minimumorderamountnet"],
        'reducedorderamountnet1' => $C["reducedorderamountnet1"],
        'reducedorderamountnet2' => $C["reducedorderamountnet2"],
        'reducedorderamountfee1' => $C["reducedorderamountfee1"],
        'reducedorderamountfee2' => $C["reducedorderamountfee2"],
        'minimumamountforfreeshipping' => $C["minimumamountforfreeshipping"],
    );
    //debug($aData["additionalcoststoitems"]);

    if (!$bReadonly) {
        $aCartpricesums = $aData["shoppingcart"]["additionalcoststoitems"];
        //$aCartpricesums["mindergebuehr"] = $aData["shoppingcart"]["additionalcoststoitems"]["fMindergebuehr"];
        //$aCartpricesums["mindergebuehrid"] = $aData["shoppingcart"]["additionalcoststoitems"]["iMindergebuehr_id"];
        //$aCartpricesums["shippingcost"] = $aData["shoppingcart"]["additionalcoststoitems"]["fVersandkosten"];
        $_SESSION["cartpricesums"] = $aCartpricesums;
    }

    if ($aData["shoppingcart"]["additionalcoststoitems"]["bMindesterreicht"] && !$bReadonly) {
        $aData["customerform"] = buildCustomerForm('shoppingcart', $aErr);
    }
    
    return $aData;
}
