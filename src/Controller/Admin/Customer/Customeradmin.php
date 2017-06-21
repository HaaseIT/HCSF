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


use HaaseIT\HCSF\HardcodedText;
use \HaaseIT\HCSF\Customer\Helper as CHelper;
use HaaseIT\HCSF\HelperConfig;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Customeradmin
 * @package HaaseIT\HCSF\Controller\Admin\Customer
 */
class Customeradmin extends Base
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $dbal;

    /**
     * Customeradmin constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->dbal = $serviceManager->get('dbal');
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
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
        $aPData = $this->handleCustomerAdmin($CUA, $this->serviceManager->get('twig'));
        $this->P->cb_customcontenttemplate = 'customer/customeradmin';
        $this->P->oPayload->cl_html = $aPData['customeradmin']['text'];
        $this->P->cb_customdata = $aPData;
    }

    /**
     * @param $CUA
     * @param $twig
     * @return mixed
     */
    private function handleCustomerAdmin($CUA, $twig)
    {
        $sType = 'all';
        if (isset($_REQUEST['type'])) {
            if ($_REQUEST['type'] === 'active') {
                $sType = 'active';
            } elseif ($_REQUEST['type'] === 'inactive') {
                $sType = 'inactive';
            }
        }
        $return = '';
        if (!isset($_GET['action'])) {
            $querybuilder = $this->dbal->createQueryBuilder();
            $querybuilder
                ->select(DB_ADDRESSFIELDS)
                ->from('customer')
                ->orderBy('cust_no', 'ASC')
            ;

            if ($sType === 'active') {
                $querybuilder
                    ->where('cust_active = ?')
                    ->setParameter(0, 'y')
                ;
            } elseif ($sType === 'inactive') {
                $querybuilder
                    ->where('cust_active = ?')
                    ->setParameter(0, 'n')
                ;
            }
            $stmt = $querybuilder->execute();
            if ($stmt->rowCount() !== 0) {
                $aData = $stmt->fetchAll();
                $return .= \HaaseIT\Toolbox\Tools::makeListtable($CUA, $aData, $twig);
            } else {
                $aInfo['nodatafound'] = true;
            }
        } elseif (isset($_GET['action']) && $_GET['action'] === 'edit') {
            $iId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $aErr = [];
            if (isset($_POST['doEdit']) && $_POST['doEdit'] === 'yes') {
                $sCustno = filter_var(trim($_POST['custno']), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                if (strlen($sCustno) < HelperConfig::$customer['minimum_length_custno']) {
                    $aErr['custnoinvalid'] = true;
                } else {
                    $querybuilder = $this->dbal->createQueryBuilder();
                    $querybuilder
                        ->select(DB_ADDRESSFIELDS)
                        ->from('customer')
                        ->where('cust_id != ?')
                        ->andWhere('cust_no = ?')
                        ->setParameter(0, $iId)
                        ->setParameter(1, $sCustno)
                    ;
                    $stmt = $querybuilder->execute();

                    if ($stmt->rowCount() === 1) {
                        $aErr['custnoalreadytaken'] = true;
                    }

                    $querybuilder = $this->dbal->createQueryBuilder();
                    $querybuilder
                        ->select(DB_ADDRESSFIELDS)
                        ->from('customer')
                        ->where('cust_id != ?')
                        ->andWhere('cust_email = ?')
                        ->setParameter(0, $iId)
                        ->setParameter(1, filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL))
                    ;
                    $stmt = $querybuilder->execute();
                    if ($stmt->rowCount() === 1) {
                        $aErr['emailalreadytaken'] = true;
                    }
                    $aErr = CHelper::validateCustomerForm(HelperConfig::$lang, $aErr, true);
                    if (count($aErr) === 0) {
                        $querybuilder = $this->dbal->createQueryBuilder();
                        $querybuilder
                            ->update('customer')
                            ->set('cust_no', ':cust_no')
                            ->set('cust_email', ':cust_email')
                            ->set('cust_corp', ':cust_corp')
                            ->set('cust_name', ':cust_name')
                            ->set('cust_street', ':cust_street')
                            ->set('cust_zip', ':cust_zip')
                            ->set('cust_town', ':cust_town')
                            ->set('cust_phone', ':cust_phone')
                            ->set('cust_cellphone', ':cust_cellphone')
                            ->set('cust_fax', ':cust_fax')
                            ->set('cust_country', ':cust_country')
                            ->set('cust_group', ':cust_group')
                            ->set('cust_emailverified', ':cust_emailverified')
                            ->set('cust_active', ':cust_active')
                            ->where('cust_id = :cust_id')
                            ->setParameter(':cust_no', $sCustno)
                            ->setParameter(':cust_email', trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL)))
                            ->setParameter(':cust_corp', trim(filter_input(INPUT_POST, 'corpname', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)))
                            ->setParameter(':cust_name', trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)))
                            ->setParameter(':cust_street', trim(filter_input(INPUT_POST, 'street', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)))
                            ->setParameter(':cust_zip', trim(filter_input(INPUT_POST, 'zip', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)))
                            ->setParameter(':cust_town', trim(filter_input(INPUT_POST, 'town', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)))
                            ->setParameter(':cust_phone', trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)))
                            ->setParameter(':cust_cellphone', trim(filter_input(INPUT_POST, 'cellphone', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)))
                            ->setParameter(':cust_fax', trim(filter_input(INPUT_POST, 'fax', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)))
                            ->setParameter(':cust_country', trim(filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)))
                            ->setParameter(':cust_group', trim(filter_input(INPUT_POST, 'custgroup', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)))
                            ->setParameter(':cust_emailverified', (isset($_POST['emailverified']) && $_POST['emailverified'] === 'y') ? 'y' : 'n')
                            ->setParameter(':cust_active', (isset($_POST['active']) && $_POST['active'] === 'y') ? 'y' : 'n')
                            ->setParameter(':cust_id', $iId)
                        ;

                        if (isset($_POST['pwd']) && $_POST['pwd'] != '') {
                            $querybuilder
                                ->set('cust_password', ':cust_password')
                                ->setParameter(':cust_password', password_hash($_POST['pwd'], PASSWORD_DEFAULT))
                            ;
                            $aInfo['passwordchanged'] = true;
                        }

                        $querybuilder->execute();
                        $aInfo['changeswritten'] = true;
                    }
                }
            }
            $querybuilder = $this->dbal->createQueryBuilder();
            $querybuilder
                ->select(DB_ADDRESSFIELDS)
                ->from('customer')
                ->where('cust_id = ?')
                ->setParameter(0, $iId)
            ;
            $stmt = $querybuilder->execute();
            if ($stmt->rowCount() === 1) {
                $aUser = $stmt->fetch();
                $aPData['customerform'] = CHelper::buildCustomerForm(HelperConfig::$lang, 'admin', $aErr, $aUser);
            } else {
                $aInfo['nosuchuserfound'] = true;
            }
        }
        $aPData['customeradmin']['text'] = $return;
        $aPData['customeradmin']['type'] = $sType;
        if (isset($aInfo)) {
            $aPData['customeradmin']['info'] = $aInfo;
        }
        return $aPData;
    }

}
