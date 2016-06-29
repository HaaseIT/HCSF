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


class Base
{
    protected $P, $C, $sLang, $DB, $twig,
        $requireAdminAuth = false,
        $requireAdminAuthAdminHome = false,
        $requireModuleCustomer = false,
        $requireModuleShop = false;

    public function __construct($C, $DB, $sLang)
    {
        $this->C = $C;
        $this->DB = $DB;
        $this->sLang = $sLang;
    }

    public function getPage()
    {
        if ($this->requireAdminAuth) {
            $this->requireAdminAuth();
        }
        if ($this->requireModuleCustomer && (empty($this->C["enable_module_customer"]) || !$this->C["enable_module_customer"])) {
            throw new \Exception(404);
        }
        if ($this->requireModuleShop && (empty($this->C["enable_module_shop"]) || !$this->C["enable_module_shop"])) {
            throw new \Exception(404);
        }
        $this->preparePage();
        return $this->P;
    }

    public function preparePage()
    {

    }

    private function requireAdminAuth() {
        if ((empty($this->C['admin_users']) || !count($this->C['admin_users'])) && $this->requireAdminAuthAdminHome) {
            return true;
        } elseif (count($this->C['admin_users'])) {

            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) { // fix for php cgi mode
                list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
            }

            if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
                $user = $_SERVER['PHP_AUTH_USER'];
                $pass = $_SERVER['PHP_AUTH_PW'];

                $validated = !empty($this->C['admin_users'][$user]) && password_verify($pass, $this->C['admin_users'][$user]);
            } else {
                $validated = false;
            }

            if (!$validated) {
                header('WWW-Authenticate: Basic realm="' . $this->C['admin_authrealm'] . '"');
                header('HTTP/1.0 401 Unauthorized');
                die("Not authorized");
            }
        } else {
            header('WWW-Authenticate: Basic realm="' . $this->C['admin_authrealm'] . '"');
            header('HTTP/1.0 401 Unauthorized');
            die('Not authorized');
        }
    }
}