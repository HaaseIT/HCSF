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

class Paypal extends Base
{
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->C, $this->sLang);
        $this->P->cb_pagetype = 'content';

        $iId = \filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $sQ = 'SELECT * FROM orders ';
        $sQ .= "WHERE o_id = :id AND o_paymentmethod = 'paypal' AND o_paymentcompleted = 'n'";

        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':id', $iId, \PDO::PARAM_INT);

        $hResult->execute();

        if ($hResult->rowCount() == 1) {
            $aOrder = $hResult->fetch();
            $fGesamtbrutto = \HaaseIT\HCSF\Shop\Helper::calculateTotalFromDB($aOrder);

            $sPaypalURL = $this->C["paypal"]["url"] . '?cmd=_xclick&rm=2&custom=' . $iId . '&business=' . $this->C["paypal"]["business"];
            $sPaypalURL .= '&notify_url=http://' . $_SERVER["SERVER_NAME"] . '/_misc/paypal_notify.html&item_name=' . \HaaseIT\Textcat::T("misc_paypaypal_paypaltitle") . ' ' . $iId;
            $sPaypalURL .= '&currency_code=' . $this->C["paypal"]["currency_id"] . '&amount=' . str_replace(',', '.',
                    number_format($fGesamtbrutto, 2, '.', ''));
            if (isset($this->C["interactive_paymentmethods_redirect_immediately"]) && $this->C["interactive_paymentmethods_redirect_immediately"]) {
                header('Location: ' . $sPaypalURL);
                die();
            }

            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("misc_paypaypal_greeting") . '<br><br>';
            $this->P->oPayload->cl_html .= '<a href="' . $sPaypalURL . '">' . \HaaseIT\Textcat::T("misc_paypaypal") . '</a>';
        } else {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("misc_paypaypal_paymentnotavailable");
        }
    }
}