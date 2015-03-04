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

define("PATH_BASEDIR", '/home/www/hostroot/');
define("PATH_TEMPLATEROOT", PATH_BASEDIR.'src/views/');
define("PATH_TEMPLATECACHE", PATH_BASEDIR.'templatecache/');
define("DIRNAME_IMAGES", '_img/');
define("DIRNAME_ITEMS", 'items/');
define("DIRNAME_ITEMSSMALLEST", '100/');
define("PATH_EMAILATTACHMENTS", $_SERVER['DOCUMENT_ROOT'].'_assets/');

$C = array(
    'debug' => (isset($_SERVER["REMOTE_USER"]) && $_SERVER["REMOTE_USER"] == 'user1' ? true : false),

    'enable_module_shop' => true,
    'enable_module_customer' => true,

    'defaulttimezone' => 'Europe/Berlin',
    'default_pagetitle' => 'Sitetitle',
    'templatecache_enable' => false,
    'template_base' => 'base.twig',
    'subnav_default' => 'root',

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
