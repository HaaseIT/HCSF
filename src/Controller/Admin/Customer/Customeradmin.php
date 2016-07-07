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

namespace HaaseIT\HCSF\Controller\Admin\Customer;
use \HaaseIT\HCSF\HardcodedText;

class Customeradmin extends Base
{
    public function __construct($C, $DB, $sLang, $twig)
    {
        parent::__construct($C, $DB, $sLang);
        $this->twig = $twig;
    }

    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->C, $this->sLang);
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        $CUA = [
            ['title' => HardcodedText::get('customeradmin_list_no'), 'key' => 'cust_no', 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',],
            ['title' => HardcodedText::get('customeradmin_list_company'), 'key' => 'cust_corp', 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',],
            ['title' => HardcodedText::get('customeradmin_list_name'), 'key' => 'cust_name', 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',],
            ['title' => HardcodedText::get('customeradmin_list_town'), 'key' => 'cust_town', 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',],
            ['title' => HardcodedText::get('customeradmin_list_active'), 'key' => 'cust_active', 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',],
            [
                'title' => HardcodedText::get('customeradmin_list_edit'),
                'key' => 'cust_id',
                'width' => '16%',
                'linked' => true,
                'ltarget' => '/_admin/customeradmin.html',
                'lkeyname' => 'id',
                'lgetvars' => ['action' => 'edit',],
            ],
        ];
        $aPData = $this->handleCustomerAdmin($CUA, $this->twig);
        $this->P->cb_customcontenttemplate = 'customer/customeradmin';
        $this->P->oPayload->cl_html = $aPData["customeradmin"]["text"];
        $this->P->cb_customdata = $aPData;
    }

    private function handleCustomerAdmin($CUA, $twig)
    {
        $sType = 'all';
        if (isset($_REQUEST["type"])) {
            if ($_REQUEST["type"] == 'active') {
                $sType = 'active';
            } elseif ($_REQUEST["type"] == 'inactive') {
                $sType = 'inactive';
            }
        }
        $sH = '';
        if (!isset($_GET["action"])) {
            $sql = 'SELECT '.DB_ADDRESSFIELDS.' FROM customer';
            if ($sType == 'active') {
                $sql .= ' WHERE cust_active = \'y\'';
            } elseif ($sType == 'inactive') {
                $sql .= ' WHERE cust_active = \'n\'';
            }
            $sql .= ' ORDER BY cust_no ASC';
            $hResult = $this->DB->query($sql);
            if ($hResult->rowCount() != 0) {
                $aData = $hResult->fetchAll();
                $sH .= \HaaseIT\Tools::makeListtable($CUA, $aData, $twig);
            } else {
                $aInfo["nodatafound"] = true;
            }
        } elseif (isset($_GET["action"]) && $_GET["action"] == 'edit') {
            $iId = \filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $aErr = [];
            if (isset($_POST["doEdit"]) && $_POST["doEdit"] == 'yes') {
                $sCustno = filter_var(trim($_POST["custno"]), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                if (strlen($sCustno) < $this->C["minimum_length_custno"]) {
                    $aErr["custnoinvalid"] = true;
                } else {

                    $sql = 'SELECT '.DB_ADDRESSFIELDS.' FROM customer WHERE cust_id != :id AND cust_no = :custno';
                    $hResult = $this->DB->prepare($sql);
                    $hResult->bindValue(':id', $iId);
                    $hResult->bindValue(':custno', $sCustno);
                    $hResult->execute();
                    $iRows = $hResult->rowCount();
                    if ($iRows == 1) {
                        $aErr["custnoalreadytaken"] = true;
                    }
                    $sql = 'SELECT '.DB_ADDRESSFIELDS.' FROM customer WHERE cust_id != :id AND cust_email = :email';
                    $hResult = $this->DB->prepare($sql);
                    $hResult->bindValue(':id', $iId);
                    $hResult->bindValue(':email', \filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
                    $hResult->execute();
                    $iRows = $hResult->rowCount();
                    if ($iRows == 1) {
                        $aErr["emailalreadytaken"] = true;
                    }
                    $aErr = \HaaseIT\HCSF\Customer\Helper::validateCustomerForm($this->C, $this->sLang, $aErr, true);
                    if (count($aErr) == 0) {
                        $aData = [
                            'cust_no' => $sCustno,
                            'cust_email' => \trim(\filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL)),
                            'cust_corp' => \trim(\filter_input(INPUT_POST, 'corpname', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            'cust_name' => \trim(\filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            'cust_street' => \trim(\filter_input(INPUT_POST, 'street', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            'cust_zip' => \trim(\filter_input(INPUT_POST, 'zip', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            'cust_town' => \trim(\filter_input(INPUT_POST, 'town', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            'cust_phone' => \trim(\filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            'cust_cellphone' => \trim(\filter_input(INPUT_POST, 'cellphone', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            'cust_fax' => \trim(\filter_input(INPUT_POST, 'fax', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            'cust_country' => \trim(\filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            'cust_group' => \trim(\filter_input(INPUT_POST, 'custgroup', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            'cust_emailverified' => ((isset($_POST["emailverified"]) && $_POST["emailverified"] == 'y') ? 'y' : 'n'),
                            'cust_active' => ((isset($_POST["active"]) && $_POST["active"] == 'y') ? 'y' : 'n'),
                            'cust_id' => $iId,
                        ];
                        if (isset($_POST["pwd"]) && $_POST["pwd"] != '') {
                            $aData['cust_password'] = password_hash($_POST["pwd"], PASSWORD_DEFAULT);
                            $aInfo["passwordchanged"] = true;
                        }
                        $sql = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'customer', 'cust_id');
                        $hResult = $this->DB->prepare($sql);
                        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':' . $sKey, $sValue);
                        $hResult->execute();
                        $aInfo["changeswritten"] = true;
                    }
                }
            }
            $sql = 'SELECT '.DB_ADDRESSFIELDS.' FROM customer WHERE cust_id = :id';
            $hResult = $this->DB->prepare($sql);
            $hResult->bindValue(':id', $iId);
            $hResult->execute();
            if ($hResult->rowCount() == 1) {
                $aUser = $hResult->fetch();
                $aPData["customerform"] = \HaaseIT\HCSF\Customer\Helper::buildCustomerForm($this->C, $this->sLang, 'admin', $aErr, $aUser);
            } else {
                $aInfo["nosuchuserfound"] = true;
            }
        }
        $aPData["customeradmin"]["text"] = $sH;
        $aPData["customeradmin"]["type"] = $sType;
        if (isset($aInfo)) $aPData["customeradmin"]["info"] = $aInfo;
        return $aPData;
    }

}