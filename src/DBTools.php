<?php

/*
    HCSF - A multilingual CMS and Shopsystem
    Copyright (C) 2014  Marcus Haase - mail@marcus.haase.name

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace HaaseIT;

class DBTools
{

    public static function buildInsertQuery($aData, $sTable, $bKeepAT = false)
    {
        $sFields = '';
        $sValues = '';
        foreach ($aData as $sKey => $sValue) {
            $sFields .= $sKey . ", ";
            $sValues .= "'" . self::cED($sValue, $bKeepAT) . "', ";
        }
        $sQ = "INSERT INTO " . $sTable . " (" . self::cutStringend($sFields, 2) . ") ";
        $sQ .= "VALUES (" . self::cutStringend($sValues, 2) . ")";
        return $sQ;
    }

    public static function buildPSInsertQuery($aData, $sTable)
    {
        $sFields = '';
        $sValues = '';
        foreach ($aData as $sKey => $sValue) {
            $sFields .= $sKey . ', ';
            $sValues .= ":" . $sKey . ", ";
        }
        $sQ = "INSERT INTO " . $sTable . " (" . self::cutStringend($sFields, 2) . ") VALUES (" . self::cutStringend($sValues, 2) . ")";
        return $sQ;
    }

    public static function buildUpdateQuery($aData, $sTable, $sPKey = '', $sPValue = '', $bKeepAT = false)
    {
        $sQ = "UPDATE " . $sTable . " SET ";
        foreach ($aData as $sKey => $sValue) {
            $sQ .= $sKey . " = '" . self::cED($sValue, $bKeepAT) . "', ";
        }
        $sQ = self::cutStringend($sQ, 2);
        if ($sPKey == '') {
            $sQ .= ' ';
        } else {
            $sQ .= " WHERE " . $sPKey . " = '" . self::cED($sPValue, $bKeepAT) . "'";
        }
        return $sQ;
    }

    public static function buildPSUpdateQuery($aData, $sTable, $sPKey = '')
    {
        $sQ = "UPDATE " . $sTable . " SET ";
        foreach ($aData as $sKey => $sValue) {
            if ($sPKey != '' && $sKey == $sPKey) {
                continue;
            }
            $sQ .= $sKey . " = :" . $sKey . ", ";
        }
        $sQ = self::cutStringend($sQ, 2);
        if ($sPKey == '') {
            $sQ .= ' ';
        } else {
            $sQ .= " WHERE " . $sPKey . " = :" . $sPKey;
        }
        return $sQ;
    }
}
