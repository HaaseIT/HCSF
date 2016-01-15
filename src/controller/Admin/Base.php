<?php
/**
 * Created by PhpStorm.
 * User: mhaase
 * Date: 14.01.16
 * Time: 23:28
 */

namespace HaaseIT\HCSF\Controller\Admin;


class Base extends \HaaseIT\HCSF\Controller\Base
{
    public function __construct($C, $DB, $sLang)
    {
        parent::__construct($C, $DB, $sLang);
        requireAdminAuth($C, true);

        $this->P = new \HaaseIT\HCSF\CorePage($C, $sLang);
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';
    }
}