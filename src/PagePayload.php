<?php
/**
 * Created by PhpStorm.
 * User: mhaase
 * Date: 19.06.2015
 * Time: 10:22
 */

namespace HaaseIT\HCSF;


class PagePayload
{
    protected $C;
    public $cl_lang, $cl_html, $cl_keywords, $cl_description, $cl_title;

    public function __construct($C) {
        $this->C = $C;
    }

    function getTitle()
    {
        if (isset($this->cl_title) && trim($this->cl_title) != '') $sH = $this->cl_title;
        else $sH = $this->C["default_pagetitle"];

        return $sH;
    }

}