<?php

namespace HaaseIT\HCSF\Controller\Admin\Customer;

class Customeradmin extends Base
{
    private $CUA;
    
    public function __construct($C, $DB, $sLang, $twig)
    {
        parent::__construct($C, $DB, $sLang);
        $this->CUA = [
            ['title' => 'Nr.', 'key' => DB_CUSTOMERFIELD_NUMBER, 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',],
            ['title' => 'Firma', 'key' => DB_CUSTOMERFIELD_CORP, 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',],
            ['title' => 'Name', 'key' => DB_CUSTOMERFIELD_NAME, 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',],
            ['title' => 'Ort', 'key' => DB_CUSTOMERFIELD_TOWN, 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',],
            ['title' => 'Aktiv', 'key' => DB_CUSTOMERFIELD_ACTIVE, 'width' => '16%', 'linked' => false,'stylehead' => 'text-align: left;',],
            [
                'title' => 'bearb.',
                'key' => DB_CUSTOMERTABLE_PKEY,
                'width' => '16%',
                'linked' => true,
                'ltarget' => '/_admin/customeradmin.html',
                'lkeyname' => 'id',
                'lgetvars' => ['action' => 'edit',],
            ],
        ];
        $aPData = $this->handleCustomerAdmin($twig);
        $this->P->cb_customcontenttemplate = 'customer/customeradmin';
        $this->P->oPayload->cl_html = $aPData["customeradmin"]["text"];
        $this->P->cb_customdata = $aPData;
    }

    private function handleCustomerAdmin($twig)
    {
        $sType = 'all';
        if (isset($_REQUEST["type"])) {
            if ($_REQUEST["type"] == 'active') {
                $sType = 'active';
            } elseif ($_REQUEST["type"] == 'inactive') {
                $sType = 'inactive';
            }
        }
        $sH = '';
        if (!isset($_GET["action"])) {
            $sQ = "SELECT " . DB_ADDRESSFIELDS . " FROM " . DB_CUSTOMERTABLE;
            if ($sType == 'active') {
                $sQ .= " WHERE " . DB_CUSTOMERFIELD_ACTIVE . " = 'y'";
            } elseif ($sType == 'inactive') {
                $sQ .= " WHERE " . DB_CUSTOMERFIELD_ACTIVE . " = 'n'";
            }
            $sQ .= " ORDER BY " . DB_CUSTOMERFIELD_NUMBER . " ASC";
            //HaaseIT\Tools::debug($sQ);
            $hResult = $this->DB->query($sQ);
            //HaaseIT\Tools::debug($DB->error());
            //HaaseIT\Tools::debug($hResult->rowCount());
            if ($hResult->rowCount() != 0) {
                $aData = $hResult->fetchAll();
                //HaaseIT\Tools::debug($aData);
                $sH .= \HaaseIT\Tools::makeListtable($this->CUA, $aData, $twig);
            } else {
                $aInfo["nodatafound"] = true;
            }
        } elseif (isset($_GET["action"]) && $_GET["action"] == 'edit') {
            $iId = \filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $aErr = [];
            if (isset($_POST["doEdit"]) && $_POST["doEdit"] == 'yes') {
                $sCustno = filter_var(trim($_POST["custno"]), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                if (strlen($sCustno) < $this->C["minimum_length_custno"]) {
                    $aErr["custnoinvalid"] = true;
                } else {

                    $sQ = "SELECT " . DB_ADDRESSFIELDS . " FROM " . DB_CUSTOMERTABLE;
                    $sQ .= " WHERE " . DB_CUSTOMERTABLE_PKEY . " != :id";
                    $sQ .= " AND " . DB_CUSTOMERFIELD_NUMBER . " = :custno";
                    $hResult = $this->DB->prepare($sQ);
                    $hResult->bindValue(':id', $iId);
                    $hResult->bindValue(':custno', $sCustno);
                    $hResult->execute();
                    //HaaseIT\Tools::debug($sQ);
                    $iRows = $hResult->rowCount();
                    if ($iRows == 1) {
                        $aErr["custnoalreadytaken"] = true;
                    }
                    $sQ = "SELECT " . DB_ADDRESSFIELDS . " FROM " . DB_CUSTOMERTABLE;
                    $sQ .= " WHERE " . DB_CUSTOMERTABLE_PKEY . " != :id";
                    $sQ .= " AND " . DB_CUSTOMERFIELD_EMAIL . " = :email";
                    $hResult = $this->DB->prepare($sQ);
                    $hResult->bindValue(':id', $iId);
                    $hResult->bindValue(':email', \filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
                    $hResult->execute();
                    //HaaseIT\Tools::debug($sQ);
                    $iRows = $hResult->rowCount();
                    if ($iRows == 1) {
                        $aErr["emailalreadytaken"] = true;
                    }
                    $aErr = validateCustomerForm($this->C, $this->sLang, $aErr, true);
                    if (count($aErr) == 0) {
                        $aData = [
                            DB_CUSTOMERFIELD_NUMBER => $sCustno,
                            DB_CUSTOMERFIELD_EMAIL => \trim(\filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL)),
                            DB_CUSTOMERFIELD_CORP => \trim(\filter_input(INPUT_POST, 'corpname', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            DB_CUSTOMERFIELD_NAME => \trim(\filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            DB_CUSTOMERFIELD_STREET => \trim(\filter_input(INPUT_POST, 'street', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            DB_CUSTOMERFIELD_ZIP => \trim(\filter_input(INPUT_POST, 'zip', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            DB_CUSTOMERFIELD_TOWN => \trim(\filter_input(INPUT_POST, 'town', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            DB_CUSTOMERFIELD_PHONE => \trim(\filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            DB_CUSTOMERFIELD_CELLPHONE => \trim(\filter_input(INPUT_POST, 'cellphone', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            DB_CUSTOMERFIELD_FAX => \trim(\filter_input(INPUT_POST, 'fax', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            DB_CUSTOMERFIELD_COUNTRY => \trim(\filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            DB_CUSTOMERFIELD_GROUP => \trim(\filter_input(INPUT_POST, 'custgroup', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),
                            DB_CUSTOMERFIELD_EMAILVERIFIED => ((isset($_POST["emailverified"]) && $_POST["emailverified"] == 'y') ? 'y' : 'n'),
                            DB_CUSTOMERFIELD_ACTIVE => ((isset($_POST["active"]) && $_POST["active"] == 'y') ? 'y' : 'n'),
                            DB_CUSTOMERTABLE_PKEY => $iId,
                        ];
                        if (isset($_POST["pwd"]) && $_POST["pwd"] != '') {
                            $aData[DB_CUSTOMERFIELD_PASSWORD] = crypt($_POST["pwd"], $this->C["blowfish_salt"]);
                            $aInfo["passwordchanged"] = true;
                        }
                        //HaaseIT\Tools::debug($aData);
                        $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_CUSTOMERTABLE, DB_CUSTOMERTABLE_PKEY);
                        //HaaseIT\Tools::debug($sQ);
                        $hResult = $this->DB->prepare($sQ);
                        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':' . $sKey, $sValue);
                        $hResult->execute();
                        //HaaseIT\Tools::debug($hResult->errorInfo());
                        $aInfo["changeswritten"] = true;
                    }
                }
            }
            $sQ = "SELECT " . DB_ADDRESSFIELDS . " FROM " . DB_CUSTOMERTABLE;
            $sQ .= " WHERE " . DB_CUSTOMERTABLE_PKEY . " = :id";
            $hResult = $this->DB->prepare($sQ);
            $hResult->bindValue(':id', $iId);
            $hResult->execute();
            if ($hResult->rowCount() == 1) {
                $aUser = $hResult->fetch();
                //HaaseIT\Tools::debug($aUser);
                $aPData["customerform"] = buildCustomerForm($this->C, $this->sLang, 'admin', $aErr, $aUser);
            } else {
                $aInfo["nosuchuserfound"] = true;
            }
        }
        $aPData["customeradmin"]["text"] = $sH;
        $aPData["customeradmin"]["type"] = $sType;
        if (isset($aInfo)) $aPData["customeradmin"]["info"] = $aInfo;
        return $aPData;
    }


}