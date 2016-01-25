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

namespace HaaseIT\HCSF\Shop;


class Helper
{
    public static function showOrderStatusText($sStatusShort)
    {
        $sStatus = '';
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

    public static function calculateTotalFromDB($aOrder)
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

    public static function addAdditionalCostsToItems($C, $sLang, $aSumme, $iVATfull, $iVATreduced)
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
            $aOrder["fVersandkostennetto"] = self::getShippingcost($C, $sLang);
            $aOrder["fVersandkostenvat"] = $aOrder["fVersandkostennetto"] * $iVATfull / 100;
            $aOrder["fVersandkostenbrutto"] = $aOrder["fVersandkostennetto"] + $aOrder["fVersandkostenvat"];

            $aOrder["fSteuervoll"] = ($aOrder["fVoll"] * $iVATfull / 100) + $aOrder["fVersandkostenvat"];
            $aOrder["fVoll"] += $aOrder["fVersandkostennetto"];
            $aOrder["fGesamtnetto"] += $aOrder["fVersandkostennetto"];
            $aOrder["fGesamtbrutto"] = $aOrder["fGesamtnetto"] + $aOrder["fSteuervoll"] + $aOrder["fSteuererm"];
        } else $aOrder["fVersandkosten"] = 0;

        return $aOrder;
    }

    public static function getShippingcost($C, $sLang) {
        $fShippingcost = $C["shippingcoststandardrate"];

        if (isset($_SESSION["user"]["cust_country"])) {
            $sCountry = $_SESSION["user"]["cust_country"];
        } elseif (isset($_POST["doCheckout"]) && $_POST["doCheckout"] == 'yes' && isset($_POST["country"])) {
            $sCountry = trim(\HaaseIT\Tools::getFormfield("country"));
        } elseif (isset($_SESSION["formsave_addrform"]["country"])) {
            $sCountry = $_SESSION["formsave_addrform"]["country"];
        } else {
            $sCountry = \HaaseIT\HCSF\Customer\Helper::getDefaultCountryByConfig($C, $sLang);
        }

        foreach ($C["shippingcosts"] as $aValue) {
            if (isset($aValue["countries"][$sCountry])) {
                $fShippingcost = $aValue["cost"];
                break;
            }
        }

        return $fShippingcost;
    }

    public static function calculateCartItems($C, $aCart)
    {
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

    public static function refreshCartItems($C, $oItem) // bei login/logout ändern sich ggf die preise, shoppingcart neu berechnen
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

    public static function buildShoppingCartTable($aCart, $sLang, $C, $bReadonly = false, $sCustomergroup = '', $aErr = '', $iVATfull = '', $iVATreduced = '')
    {
        if ($iVATfull == '' && $iVATreduced == '') {
            $iVATfull = $C["vat"]["full"];
            $iVATreduced = $C["vat"]["reduced"];
        }
        $aSumme = self::calculateCartItems($C, $aCart);
        $aData["shoppingcart"] = array(
            'readonly' => $bReadonly,
            'customergroup' => $sCustomergroup,
            'cart' => $aCart,
            'rebategroups' => $C["rebate_groups"],
            'additionalcoststoitems' => self::addAdditionalCostsToItems($C, $sLang, $aSumme, $iVATfull, $iVATreduced),
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
}