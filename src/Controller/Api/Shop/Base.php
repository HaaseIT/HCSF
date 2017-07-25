<?php

namespace HaaseIT\HCSF\Controller\Api\Shop;


use Zend\ServiceManager\ServiceManager;

class Base extends \HaaseIT\HCSF\Controller\Api\Base
{
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->requireModuleShop = true;
    }
}
