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

namespace HaaseIT\HCSF;


class UserPage extends Page
{
    protected $DB, $bReturnRaw;
    public $cb_id, $cb_key, $cb_group, $purifier;

    public function __construct($C, $sLang, $DB, $sPagekey, $bReturnRaw = false) {
        if (!$bReturnRaw) $this->C = $C;
        $this->iStatus = 200;
        $this->sLang = $sLang;
        $this->DB = $DB;
        $this->bReturnRaw = $bReturnRaw;

        if ($sPagekey == '/_misc/index.html') {
            $this->cb_id = $sPagekey;
            $this->cb_key = $sPagekey;
            $this->cb_pagetype = 'itemoverview';
            $this->oPayload = $this->getPayload();
            $this->cb_pageconfig = (object) [];
        } else {
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
    }

    protected function getPayload() {
        return new UserPagePayload($this->C, $this->sLang, $this->DB, $this->cb_id, $this->bReturnRaw);
    }

    public function write() {
        $aData = array(
            'cb_pagetype' => filter_var($this->cb_pagetype, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'cb_group' => filter_var($this->cb_group, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'cb_pageconfig' => $this->purifier->purify($this->cb_pageconfig),
            'cb_subnav' => filter_var($this->cb_subnav, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
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

    public function remove() {
        // delete children
        $this->oPayload->remove($this->cb_id);

        // then delete base row
        $sQ = "DELETE FROM content_base WHERE cb_id = '".$this->cb_id."'";
        return $this->DB->exec($sQ);
    }

}