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

namespace HaaseIT\HCSF;

use HaaseIT\Toolbox\Tools;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Helper
 * @package HaaseIT\HCSF
 */
class Helper
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var \HaaseIT\HCSF\HelperConfig
     */
    protected $config;

    /**
     * @var array
     */
    protected $core = [];

    /**
     * @var array
     */
    protected $secrets = [];

    /**
     * @var array
     */
    protected $shop = [];

    /**
     * Helper constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        $this->config = $serviceManager->get('config');
        $this->secrets = $this->config->getSecret();
        $this->core = $this->config->getCore();
        $this->shop = $this->config->getShop();
    }

    /**
     * @param string $target
     * @param bool $replace
     * @param int $http_response_header
     * @return void|false
     */
    public function redirectToPage($target = '', $replace = false, $http_response_header = 302)
    {
        if (empty($target)) {
            return false;
        }

        header('Location: '.$target, $replace, $http_response_header);
        $this->terminateScript();
    }

    /**
     * @param string $message
     */
    public function terminateScript($message = '')
    {
        die($message);
    }

    /**
     * @param $number
     * @return string
     */
    public function formatNumber($number)
    {
        return number_format(
            $number,
            $this->config->getCore('numberformat_decimals'),
            $this->config->getCore('numberformat_decimal_point'),
            $this->config->getCore('numberformat_thousands_seperator')
        );
    }

    /**
     * @param $file
     * @param int $width
     * @param int $height
     * @return bool|string
     */
    public function getSignedGlideURL($file, $width = 0, $height = 0)
    {
        $urlBuilder = \League\Glide\Urls\UrlBuilderFactory::create('', $this->secrets['glide_signkey']);

        $param = [];
        if ($width == 0 && $height == 0) {
            return false;
        }
        if ($width != 0) {
            $param['w'] = $width;
        }
        if ($height != 0) {
            $param['h'] = $height;
        }
        if ($width != 0 && $height != 0) {
            $param['fit'] = 'stretch';
        }

        return $urlBuilder->getUrl($file, $param);
    }

    /**
     * @param $to
     * @param string $subject
     * @param string $message
     * @param array $aImagesToEmbed
     * @param array $aFilesToAttach
     * @return bool
     */
    public function mailWrapper($to, $subject = '(No subject)', $message = '', $aImagesToEmbed = [], $aFilesToAttach = []) {
        $mail = new \PHPMailer;
        $mail->CharSet = 'UTF-8';

        $mail->isMail();
        if ($this->core['mail_method'] === 'sendmail') {
            $mail->isSendmail();
        } elseif ($this->core['mail_method'] === 'smtp') {
            $mail->isSMTP();
            $mail->Host = $this->secrets['mail_smtp_server'];
            $mail->Port = $this->secrets['mail_smtp_port'];
            if ($this->secrets['mail_smtp_auth'] === true) {
                $mail->SMTPAuth = true;
                $mail->Username = $this->secrets['mail_smtp_auth_user'];
                $mail->Password = $this->secrets['mail_smtp_auth_pwd'];
                if ($this->secrets['mail_smtp_secure']) {
                    $mail->SMTPSecure = 'tls';
                    if ($this->secrets['mail_smtp_secure_method'] === 'ssl') {
                        $mail->SMTPSecure = 'ssl';
                    }
                }
            }
        }

        $mail->From = $this->core['email_sender'];
        $mail->FromName = $this->core['email_sendername'];
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        if (is_array($aImagesToEmbed) && count($aImagesToEmbed)) {
            foreach ($aImagesToEmbed as $sKey => $imgdata) {
                $imginfo = getimagesizefromstring($imgdata['binimg']);
                $mail->addStringEmbeddedImage($imgdata['binimg'], $sKey, $sKey, 'base64', $imginfo['mime']);
            }
        }

        if (is_array($aFilesToAttach) && count($aFilesToAttach)) {
            foreach ($aFilesToAttach as $sValue) {
                if (file_exists($sValue)) {
                    $mail->addAttachment($sValue);
                }
            }
        }

        return $mail->send();
    }

    // don't remove this, this is the fallback for unavailable twig functions
    /**
     * @param $string
     * @return mixed
     */
    public function reachThrough($string) {
        return $string;
    }
    // don't remove this, this is the fallback for unavailable twig functions
    /**
     * @return string
     */
    public function returnEmptyString() {
        return '';
    }

    /**
     * @param array $aP
     * @param Page $P
     */
    public function getDebug($aP, $P)
    {
        if (!empty($_POST)) {
            Tools::debug($_POST, '$_POST');
        } elseif (!empty($_REQUEST)) {
            Tools::debug($_REQUEST, '$_REQUEST');
        }
        if (!empty($_SESSION)) {
            Tools::debug($_SESSION, '$_SESSION');
        }
        Tools::debug($aP, '$aP');
        Tools::debug($P, '$P');
    }

    /**
     * @param string $purpose
     * @return bool|\HTMLPurifier
     */
    public function getPurifier($purpose)
    {
        $purifier_config = \HTMLPurifier_Config::createDefault();
        $purifier_config->set('Core.Encoding', 'UTF-8');
        $purifier_config->set('Cache.SerializerPath', PATH_PURIFIERCACHE);
        $purifier_config->set('HTML.Doctype', $this->core['purifier_doctype']);

        if ($purpose === 'textcat') {
            $configkey = 'textcat';
            $configsection = 'core';
        } elseif ($purpose === 'page') {
            $configkey = 'pagetext';
            $configsection = 'core';
        } elseif ($purpose === 'item') {
            $configkey = 'itemtext';
            $configsection = 'shop';
        } elseif ($purpose === 'itemgroup') {
            $configkey = 'itemgrouptext';
            $configsection = 'shop';
        } else {
            return false;
        }

        if (!empty($this->{$configsection}[$configkey.'_unsafe_html_whitelist'])) {
            $purifier_config->set('HTML.Allowed', $this->{$configsection}[$configkey.'_unsafe_html_whitelist']);
        }
        if (!empty($this->{$configsection}[$configkey.'_loose_filtering'])) {
            $purifier_config->set('HTML.Trusted', true);
            $purifier_config->set('Attr.EnableID', true);
            $purifier_config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);
        }

        return new \HTMLPurifier($purifier_config);
    }

    /**
     * @param $callback
     * @param $parameters
     * @return bool|mixed
     */
    public function twigCallback($callback, $parameters)
    {
        $helperShop = $this->serviceManager->get('helpershop');

        $callbacks = [
            'renderItemStatusIcon' => [$helperShop, 'renderItemStatusIcon'],
            'shopadminMakeCheckbox' => [$helperShop, 'shopadminMakeCheckbox'],
        ];

        if (!isset($callbacks[$callback])) {
            return false;
        }
        
        return call_user_func($callbacks[$callback], $parameters);
    }

    /**
     * @param \Doctrine\DBAL\Connection $dbal
     * @param string $table
     * @param array $data
     * @return string
     */
    public function autoInsert(\Doctrine\DBAL\Connection $dbal, $table, array $data)
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $querybuilder */
        $querybuilder = $dbal->createQueryBuilder();
        $querybuilder->insert($table);

        foreach ($data as $colname => $col) {
            $querybuilder
                ->setValue($colname, ':'.$colname)
                ->setParameter(':'.$colname, $col);
        }

        $querybuilder->execute();

        return $dbal->lastInsertId();
    }
}
