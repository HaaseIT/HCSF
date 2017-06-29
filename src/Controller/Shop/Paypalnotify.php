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


use Zend\ServiceManager\ServiceManager;

/**
 * Class Paypalnotify
 * @package HaaseIT\HCSF\Controller\Shop
 */
class Paypalnotify extends Base
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $dbal;

    /**
     * Paypalnotify constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->dbal = $serviceManager->get('dbal');
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        $sLogData = '';

        $iId = \filter_input(INPUT_POST, 'custom', FILTER_SANITIZE_NUMBER_INT);

        $queryBuilder = $this->dbal->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('orders')
            ->where('o_id = ?')
            ->andWhere('o_paymentmethod = \'paypal\'')
            ->andWhere('o_paymentcompleted = \'n\'')
            ->setParameter(0, $iId)
        ;
        $statement = $queryBuilder->execute();

        if ($statement->rowCount() == 1) {
            $aOrder = $statement->fetch();
            $fGesamtbrutto = $this->helperShop->calculateTotalFromDB($aOrder);

            $postdata = '';

            foreach ($_POST as $i => $v) {
                $postdata .= $i . '=' . urlencode($v) . '&';
            }
            $postdata .= 'cmd=_notify-validate';
            $web = parse_url($this->config->getShop('paypal')['url']);

            if ($web['scheme'] === 'https') {
                $web['port'] = 443;
                $ssl = 'ssl://';
            } else {
                $web['port'] = 80;
                $ssl = '';
            }
            $fp = @fsockopen($ssl . $web['host'], $web['port'], $errnum, $errstr, 30);

            if ($fp) {
                fwrite($fp, 'POST ' . $web['path'] . " HTTP/1.1\r\n");
                fwrite($fp, 'Host: ' . $web['host'] . "\r\n");
                fwrite($fp, "Content-type: application/x-www-form-urlencoded\r\n");
                fwrite($fp, 'Content-length: ' . strlen($postdata) . "\r\n");
                fwrite($fp, "Connection: close\r\n\r\n");
                fwrite($fp, $postdata . "\r\n\r\n");

                $info = [];
                while (!feof($fp)) {
                    $info[] = fgets($fp, 1024);
                }
                fclose($fp);
                $info = implode(',', $info);
                if (!(strpos($info, 'VERIFIED') === false)) {

                    $sLogData .= '-- new entry - '.date($this->config->getCore('locale_format_date_time')) . " --\n\n";
                    $sLogData .= "W00T!\n\n";
                    $sLogData .= \HaaseIT\Toolbox\Tools::debug($_REQUEST, '', true, true) . "\n\n";

                    // Check if the transaction id has been used before
                    $queryBuilder = $this->dbal->createQueryBuilder();
                    $queryBuilder
                        ->select('o_paypal_tx')
                        ->from('orders')
                        ->where('o_paypal_tx = ?')
                        ->setParameter(0, filter_input(INPUT_POST, 'txn_id'));
                    $statement = $queryBuilder->execute();

                    if ($statement->rowCount() === 0) {
                        if (
                            filter_input(INPUT_POST, 'payment_status') === 'Completed'
                            && filter_input(INPUT_POST, 'mc_gross') == number_format($fGesamtbrutto, 2, '.', '')
                            && filter_input(INPUT_POST, 'custom') == $aOrder['o_id']
                            && filter_input(INPUT_POST, 'mc_currency') == $this->config->getShop('paypal')['currency_id']
                            && filter_input(INPUT_POST, 'business') == $this->config->getShop('paypal')['business']
                        ) {
                            $queryBuilder = $this->dbal->createQueryBuilder();
                            $queryBuilder
                                ->update('orders')
                                ->set('o_paypal_tx', '?')
                                ->set('o_paymentcompleted', 'y')
                                ->setParameter(0, filter_input(INPUT_POST, 'txn_id'))
                                ->where('o_id = ?')
                                ->setParameter(1, $iId);
                            $queryBuilder->execute();

                            $sLogData .= '-- Alles ok. Zahlung erfolgreich. TXNID: ' . $_REQUEST['txn_id'] . " --\n\n";
                        } else {
                            $sLogData .= "-- In my country we have problem; Problem is evaluation. Throw the data down the log!\n";
                            $sLogData .= 'mc_gross: ' . $_REQUEST['mc_gross'] . ' - number_format($fGesamtbrutto, 2, \'.\', \'\'): ' . number_format($fGesamtbrutto,
                                    2, '.', '') . "\n";
                            $sLogData .= 'custom: ' . $_REQUEST['custom'] . ' - $aOrder[\'o_id\']: ' . $aOrder['o_id'] . "\n";
                            $sLogData .= 'payment_status: ' . $_REQUEST['payment_status'] . "\n";
                            $sLogData .= 'mc_currency: ' . $_REQUEST['mc_currency'] . ' - HelperConfig::$shop["paypal"]["currency_id"]: ' . $this->config->getShop('paypal')['currency_id'] . "\n";
                            $sLogData .= 'business: ' . $_REQUEST['receiver_email'] . ' - HelperConfig::$shop["paypal"]["business"]: ' . $this->config->getShop('paypal')['business'] . "\n\n";
                        }
                    } else {
                        // INVALID LOGGING ERROR
                        $sLogData .= '-- new entry - ' . date($this->config->getCore('locale_format_date_time')) . " --\n\nPHAIL\n\n";
                        $sLogData .= '!!! JEMAND HAT EINE ALTE TXN_ID BENUTZT: ' . $_REQUEST['txn_id'] . " !!!\n\n";
                        $sLogData .= "!!! INVALID !!!\n\n";
                    }
                } else {
                    $sLogData .= '-- new entry - ' . date($this->config->getCore('locale_format_date_time')) . " --\n\nPHAIL - Transaktion fehlgeschlagen. TXNID: " . $_REQUEST['txn_id'] . "\n" . $info . "\n\n";
                }

                file_put_contents(PATH_LOGS . FILE_PAYPALLOG, $sLogData, FILE_APPEND);
            }
        }

        $this->helper->terminateScript();
    }
}
