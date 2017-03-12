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

use HaaseIT\HCSF\HardcodedText;
use HaaseIT\HCSF\HelperConfig;
use HaaseIT\Toolbox\Tools;
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
    private $db;

    /**
     * Shopadmin constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->db = $serviceManager->get('db');
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
        $sql = 'SELECT * FROM orders o INNER JOIN orders_items oi on o.o_id = oi.oi_o_id WHERE o.o_id IN('.implode(', ', $ids).') ORDER BY oi.oi_o_id, oi.oi_id';
        $query = $this->db->query($sql);

        $data = $query->fetchAll();

        // set header application/csv or whatever

        $this->P->cb_customdata['rows'] = $data;
    }

}
