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

define("DB_ADDRESSFIELDS", 'cust_id, cust_no, cust_email, cust_corp, cust_name, cust_street, cust_zip, cust_town, cust_phone, cust_cellphone, cust_fax, cust_country, cust_group, cust_active, cust_emailverified, cust_tosaccepted, cust_cancellationdisclaimeraccepted');
define("DB_ITEMFIELDS", 'itm_no, itm_name, itm_price, itm_vatid, itm_rg, itm_img, itm_group, itm_data, itm_weight, itml_name_override, itml_text1, itml_text2, itm_index');
define("DB_ITEMGROUPFIELDS", 'itmg_no, itmg_name, itmg_img, itmgt_shorttext, itmgt_details');

define("PATH_BASEDIR", __DIR__.'/../../');
define("PATH_DOCROOT", PATH_BASEDIR.'web/');

define("PATH_CACHE", PATH_BASEDIR.'cache/');
define("DIRNAME_TEMPLATECACHE", 'templates');
define("PATH_TEMPLATECACHE", PATH_CACHE.DIRNAME_TEMPLATECACHE);
define("PATH_PURIFIERCACHE", PATH_CACHE.'htmlpurifier/');
define("DIRNAME_GLIDECACHE", 'glide');
define("PATH_GLIDECACHE", PATH_CACHE.DIRNAME_GLIDECACHE);

define("GLIDE_SIGNATURE_KEY", $container['conf']['glide_signkey']);

define("PATH_LOGS", __DIR__.'/../../hcsflogs/');
define("FILE_PAYPALLOG", 'ipnlog.txt');

const ENTITY_CUSTOMER = 'HaaseIT\HCSF\Entities\Customer\Customer';
