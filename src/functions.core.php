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

function getPathsForItemIndexes($DB)
{
    $itemindexpathtree = [];
    $aItemoverviewpages = [];
    $sQ = "SELECT * FROM content_base WHERE cb_pagetype = 'itemoverview' OR cb_pagetype = 'itemoverviewgrpd'";
    $oQuery = $DB->query($sQ);
    while ($aRow = $oQuery->fetch()) {
        $aItemoverviewpages[] = array(
            'path' => $aRow['cb_key'],
            'pageconfig' => json_decode($aRow["cb_pageconfig"]),
        );
    }
    //HaaseIT\Tools::debug($aItemoverviewpages, '$aItemoverviewpages');
    foreach ($aItemoverviewpages as $aValue) {
        if (isset($aValue["pageconfig"]->itemindex)) {
            if (is_array($aValue["pageconfig"]->itemindex)) {
                foreach ($aValue["pageconfig"]->itemindex as $sIndexValue) {
                    if (!isset($itemindexpathtree[$sIndexValue])) {
                        $itemindexpathtree[$sIndexValue] = mb_substr($aValue["path"], 0, mb_strlen($aValue["path"]) - 10).'item/';
                    }
                }
            } else {
                if (!isset($itemindexpathtree[$aValue["pageconfig"]->itemindex])) {
                    $itemindexpathtree[$aValue["pageconfig"]->itemindex] = mb_substr($aValue["path"], 0, mb_strlen($aValue["path"]) - 10).'item/';
                }
            }
        }
    }
    //HaaseIT\Tools::debug($itemindexpathtree, '$itemindexpathtree');
    //HaaseIT\Tools::debug($aP["pageconfig"]->itemindex, '$aP["pageconfig"]->itemindex');

    return $itemindexpathtree;
}

function getSignedImgURL($file, $w = 0, $h =0)
{
    $urlBuilder = League\Glide\Urls\UrlBuilderFactory::create('', GLIDE_SIGNATURE_KEY);

    if ($w == 0 && $h == 0) return false;
    if ($w != 0) $param['w'] = $w;
    if ($h != 0) $param['h'] = $h;
    if ($w != 0 && $h != 0) $param['fit'] = 'stretch';

    return $urlBuilder->getUrl($file, $param);
}

function requireAdminAuth($C, $bAdminhome = false) {
    if (empty ($C['admin_users']) || (!count($C['admin_users']) && $bAdminhome)) {
        return true;
    } elseif (count($C['admin_users'])) {
        $valid_users = array_keys($C['admin_users']);

        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) { // fix for php cgi mode
            list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
        }

        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $user = $_SERVER['PHP_AUTH_USER'];
            $pass = crypt($_SERVER['PHP_AUTH_PW'], $C["blowfish_salt"]);

            $validated = (in_array($user, $valid_users)) && ($pass == $C['admin_users'][$user]);
        } else {
            $validated = false;
        }

        if (!$validated) {
            header('WWW-Authenticate: Basic realm="' . $C['admin_authrealm'] . '"');
            header('HTTP/1.0 401 Unauthorized');
            die("Not authorized");
        }
    } else {
        header('WWW-Authenticate: Basic realm="' . $C['admin_authrealm'] . '"');
        header('HTTP/1.0 401 Unauthorized');
        die('Not authorized');
    }
}

function mailWrapper($C, $to, $subject = '(No subject)', $message = '', $aImagesToEmbed = array(), $aFilesToAttach = array()) {
    //include_once(PATH_LIBRARIESROOT.'phpmailer/PHPMailerAutoload.php');
    $mail = new PHPMailer;
    $mail->CharSet = 'UTF-8';
    $mail->isMail();
    $mail->From = $C["email_sender"];
    $mail->FromName = $C["email_sendername"];
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $message;

    if (is_array($aImagesToEmbed) && count($aImagesToEmbed)) {
        $sPathImagesToEmbed = PATH_DOCROOT.$C['directory_images'].'/'.$C['directory_images_items'].'/'.$C['directory_images_items_email'].'/';
        foreach ($aImagesToEmbed as $sValue) {
            if (getimagesize($sPathImagesToEmbed.$sValue)) $mail->AddEmbeddedImage($sPathImagesToEmbed.$sValue, $sValue);
        }
    }

    if (is_array($aFilesToAttach) && count($aFilesToAttach)) {
        foreach ($aFilesToAttach as $sValue) {
            if (file_exists($sValue)) {
                $mail->AddAttachment($sValue);
            }
        }
    }

    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
    return $mail->send();
}

