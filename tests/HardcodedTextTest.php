<?php
use PHPUnit\Framework\TestCase;

class HardcodedTextTest extends TestCase
{
    public function testGetHardcodedText()
    {
        $HT = ['test' => 'textstring'];
        $hardcodedtextcats = new \HaaseIT\HCSF\HardcodedText($HT);
        $this->assertEquals('textstring', $hardcodedtextcats->get('test'));
    }

    public function testGetHardcodedTextFail()
    {
        $HT = ['test' => 'textstring'];
        $hardcodedtextcats = new \HaaseIT\HCSF\HardcodedText($HT);
        $this->assertEquals('Missing Hardcoded Text: undefined', $hardcodedtextcats->get('undefined'));
    }
}
