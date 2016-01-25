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


class Base extends \HaaseIT\HCSF\Controller\Base
{
    protected $bAdminhome = false;
    public function __construct($C, $DB, $sLang)
    {
        parent::__construct($C, $DB, $sLang);
        $this->requireAdminAuth($this->bAdminhome);

        $this->P = new \HaaseIT\HCSF\CorePage($C, $sLang);
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';
    }

    private function requireAdminAuth($bAdminhome = false) {
        if ((empty($this->C['admin_users']) || !count($this->C['admin_users'])) && $bAdminhome) {
            return true;
        } elseif (count($this->C['admin_users'])) {
            $valid_users = array_keys($this->C['admin_users']);

            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) { // fix for php cgi mode
                list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
            }

            if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
                $user = $_SERVER['PHP_AUTH_USER'];
                $pass = crypt($_SERVER['PHP_AUTH_PW'], $this->C["blowfish_salt"]);

                $validated = (in_array($user, $valid_users)) && ($pass == $this->C['admin_users'][$user]);
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