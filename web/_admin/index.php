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

include_once(__DIR__.'/../../app/init.php');

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

$sH .= '<h3>Quick check of required PHP Extensions:</h3>';
$sH .= '<p>Filter is: '.(extension_loaded('filter') ? 'enabled' : 'disabled').'</p>';


$sH .= '<h3>Quick check of required file/directory permissions:</h3>';
$sH .= '<p>Template cache '.realpath(PATH_TEMPLATECACHE).' exists: ';
if (file_exists(PATH_TEMPLATECACHE)) {
    $sH .= 'YES, and it is '.(is_writable(PATH_TEMPLATECACHE) ? '' : 'NOT ').'writable.';
} else {
    $sH .= 'NO!';
}
$sH .= '</p>';

if ($C["enable_module_shop"]) {
    $sH .= '<p>Log Directory for orders ' . realpath(PATH_ORDERLOG) . ' exists: ';
    if (file_exists(PATH_ORDERLOG)) {
        $sH .= 'YES, and it is ' . (is_writable(PATH_ORDERLOG) ? '' : 'NOT ') . 'writable.';
    } else {
        $sH .= 'NO!';
    }
    $sH .= '</p>';

    $sH .= '<p>Log Directory for PayPal Transactions ' . realpath(PATH_PAYPALLOG) . ' exists: ';
    if (file_exists(PATH_PAYPALLOG)) {
        $sH .= 'YES, and it is ' . (is_writable(PATH_PAYPALLOG) ? '' : 'NOT ') . 'writable.';
    } else {
        $sH .= 'NO!';
    }
    $sH .= '</p>';
}

$aApacheModules = apache_get_modules();
$sH .= '<p>The Apache module mod_rewrite is '.(array_search('mod_rewrite', $aApacheModules) !== false ? '' : 'NOT ').'enabled.</p>';

$P["lang"]["cl_html"] = $sH;

$aP = generatePage($C, $P, $sLang, $DB, $oItem);

echo $twig->render($C["template_base"], $aP);
