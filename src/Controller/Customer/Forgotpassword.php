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


use Zend\ServiceManager\ServiceManager;

/**
 * Class Forgotpassword
 * @package HaaseIT\HCSF\Controller\Customer
 */
class Forgotpassword extends Base
{
    /**
     * @var \HaaseIT\Toolbox\Textcat
     */
    private $textcats;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $dbal;

    /**
     * Forgotpassword constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->textcats = $serviceManager->get('textcats');
        $this->dbal = $serviceManager->get('dbal');
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        if ($this->helperCustomer->getUserData()) {
            $this->P->oPayload->cl_html = $this->textcats->T('denied_default');
        } else {
            $this->P->cb_customcontenttemplate = 'customer/forgotpassword';

            $aErr = [];
            if (filter_input(INPUT_POST, 'doSend') === 'yes') {
                $aErr = $this->handleForgotPassword($aErr);
                if (count($aErr) === 0) {
                    $this->P->cb_customdata['forgotpw']['showsuccessmessage'] = true;
                } else {
                    $this->P->cb_customdata['forgotpw']['errors'] = $aErr;
                }
            }
        }
    }

    /**
     * @param $aErr
     * @return array
     */
    private function handleForgotPassword($aErr) {
        if (!filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL)) {
            $aErr[] = 'emailinvalid';
        } else {
            $querybuilder = $this->dbal->createQueryBuilder();
            $querybuilder
                ->select('*')
                ->from('customer')
                ->where('cust_email = ?')
                ->setParameter(0, filter_var(trim(\HaaseIT\Toolbox\Tools::getFormfield('email')), FILTER_SANITIZE_EMAIL))
            ;
            $stmt = $querybuilder->execute();

            if ($stmt->rowCount() != 1) {
                $aErr[] = 'emailunknown';
            } else {
                $aResult = $stmt->fetch();
                $iTimestamp = time();
                if ($iTimestamp - strtotime('1 Hour', 0) < $aResult['cust_pwresettimestamp']) { // 1 hour delay between requests
                    $aErr[] = 'pwresetstilllocked';
                } else {
                    $sResetCode = md5($aResult['cust_email'].mt_rand().$iTimestamp);
                    $querybuilder = $this->dbal->createQueryBuilder();
                    $querybuilder
                        ->update('customer')
                        ->set('cust_pwresetcode', '?')
                        ->set('cust_pwresettimestamp', '?')
                        ->where('cust_id = ?')
                        ->setParameter(0, $sResetCode)
                        ->setParameter(1, $iTimestamp)
                        ->setParameter(2, $aResult['cust_id'])
                    ;
                    $querybuilder->execute();

                    $serverservername = filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_URL);
                    $serverhttps = filter_input(INPUT_SERVER, 'HTTPS');
                    $sTargetAddress = $aResult['cust_email'];
                    $sSubject = $this->textcats->T('forgotpw_mail_subject');
                    $sMessage = $this->textcats->T('forgotpw_mail_text1');
                    $sMessage .= '<br><br>' .'<a href="http'.($serverhttps === 'on' ? 's' : '').'://';
                    $sMessage .= $serverservername.'/_misc/rp.html?key='.$sResetCode.'&amp;email='.$sTargetAddress.'">';
                    $sMessage .= 'http'.($serverhttps === 'on' ? 's' : '').'://';
                    $sMessage .= $serverservername.'/_misc/rp.html?key='.$sResetCode.'&amp;email='.$sTargetAddress.'</a>';
                    $sMessage .= '<br><br>'.$this->textcats->T('forgotpw_mail_text2');

                    $this->helper->mailWrapper($sTargetAddress, $sSubject, $sMessage);
                }
            }
        }

        return $aErr;
    }
}
