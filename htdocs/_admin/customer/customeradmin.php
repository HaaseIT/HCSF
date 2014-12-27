<?php

//error_reporting(E_ALL);

include_once('base.php');
include_once('customer/functions.admin.customer.php');
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