function generatePage($C, $P, $sLang, $DB, $oItem)
{
    $aP = array(
        'language' => $sLang,
        'pageconfig' => $P->cb_pageconfig,
        'pagetype' => $P->cb_pagetype,
        'subnavkey' => $P->cb_subnav,
        'requesturi' => $_SERVER["REQUEST_URI"],
        'requesturiarray' => parse_url($_SERVER["REQUEST_URI"]),
        'locale_format_date' => $C['locale_format_date'],
        'locale_format_date_time' => $C['locale_format_date_time'],
        'maintenancemode' => $C['maintenancemode'],
        'numberformat_decimals' => $C['numberformat_decimals'],
        'numberformat_decimal_point' => $C['numberformat_decimal_point'],
        'numberformat_thousands_seperator' => $C['numberformat_thousands_seperator'],
    );
    if ($C["enable_module_customer"]) {
        $aP["isloggedin"] = getUserData();
        $aP["enable_module_customer"] = true;
    }
    if ($C["enable_module_shop"]) {
        $aP["currency"] = $C["waehrungssymbol"];
        $aP["orderamounts"] = $C["orderamounts"];
        if (isset($C["vat"]["full"])) $aP["vatfull"] = $C["vat"]["full"];
        if (isset($C["vat"]["reduced"])) $aP["vatreduced"] = $C["vat"]["reduced"];
        if (isset($C["custom_order_fields"])) $aP["custom_order_fields"] = $C["custom_order_fields"];
        $aP["enable_module_shop"] = true;
    }
    if (isset($P->cb_key)) $aP["path"] = pathinfo($P->cb_key);
    else $aP["path"] = pathinfo($aP["requesturi"]);
    if ($P->cb_customcontenttemplate != NULL) $aP["customcontenttemplate"] = $P->cb_customcontenttemplate;
    if ($P->cb_customdata != NULL) $aP["customdata"] = $P->cb_customdata;
    if (isset($_SERVER["HTTP_REFERER"])) $aP["referer"] = $_SERVER["HTTP_REFERER"];

    reset($C["lang_available"]);

    // if there is no subnav defined but there is a default subnav defined, use it
    // subnavkey can be used in the templates to find out, where we are
    if ((!isset($aP["subnavkey"]) || $aP["subnavkey"] == '') && $C["subnav_default"] != '') {
        $aP["subnavkey"] = $C["subnav_default"];
        $P->cb_subnav = $C["subnav_default"];
    }
    if ($P->cb_subnav != NULL && isset($C["navstruct"][$P->cb_subnav])) $aP["subnav"] = $C["navstruct"][$P->cb_subnav];

    // Get page title, meta-keywords, meta-description
    $aP["pagetitle"] = $P->oPayload->getTitle();

    $aP["keywords"] = $P->oPayload->cl_keywords;
    $aP["description"] = $P->oPayload->cl_description;

    // TODO: Add head scripts to DB
    //if (isset($P["head_scripts"]) && $P["head_scripts"] != '') $aP["head_scripts"] = $P["head_scripts"];

    // Language selector
    // TODO: move content of langselector out of php script
    if (count($C["lang_available"]) > 1) {
        $aP["langselector"] = '';
        if ($C["lang_detection_method"] == 'domain') {
            $aSessionGetVarsForLangSelector = array();
            if (session_status() == PHP_SESSION_ACTIVE) {
                $aSessionGetVarsForLangSelector[session_name()] = session_id();
            }
            $aRequestURL = parse_url($_SERVER["REQUEST_URI"]);
        }
        foreach ($C["lang_available"] as $sKey => $sValue) {
            if ($sLang != $sKey) {
                if ($C["lang_detection_method"] == 'domain') {
                    $aP["langselector"] .= '<a href="//www.' . $C["lang_by_domain"][$sKey] . $aRequestURL["path"] . \HaaseIT\Tools::makeLinkHRefWithAddedGetVars('', $aSessionGetVarsForLangSelector) . '">' . \HaaseIT\Textcat::T("misc_language_" . $sKey) . '</a> ';
                } else {
                    $aP["langselector"] .= '<a href="' . \HaaseIT\Tools::makeLinkHRefWithAddedGetVars('', array('language' => $sKey)) . '">' . \HaaseIT\Textcat::T("misc_language_" . $sKey) . '</a> ';
                }
            }
        }
        $aP["langselector"] = \HaaseIT\Tools::cutStringend($aP["langselector"], 1);
    }

    // Shopping cart infos
    if ($C["enable_module_shop"]) {
        if ((!$C["show_pricesonlytologgedin"] || getUserData()) && isset($_SESSION["cart"]) && count($_SESSION["cart"])) {
            $aCartsums = calculateCartItems($C, $_SESSION["cart"]);
            $aP["cartinfo"] = array(
                'numberofitems' => count($_SESSION["cart"]),
                'cartsums' => $aCartsums,
                'cartsumnetto' => $aCartsums["sumvoll"] + $aCartsums["sumerm"],
                'cartsumbrutto' => $aCartsums["sumvoll"] + $aCartsums["sumerm"] + $aCartsums["taxerm"] + $aCartsums["taxvoll"],
            );
            unset($aCartsums);
            foreach ($_SESSION["cart"] as $sKey => $aValue) {
                $aP["cartinfo"]["cartitems"][$sKey] = array(
                    'cartkey' => $sKey,
                    'name' => $aValue["name"],
                    'amount' => $aValue["amount"],
                    'img' => $aValue["img"],
                    'price' => $aValue["price"],
                );
            }
        } else {
            $aP["cartinfo"] = array(
                'numberofitems' => 0,
                'cartsums' => array(),
                'cartsumnetto' => 0,
                'cartsumbrutto' => 0,
            );
        }
    }

    $aP["countrylist"][] = ' | ';
    foreach ($C["countries_".$sLang] as $sKey => $sValue) {
        $aP["countrylist"][] = $sKey.'|'.$sValue;
    }

    if ($C["enable_module_shop"] && ($aP["pagetype"] == 'itemoverview' || $aP["pagetype"] == 'itemoverviewgrpd' || $aP["pagetype"] == 'itemdetail')) {
        if (isset($P->cb_pageconfig->itemindex)) {
            $mItemIndex = $P->cb_pageconfig->itemindex;
        } else {
            $mItemIndex = '';
        }
        $aP["items"] = $oItem->sortItems($mItemIndex, '', ($aP["pagetype"] == 'itemoverviewgrpd' ? true : false));
        if ($aP["pagetype"] == 'itemdetail') {

            $aP["itemindexpathtreeforsuggestions"] = getPathsForItemIndexes($DB);
            //HaaseIT\Tools::debug($aP["itemindexpathtreeforsuggestions"], '$aP["itemindexpathtreeforsuggestions"]');

            if (isset($aP["pageconfig"]->itemindex)) {
                if (is_array($aP["pageconfig"]->itemindex)) {
                    foreach ($aP["pageconfig"]->itemindex as $sItemIndexValue) {
                        $aP["itemindexpathtreeforsuggestions"][$sItemIndexValue] = '';
                    }
                } else {
                    $aP["itemindexpathtreeforsuggestions"][$aP["pageconfig"]->itemindex] = '';
                }
            }
            HaaseIT\Tools::debug($aP["itemindexpathtreeforsuggestions"], '$aP["itemindexpathtreeforsuggestions"]');

            // Change pagetype to itemoverview, will be changed back to itemdetail once the item is found
            // if it is not found, we will show the overview
            $aP["pagetype"] = 'itemoverview';
            if (count($aP["items"]["item"])) {
                foreach ($aP["items"]["item"] as $sKey => $aValue) {
                    if ($aValue[DB_ITEMFIELD_NUMBER] == $P->cb_pageconfig->itemno) {
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
                            $aPossibleSuggestions = $aP["items"]["item"]; // put all possible suggestions that are already loaded into this array
                            unset($aPossibleSuggestions[$sKey]); // remove the currently shown item from this list, we do not want to show it as a suggestion
                            //HaaseIT\Tools::debug($aPossibleSuggestions, '$aPossibleSuggestions');

                            $aDefinedSuggestions = array();
                            if (isset($aValue[DB_ITEMFIELD_DATA]["suggestions"]) && trim($aValue[DB_ITEMFIELD_DATA]["suggestions"]) != '') {
                                if (mb_strpos($aValue[DB_ITEMFIELD_DATA]["suggestions"], '|') !== false) {
                                    $aDefinedSuggestions = explode('|', $aValue[DB_ITEMFIELD_DATA]["suggestions"]); // convert all defined suggestions to array
                                } else {
                                    $aDefinedSuggestions[] = $aValue[DB_ITEMFIELD_DATA]["suggestions"];
                                }
                            }
                            //HaaseIT\Tools::debug($aDefinedSuggestions, '$aDefinedSuggestions');
                            foreach ($aDefinedSuggestions as $aDefinedSuggestionsValue) { // iterate all defined suggestions and put those not loaded yet into array
                                if (!isset($aPossibleSuggestions[$aDefinedSuggestionsValue])) {
                                    $aSuggestionsToLoad[] = $aDefinedSuggestionsValue;
                                }
                            }
                            //HaaseIT\Tools::debug($aSuggestionsToLoad, '$aSuggestionsToLoad');
                            if (isset($aSuggestionsToLoad)) { // if there are not yet loaded suggestions, load them
                                $aItemsNotInCategory = $oItem->sortItems('', $aSuggestionsToLoad, false);
                                //HaaseIT\Tools::debug($aItemsNotInCategory, '$aItemsNotInCategory');
                                if (isset($aItemsNotInCategory)) { // merge loaded and newly loaded items
                                    $aPossibleSuggestions = array_merge($aPossibleSuggestions, $aItemsNotInCategory["item"]);
                                }
                            }
                            unset($aSuggestionsToLoad, $aItemsNotInCategory);
                            //HaaseIT\Tools::debug($aPossibleSuggestions, '$aPossibleSuggestions');
                            $aSuggestions = array();
                            $aAdditionalSuggestions = array();
                            foreach ($aPossibleSuggestions as $aPossibleSuggestionsKey => $aPossibleSuggestionsValue) { // iterate through all possible suggestions
                                if (in_array($aPossibleSuggestionsKey, $aDefinedSuggestions)) { // if this suggestion is a defined one, put into this array
                                    $aSuggestions[$aPossibleSuggestionsKey] = $aPossibleSuggestionsValue;
                                } else { // if not, put into this one
                                    $aAdditionalSuggestions[$aPossibleSuggestionsKey] = $aPossibleSuggestionsValue;
                                }
                            }
                            unset($aPossibleSuggestions, $aDefinedSuggestions); // not needed anymore
                            //HaaseIT\Tools::debug($aSuggestions, '$aSuggestions');
                            //HaaseIT\Tools::debug($aAdditionalSuggestions, '$aAdditionalSuggestions');
                            $iNumberOfSuggestions = count($aSuggestions);
                            $iNumberOfAdditionalSuggestions = count($aAdditionalSuggestions);
                            if ($iNumberOfSuggestions > $C["itemdetail_suggestions"]) { // if there are more suggestions than should be displayed, randomly pick as many as to be shown
                                $aKeysSuggestions = array_rand($aSuggestions, $C["itemdetail_suggestions"]); // get the array keys that will stay
                                foreach ($aSuggestions as $aSuggestionsKey => $aSuggestionsValue) { // iterate suggestions and remove those that which will not be kept
                                    if (!in_array($aSuggestionsKey, $aKeysSuggestions)) {
                                        unset($aSuggestions[$aSuggestionsKey]);
                                    }
                                }
                                unset($aKeysSuggestions);
                            } else { // if less or equal continue here
                                if ($iNumberOfSuggestions < $C["itemdetail_suggestions"] && $iNumberOfAdditionalSuggestions > 0) { // if there are less suggestions than should be displayed and there are additional available
                                    $iAdditionalSuggestionsRequired = $C["itemdetail_suggestions"] - $iNumberOfSuggestions; // how many more are needed?
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
                                        if (isset($aP["pageconfig"]->itemindex)) { // check if there is an index configured on this page
                                            if (is_array($aP["pageconfig"]->itemindex)) { // check if it is an array
                                                if (in_array($sSuggestionIndexesValue, $aP["pageconfig"]->itemindex)) { // if the suggestions index is in that array, set path to empty string
                                                    $aSuggestions[$aSuggestionsKey]["path"] = '';
                                                    continue 2; // path to suggestion set, continue with next suggestion
                                                }
                                            } else {
                                                if ($aP["pageconfig"]->itemindex == $sSuggestionIndexesValue) { // if the suggestion index is on this page, set path to empty string
                                                    $aSuggestions[$aSuggestionsKey]["path"] = '';
                                                    continue 2; // path to suggestion set, continue with next suggestion
                                                }
                                            }
                                        }
                                        if (isset($aP["itemindexpathtreeforsuggestions"][$sSuggestionIndexesValue])) {
                                            $aSuggestions[$aSuggestionsKey]["path"] = $aP["itemindexpathtreeforsuggestions"][$sSuggestionIndexesValue];
                                            continue 2;
                                        }
                                    }
                                    unset($aSuggestionIndexes);
                                } else {
                                    if (isset($aP["itemindexpathtreeforsuggestions"][$aSuggestionsValue["itm_index"]])) {
                                        $aSuggestions[$aSuggestionsKey]["path"] = $aP["itemindexpathtreeforsuggestions"][$aSuggestionsValue["itm_index"]];
                                    }
                                }
                            }
                            //HaaseIT\Tools::debug($aSuggestions, '$aSuggestions');
                            $aP["item"]["suggestions"] = $aSuggestions;
                            unset($aSuggestions, $aAdditionalSuggestions, $iNumberOfSuggestions, $iNumberOfAdditionalSuggestions);
                            shuffle($aP["item"]["suggestions"]);
                        }
                        // Wenn der Artikel gefunden wurde können wir das Ausführen der Suche beenden.
                        break;
                    }
                }
            }
        }
    }

    $aP["content"] = $P->oPayload->cl_html;

    $aP["content"] = str_replace("@", "&#064;", $aP["content"]); // Change @ to HTML Entity -> maybe less spam mails
    $aP["content"] = str_replace("[quote]", "'", $aP["content"]);

    // TODO!!!
    /*
    if (!isset($P["keep_placeholders"]) || !$P["keep_placeholders"]) {
        $aP["content"] = stripslashes(str_replace('[sp]', '&nbsp;', $aP["content"]));
    } else {
        $aP["content"] .= stripslashes($aP["content"]);
    }
    */

    if (isset($_POST) && count($_POST)) {
        HaaseIT\Tools::debug($_POST, '$_POST');
    } elseif (isset($_REQUEST) && count($_REQUEST)) {
        HaaseIT\Tools::debug($_REQUEST, '$_REQUEST');
    }
    if (isset($_SESSION) && count($_SESSION)) {
        HaaseIT\Tools::debug($_SESSION, '$_SESSION');
    }
    HaaseIT\Tools::debug($aP, '$aP');
    HaaseIT\Tools::debug($P, '$P');

    $aP["debugdata"] = HaaseIT\Tools::$sDebug;

    return $aP;
}
