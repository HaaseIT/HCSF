<?php

namespace HaaseIT\HCSF\Entities\Customer;

/**
 * @Entity
 * @Table(name="customer")
 */
class Customer
{
    /**
     * @Id @Column(type="integer", name="cust_id")
     * @GeneratedValue
     * @var integer
     */
    protected $id;

    /**
     * @Column(length=10, name="cust_no")
     * @var string
     */
    protected $no;

    /**
     * @Column(length=128, name="cust_email")
     * @var string
     */
    protected $email;

    /**
     * @Column(length=128, name="cust_corp")
     * @var string
     */
    protected $corp;

    /**
     * @Column(length=128, name="cust_name")
     * @var string
     */
    protected $name;

    /**
     * @Column(length=256, name="cust_street")
     * @var string
     */
    protected $street;

    /**
     * @Column(length=10, name="cust_zip")
     * @var string
     */
    protected $zip;

    /**
     * @Column(length=128, name="cust_town")
     * @var string
     */
    protected $town;

    /**
     * @Column(length=32, name="cust_phone")
     * @var string
     */
    protected $phone;

    /**
     * @Column(length=32, name="cust_cellphone")
     * @var string
     */
    protected $cellphone;

    /**
     * @Column(length=32, name="cust_fax")
     * @var string
     */
    protected $fax;

    /**
     * @Column(length=32, name="cust_country")
     * @var string
     */
    protected $country;

    /**
     * @Column(length=16, name="cust_group")
     * @var string
     */
    protected $group;

    /**
     * @Column(length=255, name="cust_password")
     * @var string
     */
    protected $password;

    /**
     * @Column(length=1, name="cust_active")
     * @var string
     */
    protected $active;

    /**
     * @Column(length=1, name="cust_emailverified")
     * @var string
     */
    protected $emailverified;

    /**
     * @Column(length=32, name="cust_emailverificationcode")
     * @var string
     */
    protected $emailverificationcode;

    /**
     * @Column(length=1, name="cust_tosaccepted")
     * @var string
     */
    protected $tosaccepted;

    /**
     * @Column(length=1, name="cust_cancellationdisclaimeraccepted")
     * @var string
     */
    protected $cancellationdisclaimeraccepted;

    /**
     * @Column(type="integer", name="cust_registrationtimestamp")
     * @var integer
     */
    protected $registrationtimestamp;

    /**
     * @Column(length=32, name="cust_pwresetcode")
     * @var string
     */
    protected $pwresetcode;

    /**
     * @Column(type="integer", name="cust_pwresettimestamp")
     * @var integer
     */
    protected $pwresettimestamp;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getNo()
    {
        return $this->no;
    }

    /**
     * @param string $no
     */
    public function setNo($no)
    {
        $this->no = $no;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getCorp()
    {
        return $this->corp;
    }

    /**
     * @param string $corp
     */
    public function setCorp($corp)
    {
        $this->corp = $corp;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * @return string
     */
    public function getTown()
    {
        return $this->town;
    }

    /**
     * @param string $town
     */
    public function setTown($town)
    {
        $this->town = $town;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getCellphone()
    {
        return $this->cellphone;
    }

    /**
     * @param string $cellphone
     */
    public function setCellphone($cellphone)
    {
        $this->cellphone = $cellphone;
    }

    /**
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * @param string $fax
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
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
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param string $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return string
     */
    public function getEmailverified()
    {
        return $this->emailverified;
    }

    /**
     * @param string $emailverified
     */
    public function setEmailverified($emailverified)
    {
        $this->emailverified = $emailverified;
    }

    /**
     * @return string
     */
    public function getEmailverificationcode()
    {
        return $this->emailverificationcode;
    }

    /**
     * @param string $emailverificationcode
     */
    public function setEmailverificationcode($emailverificationcode)
    {
        $this->emailverificationcode = $emailverificationcode;
    }

    /**
     * @return string
     */
    public function getTosaccepted()
    {
        return $this->tosaccepted;
    }

    /**
     * @param string $tosaccepted
     */
    public function setTosaccepted($tosaccepted)
    {
        $this->tosaccepted = $tosaccepted;
    }

    /**
     * @return string
     */
    public function getCancellationdisclaimeraccepted()
    {
        return $this->cancellationdisclaimeraccepted;
    }

    /**
     * @param string $cancellationdisclaimeraccepted
     */
    public function setCancellationdisclaimeraccepted($cancellationdisclaimeraccepted)
    {
        $this->cancellationdisclaimeraccepted = $cancellationdisclaimeraccepted;
    }

    /**
     * @return integer
     */
    public function getRegistrationtimestamp()
    {
        return $this->registrationtimestamp;
    }

    /**
     * @param integer $registrationtimestamp
     */
    public function setRegistrationtimestamp($registrationtimestamp)
    {
        $this->registrationtimestamp = $registrationtimestamp;
    }

    /**
     * @return string
     */
    public function getPwresetcode()
    {
        return $this->pwresetcode;
    }

    /**
     * @param string $pwresetcode
     */
    public function setPwresetcode($pwresetcode)
    {
        $this->pwresetcode = $pwresetcode;
    }

    /**
     * @return integer
     */
    public function getPwresettimestamp()
    {
        return $this->pwresettimestamp;
    }

    /**
     * @param integer $pwresettimestamp
     */
    public function setPwresettimestamp($pwresettimestamp)
    {
        $this->pwresettimestamp = $pwresettimestamp;
    }
}