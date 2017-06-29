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

namespace HaaseIT\HCSF\Controller\Shop;


use Zend\ServiceManager\ServiceManager;

/**
 * Class Base
 * @package HaaseIT\HCSF\Controller\Shop
 */
class Base extends \HaaseIT\HCSF\Controller\Base
{
    /**
     * @var \HaaseIT\HCSF\Customer\Helper
     */
    protected $helperCustomer;

    /**
     * @var \HaaseIT\HCSF\Shop\Helper
     */
    protected $helperShop;

    /**
     * Base constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->requireModuleShop = true;
        $this->helperCustomer = $serviceManager->get('helpercustomer');
        $this->helperShop = $serviceManager->get('helpershop');
    }
}
