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


class Resendverificationmail extends Base
{
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        if ($this->helperCustomer->getUserData()) {
            $this->P->oPayload->cl_html = $this->serviceManager->get('textcats')->T('denied_default');
        } else {
            $sql = 'SELECT '.DB_ADDRESSFIELDS.', cust_emailverificationcode FROM customer';
            $sql .= ' WHERE cust_email = :email AND cust_emailverified = \'n\'';

            /** @var \PDOStatement $hResult */
            $hResult = $this->serviceManager->get('db')->prepare($sql);
            $hResult->bindValue(':email', trim(filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL)), \PDO::PARAM_STR);
            $hResult->execute();
            $iRows = $hResult->rowCount();
            if ($iRows == 1) {
                $aRow = $hResult->fetch();
                $sEmailVerificationcode = $aRow['cust_emailverificationcode'];

                $this->helperCustomer->sendVerificationMail($sEmailVerificationcode, $aRow['cust_email'], $this->serviceManager, true);

                $this->P->oPayload->cl_html = $this->serviceManager->get('textcats')->T('register_verificationmailresent');
            }
        }
    }
}
