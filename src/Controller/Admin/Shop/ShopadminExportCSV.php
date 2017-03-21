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

namespace HaaseIT\HCSF\Controller\Admin\Shop;

use Zend\ServiceManager\ServiceManager;

/**
 * Class Shopadmin
 * @package HaaseIT\HCSF\Controller\Admin\Shop
 */
class ShopadminExportCSV extends Base
{
    /**
     * @var \PDO
     */
    protected $db;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $dbal;

    /**
     * Shopadmin constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->db = $serviceManager->get('db');
        $this->dbal = $serviceManager->get('dbal');
    }

    /**
     *
     */
    public function preparePage()
    {
        $headers = [
            'Content-Disposition' => 'attachment; filename=hcsf_export.csv',
            'Content-type' => 'text/csv',
//            'Content-type' => 'text/plain',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager, $headers, 'shop/shopadmin-export-csv.twig');
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        // filter input
        $ids = [];
        foreach ($_POST['id'] as $item) {
            $item = (int) $item;
            if ($item > 0) {
                $ids[$item] = $item;
            }
        }

        // fetch orders from db and add to $this->P->cb_customdata
        $queryBuilder = $this->dbal->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('orders', 'o')
            ->innerJoin('o', 'orders_items', 'oi', 'o.o_id = oi.oi_o_id')
            ->where('o.o_id IN ('.substr(str_repeat('?,', count($ids)), 0, -1).')')
            ->orderBy('oi.oi_o_id')
            ->addOrderBy('oi.oi_id')
        ;
        $i = 0;
        foreach ($ids as $id) {
            $queryBuilder->setParameter($i, $id);
            $i++;
        }
        $statement = $queryBuilder->execute();

        $rows = $statement->fetchAll();

        $this->P->cb_customdata['rows'] = $rows;
    }

}
