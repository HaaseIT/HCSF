<?php

include_once('base.php');

$P = array(
    'base' => array(
        'cb_pagetype' => 'content',
        'cb_pageconfig' => '',
        'cb_subnav' => 'admin',
    ),
    'lang' => array(
        'cl_lang' => $sLang,
    ),
);

$sH = '<h1>Welcome to the administration area</h1>';

$sH .= '<h3>Quick check of required file/directory permissions:</h3>';
$sH .= 'Template cache '.PATH_TEMPLATECACHE.' exists: ';
if (file_exists(PATH_TEMPLATECACHE)) {
    $sH .= 'YES, and it is '.(is_writable(PATH_TEMPLATECACHE) ? '' : 'NOT ').'writable.';
} else {
    $sH .= 'NO!';
}

$sH .= '<br><br>';
$sH .= 'Log Directory for orders '.PATH_ORDERLOG.' exists: ';
if (file_exists(PATH_ORDERLOG)) {
    $sH .= 'YES, and it is '.(is_writable(PATH_ORDERLOG) ? '' : 'NOT ').'writable.';
} else {
    $sH .= 'NO!';
}
$aApacheModules = apache_get_modules();
$sH .= '<br><br>';
$sH .= 'The Apache module mod_rewrite is '.(array_search('mod_rewrite', $aApacheModules) !== false ? '' : 'NOT ').'enabled.';

$P["lang"]["cl_html"] = $sH;

$aP = generatePage($C, $P, $sLang, $DB, $oItem);

echo $twig->render($C["template_base"], $aP);
