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
 * Class Sofortueberweisung
 * @package HaaseIT\HCSF\Controller\Shop
 */
class Sofortueberweisung extends Base
{
    /**
     * @var \HaaseIT\Textcat
     */
    private $textcats;

    /**
     * Sofortueberweisung constructor.
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
        $sql = 'SELECT * FROM orders '
            . "WHERE o_id = :id AND o_paymentmethod = 'sofortueberweisung' AND o_paymentcompleted = 'n'";

        /** @var \PDOStatement $hResult */
        $hResult = $this->serviceManager->get('db')->prepare($sql);
        $hResult->bindValue(':id', $iId, \PDO::PARAM_INT);

        $hResult->execute();

        if ($hResult->rowCount() == 1) {
            $aOrder = $hResult->fetch();
            $fGesamtbrutto = \HaaseIT\HCSF\Shop\Helper::calculateTotalFromDB($aOrder);

            $sPURL =
                'https://www.sofortueberweisung.de/payment/start?user_id='
                .HelperConfig::$shop["sofortueberweisung"]["user_id"]
                .'&amp;project_id='.HelperConfig::$shop["sofortueberweisung"]["project_id"].'&amp;amount='
                .number_format($fGesamtbrutto, 2, '.', '')
                .'&amp;currency_id='.HelperConfig::$shop["sofortueberweisung"]["currency_id"].'&amp;reason_1='
                .urlencode($this->textcats->T("misc_paysofortueberweisung_ueberweisungsbetreff") . ' ').$iId;
            if (HelperConfig::$shop["interactive_paymentmethods_redirect_immediately"]) {
                header('Location: ' . $sPURL);
                die();
            }

            $this->P->oPayload->cl_html = $this->textcats->T("misc_paysofortueberweisung_greeting") . '<br><br>';
            $this->P->oPayload->cl_html .= '<a href="' . $sPURL . '">' . $this->textcats->T("misc_paysofortueberweisung") . '</a>';
        } else {
            $this->P->oPayload->cl_html = $this->textcats->T("misc_paysofortueberweisung_paymentnotavailable");
        }
    }
}