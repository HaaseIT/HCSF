<?php
use PHPUnit\Framework\TestCase;

class ItemsTest extends TestCase
{
    public function testGetVAT()
    {
        // vat disabled
        $C = [
            'vat' => [
                'full' => 0,
                'reduced' => 0,
            ],
        ];
        $DB = null;
        $sLang = 'de';

        $items = new \HaaseIT\HCSF\Shop\Items($C, $DB, $sLang);

        $this->assertEquals(0, $items->getVAT(11.11, 'full'));

        // regular vat
        $C = [
            'vat' => [
                'full' => 19,
                'reduced' => 7,
            ],
            'rebate_groups' => [
                '01' => [
                    'grosskunde' => 7,
                ],
            ],
        ];
        $items = new \HaaseIT\HCSF\Shop\Items($C, $DB, $sLang);

        $this->assertEquals(0.7777, $items->getVAT(11.11, 'reduced'));
        $this->assertEquals(2.1109, $items->getVAT(11.11, 'full'));

    }

    public function testCalcPrice()
    {
        $C = [
            'vat' => [
                'full' => 0,
                'reduced' => 0,
            ],
        ];
        $DB = null;
        $sLang = 'de';

        $items = new \HaaseIT\HCSF\Shop\Items($C, $DB, $sLang);

        // regular price, no rebate, vat disabled
        $aData = [
            'itm_price' => 11.11,
            'itm_rg' => '01',
            'itm_vatid' => 'full',
        ];

        $aPrice = $items->calcPrice($aData);

        $this->assertEquals(11.11, $aPrice['netto_use']);
        $this->assertEquals(11.11, $aPrice['brutto_use']);
        $this->assertEquals(11.11, $aPrice['netto_list']);

        // set vat to normal values
        $C = [
            'vat' => [
                'full' => 19,
                'reduced' => 7,
            ],
            'rebate_groups' => [
                '01' => [
                    'grosskunde' => 7,
                ],
            ],
        ];
        $items = new \HaaseIT\HCSF\Shop\Items($C, $DB, $sLang);

        // regular price, no rebate, reduced vat
        $aData = [
            'itm_price' => 11.11,
            'itm_rg' => '01',
            'itm_vatid' => 'reduced',
        ];

        $aPrice = $items->calcPrice($aData);

        $this->assertEquals(11.11, $aPrice['netto_use']);
        $this->assertEquals(11.8877, $aPrice['brutto_use']);
        $this->assertEquals(11.11, $aPrice['netto_list']);

        // regular price, no rebate, full vat
        $aData = [
            'itm_price' => 11.11,
            'itm_rg' => '01',
            'itm_vatid' => 'full',
            'itm_data' => [
                'sale' => [
//                    'start' => date("Ymd") - 1,
//                    'end' => date("Ymd") + 1,
//                    'price' => 90.10,
                ],
            ],
        ];

        $aPrice = $items->calcPrice($aData);

        $this->assertEquals(11.11, $aPrice['netto_use']);
        $this->assertEquals(13.2209, $aPrice['brutto_use']);
        $this->assertEquals(11.11, $aPrice['netto_list']);

        // test item sale

        // test item sale too early / too late

        // test rebate group

        // test sale/rebate group best price finding

        // test non valid price
        
    }
}
