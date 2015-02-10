<?php

/*
    Contanto - A multilingual CMS and Shopsystem
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

function getContent($C, $DB, $sPagekey, $sLang) {
    // first get base data
    $sQ = "SELECT ".DB_CONTENTFIELDS_BASE." FROM ".DB_CONTENTTABLE_BASE." WHERE ".DB_CONTENTFIELD_BASE_KEY." = :key ";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':key', $sPagekey, PDO::PARAM_STR);
    $hResult->execute();
    if ($hResult->rowCount() == 0) return false;
    $aResult["base"] = $hResult->fetch();

    // next lets see if we can get the current language data
    $sQ = "SELECT ".DB_CONTENTFIELDS_LANG." FROM ".DB_CONTENTTABLE_LANG." WHERE ";
    $sQ .= DB_CONTENTTABLE_LANG_PARENTPKEY." = :ppkey AND ";
    $sQ .= DB_CONTENTFIELD_LANG." = :lang";
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':ppkey', $aResult["base"][DB_CONTENTTABLE_BASE_PKEY], PDO::PARAM_STR);
    $hResult->bindValue(':lang', $sLang, PDO::PARAM_STR);
    $hResult->execute();
    // debug($sQ);
    if ($hResult->rowCount() == 1) {
        $aResult["lang"] = $hResult->fetch();
        return $aResult;
    }
    //debug($aResult["base"][DB_CONTENTFIELD_BASE_KEY]);
    // lastly, if the current language data is not available, lets see if we can get the default languages data
    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':ppkey', $aResult["base"][DB_CONTENTTABLE_BASE_PKEY], PDO::PARAM_STR);
    $hResult->bindValue(':lang', key($C["lang_available"]), PDO::PARAM_STR);
    $hResult->execute();
    if ($hResult->rowCount() == 1) $aResult["lang"] = $hResult->fetch();

    return $aResult;
}
