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


use Zend\ServiceManager\ServiceManager;

/**
 * Class Helper
 * @package HaaseIT\HCSF\Shop
 */
class Helper
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var \HaaseIT\HCSF\HelperConfig
     */
    protected $config;

    /**
     * @var array
     */
    protected $shop = [];

    /**
     * @var \HaaseIT\HCSF\Customer\Helper
     */
    protected $helperCustomer;

    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        $this->config = $serviceManager->get('config');
        $this->shop = $this->config->getShop();
        $this->helperCustomer = $serviceManager->get('helpercustomer');
    }

    /**
     * @param \HaaseIT\Toolbox\Textcat $textcats
     * @param string $sStatusShort
     * @return bool|string
     */
    public function showOrderStatusText(\HaaseIT\Toolbox\Textcat $textcats, $sStatusShort)
    {
        $mapping = [
            'y' => 'order_status_completed',
            'n' => 'order_status_open',
            'i' => 'order_status_inwork',
            's' => 'order_status_canceled',
            'd' => 'order_status_deleted',
        ];

        if (!empty($mapping[$sStatusShort])) {
            return $textcats->T($mapping[$sStatusShort]);
        }

        return '';
    }

    /**
     * @param array $aOrder
     * @return float
     */
    public function calculateTotalFromDB($aOrder)
    {
        $fGesamtnetto = $aOrder['o_sumnettoall'];
        $fVoll = $aOrder['o_sumvoll'];
        $fSteuererm = $aOrder['o_taxerm'];

        if ($aOrder['o_mindermenge'] > 0) {
            $fVoll += $aOrder['o_mindermenge'];
            $fGesamtnetto += $aOrder['o_mindermenge'];
        }
        if ($aOrder['o_shippingcost'] > 0) {
            $fVoll += $aOrder['o_shippingcost'];
            $fGesamtnetto += $aOrder['o_shippingcost'];
        }

        $fSteuervoll = ($fVoll * $aOrder['o_vatfull'] / 100);

        return $fGesamtnetto + $fSteuervoll + $fSteuererm;
    }

    /**
     * @param array $aSumme
     * @param int $iVATfull
     * @param int $iVATreduced
     * @return array
     */
    public function addAdditionalCostsToItems($aSumme, $iVATfull, $iVATreduced)
    {
        $fGesamtnetto = $aSumme['sumvoll'] + $aSumme['sumerm'];
        $fSteuervoll = $aSumme['sumvoll'] * $iVATfull / 100;
        $fSteuererm = $aSumme['sumerm'] * $iVATreduced / 100;
        $fGesamtbrutto = $fGesamtnetto + $fSteuervoll + $fSteuererm;

        $aOrder = [
            'sumvoll' => $aSumme['sumvoll'],
            'sumerm' => $aSumme['sumerm'],
            'sumnettoall' => $fGesamtnetto,
            'taxvoll' => $fSteuervoll,
            'taxerm' => $fSteuererm,
            'sumbruttoall' => $fGesamtbrutto,
        ];

        $fGesamtnettoitems = $aOrder['sumnettoall'];
        $aOrder['fVoll'] = $aOrder['sumvoll'];
        $aOrder['fErm'] = $aOrder['sumerm'];
        $aOrder['fGesamtnetto'] = $aOrder['sumnettoall'];
        $aOrder['fSteuervoll'] = $aOrder['taxvoll'];
        $aOrder['fSteuererm'] = $aOrder['taxerm'];
        $aOrder['fGesamtbrutto'] = $aOrder['sumbruttoall'];

        $aOrder['bMindesterreicht'] = true;
        $aOrder['fMindergebuehr'] = 0;
        $aOrder['iMindergebuehr_id'] = 0;
        if ($fGesamtnettoitems < $this->shop['minimumorderamountnet']) {
            $aOrder['bMindesterreicht'] = false;
            $aOrder['iMindergebuehr_id'] = 0;
        } elseif ($fGesamtnettoitems < $this->shop['reducedorderamountnet1']) {
            $aOrder['iMindergebuehr_id'] = 1;

        } elseif ($fGesamtnettoitems < $this->shop['reducedorderamountnet2']) {
            $aOrder['iMindergebuehr_id'] = 2;
        }

        if ($aOrder['iMindergebuehr_id'] > 0) {
            $aOrder['fVoll'] += $this->shop['reducedorderamountfee'.$aOrder['iMindergebuehr_id']];
            $aOrder['fGesamtnetto'] += $this->shop['reducedorderamountfee'.$aOrder['iMindergebuehr_id']];
            $aOrder['fSteuervoll'] = $aOrder['fVoll'] * $iVATfull / 100;
            $aOrder['fGesamtbrutto'] = $aOrder['fGesamtnetto'] + $aOrder['fSteuervoll'] + $aOrder['fSteuererm'];
            $aOrder['fMindergebuehr'] = $this->shop['reducedorderamountfee'.$aOrder['iMindergebuehr_id']];
        }

        $aOrder['fVersandkosten'] = 0;
        if (
            isset($this->shop['shippingcoststandardrate'])
            && $this->shop['shippingcoststandardrate'] != 0
            &&
            (
                (
                    !isset($this->shop['mindestbetragversandfrei'])
                    || !$this->shop['mindestbetragversandfrei']
                )
                || $fGesamtnettoitems < $this->shop['mindestbetragversandfrei']
            )
        ) {
            $aOrder['fVersandkostennetto'] = self::getShippingcost();
            $aOrder['fVersandkostenvat'] = $aOrder['fVersandkostennetto'] * $iVATfull / 100;
            $aOrder['fVersandkostenbrutto'] = $aOrder['fVersandkostennetto'] + $aOrder['fVersandkostenvat'];

            $aOrder['fSteuervoll'] = ($aOrder['fVoll'] * $iVATfull / 100) + $aOrder['fVersandkostenvat'];
            $aOrder['fVoll'] += $aOrder['fVersandkostennetto'];
            $aOrder['fGesamtnetto'] += $aOrder['fVersandkostennetto'];
            $aOrder['fGesamtbrutto'] = $aOrder['fGesamtnetto'] + $aOrder['fSteuervoll'] + $aOrder['fSteuererm'];
        }

        return $aOrder;
    }

    /**
     * @return mixed
     */
    public function getShippingcost()
    {
        $fShippingcost = $this->shop['shippingcoststandardrate'];

        $sCountry = $this->helperCustomer->getDefaultCountryByConfig($this->config->getLang());
        $postdocheckout = filter_input(INPUT_POST, 'doCheckout');
        if (isset($_SESSION['user']['cust_country'])) {
            $sCountry = $_SESSION['user']['cust_country'];
        } elseif (filter_input(INPUT_POST, 'country') !== null && $postdocheckout === 'yes') {
            $sCountry = trim(\HaaseIT\Toolbox\Tools::getFormfield('country'));
        } elseif (isset($_SESSION['formsave_addrform']['country'])) {
            $sCountry = $_SESSION['formsave_addrform']['country'];
        }

        foreach ($this->shop['shippingcosts'] as $aValue) {
            if (isset($aValue['countries'][$sCountry])) {
                $fShippingcost = $aValue['cost'];
                break;
            }
        }

        return $fShippingcost;
    }

    /**
     * @param array $aCart
     * @return array
     */
    public function calculateCartItems($aCart)
    {
        $fErm = 0;
        $fVoll = 0;
        $fTaxErm = 0;
        $fTaxVoll = 0;
        foreach ($aCart as $aValue) {
            // Hmmmkay, so, if vat is not disabled and there is no vat id or none as vat id set to this item, then
            // use the full vat as default. Only use reduced if it is set. Gotta use something as default or item
            // will not add up to total price
            if ($aValue['vat'] !== 'reduced') {
                $fVoll += ($aValue['amount'] * $aValue['price']['netto_use']);
                $fTaxVoll += ($aValue['amount'] * $aValue['price']['netto_use'] * ($this->shop['vat']['full'] / 100));
                continue;
            }

            $fErm += ($aValue['amount'] * $aValue['price']['netto_use']);
            $fTaxErm += ($aValue['amount'] * $aValue['price']['netto_use'] * ($this->shop['vat']['reduced'] / 100));
        }

        return [
            'sumvoll' => $fVoll,
            'sumerm' => $fErm,
            'taxvoll' => $fTaxVoll,
            'taxerm' => $fTaxErm
        ];
    }

    /**
     * @param ServiceManager $serviceManager
     */
    public function refreshCartItems(ServiceManager $serviceManager) // bei login/logout ändern sich ggf die preise, shoppingcart neu berechnen
    {
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $sKey => $aValue) {
                $sItemkey = $sKey;
                if (!empty($this->shop['custom_order_fields'])) {
                    $TMP = explode('|', $sKey);
                    $sItemkey = $TMP[0];
                    unset($TMP);
                }
                $aData = $serviceManager->get('oItem')->sortItems('', $sItemkey);
                $_SESSION['cart'][$sKey]['price'] = $aData['item'][$sItemkey]['pricedata'];
            }
        }
    }

    /**
     * @param array $aCart
     * @param bool $bReadonly
     * @param string $sCustomergroup
     * @param array $aErr
     * @param string $iVATfull
     * @param string $iVATreduced
     * @return mixed
     */
    public function buildShoppingCartTable(array $aCart, $bReadonly = false, $sCustomergroup = '', $aErr = [], $iVATfull = '', $iVATreduced = '')
    {
        if ($iVATfull === '' && $iVATreduced === '') {
            $iVATfull = $this->shop['vat']['full'];
            $iVATreduced = $this->shop['vat']['reduced'];
        }
        $aSumme = self::calculateCartItems($aCart);
        $aData['shoppingcart'] = [
            'readonly' => $bReadonly,
            'customergroup' => $sCustomergroup,
            'cart' => $aCart,
            'rebategroups' => $this->shop['rebate_groups'],
            'additionalcoststoitems' => self::addAdditionalCostsToItems($aSumme, $iVATfull, $iVATreduced),
            'minimumorderamountnet' => $this->shop['minimumorderamountnet'],
            'reducedorderamountnet1' => $this->shop['reducedorderamountnet1'],
            'reducedorderamountnet2' => $this->shop['reducedorderamountnet2'],
            'reducedorderamountfee1' => $this->shop['reducedorderamountfee1'],
            'reducedorderamountfee2' => $this->shop['reducedorderamountfee2'],
            'minimumamountforfreeshipping' => $this->shop['minimumamountforfreeshipping'],
        ];

        if (!$bReadonly) {
            $aCartpricesums = $aData['shoppingcart']['additionalcoststoitems'];
            $_SESSION['cartpricesums'] = $aCartpricesums;
        }

        if (!$bReadonly && $aData['shoppingcart']['additionalcoststoitems']['bMindesterreicht']) {
            $aData['customerform'] = $this->helperCustomer->buildCustomerForm($this->config->getLang(), 'shoppingcart', $aErr);
        }

        return $aData;
    }

    /**
     * @return array
     */
    public function getShoppingcartData()
    {
        $aCartinfo = [
            'numberofitems' => 0,
            'cartsums' => [],
            'cartsumnetto' => 0,
            'cartsumbrutto' => 0,
        ];
        if (isset($_SESSION['cart']) && (!$this->shop['show_pricesonlytologgedin'] || $this->helperCustomer->getUserData()) && count($_SESSION['cart'])) {
            $aCartsums = $this->calculateCartItems($_SESSION['cart']);
            $aCartinfo = [
                'numberofitems' => count($_SESSION['cart']),
                'cartsums' => $aCartsums,
                'cartsumnetto' => $aCartsums['sumvoll'] + $aCartsums['sumerm'],
                'cartsumbrutto' => $aCartsums['sumvoll'] + $aCartsums['sumerm'] + $aCartsums['taxerm'] + $aCartsums['taxvoll'],
            ];
            unset($aCartsums);
            foreach ($_SESSION['cart'] as $sKey => $aValue) {
                $aCartinfo['cartitems'][$sKey] = [
                    'cartkey' => $sKey,
                    'name' => $aValue['name'],
                    'amount' => $aValue['amount'],
                    'img' => $aValue['img'],
                    'price' => $aValue['price'],
                ];
            }
        }

        return $aCartinfo;
    }

    /**
     * @param Items $oItem
     * @param array $aPossibleSuggestions
     * @param string $sSetSuggestions
     * @param string $sCurrentitem
     * @param string|array $mItemindex
     * @param array $itemindexpathtree
     * @return array
     */
    public function getItemSuggestions(
        Items $oItem,
        $aPossibleSuggestions,
        $sSetSuggestions,
        $sCurrentitem,
        $mItemindex,
        $itemindexpathtree
    )
    {
        //$aPossibleSuggestions = $aP["items"]["item"]; // put all possible suggestions that are already loaded into this array
        unset($aPossibleSuggestions[$sCurrentitem]); // remove the currently shown item from this list, we do not want to show it as a suggestion

        $suggestions = static::prepareSuggestions($sSetSuggestions, $aPossibleSuggestions, $oItem);

        $suggestions = static::fillSuggestions($suggestions);

        foreach ($suggestions as $aSuggestionsKey => $aSuggestionsValue) { // build the paths to the suggested items
            if (mb_strpos($aSuggestionsValue['itm_index'], '|') !== false) { // check if the suggestions itemindex contains multiple indexes, if so explode an array
                $aSuggestionIndexes = explode('|', $aSuggestionsValue['itm_index']);

                // iterate through these indexes
                foreach ($aSuggestionIndexes as $suggestionindexval) {
                    // check if there is an index configured on this page
                    if (isset($mItemindex)) {
                        // check if it is an array and if the suggestions index is in that array, set path to empty string
                        if (is_array($mItemindex) && in_array($suggestionindexval, $mItemindex)) {
                            $suggestions[$aSuggestionsKey]['path'] = '';
                            // path to suggestion set, continue with next suggestion
                            continue 2;
                        // if the suggestion index is on this page, set path to empty string
                        } elseif ($mItemindex == $suggestionindexval) {
                            $suggestions[$aSuggestionsKey]['path'] = '';
                            continue 2; // path to suggestion set, continue with next suggestion
                        }
                    }
                    if (isset($itemindexpathtree[$suggestionindexval])) {
                        $suggestions[$aSuggestionsKey]['path'] = $itemindexpathtree[$suggestionindexval];
                        continue 2;
                    }
                }
            } elseif (isset($itemindexpathtree[$aSuggestionsValue['itm_index']])) {
                $suggestions[$aSuggestionsKey]['path'] = $itemindexpathtree[$aSuggestionsValue['itm_index']];
            }
        }
        if ($this->shop['itemdetail_suggestions_shuffle']) {
            shuffle($suggestions);
        }

        return $suggestions;
    }

    /**
     * @param string $sSetSuggestions - Items defined as Suggestions in Item config
     * @param array $aPossibleSuggestions - Items from the same category
     * @param Items $oItem
     * @return array
     */
    public function prepareSuggestions($sSetSuggestions, array $aPossibleSuggestions, Items $oItem)
    {
        // prepare defined suggestions
        $sSetSuggestions = trim($sSetSuggestions);
        $aDefinedSuggestions = [];
        if (!empty($sSetSuggestions)) {
            $aDefinedSuggestions = explode('|', $sSetSuggestions);
        }

        // see, which suggestions are not loaded through the current item category yet
        $aSuggestionsToLoad = [];
        // iterate all defined suggestions and put those not loaded yet into array
        foreach ($aDefinedSuggestions as $defisugsval) {
            if (!isset($aPossibleSuggestions[$defisugsval])) {
                $aSuggestionsToLoad[] = $defisugsval;
            }
        }

        // if there are not yet loaded suggestions, load them
        if (isset($aSuggestionsToLoad)) {
            $aItemsNotInCategory = $oItem->sortItems('', $aSuggestionsToLoad, false);

            // merge loaded and newly loaded items
            if (!empty($aItemsNotInCategory)) {
                $aPossibleSuggestions = array_merge($aPossibleSuggestions, $aItemsNotInCategory['item']);
            }
        }

        // default = configured suggestions, additional = possible suggestions to fill up to configured maximum
        $suggestions = [
            'default' => [],
            'additional' => [],
        ];
        // iterate through all possible suggestions
        foreach ($aPossibleSuggestions as $posssugskey => $posssugsval) {
            if (in_array($posssugskey, $aDefinedSuggestions)) { // if this suggestion is a defined one, put into this array
                $suggestions['default'][$posssugskey] = $posssugsval;
                continue;
            }
            // if not, put into this one
            $suggestions['additional'][$posssugskey] = $posssugsval;
        }

        // now we see, that the configured suggestions are ordered as configured
        $aDefinedSuggestions = array_reverse($aDefinedSuggestions, true);
        foreach ($aDefinedSuggestions as $aDefinedSuggestion) {
            if (isset($suggestions['default'][$aDefinedSuggestion])) {
                $tmp = $suggestions['default'][$aDefinedSuggestion];
                unset($suggestions['default'][$aDefinedSuggestion]);
                $suggestions['default'] = [$aDefinedSuggestion => $tmp] + $suggestions['default'];
            }
        }

        return $suggestions;
    }

    /**
     * @param array $suggestions
     * @return array
     */
    public function fillSuggestions($suggestions)
    {
        $iNumberOfSuggestions = count($suggestions['default']);
        if ($iNumberOfSuggestions > $this->shop['itemdetail_suggestions']) { // if there are more suggestions than should be displayed, randomly pick as many as to be shown
            $aKeysSuggestions = array_rand($suggestions['default'], $this->shop['itemdetail_suggestions']); // get the array keys that will stay
            foreach ($suggestions['default'] as $aSuggestionsKey => $aSuggestionsValue) { // iterate suggestions and remove those that which will not be kept
                if (!in_array($aSuggestionsKey, $aKeysSuggestions)) {
                    unset($suggestions['default'][$aSuggestionsKey]);
                }
            }

            return $suggestions['default'];
        }

        // if less or equal continue here
        $numAdditionalSuggs = count($suggestions['additional']);
        if (
            $numAdditionalSuggs > 0
            && $iNumberOfSuggestions < $this->shop['itemdetail_suggestions']
        ) { // if there are less suggestions than should be displayed and there are additional available
            // how many more are needed?
            $addSuggsRequired = $this->shop['itemdetail_suggestions'] - $iNumberOfSuggestions;
            // see if there are more available than required, if so, pick as many as needed
            if ($numAdditionalSuggs > $addSuggsRequired) {
                // since array_rand returns a string and no array if there is only one row picked, we have to do this awkward dance
                $keysAddSuggsTMP = array_rand($suggestions['additional'], $addSuggsRequired);
                if (is_string($keysAddSuggsTMP) || is_int($keysAddSuggsTMP)) {
                    $keysAddSuggsTMP = [$keysAddSuggsTMP];
                }
                // because array_rand will change numerical (string) values to integer, we have to do this awkward dance
                foreach ($keysAddSuggsTMP as $key => $item) {
                    $keysAddSuggs[] = (string)$item;
                }

                // iterate suggestions and remove those that which will not be kept
                foreach ($suggestions['additional'] as $addSuggsKey => $addSuggsVal) {
                    if (!in_array((string)$addSuggsKey, $keysAddSuggs, true)) {
                        unset($suggestions['additional'][$addSuggsKey]);
                    }
                }
            }
            return array_merge($suggestions['default'], $suggestions['additional']); // merge
        }

        // if the number of default suggestions is not larger than configured and also not smaller, then it equals the
        // configured amount, so lets return this then.
        return $suggestions['default'];
    }

    /**
     * @param ServiceManager $serviceManager
     * @param \HaaseIT\HCSF\Page $P
     * @param array $aP
     * @return mixed
     */
    public function handleItemPage(ServiceManager $serviceManager, \HaaseIT\HCSF\Page $P, array $aP)
    {
        $mItemIndex = '';
        if (isset($P->cb_pageconfig->itemindex)) {
            $mItemIndex = $P->cb_pageconfig->itemindex;
        }

        /** @var \HaaseIT\HCSF\Shop\Items $oItem */
        $oItem = $serviceManager->get('oItem');

        $aP['items'] = $oItem->sortItems($mItemIndex, '', ($aP['pagetype'] === 'itemoverviewgrpd'));

        // this is a way to link directly to an itemdetailpage
        if (
            count($aP['items']['item']) === 1
            && filter_input(INPUT_GET, 'artnoexact') !== null
            && filter_input(INPUT_GET, 'itemdetail') !== null
        ) {
            $aP['pagetype'] = 'itemdetail';
            if (!is_object($P->cb_pageconfig)) {
                $P->cb_pageconfig = new \stdClass();
            }
            $P->cb_pageconfig->itemno = current($aP['items']['item'])['itm_no'];
        }

        if ($aP['pagetype'] === 'itemdetail') {

            $aP['itemindexpathtreeforsuggestions'] = $oItem->getItemPathTree();

            if (isset($aP['pageconfig']->itemindex)) {
                $aP['itemindexpathtreeforsuggestions'][$aP['pageconfig']->itemindex] = '';
                if (is_array($aP['pageconfig']->itemindex)) {
                    foreach ($aP['pageconfig']->itemindex as $sItemIndexValue) {
                        $aP['itemindexpathtreeforsuggestions'][$sItemIndexValue] = '';
                    }
                }
            }
            $aP = $this->seekItem($P, $aP, $oItem);
        }

        return $aP;
    }

    /**
     * @param \HaaseIT\HCSF\Page $P
     * @param array $aP
     * @param Items $oItem
     * @return mixed
     */
    public function seekItem(\HaaseIT\HCSF\Page $P, array $aP, Items $oItem)
    {
        // Change pagetype to itemoverview, will be changed back to itemdetail once the item is found
        // if it is not found, we will show the overview
        $aP['pagetype'] = 'itemoverview';
        if (count($aP['items']['item'])) {
            foreach ($aP['items']['item'] as $sKey => $aValue) {
                if ($aValue['itm_no'] != $P->cb_pageconfig->itemno) {
                    continue;
                }

                $aP['pagetype'] = 'itemdetail';
                $aP['item']['data'] = $aValue;
                $aP['item']['key'] = $sKey;

                if ($aP['items']['totalitems'] > 1) {
                    $iPositionInItems = array_search($sKey, $aP['items']['itemkeys']);
                    $aP['item']['currentitem'] = $iPositionInItems + 1;

                    if ($iPositionInItems === 0) {
                        $aP['item']['previtem'] = $aP['items']['itemkeys'][$aP['items']['totalitems'] - 1];
                    } else {
                        $aP['item']['previtem'] = $aP['items']['itemkeys'][$iPositionInItems - 1];
                    }

                    $aP['item']['nextitem'] = $aP['items']['itemkeys'][$iPositionInItems + 1];
                    if ($iPositionInItems == $aP['items']['totalitems'] - 1) {
                        $aP['item']['nextitem'] = $aP['items']['itemkeys'][0];
                    }
                } else {
                    $aP['item']['currentitem'] = 1;
                    $aP['item']['previtem'] = 1;
                    $aP['item']['nextitem'] = 1;
                }

                // build item suggestions if needed
                if ($this->shop['itemdetail_suggestions'] > 0) {
                    $aP['item']['suggestions'] = self::getItemSuggestions(
                        $oItem,
                        $aP['items']['item'],
                        (!empty($aValue['itm_data']['suggestions']) ? $aValue['itm_data']['suggestions'] : ''),
                        $sKey,
                        (!empty($aP['pageconfig']->itemindex) ? $aP['pageconfig']->itemindex : ''),
                        (!empty($aP['itemindexpathtreeforsuggestions']) ? $aP['itemindexpathtreeforsuggestions'] : [])
                    );
                }
                // Wenn der Artikel gefunden wurde können wir das Ausführen der Suche beenden.
                break;
            }
        }

        return $aP;
    }

    /**
     * @param string $itemindex
     * @return string
     */
    public function renderItemStatusIcon($itemindex)
    {
        if (trim($itemindex) === '') {
            return '0';
        } elseif (mb_substr($itemindex, 0, 1) === '!') {
            return 'X';
        }
        return 'I';
    }

    // todo: when we use twig 2.x, move this to macro

    /**
     * @param $id
     * @return string
     */
    public function shopadminMakeCheckbox($id)
    {
        return '<input type="checkbox" name="id[]" value="'.$id.'">';
    }
}
