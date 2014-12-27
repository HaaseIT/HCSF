<?php

function handleShopAdmin($CSA, $twig, $DB, $C, $sLang)
{
    $sH = '';
    if (!isset($_GET["action"])) {
        $bIgnoreStorno = false;
        $sQ = "SELECT * FROM ".DB_ORDERTABLE." WHERE ";

        if (!isset($_REQUEST["type"]) OR $_REQUEST["type"] == 'openinwork') $sQ .= "(o_ordercompleted = 'n' OR o_ordercompleted = 'i') ";
        elseif ($_REQUEST["type"] == 'closed') $sQ .= "o_ordercompleted = 'y' ";
        elseif ($_REQUEST["type"] == 'open') $sQ .= "o_ordercompleted = 'n' ";
        elseif ($_REQUEST["type"] == 'inwork') $sQ .= "o_ordercompleted = 'i' ";
        elseif ($_REQUEST["type"] == 'storno') $sQ .= "o_ordercompleted = 's' ";
        elseif ($_REQUEST["type"] == 'deleted') $sQ .= "o_ordercompleted = 'd' ";
        elseif ($_REQUEST["type"] == 'all') {
            $sQ .= "o_ordercompleted != 'd' ";
            $bIgnoreStorno = true;
        }
        $bFromTo = false;
        if (isset($_REQUEST["type"]) && ($_REQUEST["type"] == 'deleted' OR $_REQUEST["type"] == 'all' OR $_REQUEST["type"] == 'closed')) {
            $sQ .= "AND ";
            $sFrom = $_REQUEST["fromyear"].'-'.dateAddLeadingZero($_REQUEST["frommonth"]).'-'.dateAddLeadingZero($_REQUEST["fromday"]);
            $sTo = $_REQUEST["toyear"].'-'.dateAddLeadingZero($_REQUEST["tomonth"]).'-'.dateAddLeadingZero($_REQUEST["today"]);
            $sQ .= "o_orderdate >= :from ";
            $sQ .= "AND o_orderdate <= :to ";
            $bFromTo = true;
        }
        $sQ .= "ORDER BY o_ordertimestamp DESC";

        //debug($sQ);
        //debug($_REQUEST);

        $hResult = $DB->prepare($sQ);
        if ($bFromTo) {
            $hResult->bindValue(':from', $sFrom);
            $hResult->bindValue(':to', $sTo);
        }
        $hResult->execute();

        if ($hResult->rowCount() != 0) {
            $i = 0;
            $j = 0;
            $k = 0;
            $fGesamtnetto = 0.0;
            while ($aRow = $hResult->fetch()) {
                if ($aRow["o_ordercompleted"] == 'y') $sStatus = '<span style="color: green; font-weight: bold;">'.T("order_status_completed").'</span>';
                elseif ($aRow["o_ordercompleted"] == 'n') $sStatus = '<span style="color: orange; font-weight: bold;">'.T("order_status_open").'</span>';
                elseif ($aRow["o_ordercompleted"] == 'i') $sStatus = '<span style="color: orange;">'.T("order_status_inwork").'</span>';
                elseif ($aRow["o_ordercompleted"] == 's') $sStatus = '<span style="color: red; font-weight: bold;">'.T("order_status_canceled").'</span>';
                elseif ($aRow["o_ordercompleted"] == 'd') $sStatus = T("order_status_deleted");

                if ($aRow["o_paymentcompleted"] == 'y') $sZahlungsmethode = '<span style="color: green;">';
                else $sZahlungsmethode = '<span style="color: red;">';
                $mZahlungsmethode = T("order_paymentmethod_".$aRow["o_paymentmethod"], true);
                if ($mZahlungsmethode ) $sZahlungsmethode .= $mZahlungsmethode;
                else $sZahlungsmethode .= ucwords($aRow["o_paymentmethod"]);
                $sZahlungsmethode .= '</span>';

                if (trim($aRow["o_corpname"]) == '') $sName = $aRow["o_name"];
                else $sName = $aRow["o_corpname"];

                $aData[] = [
                    'o_id' => $aRow["o_id"],
                    'o_account_no' => $aRow["o_custno"],
                    'o_email' => $aRow["o_email"],
                    'o_cust' => $sName.'<br>'.$aRow["o_zip"].' '.$aRow["o_town"],
                    'o_authed' => $aRow["o_authed"],
                    'o_sumnettoall' => number_format($aRow["o_sumnettoall"], 2, ",", ".").' '.$C["waehrungssymbol"].(($aRow["o_mindermenge"] != 0 && $aRow["o_mindermenge"] != '') ? '<br>+'.number_format($aRow["o_mindermenge"], 2, ",", ".").' '.$C["waehrungssymbol"] : ''),
                    'o_order_status' => $sStatus.((trim($aRow["o_lastedit_user"]) != '') ? '<br>'.$aRow["o_lastedit_user"] : ''),
                    'o_ordertime_number' => date("d.m.y H:i", $aRow["o_ordertimestamp"]).((trim($aRow["o_transaction_no"]) != '') ? '<br>'.$aRow["o_transaction_no"] : ''),
                    'o_order_host_payment' => $sZahlungsmethode.'<br>'.$aRow["o_srv_hostname"],
                ];
                if (!($aRow["o_ordercompleted"] == 's' && $bIgnoreStorno)) {
                    $fGesamtnetto += $aRow["o_sumnettoall"];
                    $j ++;
                } else $k++;
                $i++;
            }
            //debug($aData);
            $sH .= \HaaseIT\Tools::makeListtable($CSA["list_orders"], $aData, $twig);

            if ($i > 1) {
                $sH .= '<br>'.$i.' Bestellung(en) angezeigt'.(($k != 0) ? ', davon '.$k.' stornierte (diese werden bei den Wertberechnungen nicht ber&uuml;cksichtigt)' : '').'.';
                $sH .= '<br>Gesamtnetto der angezeigten Bestellungen: '.number_format($fGesamtnetto, 2, ",", ".").' '.$C["waehrungssymbol"].'.';
                $sH .= '<br>Durchschnittlicher Nettobestellwert der angezeigten Bestellungen: '.number_format(($fGesamtnetto / $j), 2, ",", ".").' '.$C["waehrungssymbol"];
            }
        } else $sH .= 'Es wurden keine zu Ihren Suchkriterien passenden Bestell-Datensätze gefunden.<br>';
    } elseif (isset($_GET["action"]) && $_GET["action"] == 'edit') {
        $iId = \HaaseIT\Tools::cED($_GET["id"]);
        $sQ = "SELECT * FROM ".DB_ORDERTABLE." WHERE o_id = :id";

        $hResult = $DB->prepare($sQ);
        $hResult->bindValue(':id', $iId);
        $hResult->execute();
        if ($hResult->rowCount() == 1) {
            $aSData["orderdata"] = $hResult->fetch();
            //$sH .= debug($aSData["orderdata"], true);
            $sQ = "SELECT * FROM ".DB_ORDERTABLE_ITEMS." WHERE oi_o_id = :id";
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':id', $iId);
            $hResult->execute();
            //debug($DB->numRows($hResult));
            $aItems = $hResult->fetchAll();

            $aUserdata = [
                DB_CUSTOMERFIELD_NUMBER => $aSData["orderdata"]["o_custno"],
                DB_CUSTOMERFIELD_EMAIL => $aSData["orderdata"]["o_email"],
                DB_CUSTOMERFIELD_CORP => $aSData["orderdata"]["o_corpname"],
                DB_CUSTOMERFIELD_NAME => $aSData["orderdata"]["o_name"],
                DB_CUSTOMERFIELD_STREET => $aSData["orderdata"]["o_street"],
                DB_CUSTOMERFIELD_ZIP => $aSData["orderdata"]["o_zip"],
                DB_CUSTOMERFIELD_TOWN => $aSData["orderdata"]["o_town"],
                DB_CUSTOMERFIELD_PHONE => $aSData["orderdata"]["o_phone"],
                DB_CUSTOMERFIELD_CELLPHONE => $aSData["orderdata"]["o_cellphone"],
                DB_CUSTOMERFIELD_FAX => $aSData["orderdata"]["o_fax"],
                DB_CUSTOMERFIELD_COUNTRY => $aSData["orderdata"]["o_country"],
                DB_CUSTOMERFIELD_GROUP => $aSData["orderdata"]["o_group"],
            ];
            $aSData["customerform"] = buildCustomerForm($C, $sLang, 'shopadmin', '', $aUserdata);

            $aSData["orderdata"]["options_shippingservices"] = [''];
            foreach ($C["shipping_services"] as $sValue) $aSData["orderdata"]["options_shippingservices"][] = $sValue;

            //debug($aItems);
            foreach ($aItems as $aValue) {
                $aPrice = array(
                    'netto_list' => $aValue["oi_price_netto_list"],
                    'brutto_list' => $aValue["oi_price_brutto_list"],
                    'netto_sale' => $aValue["oi_price_netto_sale"],
                    'brutto_sale' => $aValue["oi_price_brutto_sale"],
                    'netto_rebated' => $aValue["oi_price_netto_rebated"],
                    'brutto_rebated' => $aValue["oi_price_brutto_rebated"],
                    'netto_use' => $aValue["oi_price_netto_use"],
                    'brutto_use' => $aValue["oi_price_brutto_use"],
                );

                //$aPrice = $oItem->calcPrice($aValue["oi_price_netto"], $C["vat"][$aValue["oi_vat_id"]], '', true);
                $aItemsforShoppingcarttable[$aValue["oi_cartkey"]] = [
                    'amount' => $aValue["oi_amount"],
                    'price' => $aPrice,
                    'vat' => $C["vat"][$aValue["oi_vat_id"]],
                    'rg' => $aValue["oi_rg"],
                    'rg_rebate' => $aValue["oi_rg_rebate"],
                    'name' => $aValue["oi_itemname"],
                    'img' => $aValue["oi_img"],
                ];
            }
            //debug($aItemsforShoppingcarttable);

            $aSData = array_merge(buildShoppingCartTable($aItemsforShoppingcarttable, $sLang, $C, true, $aSData["orderdata"]["o_group"]), $aSData);
        } else {
            $sH .= 'Keine entsprechende Bestellung gefunden.';
        }
    }

    $aSData["html"] = $sH;

    return $aSData;
}