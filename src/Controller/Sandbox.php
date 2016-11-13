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

namespace HaaseIT\HCSF\Controller;


use Zend\ServiceManager\ServiceManager;

class Sandbox extends Base
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->entityManager = $serviceManager->get('entitymanager');
    }

    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';

        $html = '<pre>';

        /*
        $this->entityManager->getConnection()
            ->getConfiguration()
            ->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger())
        ;
        */

        //$customer = $this->entityManager->find(ENTITY_CUSTOMER, 1);

        //$this->P->oPayload->cl_html = \HaaseIT\Tools::debug($customer->getName(), 'customername', true);


        $dql = "SELECT l, b FROM ".ENTITY_USERPAGE_LANG." l JOIN l.basepage b WHERE l.language = ?1 AND b.key = ?2";
        //$dql = "SELECT l FROM ".ENTITY_USERPAGE_LANG." l";
        //die($dql);
        try
        {
            $pages = $this->entityManager->createQuery($dql)
                ->setParameter(1, 'de')
                ->setParameter(2, '/index.html')
                ->setMaxResults(10)
                ->getResult();
            foreach ($pages as $page) {
                $html .= 'base id:'.$page->getBasepage()->getId().PHP_EOL;
                $html .= 'base key:'.$page->getBasepage()->getKey().PHP_EOL;
                $html .= 'base group:'.$page->getBasepage()->getGroup().PHP_EOL;
                $html .= 'base pagetype:'.$page->getBasepage()->getPagetype().PHP_EOL;
                $html .= 'base pageconfig:'.$page->getBasepage()->getPageconfig().PHP_EOL;
                $html .= 'base subnav:'.$page->getBasepage()->getSubnav().PHP_EOL;

                $html .= 'lang id:'.$page->getId().PHP_EOL;
                $html .= 'lang language:'.$page->getLanguage().PHP_EOL;
                $html .= 'lang html:'.$page->getHtml().PHP_EOL;
                $html .= 'lang keywords:'.$page->getKeywords().PHP_EOL;
                $html .= 'lang description:'.$page->getDescription().PHP_EOL;
                $html .= 'lang title:'.$page->getTitle().PHP_EOL;

                $page->getBasepage()->setGroup('testi');
                $page->setKeywords('testi kel');

                $this->serviceManager->get('entitymanager')->persist($page);
                $this->serviceManager->get('entitymanager')->flush();


            }
        }
        catch (\Exception $e)
        {
            $html .= \HaaseIT\Tools::debug($e, 'exception', true);
        }

        $this->P->oPayload->cl_html = $html.'</pre>';

    }
}