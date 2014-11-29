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
        'cl_html' => 'The template cache has been cleared.',
    ),
);

$aP = generatePage($C, $P, $sLang, $FORM);

echo $twig->render($C["template_base"], $aP);

$twig->clearTemplateCache();
$twig->clearCacheFiles();