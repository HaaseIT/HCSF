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
    public function __construct($C, $DB, $sLang, $twig, $oItem)
    {
        parent::__construct($C, $DB, $sLang);

        if (getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
        } else {
            $sQ = "SELECT " . DB_CUSTOMERFIELD_EMAIL . ", " . DB_CUSTOMERTABLE_PKEY . " FROM " . DB_CUSTOMERTABLE;
            $sQ .= " WHERE " . DB_CUSTOMERFIELD_EMAILVERIFICATIONCODE . " = :key AND " . DB_CUSTOMERFIELD_EMAILVERIFIED . " = 'n'";
            //debug( $sQ );
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':key', $_GET["key"], PDO::PARAM_STR);
            $hResult->execute();
            $iRows = $hResult->rowCount();
            //debug( $iRows );

            if ($iRows == 1) {
                $aRow = $hResult->fetch();
                $aData = array(DB_CUSTOMERFIELD_EMAILVERIFIED => 'y', DB_CUSTOMERTABLE_PKEY => $aRow[DB_CUSTOMERTABLE_PKEY]);
                $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_CUSTOMERTABLE, DB_CUSTOMERTABLE_PKEY);
                $hResult = $DB->prepare($sQ);
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