<?php
use PHPUnit\Framework\TestCase;
use HaaseIT\HCSF\HelperConfig;
use HaaseIT\HCSF\Shop\Items;
use Zend\ServiceManager\ServiceManager;

/**
 * Class ItemsTest
 */
class ItemsTest extends TestCase
{
    /**
     *
     */
    public function testCalcPrice()
    {
        bcscale(6);

        $serviceManager = new ServiceManager();
        $serviceManager->setFactory('db', function () {
            return null;
        });
        $serviceManager->setFactory('config', function () {
            return new HelperConfig();
        });
        $config = $serviceManager->get('config');

        $serviceManager->setFactory('helper', function (ServiceManager $serviceManager) {
            return new \HaaseIT\HCSF\Helper($serviceManager);
        });

        $serviceManager->setFactory('helpercustomer', function (ServiceManager $serviceManager) {
            return new \HaaseIT\HCSF\Customer\Helper($serviceManager);
        });

        $serviceManager->setFactory('helpershop', function (ServiceManager $serviceManager) {
            return new \HaaseIT\HCSF\Shop\Helper($serviceManager);
        });

        $configreflection = new ReflectionClass($config);
        $configreflectionShop = $configreflection->getProperty('shop');
        $configreflectionShop->setAccessible(true);
        $configreflectionLang = $configreflection->getProperty('lang');
        $configreflectionLang->setAccessible(true);


        $configreflectionShop->setValue($config, [
            'vat' => [
                'full' => 0,
                'reduced' => 0,
            ],
        ]);
        $configreflectionLang->setValue($config, 'de');

        $items = new Items($serviceManager);

        // regular price, no rebate, vat disabled
        $aData = [
            'itm_price' => '11.11',
            'itm_rg' => '01',
            'itm_vatid' => 'full',
        ];

        $aPrice = $items->calcPrice($aData);

        $this->assertEquals('11.11', $aPrice['netto_use']);
        $this->assertEquals('11.11', $aPrice['brutto_use']);
        $this->assertEquals('11.11', $aPrice['netto_list']);
        $this->assertArrayNotHasKey('netto_sale', $aPrice);
        $this->assertArrayNotHasKey('netto_rebated', $aPrice);

        // set vat to normal values
        $configreflectionShop->setValue($config, [
            'vat' => [
                'full' => 19,
                'reduced' => 7,
            ],
            'rebate_groups' => [
                '01' => [
                    'grosskunde' => 7,
                ],
            ],
        ]);
        $items = new Items($serviceManager);

        // regular price, no rebate, reduced vat
        $aData = [
            'itm_price' => '11.11',
            'itm_rg' => '01',
            'itm_vatid' => 'reduced',
        ];

        $aPrice = $items->calcPrice($aData);

        $this->assertEquals('11.11', $aPrice['netto_use']);
        $this->assertEquals('11.8877', $aPrice['brutto_use']);
        $this->assertEquals('11.11', $aPrice['netto_list']);
        $this->assertArrayNotHasKey('netto_sale', $aPrice);
        $this->assertArrayNotHasKey('netto_rebated', $aPrice);

        // regular price, no rebate, full vat
        $aData = [
            'itm_price' => '11.11',
            'itm_rg' => '01',
            'itm_vatid' => 'full',
        ];

        $aPrice = $items->calcPrice($aData);

        $this->assertEquals('11.11', $aPrice['netto_use']);
        $this->assertEquals('13.2209', $aPrice['brutto_use']);
        $this->assertEquals('11.11', $aPrice['netto_list']);
        $this->assertArrayNotHasKey('netto_sale', $aPrice);
        $this->assertArrayNotHasKey('netto_rebated', $aPrice);

        // item sale
        $aData = [
            'itm_price' => '11.11',
            'itm_rg' => '01',
            'itm_vatid' => 'full',
            'itm_data' => [
                'sale' => [
                    'start' => date('Ymd') - 1,
                    'end' => date('Ymd') + 1,
                    'price' => '9.10',
                ],
            ],
        ];

        $aPrice = $items->calcPrice($aData);

        $this->assertEquals('9.1', $aPrice['netto_use']);
        $this->assertEquals('9.1', $aPrice['netto_sale']);
        $this->assertEquals('10.829', $aPrice['brutto_use']);
        $this->assertEquals('11.11', $aPrice['netto_list']);
        $this->assertArrayNotHasKey('netto_rebated', $aPrice);

        // item sale too  late
        $aData = [
            'itm_price' => '11.11',
            'itm_rg' => '01',
            'itm_vatid' => 'full',
            'itm_data' => [
                'sale' => [
                    'start' => date('Ymd') - 5,
                    'end' => date('Ymd') - 1,
                    'price' => '9.10',
                ],
            ],
        ];

        $aPrice = $items->calcPrice($aData);

        $this->assertEquals('11.11', $aPrice['netto_use']);
        $this->assertEquals('13.2209', $aPrice['brutto_use']);
        $this->assertEquals('11.11', $aPrice['netto_list']);
        $this->assertArrayNotHasKey('netto_sale', $aPrice);
        $this->assertArrayNotHasKey('netto_rebated', $aPrice);

        // test item sale too early
        $aData = [
            'itm_price' => '11.11',
            'itm_rg' => '01',
            'itm_vatid' => 'full',
            'itm_data' => [
                'sale' => [
                    'start' => date('Ymd') + 1,
                    'end' => date('Ymd') + 5,
                    'price' => '9.10',
                ],
            ],
        ];

        $aPrice = $items->calcPrice($aData);

        $this->assertEquals('11.11', $aPrice['netto_use']);
        $this->assertEquals('13.2209', $aPrice['brutto_use']);
        $this->assertEquals('11.11', $aPrice['netto_list']);
        $this->assertArrayNotHasKey('netto_sale', $aPrice);
        $this->assertArrayNotHasKey('netto_rebated', $aPrice);

        // init session for rebate testing
        $_SESSION['user'] = [
            'cust_group' => 'grosskunde',
        ];

        // test rebate group
        $aData = [
            'itm_price' => '11.11',
            'itm_rg' => '01',
            'itm_vatid' => 'full',
        ];

        $aPrice = $items->calcPrice($aData);

        $this->assertEquals('10.3323', $aPrice['netto_use']);
        $this->assertEquals('12.295437', $aPrice['brutto_use']);
        $this->assertEquals('11.11', $aPrice['netto_list']);
        $this->assertEquals('10.3323', $aPrice['netto_rebated']);
        $this->assertArrayNotHasKey('netto_sale', $aPrice);

        // test sale/rebate group best price finding
        // rebate best price
        $aData = [
            'itm_price' => '11.11',
            'itm_rg' => '01',
            'itm_vatid' => 'full',
            'itm_data' => [
                'sale' => [
                    'start' => date('Ymd') - 1,
                    'end' => date('Ymd') + 1,
                    'price' => '10.99',
                ],
            ],
        ];

        $aPrice = $items->calcPrice($aData);
        $this->assertEquals('10.3323', $aPrice['netto_use']);
        $this->assertEquals('12.295437', $aPrice['brutto_use']);
        $this->assertEquals('11.11', $aPrice['netto_list']);
        $this->assertEquals('10.3323', $aPrice['netto_rebated']);
        $this->assertEquals('10.99', $aPrice['netto_sale']);

        // sale best price
        $aData = [
            'itm_price' => '11.11',
            'itm_rg' => '01',
            'itm_vatid' => 'full',
            'itm_data' => [
                'sale' => [
                    'start' => date('Ymd') - 1,
                    'end' => date('Ymd') + 1,
                    'price' => '9.10',
                ],
            ],
        ];

        $aPrice = $items->calcPrice($aData);
        $this->assertEquals('9.1', $aPrice['netto_use']);
        $this->assertEquals('10.829', $aPrice['brutto_use']);
        $this->assertEquals('11.11', $aPrice['netto_list']);
        $this->assertEquals('10.3323', $aPrice['netto_rebated']);
        $this->assertEquals('9.1', $aPrice['netto_sale']);

        // non valid price
        $aData = [
            'itm_price' => 'asdf',
            'itm_rg' => '01',
            'itm_vatid' => 'full',
        ];

        $aPrice = $items->calcPrice($aData);
        $this->assertFalse($aPrice);

    }
}
