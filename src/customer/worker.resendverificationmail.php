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

if (getUserData()) {
    $P = array(
        'base' => array(
            'cb_pagetype' => 'content',
            'cb_pageconfig' => '',
            'cb_subnav' => '',
        ),
        'lang' => array(
            'cl_lang' => $sLang,
            'cl_html' => \HaaseIT\Textcat::T("denied_default"),
        ),
    );
} else {
    function handleResendverificationmailPage($C, $sLang, $DB) {
        $P = array(
            'base' => array(
                'cb_pagetype' => 'content',
                'cb_pageconfig' => '',
                'cb_subnav' => '',
            ),
            'lang' => array(
                'cl_lang' => $sLang,
                'cl_html' => '',
            ),
        );

        $sQ = "SELECT " . DB_ADDRESSFIELDS . ", " . DB_CUSTOMERFIELD_EMAILVERIFICATIONCODE . " FROM " . DB_CUSTOMERTABLE;
        $sQ .= " WHERE " . DB_CUSTOMERFIELD_EMAIL . " = :email";
        $sQ .= " AND " . DB_CUSTOMERFIELD_EMAILVERIFIED . " = 'n'";
        //debug($sQ);
        $hResult = $DB->prepare($sQ);
        $hResult->bindValue(':email', trim($_GET["email"]), PDO::PARAM_STR);
        $hResult->execute();
        $iRows = $hResult->rowCount();
        if ($iRows == 1) {
            $aRow = $hResult->fetch();
            $sEmailVerificationcode = $aRow[DB_CUSTOMERFIELD_EMAILVERIFICATIONCODE];

            sendVerificationMail($sEmailVerificationcode, $aRow[DB_CUSTOMERFIELD_EMAIL], $C, true);

            $P["lang"]["cl_html"] = \HaaseIT\Textcat::T("register_verificationmailresent");
        }

        return $P;
    }

    $P = handleResendverificationmailPage($C, $sLang, $DB);
}