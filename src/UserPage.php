<?php
/**
 * Created by PhpStorm.
 * User: mhaase
 * Date: 19.06.2015
 * Time: 10:30
 */

namespace HaaseIT\HCSF;


class UserPage extends Page
{
    protected $DB, $bReturnRaw;
    public $cb_id, $cb_key, $cb_group;

    public function __construct($C, $sLang, $DB, $sPagekey, $bReturnRaw = false) {
        if (!$bReturnRaw) $this->C = $C;
        $this->sLang = $sLang;
        $this->DB = $DB;
        $this->bReturnRaw = $bReturnRaw;

        // first get base data
        $sQ = "SELECT cb_id, cb_key, cb_group, cb_pagetype, cb_pageconfig, cb_subnav ";
        $sQ .= "FROM content_base WHERE cb_key = :key ";
        $hResult = $this->DB->prepare($sQ);

        $hResult->bindValue(':key', $sPagekey, \PDO::PARAM_STR);
        $hResult->setFetchMode(\PDO::FETCH_INTO, $this);
        $hResult->execute();

        if ($hResult->rowCount() == 1) {
            $hResult->fetch();

            if ($this->cb_pagetype != 'shorturl') {
                if (!$bReturnRaw) $this->cb_pageconfig = json_decode($this->cb_pageconfig);
                $this->oPayload = $this->getPayload();
            }
        }
    }

    protected function getPayload() {
        return new UserPagePayload($this->C, $this->sLang, $this->DB, $this->cb_id, $this->bReturnRaw);
    }

    public function write() {
        $aData = array(
            'cb_pagetype' => $this->cb_pagetype,
            'cb_group' => $this->cb_group,
            'cb_pageconfig' => $this->cb_pageconfig,
            'cb_subnav' => $this->cb_subnav,
            'cb_key' => $this->cb_key,
        );
        $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'content_base', 'cb_key');

        $hResult = $this->DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        return $hResult->execute();
    }

    public function insert($sPagekeytoadd) {
        $aData = array(
            'cb_key' => $sPagekeytoadd,
        );
        $sQ = \HaaseIT\DBTools::buildInsertQuery($aData, 'content_base');
        return $hResult = $this->DB->exec($sQ);
    }

}