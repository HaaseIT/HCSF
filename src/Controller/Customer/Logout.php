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

namespace HaaseIT\HCSF\Controller\Customer;

use HaaseIT\HCSF\HelperConfig;

/**
 * Class Logout
 * @package HaaseIT\HCSF\Controller\Customer
 */
class Logout extends Base
{
    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        unset($_SESSION['user']);
        if (isset($_SESSION['cart']) && HelperConfig::$core['enable_module_shop']) {
            \HaaseIT\HCSF\Shop\Helper::refreshCartItems($this->serviceManager);
        }
        $this->P->oPayload->cl_html = $this->serviceManager->get('textcats')->T('logout_message');
    }
}