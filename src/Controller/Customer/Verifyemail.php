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

namespace HaaseIT\HCSF\Controller\Customer;

use Zend\ServiceManager\ServiceManager;

/**
 * Class Verifyemail
 * @package HaaseIT\HCSF\Controller\Customer
 */
class Verifyemail extends Base
{
    /**
     * @var \HaaseIT\Toolbox\Textcat
     */
    private $textcats;

    /**
     * @var \PDO
     */
    private $db;

    /**
     * Verifyemail constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->textcats = $serviceManager->get('textcats');
        $this->db = $serviceManager->get('db');
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        if (\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = $this->textcats->T('denied_default');
        } else {
            $sql = 'SELECT cust_email, cust_id FROM customer '
               .'WHERE cust_emailverificationcode = :key AND cust_emailverified = \'n\'';
            /** @var \PDOStatement $hResult */
            $hResult = $this->db->prepare($sql);
            $hResult->bindValue(':key', filter_input(INPUT_GET, 'key', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW), \PDO::PARAM_STR);
            $hResult->execute();
            $iRows = $hResult->rowCount();

            if ($iRows == 1) {
                $aRow = $hResult->fetch();
                $aData = ['cust_emailverified' => 'y', 'cust_id' => $aRow['cust_id']];
                $sql = \HaaseIT\Toolbox\DBTools::buildPSUpdateQuery($aData, 'customer', 'cust_id');
                /** @var \PDOStatement $hResult */
                $hResult = $this->db->prepare($sql);
                foreach ($aData as $sKey => $sValue) {
                    $hResult->bindValue(':'.$sKey, $sValue);
                }
                $hResult->execute();
                $this->P->oPayload->cl_html = $this->textcats->T('register_emailverificationsuccess');
            } else {
                $this->P->oPayload->cl_html = $this->textcats->T('register_emailverificationfail');
            }
        }
    }
}
