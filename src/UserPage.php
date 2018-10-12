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


use Zend\ServiceManager\ServiceManager;

/**
 * Class UserPage
 * @package HaaseIT\HCSF
 */
class UserPage extends Page
{
    /**
     * @var bool
     */
    protected $bReturnRaw;

    /**
     * @var int|string
     */
    public $cb_id;

    /**
     * @var string
     */
    public $cb_key;

    /**
     * @var int
     */
    public $cb_group;

    /**
     * @var bool
     */
    public $cb_html_from_file;

    /**
     * @var \HTMLPurifier
     */
    public $purifier;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $dbal;

    /**
     * UserPage constructor.
     * @param ServiceManager $serviceManager
     * @param $sPagekey
     * @param bool $bReturnRaw
     */
    public function __construct(ServiceManager $serviceManager, $sPagekey, $bReturnRaw = false)
    {
        //if (!$bReturnRaw) $this->container = $container;
        $this->serviceManager = $serviceManager;
        $this->status = 200;
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
            $querybuilder = $this->dbal->createQueryBuilder();
            $querybuilder
                ->select('cb_id, cb_key, cb_group, cb_pagetype, cb_pageconfig, cb_subnav, cb_html_from_file')
                ->from('content_base')
                ->where('cb_key = ?')
                ->setParameter(0, $sPagekey)
            ;
            $stmt = $querybuilder->execute();
            $stmt->setFetchMode(\PDO::FETCH_INTO, $this);

            if ($stmt->rowCount() === 1) {
                $stmt->fetch();

                if ($this->cb_pagetype !== 'shorturl') {
                    if (!$bReturnRaw) {
                        $this->cb_pageconfig = json_decode($this->cb_pageconfig);
                    }
                    $this->oPayload = $this->getPayload();
                }
            }
        }
    }

    /**
     * @return UserPagePayload
     */
    protected function getPayload()
    {
        return new UserPagePayload($this->serviceManager, $this->cb_id, $this->bReturnRaw, $this);
    }

    /**
     * @return bool
     */
    public function write()
    {
        $querybuilder = $this->dbal->createQueryBuilder();
        $querybuilder
            ->update('content_base')
            ->set('cb_pagetype', '?')
            ->set('cb_group', '?')
            ->set('cb_pageconfig', '?')
            ->set('cb_subnav', '?')
            ->set('cb_html_from_file', '?')
            ->where('cb_key = ?')
            ->setParameter(0, filter_var($this->cb_pagetype, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
            ->setParameter(1, filter_var($this->cb_group, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
            ->setParameter(2, $this->cb_pageconfig)
            ->setParameter(3, filter_var($this->cb_subnav, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
            ->setParameter(4, ($this->cb_html_from_file) ? 'Y' : 'N')
            ->setParameter(5, $this->cb_key)
        ;

        return $querybuilder->execute();
    }

    /**
     * @param string $sPagekeytoadd
     * @return mixed
     */
    public function insert($sPagekeytoadd)
    {
        $querybuilder = $this->dbal->createQueryBuilder();
        $querybuilder
            ->insert('content_base')
            ->setValue('cb_key', '?')
            ->setParameter(0, $sPagekeytoadd)
        ;

        return $querybuilder->execute();
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function remove()
    {
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
