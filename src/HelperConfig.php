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
    protected $core = [];

    /**
     * @var array
     */
    protected $secrets = [];

    /**
     * @var array
     */
    protected $countries = [];

    /**
     * @var array
     */
    protected $shop = [];

    /**
     * @var array
     */
    protected $customer = [];

    /**
     * @var array
     */
    protected $navigation = [];

    /**
     * @var string
     */
    protected $lang;

    /**
     * @var array
     */
    protected $customization = [];

    /**
     *
     */
    public function __construct()
    {
        $this->loadCore();
        $this->loadCountries();

        $this->lang = $this->getLanguage();

        $this->loadSecrets();

        if ($this->core['enable_module_customer']) {
            $this->loadCustomer();
        }

        if ($this->core['enable_module_shop']) {
            $this->loadShop();
        }

        $this->loadCustomization();
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     *
     */
    protected function loadCore()
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

        $this->core = $core;
    }

    /**
     * @param string|false $setting
     * @return mixed
     */
    public function getCore($setting = false)
    {
        if (!$setting) {
            return $this->core;
        }

        return !empty($this->core[$setting]) ? $this->core[$setting] : false;
    }

    /**
     *
     */
    protected function loadCountries()
    {
        $countries = Yaml::parse(file_get_contents(HCSF_BASEDIR.'config/countries.yml'));
        if (is_file(PATH_BASEDIR.'config/countries.yml')) {
            $countries = array_merge($countries, Yaml::parse(file_get_contents(PATH_BASEDIR.'config/countries.yml')));
        }

        $this->countries = $countries;
    }

    /**
     * @param string|false $setting
     * @return mixed
     */
    public function getCountries($setting = false)
    {
        if (!$setting) {
            return $this->countries;
        }

        return !empty($this->countries[$setting]) ? $this->countries[$setting] : false;
    }

    /**
     *
     */
    protected function loadSecrets()
    {
        $secrets = Yaml::parse(file_get_contents(HCSF_BASEDIR.'config/secrets.yml'));
        if (is_file(PATH_BASEDIR.'config/secrets.yml')) {
            $secrets = array_merge($secrets, Yaml::parse(file_get_contents(PATH_BASEDIR.'config/secrets.yml')));
        }

        $this->secrets = $secrets;
    }

    /**
     * @param string|false $setting
     * @return mixed
     */
    public function getSecret($setting = false)
    {
        if (!$setting) {
            return $this->secrets;
        }

        return !empty($this->secrets[$setting]) ? $this->secrets[$setting] : false;
    }

    /**
     *
     */
    protected function loadCustomer()
    {
        $customer = Yaml::parse(file_get_contents(HCSF_BASEDIR.'config/customer.yml'));
        if (is_file(PATH_BASEDIR.'/config/customer.yml')) {
            $customer = array_merge($customer, Yaml::parse(file_get_contents(PATH_BASEDIR.'config/customer.yml')));
        }

        $this->customer = $customer;
    }

    /**
     * @param string|bool $setting
     * @return mixed
     */
    public function getCustomer($setting = false)
    {
        if (!$setting) {
            return $this->customer;
        }

        return !empty($this->customer[$setting]) ? $this->customer[$setting] : false;
    }

    /**
     *
     */
    protected function loadShop()
    {
        $shop = Yaml::parse(file_get_contents(HCSF_BASEDIR.'config/shop.yml'));
        if (is_file(PATH_BASEDIR.'config/shop.yml')) {
            $shop = array_merge($shop, Yaml::parse(file_get_contents(PATH_BASEDIR.'config/shop.yml')));
        }
        if (isset($shop['vat_disable']) && $shop['vat_disable']) {
            $shop['vat'] = ['full' => 0, 'reduced' => 0];
        }

        $this->shop = $shop;
    }

    /**
     * @param string|false $setting
     * @return mixed
     */
    public function getShop($setting = false)
    {
        if (!$setting) {
            return $this->shop;
        }

        return !empty($this->shop[$setting]) ? $this->shop[$setting] : false;
    }

    /**
     * @param ServiceManager $serviceManager
     */
    public function loadNavigation(ServiceManager $serviceManager)
    {
        if (is_file(PATH_BASEDIR.'config/navigation.yml')) {
            $navstruct = Yaml::parse(file_get_contents(PATH_BASEDIR.'config/navigation.yml'));
        } else {
            $navstruct = Yaml::parse(file_get_contents(HCSF_BASEDIR.'config/navigation.yml'));
        }

        if (!empty($navstruct) && $this->core['navigation_fetch_text_from_textcats']) {
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

        if ($this->core['enable_module_shop']) {
            $navstruct['admin'][$hardcodedtextcats->get('admin_nav_orders')] = '/_admin/shopadmin.html';
            $navstruct['admin'][$hardcodedtextcats->get('admin_nav_items')] = '/_admin/itemadmin.html';
            $navstruct['admin'][$hardcodedtextcats->get('admin_nav_itemgroups')] = '/_admin/itemgroupadmin.html';
        }

        if ($this->core['enable_module_customer']) {
            $navstruct['admin'][$hardcodedtextcats->get('admin_nav_customers')] = '/_admin/customeradmin.html';
        }

        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_pages')] = '/_admin/pageadmin.html';
        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_textcats')] = '/_admin/textcatadmin.html';
        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_cleartemplatecache')] = '/_admin/cleartemplatecache.html';
        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_clearimagecache')] = '/_admin/clearimagecache.html';
        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_phpinfo')] = '/_admin/phpinfo.html';
        $navstruct['admin'][$hardcodedtextcats->get('admin_nav_dbstatus')] = '/_admin/dbstatus.html';

        $this->navigation = $navstruct;
    }

    /**
     * @param string|false $setting
     * @return mixed
     */
    public function getNavigation($setting = false)
    {
        if (!$setting) {
            return $this->navigation;
        }

        return !empty($this->navigation[$setting]) ? $this->navigation[$setting] : false;
    }

    /**
     * @return int|mixed|string
     */
    public function getLanguage()
    {
        $langavailable = $this->core['lang_available'];
        if (
            $this->core['lang_detection_method'] === 'domain'
            && isset($this->core['lang_by_domain'])
            && is_array($this->core['lang_by_domain'])
        ) { // domain based language detection
            $serverservername = filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_URL);
            foreach ($this->core['lang_by_domain'] as $sKey => $sValue) {
                if ($serverservername == $sValue || $serverservername == 'www.'.$sValue) {
                    $sLang = $sKey;
                    break;
                }
            }
        } elseif ($this->core['lang_detection_method'] === 'legacy') { // legacy language detection
            $sLang = key($langavailable);
            $getlanguage = filter_input(INPUT_GET, 'language', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            $cookielanguage = filter_input(INPUT_COOKIE, 'language', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            $serverhttpaccepptlanguage = filter_input(INPUT_SERVER, '', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            if ($getlanguage !== null && array_key_exists($getlanguage, $langavailable)) {
                $sLang = strtolower($getlanguage);
                setcookie('language', strtolower($getlanguage), 0, '/');
            } elseif ($cookielanguage !== null && array_key_exists($cookielanguage, $langavailable)) {
                $sLang = strtolower($cookielanguage);
            } elseif ($serverhttpaccepptlanguage !== null && array_key_exists(substr($serverhttpaccepptlanguage, 0, 2), $langavailable)) {
                $sLang = substr($serverhttpaccepptlanguage, 0, 2);
            }
        }
        if (!isset($sLang)) {
            $sLang = key($langavailable);
        }

        return $sLang;
    }

    protected function loadCustomization()
    {
        $customization = [];

        if (is_file(PATH_BASEDIR.'config/customization.yml')) {
            $customization = array_merge($customization, Yaml::parse(file_get_contents(PATH_BASEDIR.'config/customization.yml')));
        }

        $this->customization = $customization;
    }

    /**
     * @param bool $setting
     * @return array|bool|mixed
     */
    public function getCustomization($setting = false) {
        if (!$setting) {
            return $this->customization;
        }

        return !empty($this->customization[$setting]) ? $this->customization[$setting] : false;
    }
}
