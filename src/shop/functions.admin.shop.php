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
        } else {
            die('Invalid request error.');
        }
        $bFromTo = false;
        if (isset($_REQUEST["type"]) && ($_REQUEST["type"] == 'deleted' OR $_REQUEST["type"] == 'all' OR $_REQUEST["type"] == 'closed')) {
            $sQ .= "AND ";
            $sFrom = filter_var($_REQUEST["fromyear"], FILTER_SANITIZE_NUMBER_INT).'-'.dateAddLeadingZero(filter_var($_REQUEST["frommonth"], FILTER_SANITIZE_NUMBER_INT));
            $sFrom .= '-'.dateAddLeadingZero(filter_var($_REQUEST["fromday"], FILTER_SANITIZE_NUMBER_INT));
            $sTo = filter_var($_REQUEST["toyear"], FILTER_SANITIZE_NUMBER_INT).'-'.dateAddLeadingZero(filter_var($_REQUEST["tomonth"], FILTER_SANITIZE_NUMBER_INT));
            $sTo .= '-'.dateAddLeadingZero(filter_var($_REQUEST["today"], FILTER_SANITIZE_NUMBER_INT));
            $sQ .= "o_orderdate >= :from ";
            $sQ .= "AND o_orderdate <= :to ";
            $bFromTo = true;
        }
        $sQ .= "ORDER BY o_ordertimestamp DESC";

        //HaaseIT\Tools::debug($sQ);
        //HaaseIT\Tools::debug($_REQUEST);

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
                if ($aRow["o_ordercompleted"] == 'y') $sStatus = '<span style="color: green; font-weight: bold;">'.\HaaseIT\Textcat::T("order_status_completed").'</span>';
                elseif ($aRow["o_ordercompleted"] == 'n') $sStatus = '<span style="color: orange; font-weight: bold;">'.\HaaseIT\Textcat::T("order_status_open").'</span>';
                elseif ($aRow["o_ordercompleted"] == 'i') $sStatus = '<span style="color: orange;">'.\HaaseIT\Textcat::T("order_status_inwork").'</span>';
                elseif ($aRow["o_ordercompleted"] == 's') $sStatus = '<span style="color: red; font-weight: bold;">'.\HaaseIT\Textcat::T("order_status_canceled").'</span>';
                elseif ($aRow["o_ordercompleted"] == 'd') $sStatus = \HaaseIT\Textcat::T("order_status_deleted");

                if ($aRow["o_paymentcompleted"] == 'y') $sZahlungsmethode = '<span style="color: green;">';
                else $sZahlungsmethode = '<span style="color: red;">';
                $mZahlungsmethode = \HaaseIT\Textcat::T("order_paymentmethod_".$aRow["o_paymentmethod"], true);
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
            //HaaseIT\Tools::debug($aData);
            $sH .= \HaaseIT\Tools::makeListtable($CSA["list_orders"], $aData, $twig);

            if ($i > 1) {
                $sH .= '<br>'.$i.' Bestellung(en) angezeigt'.(($k != 0) ? ', davon '.$k.' stornierte (diese werden bei den Wertberechnungen nicht ber&uuml;cksichtigt)' : '').'.';
                $sH .= '<br>Gesamtnetto der angezeigten Bestellungen: '.number_format($fGesamtnetto, 2, ",", ".").' '.$C["waehrungssymbol"].'.';
                $sH .= '<br>Durchschnittlicher Nettobestellwert der angezeigten Bestellungen: '.number_format(($fGesamtnetto / $j), 2, ",", ".").' '.$C["waehrungssymbol"];
            }
        } else $sH .= 'Es wurden keine zu Ihren Suchkriterien passenden Bestell-Datensätze gefunden.<br>';
    } elseif (isset($_GET["action"]) && $_GET["action"] == 'edit') {
        $iId = \filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $sQ = "SELECT * FROM ".DB_ORDERTABLE." WHERE o_id = :id";

        $hResult = $DB->prepare($sQ);
        $hResult->bindValue(':id', $iId);
        $hResult->execute();
        if ($hResult->rowCount() == 1) {
            $aSData["orderdata"] = $hResult->fetch();
            //HaaseIT\Tools::debug($aSData["orderdata"], true);
            $sQ = "SELECT * FROM ".DB_ORDERTABLE_ITEMS." WHERE oi_o_id = :id";
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':id', $iId);
            $hResult->execute();
            //HaaseIT\Tools::debug($DB->numRows($hResult));
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

            //HaaseIT\Tools::debug($aItems);
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
                    'vat' => $aValue["oi_vat"],
                    'rg' => $aValue["oi_rg"],
                    'rg_rebate' => $aValue["oi_rg_rebate"],
                    'name' => $aValue["oi_itemname"],
                    'img' => $aValue["oi_img"],
                ];
            }
            //HaaseIT\Tools::debug($aItemsforShoppingcarttable);

            $aSData = array_merge(
                buildShoppingCartTable(
                    $aItemsforShoppingcarttable,
                    $sLang,
                    $C,
                    true,
                    $aSData["orderdata"]["o_group"],
                    '',
                    $aSData["orderdata"]["o_vatfull"],
                    $aSData["orderdata"]["o_vatreduced"]
                ),
                $aSData);
        } else {
            $sH .= 'Keine entsprechende Bestellung gefunden.';
        }
    }

    $aSData["html"] = $sH;

    return $aSData;
}
