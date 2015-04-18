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

require_once __DIR__ . '/../../src/shop/functions.shoppingcart.php';

mb_internal_encoding('UTF-8');
header("Content-Type: text/html; charset=UTF-8");

if (ini_get('session.auto_start') == 1) {
    die('Please disable session.autostart for this to work.');
}

if (isset($_COOKIE["acceptscookies"]) && $_COOKIE["acceptscookies"] == 'yes') {
// Session handling
// session.use_trans_sid wenn nötig aktivieren
    session_name('sid');
    if (ini_get('session.use_trans_sid') == 1) {
        ini_set('session.use_trans_sid', 0);
    }
// Session wenn nötig starten
    if (session_id() == '') {
        session_start();
    }

    $_SESSION["formsave_addrform"]["country"] = $_POST["country"];
    $aData = buildShoppingCartTable($_SESSION["cart"], $sLang, $C);

    echo '<div>';
    echo '<div id="shippingcostbrutto_new">' . number_format($_SESSION["cartpricesums"]["fVersandkostenbrutto"], 2, ',', '.') . '</div>';
    echo '<div id="vatfull_new">' . number_format(round($_SESSION["cartpricesums"]["fSteuervoll"], 2), 2, ',', '.') . '</div>';
    echo '<div id="totalbrutto_new">' . number_format(round($_SESSION["cartpricesums"]["fGesamtbrutto"], 2), 2, ',', '.') . '</div>';
    echo '<div id="shippingcostnetto_new">' . number_format($_SESSION["cartpricesums"]["fVersandkostennetto"], 2, ',', '.') . '</div>';
    echo '<div id="vatreduced_new">' . number_format(round($_SESSION["cartpricesums"]["fSteuererm"], 2), 2, ',', '.') . '</div>';
    echo '<div id="totalnetto_new">' . number_format(round($_SESSION["cartpricesums"]["fGesamtnetto"], 2), 2, ',', '.') . '</div>';
    echo '</div>';
}

die();