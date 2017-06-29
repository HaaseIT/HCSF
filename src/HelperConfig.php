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

namespace HaaseIT\HCSF;

use Symfony\Component\Yaml\Yaml;
use Zend\ServiceManager\ServiceManager;

/**
 * Class HelperConfig
 * @package HaaseIT\HCSF
 */
class HelperConfig
{
    /**
     * @var array
     */
    public static $core = [];

    /**
     * @var array
     */
    public static $secrets = [];

    /**
     * @var array
     */
    public static $countries = [];

    /**
     * @var array
     */
    public static $shop = [];

    /**
     * @var array
     */
    public static $customer = [];

    /**
     * @var array
     */
    public static $navigation = [];

    /**
     * @var string
     */
    public static $lang = '';

    /**
     *
     */
    public static function init()
    {
        static::loadCore();
        static::loadCountries();

        static::$lang = \HaaseIT\HCSF\Helper::getLanguage();

        static::loadSecrets();

        if (static::$core['enable_module_customer']) {
            static::loadCustomer();
        }

        if (static::$core['enable_module_shop']) {
            static::loadShop();
        }
    }

    /**
     *
     */
    private static function loadCore()
    {
        $core = Yaml::parse(file_get_contents(HCSF_BASEDIR.'config/core.yml'));
        if (is_file(PATH_BASEDIR.'config/core.yml')) {
            $core = array_merge($core, Yaml::parse(file_get_contents(PATH_BASEDIR.'config/core.yml')));
        }

        $core['directory_images'] = trim($core['directory_images'], " \t\n\r\0\x0B/"); // trim this

        if (!empty($core['maintenancemode']) && $core['maintenancemode']) {
            $core['enable_module_customer'] = false;
            $core['enable_module_shop'] = false;
            $core['templatecache_enable'] = false;
            $core['debug'] = false;
            $core['textcatsverbose'] = false;
        } else {
            $core['maintenancemode'] = false;
        }

        if ($core['enable_module_shop']) {
            $core['enable_module_customer'] = true;
        }

        static::$core = $core;
    }

    /**
     *
     */
    private static function loadCountries()
    {
        $countries = Yaml::parse(file_get_contents(HCSF_BASEDIR.'config/countries.yml'));
        if (is_file(PATH_BASEDIR.'config/countries.yml')) {
            $countries = array_merge($countries, Yaml::parse(file_get_contents(PATH_BASEDIR.'config/countries.yml')));
        }

        static::$countries = $countries;
    }

    /**
     *
     */
    private static function loadSecrets()
    {
        $secrets = Yaml::parse(file_get_contents(HCSF_BASEDIR.'config/secrets.yml'));
        if (is_file(PATH_BASEDIR.'config/secrets.yml')) {
            $secrets = array_merge($secrets, Yaml::parse(file_get_contents(PATH_BASEDIR.'config/secrets.yml')));
        }

        static::$secrets = $secrets;
    }

    /**
     *
     */
    private static function loadCustomer()
    {
        $customer = Yaml::parse(file_get_contents(HCSF_BASEDIR.'config/customer.yml'));
        if (is_file(PATH_BASEDIR.'/config/customer.yml')) {
            $customer = array_merge($customer, Yaml::parse(file_get_contents(PATH_BASEDIR.'config/customer.yml')));
        }

        static::$customer = $customer;
    }

    /**
     *
     */
    private static function loadShop()
    {
        $shop = Yaml::parse(file_get_contents(HCSF_BASEDIR.'config/shop.yml'));
        if (is_file(PATH_BASEDIR.'config/shop.yml')) {
            $shop = array_merge($shop, Yaml::parse(file_get_contents(PATH_BASEDIR.'config/shop.yml')));
        }
        if (isset($shop['vat_disable']) && $shop['vat_disable']) {
            $shop['vat'] = ['full' => 0, 'reduced' => 0];
        }

        static::$shop = $shop;
    }

    /**
     * @param ServiceManager $serviceManager
     */
    public static function loadNavigation(ServiceManager $serviceManager)
    {
        if (is_file(PATH_BASEDIR.'config/navigation.yml')) {
            $navstruct = Yaml::parse(file_get_contents(PATH_BASEDIR.'config/navigation.yml'));
        } else {
            $navstruct = Yaml::parse(file_get_contents(HCSF_BASEDIR.'config/navigation.yml'));
        }

        if (!empty($navstruct) && static::$core['navigation_fetch_text_from_textcats']) {
            $textcats = $serviceManager->get('textcats');
            $TMP = [];

            foreach ($navstruct as $key => $item) {
                foreach ($item as $subkey => $subitem) {
                    if (!empty($textcats->T($subkey))) {
                        $TMP[$key][$textcats->T($subkey)] = $subitem;
                    } else {
                        $TMP[$key][$subkey] = $subitem;
                    }
                }
            }
            $navstruct = $TMP;
            unset($TMP);
        }

        if (isset($navstruct['admin'])) {
            unset($navstruct['admin']);
        }

        $hardcodedtextcats = $serviceManager->get('hardcodedtextcats');

        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_home')] = '/_admin/index.html';

        if (static::$core['enable_module_shop']) {
            $navstruct['admin'][$hardcodedtextcats->get('admin_nav_orders')] = '/_admin/shopadmin.html';
            $navstruct['admin'][$hardcodedtextcats->get('admin_nav_items')] = '/_admin/itemadmin.html';
            $navstruct['admin'][$hardcodedtextcats->get('admin_nav_itemgroups')] = '/_admin/itemgroupadmin.html';
        }

        if (static::$core['enable_module_customer']) {
            $navstruct['admin'][$hardcodedtextcats->get('admin_nav_customers')] = '/_admin/customeradmin.html';
        }

        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_pages')] = '/_admin/pageadmin.html';
        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_textcats')] = '/_admin/textcatadmin.html';
        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_cleartemplatecache')] = '/_admin/cleartemplatecache.html';
        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_clearimagecache')] = '/_admin/clearimagecache.html';
        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_phpinfo')] = '/_admin/phpinfo.html';
        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_dbstatus')] = '/_admin/dbstatus.html';

        static::$navigation = $navstruct;
    }
}
