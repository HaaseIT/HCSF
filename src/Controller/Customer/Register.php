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

class Register extends Base
{
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        if (CHelper::getUserData()) {
            $this->P->oPayload->cl_html = $this->serviceManager->get('textcats')->T('denied_default');
        } else {
            $this->P->cb_customcontenttemplate = 'customer/register';

            $aErr = [];
            if (filter_input(INPUT_POST, 'doRegister') === 'yes') {
                $aErr = CHelper::validateCustomerForm($this->config->getLang(), $aErr);
                if (count($aErr) == 0) {
                    $sEmail = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));

                    /** @var \Doctrine\DBAL\Connection $dbal */
                    $dbal = $this->serviceManager->get('dbal');

                    $querybuilder = $dbal->createQueryBuilder();
                    $querybuilder
                        ->select('cust_email')
                        ->from('customer')
                        ->where('cust_email = ?')
                        ->setParameter(0, $sEmail)
                    ;
                    $stmt = $querybuilder->execute();

                    if ($stmt->rowCount() === 0) {
                        $sEmailVerificationcode = md5($sEmail.mt_rand().time());

                        $querybuilder = $dbal->createQueryBuilder();
                        $querybuilder
                            ->insert('customer')
                            ->setValue('cust_email', ':cust_email')
                            ->setValue('cust_corp', ':cust_corp')
                            ->setValue('cust_name', ':cust_name')
                            ->setValue('cust_street', ':cust_street')
                            ->setValue('cust_zip', ':cust_zip')
                            ->setValue('cust_town', ':cust_town')
                            ->setValue('cust_phone', ':cust_phone')
                            ->setValue('cust_cellphone', ':cust_cellphone')
                            ->setValue('cust_fax', ':cust_fax')
                            ->setValue('cust_country', ':cust_country')
                            ->setValue('cust_password', ':cust_password')
                            ->setValue('cust_tosaccepted', (filter_input(INPUT_POST, 'tos') === 'y') ? 'y' : 'n')
                            ->setValue('cust_cancellationdisclaimeraccepted', (filter_input(INPUT_POST, 'cancellationdisclaimer') === 'y') ? 'y' : 'n')
                            ->setValue('cust_emailverified', 'n')
                            ->setValue('cust_emailverificationcode', $sEmailVerificationcode)
                            ->setValue('cust_active', $this->config->getCustomer('register_require_manual_activation') ? 'n' : 'y')
                            ->setValue('cust_registrationtimestamp', time())
                            ->setParameter(':cust_email', $sEmail)
                            ->setParameter(':cust_corp', filter_var(trim(Tools::getFormfield('corpname')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                            ->setParameter(':cust_name', filter_var(trim(Tools::getFormfield('name')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                            ->setParameter(':cust_street', filter_var(trim(Tools::getFormfield('street')),FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                            ->setParameter(':cust_zip', filter_var(trim(Tools::getFormfield('zip')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                            ->setParameter(':cust_town', filter_var(trim(Tools::getFormfield('town')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                            ->setParameter(':cust_phone', filter_var(trim(Tools::getFormfield('phone')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                            ->setParameter(':cust_cellphone', filter_var(trim(Tools::getFormfield('cellphone')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                            ->setParameter(':cust_fax', filter_var(trim(Tools::getFormfield('fax')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                            ->setParameter(':cust_country', filter_var(trim(Tools::getFormfield('country')), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
                            ->setParameter(':cust_password', password_hash(filter_input(INPUT_POST, 'pwd'), PASSWORD_DEFAULT))
                        ;
                        $querybuilder->execute();

                        CHelper::sendVerificationMail($sEmailVerificationcode, $sEmail, $this->serviceManager, true);
                        $aPData['showsuccessmessage'] = true;
                    } else {
                        $aErr['emailalreadytaken'] = true;
                        $this->P->cb_customdata['customerform'] = CHelper::buildCustomerForm(
                            $this->config->getLang(),
                            'register',
                            $aErr
                        );
                    }
                } else {
                    $this->P->cb_customdata['customerform'] = CHelper::buildCustomerForm(
                        $this->config->getLang(),
                        'register',
                        $aErr
                    );
                }
            } else {
                $this->P->cb_customdata['customerform'] = CHelper::buildCustomerForm(
                    $this->config->getLang(),
                    'register'
                );
            }
            if (isset($aPData) && count($aPData)) {
                $this->P->cb_customdata['register'] = $aPData;
            }
        }
    }
}
