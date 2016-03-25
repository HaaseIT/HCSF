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

class Login extends Base
{
    public function __construct($C, $DB, $sLang, $twig, $oItem)
    {
        parent::__construct($C, $DB, $sLang);

        if (!isset($_POST["sAction"]) || $_POST["sAction"] != "login") {
            $this->P->cb_customcontenttemplate = 'customer/login';
        } else {
            $mLogin = $this->getLogin($C, $DB);
            if (isset($mLogin["status"]) && $mLogin["status"] == 'success') {
                $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("login_success") . '<br>';
                header('Location: /_misc/userhome.html?login=true');
                die();
            } elseif (isset($mLogin["status"]) && $mLogin["status"] == 'tosnotaccepted') {
                $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("login_fail_tosnotaccepted") . '<br>';
                $this->P->cb_customcontenttemplate = 'customer/login';
            } elseif (isset($mLogin["status"]) && $mLogin["status"] == 'emailnotverified') {
                $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("login_fail_emailnotverified") . '<br><br>';
                $this->P->oPayload->cl_html .= '<a href="/_misc/resendverificationmail.html?email='
                    . $mLogin["data"][DB_CUSTOMERFIELD_EMAIL] . '">' . \HaaseIT\Textcat::T("login_fail_emailnotverifiedresend") . '</a>';
                $this->P->cb_customcontenttemplate = 'customer/login';
            } elseif (isset($mLogin["status"]) && $mLogin["status"] == 'accountinactive') {
                $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("login_fail_accountinactive") . '<br>';
                $this->P->cb_customcontenttemplate = 'customer/login';
            } else {
                $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("login_fail");
                $this->P->cb_customcontenttemplate = 'customer/login';
            }
        }

        if ($C["enable_module_shop"]) {
            \HaaseIT\HCSF\Shop\Helper::refreshCartItems($C, $oItem);
        }
    }

    private function getLogin()
    {
        $bTryEmail = false;
        if (DB_CUSTOMERFIELD_USER != DB_CUSTOMERFIELD_EMAIL) $bTryEmail = true;
        //$sEnc = crypt($_POST["password"], $this->C["blowfish_salt"]);

        $sEmail = filter_var(trim(\HaaseIT\Tools::getFormfield("user")), FILTER_SANITIZE_EMAIL);
        $sUser = filter_var(trim(\HaaseIT\Tools::getFormfield("user")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

        $sQ = 'SELECT '.DB_CUSTOMERFIELD_USER.', '.DB_CUSTOMERFIELD_EMAIL.', '.DB_CUSTOMERFIELD_PASSWORD.', ';
        $sQ .= DB_CUSTOMERFIELD_ACTIVE.', '.DB_CUSTOMERFIELD_EMAILVERIFIED.', '.DB_CUSTOMERFIELD_TOSACCEPTED;
        $sQ .= ' FROM '.DB_CUSTOMERTABLE.' WHERE ';
        if ($bTryEmail) $sQ .= "(";
        $sQ .= DB_CUSTOMERFIELD_USER." = :user";
        if ($bTryEmail) $sQ .= " OR ".DB_CUSTOMERFIELD_EMAIL." = :email) ";
        $sQ .= " AND ";
        if ($bTryEmail) $sQ .= "(";
        $sQ .= DB_CUSTOMERFIELD_USER." != ''";

        if ($bTryEmail) $sQ .= " OR ".DB_CUSTOMERFIELD_EMAIL." != '')";
        //$sQ .= " AND ".DB_CUSTOMERFIELD_PASSWORD." = :pwd ";

        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':user', $sUser, \PDO::PARAM_STR);
        if ($bTryEmail) {
            $hResult->bindValue(':email', $sEmail, \PDO::PARAM_STR);
        }
        //$hResult->bindValue(':pwd', $sEnc, \PDO::PARAM_STR);
        $hResult->execute();

        $iRows = $hResult->rowCount();
        if($iRows == 1) {
            $aRow = $hResult->fetch();

            if (password_verify($_POST["password"], $aRow[DB_CUSTOMERFIELD_PASSWORD])) {
                //HaaseIT\Tools::debug($aRow);
                if ($aRow[DB_CUSTOMERFIELD_ACTIVE] == 'y' && $aRow[DB_CUSTOMERFIELD_EMAILVERIFIED] == 'y' && $aRow[DB_CUSTOMERFIELD_TOSACCEPTED] == 'y') {
                    $_SESSION["user"] = $aRow;
                    $mGet["status"] = 'success';
                } elseif ($aRow[DB_CUSTOMERFIELD_TOSACCEPTED] == 'n') {
                    $mGet["status"] = 'tosnotaccepted';
                } elseif ($aRow[DB_CUSTOMERFIELD_EMAILVERIFIED] == 'n') {
                    $mGet["status"] = 'emailnotverified';
                    $mGet["data"] = $aRow;
                } elseif ($aRow[DB_CUSTOMERFIELD_ACTIVE] == 'n') {
                    $mGet["status"] = 'accountinactive';
                } else {
                    $mGet = false;
                }
            } else {
                $mGet = false;
            }

        } else {
            $mGet = false;
        }

        return $mGet;
    }

}