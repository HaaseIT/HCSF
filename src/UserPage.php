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


use HaaseIT\Toolbox\DBTools;
use Zend\ServiceManager\ServiceManager;

class UserPage extends Page
{
    protected $bReturnRaw;
    public $cb_id, $cb_key, $cb_group, $purifier;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $dbal;

    public function __construct(ServiceManager $serviceManager, $sPagekey, $bReturnRaw = false) {
        //if (!$bReturnRaw) $this->container = $container;
        $this->serviceManager = $serviceManager;
        $this->iStatus = 200;
        $this->bReturnRaw = $bReturnRaw;
        $this->dbal = $this->serviceManager->get('dbal');

        if ($sPagekey === '/_misc/index.html') {
            $this->cb_id = $sPagekey;
            $this->cb_key = $sPagekey;
            $this->cb_pagetype = 'itemoverview';
            $this->oPayload = $this->getPayload();
            $this->cb_pageconfig = (object) [];
        } else {
            // first get base data
            $sql = "SELECT cb_id, cb_key, cb_group, cb_pagetype, cb_pageconfig, cb_subnav ";
            $sql .= "FROM content_base WHERE cb_key = :key ";

            /** @var \PDOStatement $hResult */
            $hResult = $this->serviceManager->get('db')->prepare($sql);
            $hResult->bindValue(':key', $sPagekey, \PDO::PARAM_STR);
            $hResult->setFetchMode(\PDO::FETCH_INTO, $this);
            $hResult->execute();

            if ($hResult->rowCount() == 1) {
                $hResult->fetch();

                if ($this->cb_pagetype !== 'shorturl') {
                    if (!$bReturnRaw) $this->cb_pageconfig = json_decode($this->cb_pageconfig);
                    $this->oPayload = $this->getPayload();
                }
            }
        }
    }

    protected function getPayload() {
        return new UserPagePayload($this->serviceManager, $this->cb_id, $this->bReturnRaw);
    }

    public function write() {
        $aData = [
            'cb_pagetype' => filter_var($this->cb_pagetype, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'cb_group' => filter_var($this->cb_group, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'cb_pageconfig' => $this->cb_pageconfig,
            'cb_subnav' => filter_var($this->cb_subnav, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            'cb_key' => $this->cb_key,
        ];
        $sql = DBTools::buildPSUpdateQuery($aData, 'content_base', 'cb_key');

        /** @var \PDOStatement $hResult */
        $hResult = $this->serviceManager->get('db')->prepare($sql);
        foreach ($aData as $sKey => $sValue) {
            $hResult->bindValue(':'.$sKey, $sValue);
        }
        return $hResult->execute();
    }

    public function insert($sPagekeytoadd) {
        $aData = [
            'cb_key' => $sPagekeytoadd,
        ];
        $sql = DBTools::buildInsertQuery($aData, 'content_base');
        return $hResult = $this->serviceManager->get('db')->exec($sql);
    }

    public function remove() {
        // delete children
        $this->oPayload->remove($this->cb_id);

        // then delete base row
        $queryBuilder = $this->dbal->createQueryBuilder();
        $queryBuilder
            ->delete('content_base')
            ->where('cb_id = '.$queryBuilder->createNamedParameter($this->cb_id))
        ;

        return $queryBuilder->execute();
    }
}