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


/**
 * Class Page
 * @package HaaseIT\HCSF
 */
/**
 * Class Page
 * @package HaaseIT\HCSF
 */
class Page
{
    /**
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceManager;

    /**
     * @var string
     */
    protected $customroottemplate;

    /**
     * @var string
     */
    public $cb_pagetype;

    /**
     * @var string
     */
    public $cb_subnav;

    /**
     * @var string
     */
    public $cb_customcontenttemplate;

    /**
     * @var int
     */
    protected $status = 200;

    /**
     * @var \HaaseIT\HCSF\PagePayload
     */
    public $oPayload;

    /**
     * @var array
     */
    public $cb_customdata;

    /**
     * @var array|string
     */
    public $cb_pageconfig;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var string
     */
    protected $contenttype = 'text/html; charset=utf-8';

    /**
     * @var bool
     */
    protected $renderwithtemplate = true;

    /**
     * @return string
     */
    public function getContenttype()
    {
        return $this->contenttype;
    }

    /**
     * @param string $contenttype
     */
    public function setContenttype($contenttype)
    {
        $this->contenttype = $contenttype;
    }

    /**
     * @return bool
     */
    public function isRenderwithtemplate()
    {
        return $this->renderwithtemplate;
    }

    /**
     * @param bool $renderwithtemplate
     */
    public function setRenderwithtemplate($renderwithtemplate)
    {
        $this->renderwithtemplate = $renderwithtemplate;
    }

    /**
     * @return string
     */
    public function getCustomRootTemplate()
    {
        return $this->customroottemplate;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $header
     */
    public function addHeader($header)
    {
        $this->headers[] = $header;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
