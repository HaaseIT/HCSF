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
    public function __construct($C, $DB, $sLang, $twig, $oItem)
    {
        parent::__construct($C, $DB, $sLang);

        if ($C["show_pricesonlytologgedin"] && !\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_notloggedin");
        } else {
            $this->P->cb_customcontenttemplate = 'shop/checkedout';

            $iId = \filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $sQ = "SELECT * FROM " . DB_ORDERTABLE . " ";
            $sQ .= "WHERE o_id = :id AND o_paymentcompleted = 'n'";

            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':id', $iId, PDO::PARAM_INT);

            $hResult->execute();

            if ($hResult->rowCount() == 1) {
                $this->P->cb_customdata["order"] = $hResult->fetch();
                $this->P->cb_customdata["gesamtbrutto"] = \HaaseIT\HCSF\Shop\Helper::calculateTotalFromDB($this->P->cb_customdata["order"]);
            }
        }
    }
}