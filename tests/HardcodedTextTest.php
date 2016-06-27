<?php
use PHPUnit\Framework\TestCase;

class HardcodedTextTest extends TestCase
{
    public function testGetHardcodedText()
    {
        $HT = ['test' => 'textstring'];
        \HaaseIT\HCSF\HardcodedText::init($HT);
        $this->assertEquals('textstring', \HaaseIT\HCSF\HardcodedText::get('test'));
    }

    public function testGetHardcodedTextFail()
    {
        $HT = ['test' => 'textstring'];
        \HaaseIT\HCSF\HardcodedText::init($HT);
        $this->assertEquals('Missing Hardcoded Text: undefined', \HaaseIT\HCSF\HardcodedText::get('undefined'));
    }
}
