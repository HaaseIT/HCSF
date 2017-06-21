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

namespace HaaseIT\HCSF\Controller\Admin;


use HaaseIT\HCSF\HelperConfig;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Index
 * @package HaaseIT\HCSF\Controller\Admin
 */
class Index extends Base
{
    /**
     * Index constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->requireAdminAuthAdminHome = true;
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        $this->P->cb_customcontenttemplate = 'adminhome';
        $this->P->cb_customdata = [
            'filter_enabled' => extension_loaded('filter'),
            'path_templatecache' => realpath(PATH_TEMPLATECACHE),
            'path_templatecache_exists' => file_exists(PATH_TEMPLATECACHE),
            'path_templatecache_writable' => is_writable(PATH_TEMPLATECACHE),
            'path_purifiercache' => realpath(PATH_PURIFIERCACHE),
            'path_purifiercache_exists' => file_exists(PATH_PURIFIERCACHE),
            'path_purifiercache_writable' => is_writable(PATH_PURIFIERCACHE),
            'enable_module_shop' => HelperConfig::$core['enable_module_shop'],
            'path_logs' => realpath(PATH_LOGS),
            'path_logs_exists' => file_exists(PATH_LOGS),
            'path_logs_writable' => is_writable(PATH_LOGS),
        ];
        if (function_exists('apache_get_modules')) {
            $aApacheModules = apache_get_modules();
            $this->P->cb_customdata['check_mod_rewrite'] = true;
            $this->P->cb_customdata['mod_rewrite_available'] = (array_search('mod_rewrite', $aApacheModules) !== false);
            unset($aApacheModules);
        }
        if (isset($_POST['string']) && trim($_POST['string']) != '') {
            $this->P->cb_customdata['encrypted_string'] = password_hash($_POST['string'], PASSWORD_DEFAULT);
        }
    }
}
