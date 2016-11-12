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


class UserPagePayload extends PagePayload
{
    public $cl_id, $cl_cb, $cl_lang, $purifier;

    public function __construct($container, $iParentID, $bReturnRaw = false) {
        //if (!$bReturnRaw) $this->container = $container;
        $this->container = $container;

        if ($iParentID != '/_misc/index.html') { // no need to fetch from db if this is the itemsearch page
            $sql = "SELECT cl_id, cl_cb, cl_lang, cl_html, cl_keywords, cl_description, cl_title ";
            $sql .= "FROM content_lang WHERE cl_cb = :ppkey AND cl_lang = :lang";
            $hResult = $this->container['db']->prepare($sql);

            // Try to get the payload in the current language
            $hResult->bindValue(':ppkey', $iParentID, \PDO::PARAM_STR);
            $hResult->bindValue(':lang', $container['lang'], \PDO::PARAM_STR);
            $hResult->setFetchMode(\PDO::FETCH_INTO, $this);
            $hResult->execute();

            if ($hResult->rowCount() == 1) {
                $hResult->fetch();
            } elseif (!$bReturnRaw) { // if raw data is required, don't try to fetch default lang data
                // if the current language data is not available, lets see if we can get the default languages data
                $hResult = $this->container['db']->prepare($sql);
                $hResult->bindValue(':ppkey', $iParentID, \PDO::PARAM_STR);
                $lang_available = $this->container['conf']["lang_available"];
                $hResult->bindValue(':lang', key($lang_available), \PDO::PARAM_STR);
                $hResult->setFetchMode(\PDO::FETCH_INTO, $this);
                $hResult->execute();

                if ($hResult->rowCount() == 1) {
                    $hResult->fetch();
                }
            }
        }
    }

    public function write() {
        $aData = [
            'cl_html' => (!empty($this->purifier) ? $this->purifier->purify($this->cl_html) : $this->cl_html),
            'cl_title' => filter_var($this->cl_title, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'cl_description' => filter_var($this->cl_description, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'cl_keywords' => filter_var($this->cl_keywords, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'cl_id' => $this->cl_id,
        ];
        $sql = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'content_lang', 'cl_id');

        $hResult = $this->container['db']->prepare($sql);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        return $hResult->execute();
    }

    public function insert($iParentID) {
        $aData = [
            'cl_cb' => $iParentID,
            'cl_lang' => $this->container['lang'],
        ];
        $sql = \HaaseIT\DBTools::buildInsertQuery($aData, 'content_lang');
        $this->container['db']->exec($sql);
    }

    public function remove($sParentID) {
        $sql = "DELETE FROM content_lang WHERE cl_cb = '".$sParentID."'";
        return $this->container['db']->exec($sql);
    }

}