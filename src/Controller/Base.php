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

namespace HaaseIT\HCSF\Controller;


use Zend\ServiceManager\ServiceManager;

class Base
{
    /**
     * @var \HaaseIT\HCSF\Page
     */
    protected $P;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var bool
     */
    protected $requireAdminAuth = false;

    /**
     * @var bool
     */
    protected $requireAdminAuthAdminHome = false;

    /**
     * @var bool
     */
    protected $requireModuleCustomer = false;

    /**
     * @var bool
     */
    protected $requireModuleShop = false;

    /**
     * @var \HaaseIT\HCSF\HelperConfig
     */
    protected $config;

    /**
     * @var \HaaseIT\HCSF\Helper
     */
    protected $helper;

    /**
     * Base constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        $this->config = $serviceManager->get('config');
        $this->helper = $serviceManager->get('helper');
    }

    /**
     * @return \HaaseIT\HCSF\Page
     * @throws \Exception
     */
    public function getPage()
    {
        if ($this->requireAdminAuth) {
            $this->requireAdminAuth();
        }
        if ($this->requireModuleCustomer && !$this->config->getCore('enable_module_customer')) {
            throw new \Exception(404);
        }
        if ($this->requireModuleShop && !$this->config->getCore('enable_module_shop')) {
            throw new \Exception(404);
        }
        $this->preparePage();
        return $this->P;
    }

    public function preparePage()
    {

    }

    /**
     * @return bool
     */
    private function requireAdminAuth() {

        $adminusers = $this->config->getSecret('admin_users');
        if ($this->requireAdminAuthAdminHome && (empty($adminusers) || !count($adminusers))) {
            return true;
        } elseif (count($adminusers)) {
            $user = filter_var($_SERVER['PHP_AUTH_USER'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            $pass = filter_var($_SERVER['PHP_AUTH_PW'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

            if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) { // fix for php cgi mode
                //die($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
                list($user, $pass) = explode(':' , base64_decode(substr(filter_var($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], FILTER_SANITIZE_STRING), 6)));
            }

            if (!empty($user) && !empty($pass)) {
                $validated = !empty(
                    $adminusers[$user])
                    && password_verify($pass, $adminusers[$user]
                    );
            } else {
                $validated = false;
            }

            if (!$validated) {
                header('WWW-Authenticate: Basic realm="'.$this->config->getSecret('admin_authrealm').'"');
                header('HTTP/1.0 401 Unauthorized');
                $this->helper->terminateScript('Not authorized');
            }

        } else {
//            die('foo');
            header('WWW-Authenticate: Basic realm="'.$this->config->getSecret('admin_authrealm').'"');
            header('HTTP/1.0 401 Unauthorized');
            $this->helper->terminateScript('Not authorized');
        }
    }
}
