<?php
/**
 * Created by PhpStorm.
 * User: mhaase
 * Date: 14.01.16
 * Time: 23:31
 */

namespace HaaseIT\HCSF\Controller;


class Base
{
    protected $P, $C, $sLang, $DB;

    public function __construct($C, $DB, $sLang)
    {
        $this->C = $C;
        $this->DB = $DB;
        $this->sLang = $sLang;
    }

    public function getPage()
    {
        return $this->P;
    }
}