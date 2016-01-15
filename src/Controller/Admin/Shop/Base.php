<?php
/**
 * Created by PhpStorm.
 * User: mhaase
 * Date: 14.01.16
 * Time: 23:28
 */

namespace HaaseIT\HCSF\Controller\Admin\Shop;


class Base extends \HaaseIT\HCSF\Controller\Admin\Base
{
    public function __construct($C, $DB, $sLang)
    {
        parent::__construct($C, $DB, $sLang);

        if (empty($C["enable_module_shop"]) || !$C["enable_module_shop"]) {
            throw new \Exception(404);
        }
    }
}