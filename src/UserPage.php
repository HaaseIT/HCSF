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
    protected $DB;
    public $cb_id, $cb_key, $cb_group;

    public function __construct($C, $sLang, $DB, $sPagekey) {
        $this->C = $C;
        $this->sLang = $sLang;
        $this->DB = $DB;

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
                $this->cb_pageconfig = json_decode($this->cb_pageconfig);
                $this->oPayload = $this->getPayload();
            }
        }
    }

    protected function getPayload() {
        $sQ = "SELECT cl_id, cl_cb, cl_lang, cl_html, cl_keywords, cl_description, cl_title ";
        $sQ .= "FROM content_lang WHERE cl_cb = :ppkey AND cl_lang = :lang";
        $hResult = $this->DB->prepare($sQ);

        // Try to get the payload in the current language
        $hResult->bindValue(':ppkey', $this->cb_id, \PDO::PARAM_STR);
        $hResult->bindValue(':lang', $this->sLang, \PDO::PARAM_STR);
        $hResult->execute();

        if ($hResult->rowCount() == 1) {
            return $hResult->fetchObject('\HaaseIT\HCSF\PagePayload', [$this->C]);
        } else {
            // if the current language data is not available, lets see if we can get the default languages data
            $hResult = $this->DB->prepare($sQ);
            $hResult->bindValue(':ppkey', $this->cb_id, \PDO::PARAM_STR);
            $hResult->bindValue(':lang', key($this->C["lang_available"]), \PDO::PARAM_STR);
            $hResult->execute();
            if ($hResult->rowCount() == 1) {
                return $hResult->fetchObject('\HaaseIT\HCSF\PagePayload');
            }
        }

    }

}