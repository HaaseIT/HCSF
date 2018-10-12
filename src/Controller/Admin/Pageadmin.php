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


use HaaseIT\HCSF\UserPage;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Pageadmin
 * @package HaaseIT\HCSF\Controller\Admin
 */
class Pageadmin extends Base
{
    /**
     * @var \HaaseIT\HCSF\HardcodedText
     */
    private $hardcodedtextcats;

    /**
     * Pageadmin constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->hardcodedtextcats = $serviceManager->get('hardcodedtextcats');
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager, [], 'admin/base.twig');
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        $this->P->cb_customcontenttemplate = 'pageadmin';

        // adding language to page here
        if (filter_input(INPUT_GET, 'action') === 'insert_lang') {
            $this->insertLang();
        }

        $getaction = filter_input(INPUT_GET, 'action');
        if ($getaction === null) {
            $this->P->cb_customdata['pageselect'] = $this->showPageselect();
        } elseif (!empty(filter_input(INPUT_GET, 'page_key')) && ($getaction === 'edit' || $getaction === 'delete')) {
            if ($getaction === 'delete' && filter_input(INPUT_POST, 'delete') === 'do') {
                $this->handleDeletePage();
            } else { // edit or update page
                $this->handleEditPage();
            }
        } elseif ($getaction === 'addpage') {
            $this->handleAddPage();
        }
    }

    protected function handleDeletePage()
    {
        // delete and put message in customdata
        $Ptodelete = new UserPage($this->serviceManager, filter_input(INPUT_GET, 'page_key', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW), true);
        if ($Ptodelete->cb_id != NULL) {
            $Ptodelete->remove();
        } else {
            $this->helper->terminateScript($this->hardcodedtextcats->get('pageadmin_exception_pagetodeletenotfound'));
        }
        $this->P->cb_customdata['deleted'] = true;
    }

    protected function handleAddPage()
    {
        $aErr = [];
        if (filter_input(INPUT_POST, 'addpage') === 'do') {
            $sPagekeytoadd = trim(filter_input(INPUT_POST, 'pagekey', FILTER_SANITIZE_SPECIAL_CHARS));

            if (mb_substr($sPagekeytoadd, 0, 2) === '/_') {
                $aErr['reservedpath'] = true;
            } elseif (strlen($sPagekeytoadd) < 4) {
                $aErr['keytooshort'] = true;
            } else {
                $Ptoadd = new UserPage($this->serviceManager, $sPagekeytoadd, true);
                if ($Ptoadd->cb_id == NULL) {
                    if ($Ptoadd->insert($sPagekeytoadd)) {
                        $this->helper->redirectToPage('/_admin/pageadmin.html?page_key='.$sPagekeytoadd.'&action=edit');
                    } else {
                        $this->helper->terminateScript($this->hardcodedtextcats->get('pageadmin_exception_couldnotinsertpage'));
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
        $adminpagegroups = $this->config->getCore('admin_page_groups');
        foreach ($adminpagegroups as $sValue) {
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
        $Ptoinsertlang = new UserPage(
            $this->serviceManager,
            filter_input(INPUT_GET, 'page_key', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            true)
        ;

        if ($Ptoinsertlang->cb_id != NULL && $Ptoinsertlang->oPayload->cl_id == NULL) {
            $Ptoinsertlang->oPayload->insert($Ptoinsertlang->cb_id);
            $this->helper->redirectToPage('/_admin/pageadmin.html?page_key='.$Ptoinsertlang->cb_key.'&action=edit');
        } else {
            $this->helper->terminateScript($this->hardcodedtextcats->get('pageadmin_exception_couldnotinsertlang'));
        }
    }

    protected function handleEditPage()
    {
        $requestpagekey = filter_input(INPUT_GET, 'page_key', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if ($requestpagekey !== null && $Ptoedit = new UserPage($this->serviceManager, $requestpagekey, true)) {
            if (filter_input(INPUT_POST, 'action_a') === 'true') {
                $Ptoedit = $this->updatePage($Ptoedit);
            }
            $this->P->cb_customdata['page'] = $Ptoedit;
            $this->P->cb_customdata['admin_page_types'] = $this->config->getCore('admin_page_types');
            $this->P->cb_customdata['admin_page_groups'] = $this->config->getCore('admin_page_groups');
            $this->P->cb_customdata['allow_page_from_file'] = $this->config->getCore('allow_pages_from_file');
            $aOptions = [''];
            $navigation = $this->config->getNavigation();
            foreach ($navigation as $sKey => $aValue) {
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
                    ->setParameter(1, $this->config->getLang())
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
            $this->helper->terminateScript($this->hardcodedtextcats->get('pageadmin_exception_pagenotfound'));
        }
    }

    protected function updatePage(UserPage $Ptoedit)
    {
        $purifier = false;
        if ($this->config->getCore('pagetext_enable_purifier')) {
            $purifier = $this->helper->getPurifier('page');
        }

        $Ptoedit->cb_html_from_file = false;
        if ($this->config->getCore('allow_pages_from_file')) {
            $htmlFromFile = filter_input(INPUT_POST, 'page_from_file', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            if ($htmlFromFile == 'y') {
                $Ptoedit->cb_html_from_file = true;
            }
        }

        $Ptoedit->cb_pagetype = filter_input(INPUT_POST, 'page_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        $Ptoedit->cb_group = filter_input(INPUT_POST, 'page_group', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        $Ptoedit->cb_pageconfig = filter_input(INPUT_POST, 'page_config', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_NO_ENCODE_QUOTES);
        $Ptoedit->cb_subnav = filter_input(INPUT_POST, 'page_subnav', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        $Ptoedit->purifier = $purifier;
        $Ptoedit->write();

        if ($Ptoedit->oPayload->cl_id != NULL) {
            $Ptoedit->oPayload->cl_html = filter_input(INPUT_POST, 'page_html');
            $Ptoedit->oPayload->cl_title = filter_input(INPUT_POST, 'page_title', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            $Ptoedit->oPayload->cl_description = filter_input(INPUT_POST, 'page_description', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            $Ptoedit->oPayload->cl_keywords = filter_input(INPUT_POST, 'page_keywords', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            $Ptoedit->oPayload->purifier = $purifier;
            $Ptoedit->oPayload->write();
        }

        $Ptoedit = new UserPage(
            $this->serviceManager,
            filter_input(INPUT_GET, 'page_key', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            true
        );
        $this->P->cb_customdata['updated'] = true;

        return $Ptoedit;
    }
}
