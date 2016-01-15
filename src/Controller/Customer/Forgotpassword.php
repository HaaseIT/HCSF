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

class Forgotpassword extends Base
{
    public function __construct($C, $DB, $sLang, $twig, $oItem)
    {
        parent::__construct($C, $DB, $sLang);

        if (getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
        } else {
            $this->P->cb_customcontenttemplate = 'customer/forgotpassword';

            $aErr = [];
            if (isset($_POST["doSend"]) && $_POST["doSend"] == 'yes') {
                $aErr = handleForgotPassword($DB, $C, $aErr);
                if (count($aErr) == 0) {
                    $this->P->cb_customdata["forgotpw"]["showsuccessmessage"] = true;
                } else {
                    $this->P->cb_customdata["forgotpw"]["errors"] = $aErr;
                }
            }
        }
    }
}