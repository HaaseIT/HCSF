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

class Updateshippingcost extends Base
{
    public function __construct($C, $DB, $sLang, $twig, $oItem)
    {
        parent::__construct($C, $DB, $sLang);

        $_SESSION["formsave_addrform"]["country"] = $_POST["country"];
        buildShoppingCartTable($_SESSION["cart"], $sLang, $C);

        header("Content-Type: text/html; charset=UTF-8");
        $sH = '<div>';
        $sH .= '<div id="shippingcostbrutto_new">' . number_format($_SESSION["cartpricesums"]["fVersandkostenbrutto"], 2, ',', '.') . '</div>';
        $sH .= '<div id="vatfull_new">' . number_format(round($_SESSION["cartpricesums"]["fSteuervoll"], 2), 2, ',', '.') . '</div>';
        $sH .= '<div id="totalbrutto_new">' . number_format(round($_SESSION["cartpricesums"]["fGesamtbrutto"], 2), 2, ',', '.') . '</div>';
        $sH .= '<div id="shippingcostnetto_new">' . number_format($_SESSION["cartpricesums"]["fVersandkostennetto"], 2, ',', '.') . '</div>';
        $sH .= '<div id="vatreduced_new">' . number_format(round($_SESSION["cartpricesums"]["fSteuererm"], 2), 2, ',', '.') . '</div>';
        $sH .= '<div id="totalnetto_new">' . number_format(round($_SESSION["cartpricesums"]["fGesamtnetto"], 2), 2, ',', '.') . '</div>';
        $sH .= '</div>';
        die($sH);
    }
}