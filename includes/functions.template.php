<?php

function buildTitle($P, $C)
{
    if (isset($P["lang"][DB_CONTENTFIELD_TITLE]) && trim($P["lang"][DB_CONTENTFIELD_TITLE]) != '') $sH = $P["lang"][DB_CONTENTFIELD_TITLE];
    else $sH = $C["default_pagetitle"];

    return $sH;
}

function mailWrapper($to, $from_user, $from_email, $subject = '(No subject)', $message = '', $aImagesToEmbed = array(), $aFilesToAttach = array()) {
    include_once(PATH_LIBRARIESROOT.'phpmailer/PHPMailerAutoload.php');
    $mail = new PHPMailer;
    $mail->CharSet = 'UTF-8';
    $mail->isSendmail();
    $mail->From = $from_email;
    $mail->FromName = $from_user;
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $message;

    if (is_array($aImagesToEmbed) && count($aImagesToEmbed)) {
        foreach ($aImagesToEmbed as $sValue) {
            if (getimagesize(PATH_DOCROOT.DIRNAME_IMAGES.DIRNAME_ITEMS.DIRNAME_ITEMSSMALLEST.$sValue)) $mail->AddEmbeddedImage(PATH_DOCROOT.DIRNAME_IMAGES.DIRNAME_ITEMS.DIRNAME_ITEMSSMALLEST.$sValue, $sValue);
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
    global $sDebug;
    $aP = array(
        'language' => $sLang,
        'pageconfig' => $P["base"]["cb_pageconfig"],
        'pagetype' => $P["base"]["cb_pagetype"],
        'subnavkey' => $P["base"]["cb_subnav"],
        'currency' => $C["waehrungssymbol"],
        'requesturi' => $_SERVER["REQUEST_URI"],
        'requesturiarray' => parse_url($_SERVER["REQUEST_URI"]),
        'isloggedin' => getUserData(),
        'orderamounts' => $C["orderamounts"],
    );
    if (isset($C["vat"]["19"])) $aP["vatfull"] = $C["vat"]["19"];
    if (isset($C["vat"]["7"])) $aP["vatreduced"] = $C["vat"]["7"];
    if (isset($C["custom_order_fields"])) $aP["custom_order_fields"] = $C["custom_order_fields"];
    if (isset($P["base"]["cb_key"])) $aP["path"] = pathinfo($P["base"]["cb_key"]);
    else $aP["path"] = pathinfo($aP["requesturi"]);
    if (isset($P["base"]["cb_customcontenttemplate"]) && trim($P["base"]["cb_customcontenttemplate"]) != '') $aP["customcontenttemplate"] = $P["base"]["cb_customcontenttemplate"];
    if (isset($P["base"]["cb_customdata"])) $aP["customdata"] = $P["base"]["cb_customdata"];
    if (isset($_SERVER["HTTP_REFERER"])) $aP["referer"] = $_SERVER["HTTP_REFERER"];

    reset($C["lang_available"]);
    if (!$P) {
        $P["base"]['cb_pagetype'] = 'error';
        $P["lang"] = array(
            'cl_html' => T("misc_page_not_found"),
        );
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    } elseif (isset($P["base"]) && !isset($P["lang"])) {
        if ($aP["pagetype"] == 'itemoverview' || $aP["pagetype"] == 'itemdetail') {
            $P["lang"] = array(
                'cl_html' => '',
            );
        } else {
            $P["lang"] = array(
                'cl_html' => T("misc_content_not_found"),
            );
            header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
        }
    } elseif($P["lang"][DB_CONTENTFIELD_LANG] != $sLang) {
        $P["lang"]["cl_html"] = T("misc_page_not_available_lang").'<br><br>'.$P["lang"]["cl_html"];
    }
    if ((!isset($aP["subnavkey"]) || $aP["subnavkey"] == '') && $C["subnav_default"] != '') { // if there is no subnav defined but there is a default subnav defined, use it.
        $aP["subnavkey"] = $C["subnav_default"];
        $P["base"]["cb_subnav"] = $C["subnav_default"];
    }
    if (isset($P["base"]["cb_subnav"]) && isset($C["navstruct"][$P["base"]["cb_subnav"]])) $aP["subnav"] = $C["navstruct"][$P["base"]["cb_subnav"]];

    // Get page title, meta-keywords, meta-description
    $aP["pagetitle"] = buildTitle($P, $C);
    if (isset($P["lang"][DB_CONTENTFIELD_KEYWORDS]) && trim($P["lang"][DB_CONTENTFIELD_KEYWORDS]) != '')
        $aP["keywords"] = trim($P["lang"][DB_CONTENTFIELD_KEYWORDS]);
    if (isset($P["lang"][DB_CONTENTFIELD_DESCRIPTION]) && trim($P["lang"][DB_CONTENTFIELD_DESCRIPTION]) != '')
        $aP["description"] = trim($P["lang"][DB_CONTENTFIELD_DESCRIPTION]);

    // TODO: Add head scripts to DB
    if (isset($P["head_scripts"]) && $P["head_scripts"] != '') $aP["head_scripts"] = $P["head_scripts"];

    // Language selector
    // TODO: move content of langselector out of php script
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
                $aP["langselector"] .= '<a href="//www.'.$C["lang_by_domain"][$sKey].$aRequestURL["path"].\HaaseIT\Tools::makeLinkHRefWithAddedGetVars('', $aSessionGetVarsForLangSelector).'">'.T("misc_language_".$sKey).'</a> ';
            } else {
                $aP["langselector"] .= '<a href="'.\HaaseIT\Tools::makeLinkHRefWithAddedGetVars('', array('language' => $sKey)).'">'.T("misc_language_".$sKey).'</a> ';
            }
        }
    }
    $aP["langselector"] = \HaaseIT\Tools::cutStringend($aP["langselector"], 1);

    // Shopping cart infos
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

    $aP["countrylist"][] = ' | ';
    foreach ($C["countries_".$sLang] as $sKey => $sValue) {
        $aP["countrylist"][] = $sKey.'|'.$sValue;
    }

    if ($aP["pagetype"] == 'itemoverview' || $aP["pagetype"] == 'itemdetail') {
        if (isset($P["base"]["cb_pageconfig"]["itemindex"])) {
            $mItemIndex = $P["base"]["cb_pageconfig"]["itemindex"];
        } else {
            $mItemIndex = '';
        }
        $aP["items"] = $oItem->sortItems($mItemIndex);
        if ($aP["pagetype"] == 'itemdetail') {

            // Todo: move building of paths for itemindexes to better location
            $sQ = "SELECT * FROM ".DB_CONTENTTABLE_BASE." WHERE cb_pagetype = 'itemoverview'";
            $oQuery = $DB->query($sQ);
            while ($aRow = $oQuery->fetch()) {
                $aItemoverviewpages[] = array(
                    'path' => $aRow[DB_CONTENTFIELD_BASE_KEY],
                    'pageconfig' => json_decode($aRow["cb_pageconfig"], true),
                );
            }
            //debug($aItemoverviewpages, false, '$aItemoverviewpages');
            $aP["itemindexpathtreeforsuggestions"] = array();
            foreach ($aItemoverviewpages as $aValue) {
                if (isset($aValue["pageconfig"]["itemindex"])) {
                    if (is_array($aValue["pageconfig"]["itemindex"])) {
                        foreach ($aValue["pageconfig"]["itemindex"] as $sIndexValue) {
                            if (!isset($aP["itemindexpathtreeforsuggestions"][$sIndexValue])) {
                                $aP["itemindexpathtreeforsuggestions"][$sIndexValue] = mb_substr($aValue["path"], 0, mb_strlen($aValue["path"]) - 10).'item/';
                            }
                        }
                    } else {
                        if (!isset($aP["itemindexpathtreeforsuggestions"][$aValue["pageconfig"]["itemindex"]])) {
                            $aP["itemindexpathtreeforsuggestions"][$aValue["pageconfig"]["itemindex"]] = mb_substr($aValue["path"], 0, mb_strlen($aValue["path"]) - 10).'item/';
                        }
                    }
                }
            }
            //debug($aP["pageconfig"]["itemindex"], false, '$aP["pageconfig"]["itemindex"]');
            if (isset($aP["pageconfig"]["itemindex"])) {
                if (is_array($aP["pageconfig"]["itemindex"])) {
                    foreach ($aP["pageconfig"]["itemindex"] as $sItemIndexValue) {
                        $aP["itemindexpathtreeforsuggestions"][$sItemIndexValue] = '';
                    }
                } else {
                    $aP["itemindexpathtreeforsuggestions"][$aP["pageconfig"]["itemindex"]] = '';
                }
            }
            unset($aItemoverviewpages);
            debug($aP["itemindexpathtreeforsuggestions"], false, '$aP["itemindexpathtreeforsuggestions"]');

            // Change pagetype to itemoverview, will be changed back to itemdetail once the item is found
            // if it is not found, we will show the overview
            $aP["pagetype"] = 'itemoverview';
            if (count($aP["items"]["item"])) {
                foreach ($aP["items"]["item"] as $sKey => $aValue) {
                    if ($aValue[DB_ITEMFIELD_NUMBER] == $P["base"]["itemno"]) {
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
                            //debug($aPossibleSuggestions, false, '$aPossibleSuggestions');

                            $aDefinedSuggestions = array();
                            if (isset($aValue[DB_ITEMFIELD_DATA]["suggestions"]) && trim($aValue[DB_ITEMFIELD_DATA]["suggestions"]) != '') {
                                if (mb_strpos($aValue[DB_ITEMFIELD_DATA]["suggestions"], '|') !== false) {
                                    $aDefinedSuggestions = explode('|', $aValue[DB_ITEMFIELD_DATA]["suggestions"]); // convert all defined suggestions to array
                                } else {
                                    $aDefinedSuggestions[] = $aValue[DB_ITEMFIELD_DATA]["suggestions"];
                                }
                            }
                            //debug($aDefinedSuggestions, false, '$aDefinedSuggestions');
                            foreach ($aDefinedSuggestions as $aDefinedSuggestionsValue) { // iterate all defined suggestions and put those not loaded yet into array
                                if (!isset($aPossibleSuggestions[$aDefinedSuggestionsValue])) {
                                    $aSuggestionsToLoad[] = $aDefinedSuggestionsValue;
                                }
                            }
                            //debug($aSuggestionsToLoad, false, '$aSuggestionsToLoad');
                            if (isset($aSuggestionsToLoad)) { // if there are not yet loaded suggestions, load them
                                $aItemsNotInCategory = $oItem->sortItems('', $aSuggestionsToLoad);
                                //debug($aItemsNotInCategory, false, '$aItemsNotInCategory');
                                if (isset($aItemsNotInCategory)) { // merge loaded and newly loaded items
                                    $aPossibleSuggestions = array_merge($aPossibleSuggestions, $aItemsNotInCategory["item"]);
                                }
                            }
                            unset($aSuggestionsToLoad, $aItemsNotInCategory);
                            //debug($aPossibleSuggestions, false, '$aPossibleSuggestions');
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
                            //debug($aSuggestions, false, '$aSuggestions');
                            //debug($aAdditionalSuggestions, false, '$aAdditionalSuggestions');
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
                                        if (isset($aP["pageconfig"]["itemindex"])) { // check if there is an index configured on this page
                                            if (is_array($aP["pageconfig"]["itemindex"])) { // check if it is an array
                                                if (in_array($sSuggestionIndexesValue, $aP["pageconfig"]["itemindex"])) { // if the suggestions index is in that array, set path to empty string
                                                    $aSuggestions[$aSuggestionsKey]["path"] = '';
                                                    continue 2; // path to suggestion set, continue with next suggestion
                                                }
                                            } else {
                                                if ($aP["pageconfig"]["itemindex"] == $sSuggestionIndexesValue) { // if the suggestion index is on this page, set path to empty string
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
                            //debug($aSuggestions, false, '$aSuggestions');
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

    $aP["content"] = $P["lang"]["cl_html"];

    $aP["content"] = str_replace("@", "&#064;", $aP["content"]); // Change @ to HTML Entity -> maybe less spam mails
    $aP["content"] = str_replace("[quote]", "'", $aP["content"]);

    if (!isset($P["keep_placeholders"]) || !$P["keep_placeholders"]) {
        $aP["content"] = stripslashes(str_replace('[sp]', '&nbsp;', $aP["content"]));
    } else {
        $aP["content"] .= stripslashes($aP["content"]);
    }

    if (isset($_POST)) {
        debug($_POST, false, '$_POST');
    }
    if (isset($_SESSION)) {
        debug($_SESSION, false, '$_SESSION');
    }
    debug($aP, false, '$aP');
    debug($P, false, '$P');

    if (isset($sDebug) && isset($C["debug"]) && $C["debug"]) $aP["debugdata"] = $sDebug;

    return $aP;
}