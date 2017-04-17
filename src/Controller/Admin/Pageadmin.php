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

namespace HaaseIT\HCSF\Controller\Admin;

use HaaseIT\HCSF\HelperConfig;
use HaaseIT\HCSF\UserPage;
use HaaseIT\HCSF\HardcodedText;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Pageadmin
 * @package HaaseIT\HCSF\Controller\Admin
 */
class Pageadmin extends Base
{
    /**
     * @var \Zend\Diactoros\ServerRequest
     */
    private $request;

    /**
     * @var null|array|object
     */
    private $post;

    /**
     * @var array
     */
    private $get;

    /**
     * Pageadmin constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        /** @var \Zend\Diactoros\ServerRequest request */
        $this->request = $serviceManager->get('request');
        $this->post = $this->request->getParsedBody();
        $this->get = $this->request->getQueryParams();
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        $this->P->cb_customcontenttemplate = 'pageadmin';

        // adding language to page here
        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'insert_lang') {
            $this->insertLang();
        }

        if (!isset($this->get['action'])) {
            $this->P->cb_customdata['pageselect'] = $this->showPageselect();
        } elseif (isset($_REQUEST['page_key']) && $_REQUEST['page_key'] != '' && ($this->get['action'] === 'edit' || $this->get['action'] === 'delete')) {
            if ($this->get['action'] === 'delete' && isset($this->post['delete']) && $this->post['delete'] === 'do') {
                $this->handleDeletePage();
            } else { // edit or update page
                $this->handleEditPage();
            }
        } elseif ($this->get['action'] === 'addpage') {
            $this->handleAddPage();
        }
    }

    protected function handleDeletePage()
    {
        // delete and put message in customdata
        $Ptodelete = new UserPage($this->serviceManager, $this->get['page_key'], true);
        if ($Ptodelete->cb_id != NULL) {
            $Ptodelete->remove();
        } else {
            die(HardcodedText::get('pageadmin_exception_pagetodeletenotfound'));
        }
        $this->P->cb_customdata['deleted'] = true;
    }

    protected function handleAddPage()
    {
        $aErr = [];
        if (isset($this->post['addpage']) && $this->post['addpage'] === 'do') {
            $sPagekeytoadd = \trim(\filter_input(INPUT_POST, 'pagekey', FILTER_SANITIZE_SPECIAL_CHARS));

            if (mb_substr($sPagekeytoadd, 0, 2) === '/_') {
                $aErr['reservedpath'] = true;
            } elseif (strlen($sPagekeytoadd) < 4) {
                $aErr['keytooshort'] = true;
            } else {
                $Ptoadd = new UserPage($this->serviceManager, $sPagekeytoadd, true);
                if ($Ptoadd->cb_id == NULL) {
                    if ($Ptoadd->insert($sPagekeytoadd)) {
                        header('Location: /_admin/pageadmin.html?page_key='.$sPagekeytoadd.'&action=edit');
                        die();
                    } else {
                        die(HardcodedText::get('pageadmin_exception_couldnotinsertpage'));
                    }
                } else {
                    $aErr['keyalreadyinuse'] = true;
                }
            }
            $this->P->cb_customdata['err'] = $aErr;
            unset($aErr);
        }
        $this->P->cb_customdata['showaddform'] = true;
    }

    /**
     * @return array
     */
    protected function showPageselect() {
        $aGroups = [];
        foreach (HelperConfig::$core['admin_page_groups'] as $sValue) {
            $TMP = explode('|', $sValue);
            $aGroups[$TMP[0]] = $TMP[1];
        }

        $dbal = $this->serviceManager->get('dbal');

        /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $dbal->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('content_base')
            ->orderBy('cb_key')
        ;
        $statement = $queryBuilder->execute();

        while ($aResult = $statement->fetch()) {
            if (isset($aGroups[$aResult['cb_group']])) {
                $aTree[$aResult['cb_group']][] = $aResult;
            } else {
                $aTree['_'][] = $aResult;
            }
        }

        foreach ($aGroups as $sKey => $sValue) {
            if (isset($aTree[$sKey])) {
                $aOptions_g[] = $sKey.'|'.$sValue;
            }
        }

        return [
            'options_groups' => isset($aOptions_g) ? $aOptions_g : [],
            'tree' => isset($aTree) ? $aTree : [],
        ];
    }

    protected function insertLang()
    {
        $Ptoinsertlang = new UserPage($this->serviceManager, $_REQUEST['page_key'], true);

        if ($Ptoinsertlang->cb_id != NULL && $Ptoinsertlang->oPayload->cl_id == NULL) {
            $Ptoinsertlang->oPayload->insert($Ptoinsertlang->cb_id);
            header('Location: /_admin/pageadmin.html?page_key='.$Ptoinsertlang->cb_key.'&action=edit');
            die();
        } else {
            die(HardcodedText::get('pageadmin_exception_couldnotinsertlang'));
        }
    }

    protected function handleEditPage()
    {
        if (isset($_REQUEST['page_key']) && $Ptoedit = new UserPage($this->serviceManager, $_REQUEST['page_key'], true)) {
            if (isset($_REQUEST['action_a']) && $_REQUEST['action_a'] === 'true') {
                $Ptoedit = $this->updatePage($Ptoedit);
            }
            $this->P->cb_customdata['page'] = $Ptoedit;
            $this->P->cb_customdata['admin_page_types'] = HelperConfig::$core['admin_page_types'];
            $this->P->cb_customdata['admin_page_groups'] = HelperConfig::$core['admin_page_groups'];
            $aOptions = [''];
            foreach (HelperConfig::$navigation as $sKey => $aValue) {
                if ($sKey === 'admin') {
                    continue;
                }
                $aOptions[] = $sKey;
            }
            $this->P->cb_customdata['subnavarea_options'] = $aOptions;
            unset($aOptions);

            // show archived versions of this page
            if ($Ptoedit->oPayload->cl_id != NULL) {

                $dbal = $this->serviceManager->get('dbal');

                /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
                $queryBuilder = $dbal->createQueryBuilder();
                $queryBuilder
                    ->select('*')
                    ->from('content_lang_archive')
                    ->where('cl_id = ?')
                    ->andWhere('cl_lang = ?')
                    ->setParameter(0, $Ptoedit->oPayload->cl_id)
                    ->setParameter(1, HelperConfig::$lang)
                    ->orderBy('cla_timestamp', 'DESC')
                ;
                $statement = $queryBuilder->execute();
                $iArchivedRows = $statement->rowCount();

                if ($iArchivedRows > 0) {
                    $aListSetting = [
                        ['title' => 'cla_timestamp', 'key' => 'cla_timestamp', 'width' => '15%', 'linked' => false,],
                        ['title' => 'cl_html', 'key' => 'cl_html', 'width' => '40%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                        ['title' => 'cl_keywords', 'key' => 'cl_keywords', 'width' => '15%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                        ['title' => 'cl_description', 'key' => 'cl_description', 'width' => '15%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                        ['title' => 'cl_title', 'key' => 'cl_title', 'width' => '15%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                    ];
                    $aData = $statement->fetchAll();
                    $this->P->cb_customdata['archived_list'] = \HaaseIT\Toolbox\Tools::makeListtable(
                        $aListSetting,
                        $aData,
                        $this->serviceManager->get('twig')
                    );
                }
            }
        } else {
            die(HardcodedText::get('pageadmin_exception_pagenotfound'));
        }
    }

    protected function updatePage(UserPage $Ptoedit)
    {
        $purifier = false;
        if (HelperConfig::$core['pagetext_enable_purifier']) {
            $purifier = \HaaseIT\HCSF\Helper::getPurifier('page');
        }

        $Ptoedit->cb_pagetype = $this->post['page_type'];
        $Ptoedit->cb_group = $this->post['page_group'];
        $Ptoedit->cb_pageconfig = $this->post['page_config'];
        $Ptoedit->cb_subnav = $this->post['page_subnav'];
        $Ptoedit->purifier = $purifier;
        $Ptoedit->write();

        if ($Ptoedit->oPayload->cl_id != NULL) {
            $Ptoedit->oPayload->cl_html = $this->post['page_html'];
            $Ptoedit->oPayload->cl_title = $this->post['page_title'];
            $Ptoedit->oPayload->cl_description = $this->post['page_description'];
            $Ptoedit->oPayload->cl_keywords = $this->post['page_keywords'];
            $Ptoedit->oPayload->purifier = $purifier;
            $Ptoedit->oPayload->write();
        }

        $Ptoedit = new UserPage($this->serviceManager, $_REQUEST['page_key'], true);
        $this->P->cb_customdata['updated'] = true;

        return $Ptoedit;
    }
}