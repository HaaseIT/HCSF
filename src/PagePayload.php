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


class PagePayload
{
    protected $container;
    public $cl_lang, $cl_html, $cl_keywords, $cl_description, $cl_title;

    public function __construct($container) {
        $this->container = $container;
    }

    function getTitle()
    {
        if (isset($this->cl_title) && trim($this->cl_title) != '') {
            return $this->cl_title;
        } else {
            return $this->container['conf']['default_pagetitle'];
        }
    }
}