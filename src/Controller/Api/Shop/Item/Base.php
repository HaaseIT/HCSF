<?php
/**
 * Created by PhpStorm.
 * User: mhaase
 * Date: 25.07.2017
 * Time: 18:21
 */

namespace HaaseIT\HCSF\Controller\Api\Shop\Item;


use Zend\ServiceManager\ServiceManager;

class Base extends \HaaseIT\HCSF\Controller\Api\Shop\Base
{
    /**
     * @var array
     */
    protected $matches;

    /**
     * Base constructor.
     * @param ServiceManager $serviceManager
     * @param $aPath
     * @param $matches
     */
    public function __construct(ServiceManager $serviceManager, $aPath, array $matches)
    {
        $this->matches = $matches;
        parent::__construct($serviceManager);
    }
}
