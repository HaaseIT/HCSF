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

$TMP = array(
    'navstruct' => array(
        'root' => array(
            \HaaseIT\Textcat::T("sidenav_root_01") => '/page1/',
            \HaaseIT\Textcat::T("sidenav_root_02") => '/page2/',
        ),
        'navarea1' => array(
            \HaaseIT\Textcat::T("sidenav_navarea1_01") => '/navarea1/page1/',
            \HaaseIT\Textcat::T("sidenav_navarea1_02") => '/navarea1/page2/',
        ),
        'admin' => array(
            'Shopverwaltung' => '/_admin/shop/shopadmin.php',
            'Benutzerverwaltung' => '/_admin/customer/customeradmin.php',
            'Artikelverwaltung' => '/_admin/shop/itemadmin.php',
//			'Artikelgruppen' => '/_admin/shop/itemgroupadmin.php',
            'Seite bearbeiten' => '/_admin/pageadmin.php',
            'Textkataloge bearbeiten' => '/_admin/textcatadmin.php',
            'Templatecache leeren' => '/_admin/cleartemplatecache.php',
        ),
    ),
);

$C = array_merge($C, $TMP);
unset($TMP);
