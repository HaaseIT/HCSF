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

        $aOrder = [
            'sumvoll' => $aSumme["sumvoll"],
            'sumerm' => $aSumme["sumerm"],
            'sumnettoall' => $fGesamtnetto,
            'taxvoll' => $fSteuervoll,
            'taxerm' => $fSteuererm,
            'sumbruttoall' => $fGesamtbrutto,
        ];

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
        $aSumme = ['sumvoll' => $fVoll, 'sumerm' => $fErm, 'taxvoll' => $fTaxVoll, 'taxerm' => $fTaxErm];

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
        $aData["shoppingcart"] = [
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
        ];

        if (!$bReadonly) {
            $aCartpricesums = $aData["shoppingcart"]["additionalcoststoitems"];
            $_SESSION["cartpricesums"] = $aCartpricesums;
        }

        if ($aData["shoppingcart"]["additionalcoststoitems"]["bMindesterreicht"] && !$bReadonly) {
            $aData["customerform"] = \HaaseIT\HCSF\Customer\Helper::buildCustomerForm($C, $sLang, 'shoppingcart', $aErr);
        }

        return $aData;
    }

    public static function getShoppingcartData($C)
    {
        if ((!$C["show_pricesonlytologgedin"] || \HaaseIT\HCSF\Customer\Helper::getUserData()) && isset($_SESSION["cart"]) && count($_SESSION["cart"])) {
            $aCartsums = \HaaseIT\HCSF\Shop\Helper::calculateCartItems($C, $_SESSION["cart"]);
            $aCartinfo = [
                'numberofitems' => count($_SESSION["cart"]),
                'cartsums' => $aCartsums,
                'cartsumnetto' => $aCartsums["sumvoll"] + $aCartsums["sumerm"],
                'cartsumbrutto' => $aCartsums["sumvoll"] + $aCartsums["sumerm"] + $aCartsums["taxerm"] + $aCartsums["taxvoll"],
            ];
            unset($aCartsums);
            foreach ($_SESSION["cart"] as $sKey => $aValue) {
                $aCartinfo["cartitems"][$sKey] = [
                    'cartkey' => $sKey,
                    'name' => $aValue["name"],
                    'amount' => $aValue["amount"],
                    'img' => $aValue["img"],
                    'price' => $aValue["price"],
                ];
            }
        } else {
            $aCartinfo = [
                'numberofitems' => 0,
                'cartsums' => [],
                'cartsumnetto' => 0,
                'cartsumbrutto' => 0,
            ];
        }

        return $aCartinfo;
    }

    public static function getItemSuggestions($iSuggestionsToBuild, $oItem, $aPossibleSuggestions, $sSetSuggestions, $sCurrentitem, $mItemindex, $aItemindexpathtreeforsuggestions)
    {
        //$aPossibleSuggestions = $aP["items"]["item"]; // put all possible suggestions that are already loaded into this array
        unset($aPossibleSuggestions[$sCurrentitem]); // remove the currently shown item from this list, we do not want to show it as a suggestion

        $aDefinedSuggestions = [];
        if (trim($sSetSuggestions) != '') {
            if (mb_strpos($sSetSuggestions, '|') !== false) {
                $aDefinedSuggestions = explode('|', $sSetSuggestions); // convert all defined suggestions to array
            } else {
                $aDefinedSuggestions[] = $sSetSuggestions;
            }
        }
        foreach ($aDefinedSuggestions as $aDefinedSuggestionsValue) { // iterate all defined suggestions and put those not loaded yet into array
            if (!isset($aPossibleSuggestions[$aDefinedSuggestionsValue])) {
                $aSuggestionsToLoad[] = $aDefinedSuggestionsValue;
            }
        }
        if (isset($aSuggestionsToLoad)) { // if there are not yet loaded suggestions, load them
            $aItemsNotInCategory = $oItem->sortItems('', $aSuggestionsToLoad, false);
            if (isset($aItemsNotInCategory)) { // merge loaded and newly loaded items
                $aPossibleSuggestions = array_merge($aPossibleSuggestions, $aItemsNotInCategory["item"]);
            }
        }
        unset($aSuggestionsToLoad, $aItemsNotInCategory);
        $aSuggestions = [];
        $aAdditionalSuggestions = [];
        foreach ($aPossibleSuggestions as $aPossibleSuggestionsKey => $aPossibleSuggestionsValue) { // iterate through all possible suggestions
            if (in_array($aPossibleSuggestionsKey, $aDefinedSuggestions)) { // if this suggestion is a defined one, put into this array
                $aSuggestions[$aPossibleSuggestionsKey] = $aPossibleSuggestionsValue;
            } else { // if not, put into this one
                $aAdditionalSuggestions[$aPossibleSuggestionsKey] = $aPossibleSuggestionsValue;
            }
        }
        unset($aPossibleSuggestions, $aDefinedSuggestions); // not needed anymore
        $iNumberOfSuggestions = count($aSuggestions);
        $iNumberOfAdditionalSuggestions = count($aAdditionalSuggestions);
        if ($iNumberOfSuggestions > $iSuggestionsToBuild) { // if there are more suggestions than should be displayed, randomly pick as many as to be shown
            $aKeysSuggestions = array_rand($aSuggestions, $iSuggestionsToBuild); // get the array keys that will stay
            foreach ($aSuggestions as $aSuggestionsKey => $aSuggestionsValue) { // iterate suggestions and remove those that which will not be kept
                if (!in_array($aSuggestionsKey, $aKeysSuggestions)) {
                    unset($aSuggestions[$aSuggestionsKey]);
                }
            }
            unset($aKeysSuggestions);
        } else { // if less or equal continue here
            if ($iNumberOfSuggestions < $iSuggestionsToBuild && $iNumberOfAdditionalSuggestions > 0) { // if there are less suggestions than should be displayed and there are additional available
                $iAdditionalSuggestionsRequired = $iSuggestionsToBuild - $iNumberOfSuggestions; // how many more are needed?
                if ($iNumberOfAdditionalSuggestions > $iAdditionalSuggestionsRequired) { // see if there are more available than required, if so, pick as many as needed
                    if ($iAdditionalSuggestionsRequired == 1) { // since array_rand returns a string and no array if there is only one row picked, we have to do this awkward dance
                        $aKeysAdditionalSuggestions[] = array_rand($aAdditionalSuggestions, $iAdditionalSuggestionsRequired);
                    } else {
                        $aKeysAdditionalSuggestions = array_rand($aAdditionalSuggestions, $iAdditionalSuggestionsRequired);
                    }
                    foreach ($aAdditionalSuggestions as $aAdditionalSuggestionsKey => $aAdditionalSuggestionsValue) { // iterate suggestions and remove those that which will not be kept
                        if (!in_array($aAdditionalSuggestionsKey, $aKeysAdditionalSuggestions)) {
                            unset($aAdditionalSuggestions[$aAdditionalSuggestionsKey]);
                        }
                    }
                    unset($aKeysAdditionalSuggestions);
                }
                $aSuggestions = array_merge($aSuggestions, $aAdditionalSuggestions); // merge
                unset($iAdditionalSuggestionsRequired);
            }
        }
        foreach ($aSuggestions as $aSuggestionsKey => $aSuggestionsValue) { // build the paths to the suggested items
            if (mb_strpos($aSuggestionsValue["itm_index"], '|') !== false) { // check if the suggestions itemindex contains multiple indexes, if so explode an array
                $aSuggestionIndexes = explode('|', $aSuggestionsValue["itm_index"]);
                foreach ($aSuggestionIndexes as $sSuggestionIndexesValue) { // iterate through these indexes
                    if (isset($mItemindex)) { // check if there is an index configured on this page
                        if (is_array($mItemindex)) { // check if it is an array
                            if (in_array($sSuggestionIndexesValue, $mItemindex)) { // if the suggestions index is in that array, set path to empty string
                                $aSuggestions[$aSuggestionsKey]["path"] = '';
                                continue 2; // path to suggestion set, continue with next suggestion
                            }
                        } else {
                            if ($mItemindex == $sSuggestionIndexesValue) { // if the suggestion index is on this page, set path to empty string
                                $aSuggestions[$aSuggestionsKey]["path"] = '';
                                continue 2; // path to suggestion set, continue with next suggestion
                            }
                        }
                    }
                    if (isset($aItemindexpathtreeforsuggestions[$sSuggestionIndexesValue])) {
                        $aSuggestions[$aSuggestionsKey]["path"] = $aItemindexpathtreeforsuggestions[$sSuggestionIndexesValue];
                        continue 2;
                    }
                }
                unset($aSuggestionIndexes);
            } else {
                if (isset($aItemindexpathtreeforsuggestions[$aSuggestionsValue["itm_index"]])) {
                    $aSuggestions[$aSuggestionsKey]["path"] = $aItemindexpathtreeforsuggestions[$aSuggestionsValue["itm_index"]];
                }
            }
        }
        shuffle($aSuggestions);

        return $aSuggestions;
    }

    static function handleItemPage($C, $oItem, $P, $aP)
    {
        if (isset($P->cb_pageconfig->itemindex)) {
            $mItemIndex = $P->cb_pageconfig->itemindex;
        } else {
            $mItemIndex = '';
        }
        $aP["items"] = $oItem->sortItems($mItemIndex, '', ($aP["pagetype"] == 'itemoverviewgrpd' ? true : false));
        if ($aP["pagetype"] == 'itemdetail') {

            $aP["itemindexpathtreeforsuggestions"] = $oItem->getItemPathTree();

            if (isset($aP["pageconfig"]->itemindex)) {
                if (is_array($aP["pageconfig"]->itemindex)) {
                    foreach ($aP["pageconfig"]->itemindex as $sItemIndexValue) {
                        $aP["itemindexpathtreeforsuggestions"][$sItemIndexValue] = '';
                    }
                } else {
                    $aP["itemindexpathtreeforsuggestions"][$aP["pageconfig"]->itemindex] = '';
                }
            }

            // Change pagetype to itemoverview, will be changed back to itemdetail once the item is found
            // if it is not found, we will show the overview
            $aP["pagetype"] = 'itemoverview';
            if (count($aP["items"]["item"])) {
                foreach ($aP["items"]["item"] as $sKey => $aValue) {
                    if ($aValue['itm_no'] == $P->cb_pageconfig->itemno) {
                        $aP["pagetype"] = 'itemdetail';
                        $aP["item"]["data"] = $aValue;
                        $aP["item"]["key"] = $sKey;

                        $iPositionInItems = array_search($sKey, $aP["items"]["itemkeys"]);
                        $aP["item"]["currentitem"] = $iPositionInItems + 1;
                        if ($iPositionInItems == 0) {
                            $aP["item"]["previtem"] = $aP["items"]["itemkeys"][$aP["items"]["totalitems"] - 1];
                        } else {
                            $aP["item"]["previtem"] = $aP["items"]["itemkeys"][$iPositionInItems - 1];
                        }
                        if ($iPositionInItems == $aP["items"]["totalitems"] - 1) {
                            $aP["item"]["nextitem"] = $aP["items"]["itemkeys"][0];
                        } else {
                            $aP["item"]["nextitem"] = $aP["items"]["itemkeys"][$iPositionInItems + 1];
                        }
                        // build item suggestions if needed
                        if ($C["itemdetail_suggestions"] > 0) {
                            $aP["item"]["suggestions"] = self::getItemSuggestions(
                                $C["itemdetail_suggestions"],
                                $oItem,
                                $aP["items"]["item"],
                                (!empty($aValue['itm_data']["suggestions"]) ? $aValue['itm_data']["suggestions"] : ''),
                                $sKey,
                                (!empty($aP["pageconfig"]->itemindex) ? $aP["pageconfig"]->itemindex : ''),
                                (!empty($aP["itemindexpathtreeforsuggestions"]) ? $aP["itemindexpathtreeforsuggestions"] : [])
                            );
                        }
                        // Wenn der Artikel gefunden wurde können wir das Ausführen der Suche beenden.
                        break;
                    }
                }
            }
        }

        return $aP;
    }
}