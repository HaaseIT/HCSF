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


use HaaseIT\HCSF\HelperConfig;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Paypal
 * @package HaaseIT\HCSF\Controller\Shop
 */
class Paypal extends Base
{
    /**
     * @var \HaaseIT\Toolbox\Textcat
     */
    private $textcats;

    /**
     * Paypal constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->textcats = $serviceManager->get('textcats');
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        $iId = \filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $sql = 'SELECT * FROM orders ';
        $sql .= "WHERE o_id = :id AND o_paymentmethod = 'paypal' AND o_paymentcompleted = 'n'";

        /** @var \PDOStatement $hResult */
        $hResult = $this->serviceManager->get('db')->prepare($sql);
        $hResult->bindValue(':id', $iId, \PDO::PARAM_INT);

        $hResult->execute();

        if ($hResult->rowCount() == 1) {
            $aOrder = $hResult->fetch();
            $fGesamtbrutto = \HaaseIT\HCSF\Shop\Helper::calculateTotalFromDB($aOrder);

            $sPaypalURL = $this->config->getShop('paypal')['url']
                .'?cmd=_xclick&rm=2&custom='
                .$iId.'&business='.$this->config->getShop('paypal')['business'];
            $sPaypalURL .= '&notify_url=http://'.filter_input(INPUT_SERVER, 'SERVER_NAME').'/_misc/paypal_notify.html&item_name='.$this->textcats->T('misc_paypaypal_paypaltitle').' '.$iId;
            $sPaypalURL .= '&currency_code='.$this->config->getShop('paypal')['currency_id']
                .'&amount='.str_replace(',', '.', number_format($fGesamtbrutto, 2, '.', ''));
            if ($this->config->getShop('interactive_paymentmethods_redirect_immediately')) {
                \HaaseIT\HCSF\Helper::redirectToPage($sPaypalURL);
            }

            $this->P->oPayload->cl_html = $this->textcats->T('misc_paypaypal_greeting').'<br><br>';
            $this->P->oPayload->cl_html .= '<a href="'.$sPaypalURL.'">'.$this->textcats->T('misc_paypaypal').'</a>';
        } else {
            $this->P->oPayload->cl_html = $this->textcats->T('misc_paypaypal_paymentnotavailable');
        }
    }
}
