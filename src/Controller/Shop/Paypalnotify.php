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

namespace HaaseIT\HCSF\Controller\Shop;

class Paypalnotify extends Base
{
    public function __construct($C, $DB, $sLang, $twig, $oItem)
    {
        parent::__construct($C, $DB, $sLang);

        $sLogData = '';

        $iId = \filter_input(INPUT_POST, 'custom', FILTER_SANITIZE_NUMBER_INT);
        $sQ = 'SELECT * FROM orders WHERE o_id = '.$iId.' AND o_paymentmethod'." = 'paypal' AND o_paymentcompleted = 'n'";

        $hResult = $DB->query($sQ);

        if ($hResult->rowCount() == 1) {
            $aOrder = $hResult->fetch();
            $fGesamtbrutto = \HaaseIT\HCSF\Shop\Helper::calculateTotalFromDB($aOrder);

            $postdata = '';

            foreach ($_POST as $i => $v) {
                $postdata .= $i . '=' . urlencode($v) . '&';
            }
            $postdata .= 'cmd=_notify-validate';
            $web = parse_url($C["paypal"]["url"]);

            if ($web['scheme'] == 'https') {
                $web['port'] = 443;
                $ssl = 'ssl://';
            } else {
                $web['port'] = 80;
                $ssl = '';
            }
            $fp = @fsockopen($ssl . $web['host'], $web['port'], $errnum, $errstr, 30);

            if ($fp) {
                fputs($fp, "POST " . $web['path'] . " HTTP/1.1\r\n");
                fputs($fp, "Host: " . $web['host'] . "\r\n");
                fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
                fputs($fp, "Content-length: " . strlen($postdata) . "\r\n");
                fputs($fp, "Connection: close\r\n\r\n");
                fputs($fp, $postdata . "\r\n\r\n");
                while (!feof($fp)) $info[] = @fgets($fp, 1024);
                fclose($fp);
                $info = implode(',', $info);
                if (!(strpos($info, 'VERIFIED') === false)) {

                    $sLogData .= "-- new entry - " . date($this->C['locale_format_date_time']) . " --\n\n";
                    $sLogData .= "W00T!\n\n";
                    $sLogData .= \HaaseIT\Tools::debug($_REQUEST, '', true, true)."\n\n";

                    // Check if the transaction id has been used before
                    $sTxn_idQ = 'SELECT o_paypal_tx FROM orders WHERE o_paypal_tx = :txn_id';
                    $hTxn_idResult = $DB->prepare($sTxn_idQ);
                    $hTxn_idResult->bindValue(':txn_id', $_REQUEST["txn_id"]);
                    $hTxn_idResult->execute();

                    if ($hTxn_idResult->rowCount() == 0) {
                        if (
                            $_REQUEST["mc_gross"] == number_format($fGesamtbrutto, 2, '.', '')
                            && $_REQUEST["custom"] == $aOrder['o_id']
                            && $_REQUEST["payment_status"] == "Completed"
                            && $_REQUEST["mc_currency"] == $C["paypal"]["currency_id"]
                            && $_REQUEST["business"] == $C["paypal"]["business"]
                        ) {
                            $aTxnUpdateData = [
                                'o_paypal_tx' => $_REQUEST["txn_id"],
                                'o_paymentcompleted' => 'y',
                                'o_id' => $iId,
                            ];
                            $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aTxnUpdateData, 'orders', 'o_id');
                            $hResult = $DB->prepare($sQ);
                            foreach ($aTxnUpdateData as $sKey => $sValue) {
                                $hResult->bindValue(':' . $sKey, $sValue);
                            }
                            $hResult->execute();

                            $sLogData .= "-- Alles ok. Zahlung erfolgreich. TXNID: " . $_REQUEST["txn_id"] . " --\n\n";
                        } else {
                            $sLogData .= "-- In my country we have problem; Problem is evaluation. Throw the data down the log!\n";
                            $sLogData .= "mc_gross: ".$_REQUEST["mc_gross"].' - number_format($fGesamtbrutto, 2, \'.\', \'\'): '.number_format($fGesamtbrutto, 2, '.', '')."\n";
                            $sLogData .= "custom: ".$_REQUEST["custom"].' - $aOrder[\'o_id\']: '.$aOrder['o_id']."\n";
                            $sLogData .= "payment_status: ".$_REQUEST["payment_status"]."\n";
                            $sLogData .= "mc_currency: ".$_REQUEST["mc_currency"].' - $C["paypal"]["currency_id"]: '.$C["paypal"]["currency_id"]."\n";
                            $sLogData .= "business: ".$_REQUEST["receiver_email"].' - $C["paypal"]["business"]: '.$C["paypal"]["business"]."\n\n";
                        }
                    } else {
                        // INVALID LOGGING ERROR
                        $sLogData .= "-- new entry - " . date($this->C['locale_format_date_time']) . " --\n\nPHAIL\n\n";
                        $sLogData .= "!!! JEMAND HAT EINE ALTE TXN_ID BENUTZT: " . $_REQUEST["txn_id"] . " !!!\n\n";
                        $sLogData .= "!!! INVALID !!!\n\n";
                    }
                } else {
                    $sLogData .= "-- new entry - " . date($this->C['locale_format_date_time']) . " --\n\nPHAIL - Transaktion fehlgeschlagen. TXNID: " . $_REQUEST["txn_id"] . "\n" . $info . "\n\n";
                }
                $bNufile = false;
                if (!file_exists(PATH_LOGS . FILE_PAYPALLOG)) $bNufile = true;
                $fp = fopen(PATH_LOGS . FILE_PAYPALLOG, 'a');
                // Write $somecontent to our opened file.
                fwrite($fp, $sLogData);
                fclose($fp);
            }
        }

        die();
    }

}