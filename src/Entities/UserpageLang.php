<?php

namespace HaaseIT\HCSF\Entities;


/**
 * @Entity
 * @Table(name="content_lang")
 */
class UserpageLang
{
    /**
     * @Id @Column(type="integer", name="cl_id")
     * @GeneratedValue
     * @var integer
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="HaaseIT\HCSF\Entities\UserpageBase", inversedBy="id")
     * @JoinColumn(name="cl_cb", referencedColumnName="cb_id")
     * @var integer
     **/
    protected $basepage;

    /**
     * @Column(length=2, name="cl_lang")
     * @var string
     */
    protected $language;

    /**
     * @Column(type="text", name="cl_html")
     * @var string
     */
    protected $html;

    /**
     * @Column(type="text", name="cl_keywords")
     * @var string
     */
    protected $keywords;

    /**
     * @Column(type="text", name="cl_description")
     * @var string
     */
    protected $description;

    /**
     * @Column(length=255, name="cl_title")
     * @var string
     */
    protected $title;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getBasepage()
    {
        return $this->basepage;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param string $html
     */
    public function setHtml($html)
    {
        $this->html = $html;
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
}