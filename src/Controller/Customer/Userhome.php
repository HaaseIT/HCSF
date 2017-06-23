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

use HaaseIT\HCSF\Customer\Helper as CHelper;
use HaaseIT\HCSF\HelperConfig;
use HaaseIT\Toolbox\Tools;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Userhome
 * @package HaaseIT\HCSF\Controller\Customer
 */
class Userhome extends Base
{
    /**
     * @var \HaaseIT\Toolbox\Textcat
     */
    private $textcats;

    /**
     * @var \PDO
     */
    private $db;

    /**
     * Userhome constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->textcats = $serviceManager->get('textcats');
        $this->db = $serviceManager->get('db');
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        if (!CHelper::getUserData()) {
            $this->P->oPayload->cl_html = $this->textcats->T('denied_notloggedin');
        } else {
            $this->P->cb_customcontenttemplate = 'customer/customerhome';

            $aPData['display_logingreeting'] = false;
            if (filter_input(INPUT_GET, 'login') !== null) {
                $aPData['display_logingreeting'] = true;
            }
            if (filter_input(INPUT_GET, 'editprofile') !== null) {
                $sErr = '';

                if (filter_input(INPUT_POST, 'doEdit') === 'yes') {
                    $sql = 'SELECT '.DB_ADDRESSFIELDS.' FROM customer WHERE cust_id != :id AND cust_email = :email';

                    $sEmail = filter_var(trim(Tools::getFormfield('email')), FILTER_SANITIZE_EMAIL);

                    $hResult = $this->db->prepare($sql);
                    $hResult->bindValue(':id', $_SESSION['user']['cust_id'], \PDO::PARAM_INT);
                    $hResult->bindValue(':email', $sEmail, \PDO::PARAM_STR);
                    $hResult->execute();
                    $iRows = $hResult->rowCount();
                    if ($iRows == 1) {
                        $sErr .= $this->textcats->T('userprofile_emailalreadyinuse').'<br>';
                    }
                    $sErr = CHelper::validateCustomerForm(HelperConfig::$lang, $sErr, true);

                    if ($sErr == '') {
                        if (HelperConfig::$customer['allow_edituserprofile']) {
                            $aData = [
                                //'cust_email' => $sEmail, // disabled until renwewd email verification implemented
                                'cust_corp' => filter_var(trim(Tools::getFormfield('corpname')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_name' => filter_var(trim(Tools::getFormfield('name')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_street' => filter_var(trim(Tools::getFormfield('street')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_zip' => filter_var(trim(Tools::getFormfield('zip')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_town' => filter_var(trim(Tools::getFormfield('town')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_phone' => filter_var(trim(Tools::getFormfield('phone')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_cellphone' => filter_var(trim(Tools::getFormfield('cellphone')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_fax' => filter_var(trim(Tools::getFormfield('fax')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                                'cust_country' => filter_var(trim(Tools::getFormfield('country')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            ];
                        }
                        $postpwd = filter_input(INPUT_POST, 'pwd');
                        if (!empty($postpwd)) {
                            $aData['cust_password'] = password_hash($postpwd, PASSWORD_DEFAULT);
                            $aPData['infopasswordchanged'] = true;
                        }
                        $aData['cust_id'] = $_SESSION['user']['cust_id'];

                        if (count($aData) > 1) {
                            $sql = \HaaseIT\Toolbox\DBTools::buildPSUpdateQuery($aData, 'customer', 'cust_id');
                            $hResult = $this->db->prepare($sql);
                            foreach ($aData as $sKey => $sValue) {
                                $hResult->bindValue(':'.$sKey, $sValue);
                            }
                            $hResult->execute();
                            $aPData['infochangessaved'] = true;
                        } else {
                            $aPData['infonothingchanged'] = true;
                        }
                    }
                }
                $this->P->cb_customdata['customerform'] = CHelper::buildCustomerForm(
                    HelperConfig::$lang,
                    'editprofile',
                    $sErr
                );
                //if (HelperConfig::$customer["allow_edituserprofile"]) $P["lang"]["cl_html"] .= '<br>'.$this->textcats->T("userprofile_infoeditemail"); // Future implementation
            } else {
                $this->P->cb_customdata['customerform'] = CHelper::buildCustomerForm(
                    HelperConfig::$lang,
                    'userhome'
                );
            }
            $aPData['showprofilelinks'] = false;
            if (filter_input(INPUT_GET, 'editprofile') === null) {
                $aPData['showprofilelinks'] = true;
            }
            if (isset($aPData) && count($aPData)) {
                $this->P->cb_customdata['userhome'] = $aPData;
            }
        }
    }
}
