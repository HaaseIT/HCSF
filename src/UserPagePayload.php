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


use Zend\ServiceManager\ServiceManager;

class UserPagePayload extends PagePayload
{
    /**
     * @var int|string
     */
    public $cl_id;

    /**
     * @var int|string
     */
    public $cl_cb;

    /**
     * @var string
     */
    public $cl_lang;

    /**
     * @var \HTMLPurifier
     */
    public $purifier;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $dbal;

    /**
     * UserPagePayload constructor.
     * @param ServiceManager $serviceManager
     * @param $iParentID
     * @param bool $bReturnRaw
     * @param UserPage $basePage
     */
    public function __construct(ServiceManager $serviceManager, $iParentID, $bReturnRaw = false, UserPage $basePage = null) {
        parent::__construct($serviceManager);

        $this->dbal = $this->serviceManager->get('dbal');

        if ($iParentID !== '/_misc/index.html') { // no need to fetch from db if this is the itemsearch page
            $querybuilder = $this->dbal->createQueryBuilder();
            $querybuilder
                ->select('cl_id, cl_cb, cl_lang, cl_html, cl_keywords, cl_description, cl_title')
                ->from('content_lang')
                ->where('cl_cb = ?')
                ->andWhere('cl_lang = ?')
                ->setParameter(0, $iParentID)
                ->setParameter(1, $this->config->getLang())
            ;
            $stmt = $querybuilder->execute();
            $stmt->setFetchMode(\PDO::FETCH_INTO, $this);

            if ($stmt->rowCount() === 1) {
                $stmt->fetch();
            } elseif (!$bReturnRaw) { // if raw data is required, don't try to fetch default lang data
                // if the current language data is not available, lets see if we can get the default languages data
                $lang_available = $this->config->getCore('lang_available');
                $querybuilder
                    ->setParameter(0, $iParentID)
                    ->setParameter(1, key($lang_available))
                ;
                $stmt = $querybuilder->execute();
                $stmt->setFetchMode(\PDO::FETCH_INTO, $this);

                if ($stmt->rowCount() === 1) {
                    $stmt->fetch();
                }
            }

            // if this page is set to load from file and loading from file is allowed, try to load it from file.
            // if file is not available, fall back to db content
            if (!$bReturnRaw && $this->config->getCore('allow_pages_from_file') && $basePage->cb_html_from_file === 'Y') {
                $filePath = PATH_BASEDIR . 'customization/pages/' . $this->config->getLang() . $basePage->cb_key;
                if (is_file($filePath)) {
                    $this->cl_html = file_get_contents($filePath);
                }
            }
        }
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function write() {
        $querybuilder = $this->dbal->createQueryBuilder();
        $querybuilder
            ->update('content_lang')
            ->set('cl_html', '?')
            ->set('cl_title', '?')
            ->set('cl_description', '?')
            ->set('cl_keywords', '?')
            ->where('cl_id = ?')
            ->setParameter(0, !empty($this->purifier) ? $this->purifier->purify($this->cl_html) : $this->cl_html)
            ->setParameter(1, filter_var($this->cl_title, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
            ->setParameter(2, filter_var($this->cl_description, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
            ->setParameter(3, filter_var($this->cl_keywords, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW))
            ->setParameter(4, $this->cl_id)
        ;

        return $querybuilder->execute();
    }

    /**
     * @param int $iParentID
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function insert($iParentID) {
        $querybuilder = $this->dbal->createQueryBuilder();
        $querybuilder
            ->insert('content_lang')
            ->setValue('cl_cb', '?')
            ->setValue('cl_lang', '?')
            ->setParameter(0, $iParentID)
            ->setParameter(1, $this->config->getLang())
        ;

        return $querybuilder->execute();
    }

    /**
     * @param $sParentID
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function remove($sParentID) {
        $queryBuilder = $this->dbal->createQueryBuilder();
        $queryBuilder
            ->delete('content_lang')
            ->where('cl_cb = '.$queryBuilder->createNamedParameter($sParentID))
        ;

        return $queryBuilder->execute();
    }
}
