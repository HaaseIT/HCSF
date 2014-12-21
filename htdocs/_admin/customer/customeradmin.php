<?php

//error_reporting(E_ALL);

include_once('base.inc.php');
include_once('customer/functions.admin.customer.inc.php');
$sH = '';

$aPData = handleUserAdmin($CUA, $twig);

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

$aP = generatePage($C, $P, $sLang);
$aP["debug"] = true;

echo $twig->render($C["template_base"], $aP);

