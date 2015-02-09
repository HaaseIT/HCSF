<?php

include_once($_SERVER['DOCUMENT_ROOT'].'/../app/init.php');

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

$aP = generatePage($C, $P, $sLang, $DB, $oItem);

echo $twig->render($C["template_base"], $aP);

$twig->clearTemplateCache();
$twig->clearCacheFiles();