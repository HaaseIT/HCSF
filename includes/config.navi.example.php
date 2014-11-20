<?php

$TMP = array(
    'navstruct' => array(
        'navarea1' => array(
            T("sidenav_navarea1_01") => '/navarea1/page1/',
            T("sidenav_navarea1_02") => '/navarea1/page2/',
        ),
        'admin' => array(
            'Shopverwaltung' => '/_admin/shop/shopadmin.php',
            'Benutzerverwaltung' => '/_admin/customer/customeradmin.php',
            'Artikelverwaltung' => '/_admin/shop/itemadmin.php',
//			'Artikelgruppen' => '/_admin/shop/itemgroupadmin.php',
            'Seite bearbeiten' => '/_admin/pageadmin.php',
            'Textkataloge bearbeiten' => '/_admin/textcatadmin.php',
        ),
    ),
);

$C = array_merge($C, $TMP);
unset($TMP);
