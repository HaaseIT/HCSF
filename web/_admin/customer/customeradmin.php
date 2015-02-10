<?php

/*
    Contanto - A multilingual CMS and Shopsystem
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

include_once($_SERVER['DOCUMENT_ROOT'].'/../app/init.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/../src/customer/functions.admin.customer.php');
$sH = '';

$aPData = handleUserAdmin($CUA, $twig, $DB, $C, $sLang);

$P = array(
    'base' => array(
        'cb_pagetype' => 'content',
        'cb_pageconfig' => '',
        'cb_subnav' => 'admin',
        'cb_customcontenttemplate' => 'customer/useradmin',
    ),
    'lang' => array(
        'cl_lang' => $sLang,
        'cl_html' => $aPData["customeradmin"]["text"],
    ),
);

$P["base"]["cb_customdata"] = $aPData;

$aP = generatePage($C, $P, $sLang, $DB, $oItem);

echo $twig->render($C["template_base"], $aP);

