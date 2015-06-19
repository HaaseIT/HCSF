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

define("MINUTE", 60);
define("HOUR", MINUTE * 60);
define("DAY", HOUR * 24);
define("WEEK", DAY * 7);

//define("DB_CONTENTTABLE_BASE", 'content_base');
//define("DB_CONTENTTABLE_BASE_PKEY", 'cb_id');
//define("DB_CONTENTFIELDS_BASE", '*');
//define("DB_CONTENTFIELD_BASE_KEY", 'cb_key');

//define("DB_CONTENTTABLE_LANG", 'content_lang');
//define("DB_CONTENTTABLE_LANG_PKEY", 'cl_id');
//define("DB_CONTENTTABLE_LANG_PARENTPKEY", 'cl_cb');
//define("DB_CONTENTFIELDS_LANG", '*');
//define("DB_CONTENTFIELD_LANG", 'cl_lang');
//define("DB_CONTENTFIELD_TITLE", 'cl_title');
//define("DB_CONTENTFIELD_KEYWORDS", 'cl_keywords');
//define("DB_CONTENTFIELD_DESCRIPTION", 'cl_description');

define("DB_CUSTOMERTABLE", 'customer');
define("DB_CUSTOMERTABLE_PKEY", 'cust_id');
define("DB_CUSTOMERFIELD_NUMBER", 'cust_no');
define("DB_CUSTOMERFIELD_USER", 'cust_no');
define("DB_CUSTOMERFIELD_EMAIL", 'cust_email');
define("DB_CUSTOMERFIELD_CORP", 'cust_corp');
define("DB_CUSTOMERFIELD_NAME", 'cust_name');
define("DB_CUSTOMERFIELD_STREET", 'cust_street');
define("DB_CUSTOMERFIELD_ZIP", 'cust_zip');
define("DB_CUSTOMERFIELD_TOWN", 'cust_town');
define("DB_CUSTOMERFIELD_PHONE", 'cust_phone');
define("DB_CUSTOMERFIELD_CELLPHONE", 'cust_cellphone');
define("DB_CUSTOMERFIELD_FAX", 'cust_fax');
define("DB_CUSTOMERFIELD_COUNTRY", 'cust_country');
define("DB_CUSTOMERFIELD_GROUP", 'cust_group');
define("DB_CUSTOMERFIELD_PASSWORD", 'cust_password');
define("DB_CUSTOMERFIELD_ACTIVE", 'cust_active');
define("DB_CUSTOMERFIELD_REGISTRATIONTIMESTAMP", 'cust_registrationtimestamp');
define("DB_CUSTOMERFIELD_EMAILVERIFIED", 'cust_emailverified');
define("DB_CUSTOMERFIELD_EMAILVERIFICATIONCODE", 'cust_emailverificationcode');
define("DB_CUSTOMERFIELD_TOSACCEPTED", 'cust_tosaccepted');
define("DB_CUSTOMERFIELD_CANCELLATIONDISCLAIMERACCEPTED", 'cust_cancellationdisclaimeraccepted');
define("DB_ADDRESSFIELDS", 'cust_id, cust_no, cust_email, cust_corp, cust_name, cust_street, cust_zip, cust_town, cust_phone, cust_cellphone, cust_fax, cust_country, cust_group, cust_active, cust_emailverified, cust_tosaccepted, cust_cancellationdisclaimeraccepted');
define("DB_CUSTOMERFIELD_PWRESETCODE", 'cust_pwresetcode');
define("DB_CUSTOMERFIELD_PWRESETTIMESTAMP", 'cust_pwresettimestamp');

define("DB_ITEMTABLE_BASE", 'item_base');
define("DB_ITEMTABLE_BASE_PKEY", 'itm_id');
define("DB_ITEMFIELD_NUMBER", 'itm_no');
define("DB_ITEMFIELD_NAME", 'itm_name');
define("DB_ITEMFIELD_GROUP", 'itm_group');
define("DB_ITEMFIELD_INDEX", 'itm_index');
define("DB_ITEMFIELD_PRICE", 'itm_price');
define("DB_ITEMFIELD_VAT", 'itm_vatid');
define("DB_ITEMFIELD_RG", 'itm_rg');
define("DB_ITEMFIELD_ORDER", 'itm_order');
define("DB_ITEMFIELD_IMG", 'itm_img');
define("DB_ITEMFIELD_DATA", 'itm_data');
define("DB_ITEMFIELD_WEIGHT", 'itm_weight');

define("DB_ITEMTABLE_TEXT", 'item_lang');
define("DB_ITEMTABLE_TEXT_PKEY", 'itml_id');
define("DB_ITEMTABLE_TEXT_PARENTPKEY", 'itml_pid');
define("DB_ITEMFIELD_LANGUAGE", 'itml_lang');
define("DB_ITEMFIELD_NAME_OVERRIDE", 'itml_name_override');
define("DB_ITEMFIELD_TEXT1", 'itml_text1');
define("DB_ITEMFIELD_TEXT2", 'itml_text2');
define("DB_ITEMFIELDS", 'itm_no, itm_name, itm_price, itm_vatid, itm_rg, itm_img, itm_group, itm_data, itm_weight, itml_name_override, itml_text1, itml_text2, itm_index');

define("DB_ITEMGROUPTABLE_BASE", 'itemgroups_base');
define("DB_ITEMGROUPTABLE_BASE_PKEY", 'itmg_id');
define("DB_ITEMGROUPFIELD_NUMBER", 'itmg_no');
define("DB_ITEMGROUPFIELD_NAME", 'itmg_name');
define("DB_ITEMGROUPFIELD_IMG", 'itmg_img');

define("DB_ITEMGROUPTABLE_TEXT", 'itemgroups_text');
define("DB_ITEMGROUPTABLE_TEXT_PKEY", 'itmgt_id');
define("DB_ITEMGROUPTABLE_TEXT_PARENTPKEY", 'itmgt_pid');
define("DB_ITEMGROUPFIELD_SHORTTEXT", 'itmgt_shorttext');
define("DB_ITEMGROUPFIELD_DETAILS", 'itmgt_details');
define("DB_ITEMGROUPFIELD_LANGUAGE", 'itmgt_lang');
define("DB_ITEMGROUPFIELDS", 'itmg_no, itmg_name, itmg_img, itmgt_shorttext, itmgt_details');

define("DB_ORDERTABLE", 'orders');
define("DB_ORDERTABLE_PKEY", 'o_id');
define("DB_ORDERFIELD_PAYMENTMETHOD", 'o_paymentmethod');
define("DB_ORDERTABLE_ITEMS", 'orders_items');

define("PATH_BASEDIR", __DIR__.'/../../');
define("PATH_DOCROOT", PATH_BASEDIR.'web/');

define("PATH_TEMPLATECACHE", PATH_BASEDIR.'templatecache/');