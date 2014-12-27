<?php

define("PATH_BASEDIR", '/home/www/hostroot/');
define("PATH_DOCROOT", PATH_BASEDIR.'htdocs/');
define("PATH_LIBRARIESROOT", PATH_BASEDIR.'libs/');
define("PATH_TWIGROOT", PATH_LIBRARIESROOT.'twig/');
define("PATH_TEMPLATEROOT", PATH_BASEDIR.'templates/');
define("PATH_TEMPLATECACHE", PATH_BASEDIR.'templatecache/');
define("DIRNAME_IMAGES", '_img/');
define("DIRNAME_ITEMS", 'items/');
define("DIRNAME_ITEMSSMALLEST", '100/');
define("PATH_EMAILATTACHMENTS", PATH_DOCROOT.'_assets/');

$C = array(
    'debug' => (isset($_SERVER["REMOTE_USER"]) && $_SERVER["REMOTE_USER"] == 'user1' ? true : false),
    'defaulttimezone' => 'Europe/Berlin',
    'default_pagetitle' => 'Sitetitle',
    'templatecache_enable' => false,
    'template_base' => 'base.twig',
    'subnav_default' => '',

    'admin_users' => array('user1', 'user2'),

    'email_sendername' => 'Sitename Webshop',
    'email_sender' => 'mail@domain.tld',

    'lang_available' => array(
        'de' => 'German',
        'en' => 'English',
        'es' => 'Español'
    ),
    'lang_detection_method' => 'legacy', // legacy / domain
    'lang_by_domain' => array('de' => 'domain.de', 'en' => 'domain.com', 'es' => 'domain.es'), // only needed if lang_detection_method == domain

    'admin_page_groups' => array(
        '_|_Keine_',
        'admin|Administration',
        'verschiedenes|Verschiedenes',
        'obsolete|Obsolet',
    ),

    'admin_page_types' => array(
        'content',
        'contentnosubnav',
        'itemoverview',
        //'itemdetail',
    ),

    'locale_format_date' => "d.m.Y",
    'locale_format_date_time' => "d.m.Y H:i",

    'defaultcountrybylang' => array(
        'de' => 'DE',
        'en' => 'GB',
        'es' => 'ES',
    ),
);
