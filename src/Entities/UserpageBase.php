<?php

namespace HaaseIT\HCSF\Entities;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="content_base")
 */
class UserpageBase
{
    /**
     * @Id @Column(type="integer", name="cb_id")
     * @GeneratedValue
     * @var integer
     */
    protected $id;

    /**
     * @Column(length=80, name="cb_key")
     * @var string
     */
    protected $key;

    /**
     * @Column(length=80, name="cb_group")
     * @var string
     */
    protected $group;

    /**
     * @Column(length=16, name="cb_pagetype")
     * @var string
     */
    protected $pagetype;

    /**
     * @Column(type="text", name="cb_pageconfig")
     * @var string
     */
    protected $pageconfig;

    /**
     * @Column(length=32, name="cb_subnav")
     * @var string
     */
    protected $subnav;

    /**
     * @OneToMany(targetEntity="HaaseIT\HCSF\Entities\UserpageLang", mappedBy="basepage")
     * @var UserpageLang[]
     **/
    private $langpages = null;

    public function __construct()
    {
        $this->langpages = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getPagetype()
    {
        return $this->pagetype;
    }

    /**
     * @param string $pagetype
     */
    public function setPagetype($pagetype)
    {
        $this->pagetype = $pagetype;
    }

    /**
     * @return string
     */
    public function getPageconfig()
    {
        return $this->pageconfig;
    }

    /**
     * @param string $pageconfig
     */
    public function setPageconfig($pageconfig)
    {
        $this->pageconfig = $pageconfig;
    }

    /**
     * @return string
     */
    public function getSubnav()
    {
        return $this->subnav;
    }

    /**
     * @param string $subnav
     */
    public function setSubnav($subnav)
    {
        $this->subnav = $subnav;
    }

}