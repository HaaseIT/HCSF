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

/**
 * Class Updateshippingcost
 * @package HaaseIT\HCSF\Controller\Shop
 */
class Updateshippingcost extends Base
{
    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        $_SESSION['formsave_addrform']['country'] = $_POST['country'];
        \HaaseIT\HCSF\Shop\Helper::buildShoppingCartTable($_SESSION['cart']);

        header('Content-Type: text/html; charset=UTF-8');
        $return = '<div>';
        $return .= '<div id="shippingcostbrutto_new">' . number_format($_SESSION['cartpricesums']['fVersandkostenbrutto'],
                HelperConfig::$core['numberformat_decimals'], HelperConfig::$core['numberformat_decimal_point'],
                HelperConfig::$core['numberformat_thousands_seperator']) . '</div>';
        $return .= '<div id="vatfull_new">' . number_format(round($_SESSION['cartpricesums']['fSteuervoll'], 2),
                HelperConfig::$core['numberformat_decimals'], HelperConfig::$core['numberformat_decimal_point'],
                HelperConfig::$core['numberformat_thousands_seperator']) . '</div>';
        $return .= '<div id="totalbrutto_new">' . number_format(round($_SESSION['cartpricesums']['fGesamtbrutto'], 2),
                HelperConfig::$core['numberformat_decimals'], HelperConfig::$core['numberformat_decimal_point'],
                HelperConfig::$core['numberformat_thousands_seperator']) . '</div>';
        $return .= '<div id="shippingcostnetto_new">' . number_format($_SESSION['cartpricesums']['fVersandkostennetto'],
                HelperConfig::$core['numberformat_decimals'], HelperConfig::$core['numberformat_decimal_point'],
                HelperConfig::$core['numberformat_thousands_seperator']) . '</div>';
        $return .= '<div id="vatreduced_new">' . number_format(round($_SESSION['cartpricesums']['fSteuererm'], 2),
                HelperConfig::$core['numberformat_decimals'], HelperConfig::$core['numberformat_decimal_point'],
                HelperConfig::$core['numberformat_thousands_seperator']) . '</div>';
        $return .= '<div id="totalnetto_new">' . number_format(round($_SESSION['cartpricesums']['fGesamtnetto'], 2),
                HelperConfig::$core['numberformat_decimals'], HelperConfig::$core['numberformat_decimal_point'],
                HelperConfig::$core['numberformat_thousands_seperator']) . '</div>';
        $return .= '</div>';
        \HaaseIT\HCSF\Helper::terminateScript($return);
    }
}
