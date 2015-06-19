<?php
/**
 * Created by PhpStorm.
 * User: mhaase
 * Date: 19.06.2015
 * Time: 10:28
 */

namespace HaaseIT\HCSF;


class CorePage extends Page
{
    public function __construct($C, $sLang)
    {
        $this->C = $C;
        $this->sLang = $sLang;
        $this->getPayload();
    }

    protected function getPayload() {
        $this->oPayload = new PagePayload($this->C);
    }
}