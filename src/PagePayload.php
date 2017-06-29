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

class PagePayload
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var \HaaseIT\HCSF\HelperConfig
     */
    protected $config;

    public $cl_lang;
    public $cl_html;
    public $cl_keywords;
    public $cl_description;
    public $cl_title;

    /**
     * PagePayload constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager) {
        $this->serviceManager = $serviceManager;
        $this->config = $serviceManager->get('config');
    }

    public function getTitle()
    {
        if (!empty($this->cl_title)) {
            return $this->cl_title;
        }

        return $this->config->getCore('default_pagetitle');
    }
}
