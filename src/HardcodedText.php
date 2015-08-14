<?php
/**
 * Created by PhpStorm.
 * User: mhaase
 * Date: 14.08.2015
 * Time: 12:09
 */

namespace HaaseIT\HCSF;


class HardcodedText
{
    protected static $HT;

    public static function init($HT) {
        self::$HT = $HT;
    }

    public static function get($sKey) {
        if (isset(self::$HT[$sKey])) {
            return self::$HT[$sKey];
        } else {
            return 'Missing Hardcoded Text: '.$sKey;
        }
    }
}
