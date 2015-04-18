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

function handleLoginPage($C, $sLang, $DB)
{
    $P = array(
        'base' => array(
            'cb_pagetype' => 'content',
            'cb_pageconfig' => '',
            'cb_subnav' => '',
        ),
        'lang' => array(
            'cl_lang' => $sLang,
            'cl_html' => '',
        ),
    );

    if (!isset($_POST["sAction"]) || $_POST["sAction"] != "login") {
        //$P["lang"]["cl_html"] = \HaaseIT\Textcat::T("login_fail");
        $P["base"]["cb_customcontenttemplate"] = 'customer/login';
    } else {
        $mLogin = getLogin($C, $DB);
        if (isset($mLogin["status"]) && $mLogin["status"] == 'success') {
            $P["lang"]["cl_html"] = \HaaseIT\Textcat::T("login_success") . '<br>';
            header('Location: /_misc/userhome.html?login=true');
        } elseif (isset($mLogin["status"]) && $mLogin["status"] == 'tosnotaccepted') {
            $P["lang"]["cl_html"] = \HaaseIT\Textcat::T("login_fail_tosnotaccepted") . '<br>';
        } elseif (isset($mLogin["status"]) && $mLogin["status"] == 'emailnotverified') {
            $P["lang"]["cl_html"] = \HaaseIT\Textcat::T("login_fail_emailnotverified") . '<br><br>';
            $P["lang"]["cl_html"] .= '<a href="/_misc/resendverificationmail.html?email=' . $mLogin["data"][DB_CUSTOMERFIELD_EMAIL] . '">' . \HaaseIT\Textcat::T("login_fail_emailnotverifiedresend") . '</a>';
        } elseif (isset($mLogin["status"]) && $mLogin["status"] == 'accountinactive') {
            $P["lang"]["cl_html"] = \HaaseIT\Textcat::T("login_fail_accountinactive") . '<br>';
        } else {
            $P["lang"]["cl_html"] = \HaaseIT\Textcat::T("login_fail");
        }
    }

    return $P;
}

$P = handleLoginPage($C, $sLang, $DB);

if ($C["enable_module_shop"]) {
    refreshCartItems($C, $oItem);
}
