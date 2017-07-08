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


use Zend\ServiceManager\ServiceManager;

/**
 * Class CorePage
 * @package HaaseIT\HCSF
 */
class CorePage extends Page
{
    /**
     * CorePage constructor.
     * @param ServiceManager $serviceManager
     * @param array $headers
     * @param string $customroottemplate
     */
    public function __construct(ServiceManager $serviceManager, $headers = [], $customroottemplate = '')
    {
        $this->status = 200;
        $this->headers = $headers;
        $this->serviceManager = $serviceManager;
        $this->customroottemplate = $customroottemplate;
        $this->getPayload();
    }

    /**
     *
     */
    protected function getPayload()
    {
        $this->oPayload = new PagePayload($this->serviceManager);
    }
}
