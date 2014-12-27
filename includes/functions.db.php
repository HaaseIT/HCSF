<?php

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
