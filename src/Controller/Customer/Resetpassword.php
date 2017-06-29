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
use Zend\ServiceManager\ServiceManager;

/**
 * Class Resetpassword
 * @package HaaseIT\HCSF\Controller\Customer
 */
class Resetpassword extends Base
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
     * Resetpassword constructor.
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

        if (\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = $this->textcats->T('denied_default');
        } else {
            $getemail = filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL);
            $getkey = filter_input(INPUT_GET, 'key', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            if (empty($getkey) || empty($getemail) || !filter_input(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL)) {
                $this->P->oPayload->cl_html = $this->textcats->T('denied_default');
            } else {
                $sql = 'SELECT * FROM customer WHERE cust_email = :email AND cust_pwresetcode = :pwresetcode AND cust_pwresetcode != \'\'';

                $hResult = $this->db->prepare($sql);
                $hResult->bindValue(':email', $getemail, \PDO::PARAM_STR);
                $hResult->bindValue(':pwresetcode', $getkey, \PDO::PARAM_STR);
                $hResult->execute();
                if ($hResult->rowCount() !== 1) {
                    $this->P->oPayload->cl_html = $this->textcats->T('denied_default');
                } else {
                    $aErr = [];
                    $aResult = $hResult->fetch();
                    $iTimestamp = time();
                    if ($aResult['cust_pwresettimestamp'] < $iTimestamp - strtotime('1 Day', 0)) {
                        $this->P->oPayload->cl_html = $this->textcats->T('pwreset_error_expired');
                    } else {
                        $this->P->cb_customcontenttemplate = 'customer/resetpassword';
                        $this->P->cb_customdata['pwreset']['minpwlength'] = $this->config->getCustomer('minimum_length_password');
                        if (filter_input(INPUT_POST, 'doSend') === 'yes') {
                            $aErr = $this->handlePasswordReset($aErr, $aResult['cust_id']);
                            if (count($aErr) === 0) {
                                $this->P->cb_customdata['pwreset']['showsuccessmessage'] = true;
                            } else {
                                $this->P->cb_customdata['pwreset']['errors'] = $aErr;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $aErr
     * @param $iID
     * @return array
     */
    private function handlePasswordReset($aErr, $iID) {
        $postpwd = filter_input(INPUT_POST, 'pwd');
        if (!empty($postpwd)) {
            if (strlen($postpwd) < $this->config->getCustomer('minimum_length_password')) {
                $aErr[] = 'pwlength';
            }
            if ($postpwd !== filter_input(INPUT_POST, 'pwdc')) {
                $aErr[] = 'pwmatch';
            }
            if (count($aErr) == 0) {
                $sEnc = password_hash($postpwd, PASSWORD_DEFAULT);
                $aData = [
                    'cust_password' => $sEnc,
                    'cust_pwresetcode' => '',
                    'cust_id' => $iID,
                ];
                $sql = \HaaseIT\Toolbox\DBTools::buildPSUpdateQuery($aData, 'customer', 'cust_id');
                $hResult = $this->db->prepare($sql);
                foreach ($aData as $sKey => $sValue) {
                    $hResult->bindValue(':'.$sKey, $sValue);
                }
                $hResult->execute();
            }
        } else {
            $aErr[] = 'nopw';
        }

        return $aErr;
    }
}
