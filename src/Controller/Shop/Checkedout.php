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

namespace HaaseIT\HCSF\Controller\Shop;

class Checkedout extends Base
{
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->container['conf'], $this->container['lang']);
        $this->P->cb_pagetype = 'content';

        if ($this->container['conf']["show_pricesonlytologgedin"] && !\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_notloggedin");
        } else {
            $this->P->cb_customcontenttemplate = 'shop/checkedout';

            $iId = \filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $sql = 'SELECT * FROM orders WHERE o_id = :id AND o_paymentcompleted = \'n\'';

            $hResult = $this->container['db']->prepare($sql);
            $hResult->bindValue(':id', $iId, \PDO::PARAM_INT);

            $hResult->execute();

            if ($hResult->rowCount() == 1) {
                $this->P->cb_customdata["order"] = $hResult->fetch();
                $this->P->cb_customdata["gesamtbrutto"] = \HaaseIT\HCSF\Shop\Helper::calculateTotalFromDB($this->P->cb_customdata["order"]);
            }
        }
    }
}