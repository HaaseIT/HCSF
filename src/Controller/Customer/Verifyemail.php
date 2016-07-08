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

class Verifyemail extends Base
{
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->container['conf'], $this->container['lang']);
        $this->P->cb_pagetype = 'content';

        if (\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
        } else {
            $sql = 'SELECT cust_email, cust_id FROM customer '
                . 'WHERE cust_emailverificationcode = :key AND cust_emailverified = \'n\'';
            $hResult = $this->container['db']->prepare($sql);
            $hResult->bindValue(':key', $_GET["key"], \PDO::PARAM_STR);
            $hResult->execute();
            $iRows = $hResult->rowCount();

            if ($iRows == 1) {
                $aRow = $hResult->fetch();
                $aData = ['cust_emailverified' => 'y', 'cust_id' => $aRow['cust_id']];
                $sql = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'customer', 'cust_id');
                $hResult = $this->container['db']->prepare($sql);
                foreach ($aData as $sKey => $sValue) {
                    $hResult->bindValue(':' . $sKey, $sValue);
                }
                $hResult->execute();
                $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("register_emailverificationsuccess");
            } else {
                $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("register_emailverificationfail");
            }
        }
    }
}