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


use HaaseIT\Toolbox\Tools;
use Zend\ServiceManager\ServiceManager;

class Textcatadmin extends Base
{
    /**
     * @var \HaaseIT\Toolbox\Textcat
     */
    private $textcats;

    /**
     * @var \HaaseIT\HCSF\HardcodedText
     */
    private $hardcodedtextcats;

    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->textcats = $serviceManager->get('textcats');
        $this->hardcodedtextcats = $serviceManager->get('hardcodedtextcats');
    }

    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager, [], 'admin/base.twig');
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        $this->P->cb_customcontenttemplate = 'textcatadmin';

        $return = '';

        $getaction = filter_input(INPUT_GET, 'action');
        if (empty($getaction)) {
            $aData = $this->textcats->getCompleteTextcatForCurrentLang();

            $aListSetting = [
                ['title' => $this->hardcodedtextcats->get('textcatadmin_list_title_key'), 'key' => 'tc_key', 'width' => '20%', 'linked' => false,],
                ['title' => $this->hardcodedtextcats->get('textcatadmin_list_title_text'), 'key' => 'tcl_text', 'width' => '80%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                [
                    'title' => $this->hardcodedtextcats->get('textcatadmin_list_title_edit'),
                    'key' => 'tc_id',
                    'width' => 35,
                    'linked' => true,
                    'ltarget' => '/_admin/textcatadmin.html',
                    'lkeyname' => 'id',
                    'lgetvars' => [
                        'action' => 'edit',
                    ],
                ],
            ];
            $return .= Tools::makeListtable($aListSetting, $aData, $this->serviceManager->get('twig'));
        } elseif ($getaction === 'edit' || $getaction === 'delete') {
            $getid = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            if ($getaction === 'delete' && filter_input(INPUT_POST, 'delete') === 'do') {
                $this->textcats->deleteText($getid);
                $this->P->cb_customdata['deleted'] = true;
            } else {
                $this->P->cb_customdata['edit'] = true;

                $this->textcats->initTextIfVoid($getid);

                // if post:edit is set, update
                if (filter_input(INPUT_POST, 'edit') === 'do') {
                    $this->textcats->purifier = false;
                    if ($this->config->getCore('textcat_enable_purifier')) {
                        $this->textcats->purifier = $this->helper->getPurifier('textcat');
                    }
                    $this->textcats->saveText(
                        filter_input(INPUT_POST, 'lid', FILTER_SANITIZE_NUMBER_INT),
                        filter_input(INPUT_POST, 'text')
                    );
                    $this->P->cb_customdata['updated'] = true;
                }

                $aData = $this->textcats->getSingleTextByID($getid);
                $this->P->cb_customdata['editform'] = [
                    'id' => $aData['tc_id'],
                    'lid' => $aData['tcl_id'],
                    'key' => $aData['tc_key'],
                    'lang' => $aData['tcl_lang'],
                    'text' => $aData['tcl_text'],
                ];

                // show archived versions of this textcat
                $dbal = $this->serviceManager->get('dbal');

                /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
                $queryBuilder = $dbal->createQueryBuilder();
                $queryBuilder
                    ->select('*')
                    ->from('textcat_lang_archive')
                    ->where('tcl_id = ?')
                    ->andWhere('tcl_lang = ?')
                    ->setParameter(0, $aData['tcl_id'])
                    ->setParameter(1, $this->config->getLang())
                    ->orderBy('tcla_timestamp', 'DESC')
                ;
                $statement = $queryBuilder->execute();
                $iArchivedRows = $statement->rowCount();

                if ($iArchivedRows > 0) {
                    $aListSetting = [
                        ['title' => 'tcla_timestamp', 'key' => 'tcla_timestamp', 'width' => '15%', 'linked' => false,],
                        ['title' => 'tcl_text', 'key' => 'tcl_text', 'width' => '85%', 'linked' => false, 'escapehtmlspecialchars' => true,],
                    ];
                    $aData = $statement->fetchAll();
                    $this->P->cb_customdata['archived_list'] = Tools::makeListtable($aListSetting,
                        $aData, $this->serviceManager->get('twig'));
                }
            }
        } elseif ($getaction === 'add') {
            $this->P->cb_customdata['add'] = true;
            if (filter_input(INPUT_POST, 'add') === 'do') {
                $postkey = filter_input(INPUT_POST, 'key', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                $this->P->cb_customdata['err'] = $this->textcats->verifyAddTextKey($postkey);

                if (count($this->P->cb_customdata['err']) == 0) {
                    $this->P->cb_customdata['addform'] = [
                        'key' => $postkey,
                        'id' => $this->textcats->addTextKey($postkey),
                    ];
                }
            }
        }

        $this->P->oPayload->cl_html = $return;
    }
}
