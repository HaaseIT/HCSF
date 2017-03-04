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

use HaaseIT\HCSF\HelperConfig;
use HaaseIT\Toolbox\Tools;

/**
 * Class Login
 * @package HaaseIT\HCSF\Controller\Customer
 */
class Login extends Base
{
    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        /** @var \HaaseIT\Toolbox\Textcat $textcats */
        $textcats = $this->serviceManager->get('textcats');

        if (!isset($_POST["sAction"]) || $_POST["sAction"] != "login") {
            $this->P->cb_customcontenttemplate = 'customer/login';
        } else {
            $mLogin = $this->getLogin();
            if (isset($mLogin["status"]) && $mLogin["status"] == 'success') {
                $this->P->oPayload->cl_html = $textcats->T("login_success") . '<br>';
                header('Location: /_misc/userhome.html?login=true');
                die();
            } elseif (isset($mLogin["status"]) && $mLogin["status"] == 'tosnotaccepted') {
                $this->P->oPayload->cl_html = $textcats->T("login_fail_tosnotaccepted") . '<br>';
                $this->P->cb_customcontenttemplate = 'customer/login';
            } elseif (isset($mLogin["status"]) && $mLogin["status"] == 'emailnotverified') {
                $this->P->oPayload->cl_html = $textcats->T("login_fail_emailnotverified") . '<br><br>';
                $this->P->oPayload->cl_html .= '<a href="/_misc/resendverificationmail.html?email='
                    . $mLogin["data"]['cust_email'] . '">' . $textcats->T("login_fail_emailnotverifiedresend") . '</a>';
                $this->P->cb_customcontenttemplate = 'customer/login';
            } elseif (isset($mLogin["status"]) && $mLogin["status"] == 'accountinactive') {
                $this->P->oPayload->cl_html = $textcats->T("login_fail_accountinactive") . '<br>';
                $this->P->cb_customcontenttemplate = 'customer/login';
            } else {
                $this->P->oPayload->cl_html = $textcats->T("login_fail");
                $this->P->cb_customcontenttemplate = 'customer/login';
            }
        }

        if (HelperConfig::$core["enable_module_shop"]) {
            \HaaseIT\HCSF\Shop\Helper::refreshCartItems($this->serviceManager);
        }
    }

    /**
     * @return array|bool
     */
    private function getLogin()
    {
        $bTryEmail = false;
        if ('cust_no' != 'cust_email') $bTryEmail = true;

        $sEmail = filter_var(trim(Tools::getFormfield("user")), FILTER_SANITIZE_EMAIL);
        $sUser = filter_var(trim(Tools::getFormfield("user")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

        $sql = 'SELECT cust_no, cust_email, cust_password, cust_active, cust_emailverified, cust_tosaccepted'
            . ' FROM customer WHERE ';
        if ($bTryEmail) $sql .= "(";
        $sql .= 'cust_no = :user';
        if ($bTryEmail) $sql .= ' OR cust_email = :email) ';
        $sql .= " AND ";
        if ($bTryEmail) $sql .= "(";
        $sql .= 'cust_no != \'\'';

        if ($bTryEmail) $sql .= ' OR cust_email != \'\')';

        /** @var \PDOStatement $hResult */
        $hResult = $this->serviceManager->get('db')->prepare($sql);
        $hResult->bindValue(':user', $sUser, \PDO::PARAM_STR);
        if ($bTryEmail) {
            $hResult->bindValue(':email', $sEmail, \PDO::PARAM_STR);
        }
        $hResult->execute();

        $iRows = $hResult->rowCount();
        if($iRows == 1) {
            $aRow = $hResult->fetch();

            if (password_verify($_POST["password"], $aRow['cust_password'])) {
                if ($aRow['cust_active'] == 'y' && $aRow['cust_emailverified'] == 'y' && $aRow['cust_tosaccepted'] == 'y') {
                    $_SESSION["user"] = $aRow;
                    return ['status' => 'success'];
                } elseif ($aRow['cust_tosaccepted'] == 'n') {
                    return ['status' => 'tosnotaccepted'];
                } elseif ($aRow['cust_emailverified'] == 'n') {
                    return ['status' => 'emailnotverified', 'data' => $aRow,];
                } elseif ($aRow['cust_active'] == 'n') {
                    return ['status' => 'accountinactive',];
                }
            }
        }

        return false;
    }

}