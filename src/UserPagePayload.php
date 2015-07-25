<?php

namespace HaaseIT\HCSF;


class UserPagePayload extends PagePayload
{
    public $cl_id, $cl_cb, $cl_lang;

    public function __construct($C, $sLang, $DB, $iParentID, $bReturnRaw = false) {
        if (!$bReturnRaw) $this->C = $C;
        $this->sLang = $sLang;
        $this->DB = $DB;

        $sQ = "SELECT cl_id, cl_cb, cl_lang, cl_html, cl_keywords, cl_description, cl_title ";
        $sQ .= "FROM content_lang WHERE cl_cb = :ppkey AND cl_lang = :lang";
        $hResult = $this->DB->prepare($sQ);

        // Try to get the payload in the current language
        $hResult->bindValue(':ppkey', $iParentID, \PDO::PARAM_STR);
        $hResult->bindValue(':lang', $sLang, \PDO::PARAM_STR);
        $hResult->setFetchMode(\PDO::FETCH_INTO, $this);
        $hResult->execute();

        if ($hResult->rowCount() == 1) {
            $hResult->fetch();
        } elseif (!$bReturnRaw) { // if raw data is required, don't try to fetch default lang data
            // if the current language data is not available, lets see if we can get the default languages data
            $hResult = $this->DB->prepare($sQ);
            $hResult->bindValue(':ppkey', $iParentID, \PDO::PARAM_STR);
            $hResult->bindValue(':lang', key($this->C["lang_available"]), \PDO::PARAM_STR);
            $hResult->setFetchMode(\PDO::FETCH_INTO, $this);
            $hResult->execute();

            if ($hResult->rowCount() == 1) {
                $hResult->fetch();
            }
        }
    }

    public function write() {
        $aData = array(
            'cl_html' => $this->cl_html,
            'cl_title' => $this->cl_title,
            'cl_description' => $this->cl_description,
            'cl_keywords' => $this->cl_keywords,
            'cl_id' => $this->cl_id,
        );
        $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'content_lang', 'cl_id');

        $hResult = $this->DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        return $hResult->execute();
    }

    public function insert($iParentID) {
        $aData = array(
            'cl_cb' => $iParentID,
            'cl_lang' => $this->sLang,
        );
        $sQ = \HaaseIT\DBTools::buildInsertQuery($aData, 'content_lang');
        $this->DB->exec($sQ);
    }

}