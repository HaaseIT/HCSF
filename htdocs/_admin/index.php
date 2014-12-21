<?php

include_once('base.inc.php');

$P = array(
    'base' => array(
        'cb_pagetype' => 'content',
        'cb_pageconfig' => '',
        'cb_subnav' => 'admin',
    ),
    'lang' => array(
        'cl_lang' => $sLang,
        'cl_html' => 'Welcome to the administration area',
    ),
);

$aP = generatePage($C, $P, $sLang);

echo $twig->render($C["template_base"], $aP);
