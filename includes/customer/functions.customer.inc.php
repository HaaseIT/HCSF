<?php

function handlePasswordReset($DB, $C, $aErr, $iID) {
    if (isset($_POST["pwd"]) && trim($_POST["pwd"]) != '') {
        if (strlen($_POST["pwd"]) < $C["minimum_length_password"] || strlen($_POST["pwd"]) > $C["maximum_length_password"]) $aErr[] = 'pwlength';
        if ($_POST["pwd"] != $_POST["pwdc"]) $aErr[] = 'pwmatch';
        if (count($aErr) == 0) {
            $sEnc = crypt($_POST["pwd"], $C["blowfish_salt"]);
            $aData = array(
                DB_CUSTOMERFIELD_PASSWORD => $sEnc,
                DB_CUSTOMERFIELD_PWRESETCODE => '',
                DB_CUSTOMERTABLE_PKEY => $iID,
            );
            //debug($aData, false, '$aData');
            $sQ = \HaaseIT\Tools::buildPSUpdateQuery($aData, DB_CUSTOMERTABLE, DB_CUSTOMERTABLE_PKEY);
            //debug($sQ);
            $hResult = $DB->prepare($sQ);
            foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
            $hResult->execute();
        }
    } else {
        $aErr[] = 'nopw';
    }

    return $aErr;
}

function handleForgotPassword($DB, $C, $aErr) {
    if (!\HaaseIT\Tools::validateEmail($_GET["email"])) {
        $aErr[] = 'emailinvalid';
    } else {
        $sQ = "SELECT * FROM ".DB_CUSTOMERTABLE." WHERE ".DB_CUSTOMERFIELD_EMAIL." = :email";
        $hResult = $DB->prepare($sQ);
        $hResult->bindValue(':email', $_GET["email"], PDO::PARAM_STR);
        $hResult->execute();
        if ($hResult->rowCount() != 1) {
            $aErr[] = 'emailunknown';
        } else {
            $aResult = $hResult->fetch();
            //debug($aResult, false, '$aResult');
            $iTimestamp = time();
            if ($iTimestamp - HOUR < $aResult[DB_CUSTOMERFIELD_PWRESETTIMESTAMP]) { // 1 hour delay between requests
                $aErr[] = 'pwresetstilllocked';
            } else {
                $sResetCode = md5($aResult[DB_CUSTOMERFIELD_EMAIL].$iTimestamp);
                $aData = array(
                    DB_CUSTOMERFIELD_PWRESETCODE => $sResetCode,
                    DB_CUSTOMERFIELD_PWRESETTIMESTAMP => $iTimestamp,
                    DB_CUSTOMERTABLE_PKEY => $aResult[DB_CUSTOMERTABLE_PKEY],
                );
                //debug($aData, false, '$aData');
                $sQ = \HaaseIT\Tools::buildPSUpdateQuery($aData, DB_CUSTOMERTABLE, DB_CUSTOMERTABLE_PKEY);
                //debug($sQ);
                $hResult = $DB->prepare($sQ);
                foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
                $hResult->execute();

                $sTargetAddress = $aResult[DB_CUSTOMERFIELD_EMAIL];
                $sSubject = T("forgotpw_mail_subject");
                $sMessage = T("forgotpw_mail_text1");
                $sMessage .= "<br><br>".'<a href="http'.(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on' ? 's' : '').'://';
                $sMessage .= $_SERVER["HTTP_HOST"].'/_misc/rp.html?key='.$sResetCode.'&amp;email='.$sTargetAddress.'">';
                $sMessage .= 'http'.(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on' ? 's' : '').'://';
                $sMessage .= $_SERVER["HTTP_HOST"].'/_misc/rp.html?key='.$sResetCode.'&amp;email='.$sTargetAddress.'</a>';
                $sMessage .= '<br><br>'.T("forgotpw_mail_text2");

                mailWrapper($sTargetAddress, $C["email_sendername"], $C["email_sender"], $sSubject, $sMessage);
            }
        }
    }

    return $aErr;
}

function validateCustomerForm($aErr = array(), $bEdit = false)
{
    global $C, $sLang;

    if (!isset($_POST["email"]) || !\HaaseIT\Tools::validateEmail($_POST["email"])) $aErr["email"] = true;
    if ($C["validate_corpname"] && (!isset($_POST["corpname"]) || strlen(trim($_POST["corpname"])) < 3)) $aErr["corpname"] = true;
    if ($C["validate_name"] && (!isset($_POST["name"]) || strlen(trim($_POST["name"])) < 3)) $aErr["name"] = true;
    if ($C["validate_street"] && (!isset($_POST["street"]) || strlen(trim($_POST["street"])) < 3)) $aErr["street"] = true;
    if ($C["validate_zip"] && (!isset($_POST["zip"]) || strlen(trim($_POST["zip"])) < 4)) $aErr["zip"] = true;
    if ($C["validate_town"] && (!isset($_POST["town"]) || strlen(trim($_POST["town"])) < 3)) $aErr["town"] = true;
    if ($C["validate_phone"] && (!isset($_POST["phone"]) || strlen(trim($_POST["phone"])) < 6)) $aErr["phone"] = true;
    if ($C["validate_cellphone"] && (!isset($_POST["cellphone"]) || strlen(trim($_POST["cellphone"])) < 11)) $aErr["cellphone"] = true;
    if ($C["validate_fax"] && (!isset($_POST["fax"]) || strlen(trim($_POST["fax"]))) < 6) $aErr["fax"] = true;
    if ($C["validate_country"] && (!isset($_POST["country"]) || !isset($C["countries_".$sLang][$_POST["country"]]))) $aErr["country"] = true;
    if (!$bEdit && (!isset($_POST["tos"]) || $_POST["tos"] != 'y')) $aErr["tos"] = true;
    if (!$bEdit && (!isset( $_POST["cancellationdisclaimer"] ) || $_POST["cancellationdisclaimer"] != 'y')) $aErr["cancellationdisclaimer"] = true;

    if (!$bEdit || (isset($_POST["pwd"]) && trim($_POST["pwd"]) != '')) {
        if (strlen($_POST["pwd"]) < $C["minimum_length_password"] || strlen($_POST["pwd"]) > $C["maximum_length_password"]) $aErr["passwordlength"] = true;
        if ($_POST["pwd"] != $_POST["pwdc"]) $aErr["passwordmatch"] = true;
    }

    return $aErr;
}

function getDefaultCountryByConfig($C, $sLang) {
    if (isset($C["defaultcountrybylang"][$sLang])) {
        $sDefaultCountryByConfig = $C["defaultcountrybylang"][$sLang];
    } else {
        $sDefaultCountryByConfig = '';
    }
    return $sDefaultCountryByConfig;
}

function getCustomerFormDefaultValue($sKeyConfig, $sKeyForm, $aUserData) {
    $sDefaultValue = getUserData($sKeyConfig, $aUserData);
    if (!$sDefaultValue && isset($_SESSION["formsave_addrform"][$sKeyForm])) $sDefaultValue = $_SESSION["formsave_addrform"][$sKeyForm];

    return $sDefaultValue;
}

function buildCustomerForm($sPurpose = 'none', $sErr = '', $aUserData = false)
{
    // Purposes: shoppingcart, userhome, shopadmin, editprofile, register
    global $C, $sLang;

    $aData["purpose"] = $sPurpose;
    $aData["errormessage"] = $sErr;
    $aData["readonly"] = false;
    $aData["readonlycustno"] = false;
    if ($sPurpose == 'shopadmin') {
        $aData["readonly"] = true;
        $aData["readonlycustno"] = true;
    }
    if ($sPurpose == 'userhome') $aData["readonly"] = true;
    if ($sPurpose == 'editprofile' && !$C["allow_edituserprofile"]) $aData["readonly"] = true;
    if ($sPurpose == 'shoppingcart' && getUserData()) $aData["readonly"] = true;

    // fv = field_value, fr = field_required
    $sDefaultCustno = getCustomerFormDefaultValue(DB_CUSTOMERFIELD_NUMBER, "custno", $aUserData);
    $aData["fv_custno"] = \HaaseIT\Tools::getFormField('custno', $sDefaultCustno, true);

    $sDefaultEmail = getCustomerFormDefaultValue(DB_CUSTOMERFIELD_EMAIL, "email", $aUserData);
    $aData["fv_email"] = \HaaseIT\Tools::getFormField('email', $sDefaultEmail, true);

    $sDefaultCorpname = getCustomerFormDefaultValue(DB_CUSTOMERFIELD_CORP, "corpname", $aUserData);
    $aData["fv_corpname"] = \HaaseIT\Tools::getFormField('corpname', $sDefaultCorpname, true);
    $aData["fr_corpname"] = $C["validate_corpname"];

    $sDefaultName = getCustomerFormDefaultValue(DB_CUSTOMERFIELD_NAME, "name", $aUserData);
    $aData["fv_name"] = \HaaseIT\Tools::getFormField('name', $sDefaultName, true);
    $aData["fr_name"] = $C["validate_name"];

    $sDefaultStreet = getCustomerFormDefaultValue(DB_CUSTOMERFIELD_STREET, "street", $aUserData);
    $aData["fv_street"] = \HaaseIT\Tools::getFormField('street', $sDefaultStreet, true);
    $aData["fr_street"] = $C["validate_street"];

    $sDefaultZip = getCustomerFormDefaultValue(DB_CUSTOMERFIELD_ZIP, "zip", $aUserData);
    $aData["fv_zip"] = \HaaseIT\Tools::getFormField('zip', $sDefaultZip, true);
    $aData["fr_zip"] = $C["validate_zip"];

    $sDefaultTown = getCustomerFormDefaultValue(DB_CUSTOMERFIELD_TOWN, "town", $aUserData);
    $aData["fv_town"] = \HaaseIT\Tools::getFormField('town', $sDefaultTown, true);
    $aData["fr_town"] = $C["validate_town"];

    $sDefaultPhone = getCustomerFormDefaultValue(DB_CUSTOMERFIELD_PHONE, "phone", $aUserData);
    $aData["fv_phone"] = \HaaseIT\Tools::getFormField('phone', $sDefaultPhone, true);
    $aData["fr_phone"] = $C["validate_phone"];

    $sDefaultCellphone = getCustomerFormDefaultValue(DB_CUSTOMERFIELD_CELLPHONE, "cellphone", $aUserData);
    $aData["fv_cellphone"] = \HaaseIT\Tools::getFormField('cellphone', $sDefaultCellphone, true);
    $aData["fr_cellphone"] = $C["validate_cellphone"];

    $sDefaultFax = getCustomerFormDefaultValue(DB_CUSTOMERFIELD_FAX, "fax", $aUserData);
    $aData["fv_fax"] = \HaaseIT\Tools::getFormField('fax', $sDefaultFax, true);
    $aData["fr_fax"] = $C["validate_fax"];

    $sDefaultCountryByConfig = getDefaultCountryByConfig($C, $sLang);
    $sDefaultCountry = getCustomerFormDefaultValue(DB_CUSTOMERFIELD_COUNTRY, "country", $aUserData);
    $aData["fv_country"] = \HaaseIT\Tools::getFormField('country', ($sDefaultCountry ? $sDefaultCountry : $sDefaultCountryByConfig), true);
    $aData["fr_country"] = $C["validate_country"];

    if ($sPurpose == 'admin') {
        $aData["fv_custgroups"] = $C["customer_groups"];
        $aData["fv_custgroup_selected"] = \HaaseIT\Tools::getFormField('custgroup', getUserData(DB_CUSTOMERFIELD_GROUP, $aUserData), true);
    } elseif ($sPurpose == 'shopadmin') {
        if (isset($C["customer_groups"][getUserData(DB_CUSTOMERFIELD_GROUP, $aUserData)])) {
            $aData["fv_custgroup"] = $C["customer_groups"][getUserData(DB_CUSTOMERFIELD_GROUP, $aUserData)];
        } else {
            $aData["fv_custgroup"] = '';
        }
    }

    if ($sPurpose == 'admin' || $sPurpose == 'register' || $sPurpose == 'editprofile') {
        $aData["fv_pwd"] = (($sPurpose == 'admin' || $sPurpose == 'editprofile') ? '' : \HaaseIT\Tools::getFormField('pwd', ''));
        $aData["fv_pwdc"] = (($sPurpose == 'admin' || $sPurpose == 'editprofile') ? '' : \HaaseIT\Tools::getFormField('pwdc', ''));
    }

    if ($sPurpose == 'shoppingcart') {
        $sRememberedRemarks = '';
        if (isset($_SESSION["formsave_addrform"]["remarks"])) {
            $sRememberedRemarks = $_SESSION["formsave_addrform"]["remarks"];
        }
        $aData["fv_remarks"] = \HaaseIT\Tools::getFormField('remarks', $sRememberedRemarks, true);
    }

    if ($sPurpose == 'shoppingcart' || $sPurpose == 'register') {
        if (!getUserData()) {
            $aData["fv_tos"] = \HaaseIT\Tools::getCheckbox('tos', 'y');
            $aData["fv_cancellationdisclaimer"] = \HaaseIT\Tools::getCheckbox('cancellationdisclaimer', 'y');
        }
    }

    if ($sPurpose == 'shoppingcart') {
        $aData["fv_paymentmethods"] = $C["paymentmethods"];
        $aData["fv_paymentmethod"] = \HaaseIT\Tools::getFormField('paymentmethod', '');
    }

    if ($sPurpose == 'admin') {
        $aData["fv_active"] = ((getUserData(DB_CUSTOMERFIELD_ACTIVE, $aUserData) == 'y') ? true : false);
        $aData["fv_emailverified"] = ((getUserData(DB_CUSTOMERFIELD_EMAILVERIFIED, $aUserData) == 'y') ? true : false);
    }
    //debug($aData);
    return $aData;
}

function sendVerificationMail($sEmailVerificationcode, $sTargetAddress, $bCust = false)
{
    global $C;
    if ($bCust) {
        $sSubject = T("register_mail_emailverification_subject");
        $sMessage = T("register_mail_emailverification_text1");
        $sMessage .= "<br><br>".'<a href="http'.(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on' ? 's' : '').'://';
        $sMessage .= $_SERVER["HTTP_HOST"].'/_misc/verifyemail.html?key='.$sEmailVerificationcode.'">';
        $sMessage .= 'http'.(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on' ? 's' : '').'://';
        $sMessage .= $_SERVER["HTTP_HOST"].'/_misc/verifyemail.html?key='.$sEmailVerificationcode.'</a>';
        $sMessage .= '<br><br>'.T("register_mail_emailverification_text2");
    }
    else {
        $sSubject = 'Neue Kundenregistrierung';
        $sMessage = 'Von: '.$sTargetAddress.'<br>Am:'.date("d.m.Y H:i:s");
        $sTargetAddress = $C["email_sender"];
    }

    mailWrapper($sTargetAddress, $C["email_sendername"], $C["email_sender"], $sSubject, $sMessage);
}

function getUserData($sField = '', $aUserdata = false)
{
    if (!$aUserdata) {
        if (!isset($_SESSION["user"]) || !is_array($_SESSION["user"])) return false;
        elseif ($sField == '') return true;

        if ($sField != '' && isset($_SESSION["user"][$sField]) && $_SESSION["user"][$sField] != '') return $_SESSION["user"][$sField];
        //debug($sField);
        //debug($_SESSION["user"]);
    } else {
        if (isset($aUserdata[$sField])) return $aUserdata[$sField];
        elseif ($sField = '') return false;
    }
}

function handleLogout()
{
    session_destroy();
    $sH= '';
    $sH .= T("login_logout_success");
    return $sH;
}

function getLogin()
{
    global $C, $DB;
    $bTryEmail = false;
    if (DB_CUSTOMERFIELD_USER != DB_CUSTOMERFIELD_EMAIL) $bTryEmail = true;
    $sEnc = crypt($_POST["password"], $C["blowfish_salt"]);
    $sQ = "SELECT * FROM ".DB_CUSTOMERTABLE." WHERE ";
    if ($bTryEmail) $sQ .= "(";
    $sQ .= DB_CUSTOMERFIELD_USER." = :user";
    if ($bTryEmail) $sQ .= " OR ".DB_CUSTOMERFIELD_EMAIL." = :email) ";
    $sQ .= " AND ";
    if ($bTryEmail) $sQ .= "(";
    $sQ .= DB_CUSTOMERFIELD_USER." != ''";
    if ($bTryEmail) $sQ .= " OR ".DB_CUSTOMERFIELD_EMAIL." != '')";
    $sQ .= " AND ".DB_CUSTOMERFIELD_PASSWORD." = :pwd ";

    $hResult = $DB->prepare($sQ);
    $hResult->bindValue(':user', $_POST["user"], PDO::PARAM_STR);
    if ($bTryEmail) {
        $hResult->bindValue(':email', $_POST["user"], PDO::PARAM_STR);
    }
    $hResult->bindValue(':pwd', $sEnc, PDO::PARAM_STR);
    $hResult->execute();
    //debug($_POST);
    //debug($sQ);
    //debug($sEnc);

    $iRows = $hResult->rowCount();
    if($iRows == 1) {
        $aRow = $hResult->fetch();
        //debug($aRow);
        if ($aRow[DB_CUSTOMERFIELD_ACTIVE] == 'y' && $aRow[DB_CUSTOMERFIELD_EMAILVERIFIED] == 'y' && $aRow[DB_CUSTOMERFIELD_TOSACCEPTED] == 'y') {
            $_SESSION["user"] = $aRow;
            $mGet["status"] = 'success';
        } elseif ($aRow[DB_CUSTOMERFIELD_TOSACCEPTED] == 'n') {
            $mGet["status"] = 'tosnotaccepted';
        } elseif ($aRow[DB_CUSTOMERFIELD_EMAILVERIFIED] == 'n') {
            $mGet["status"] = 'emailnotverified';
            $mGet["data"] = $aRow;
        } elseif ($aRow[DB_CUSTOMERFIELD_ACTIVE] == 'n') {
            $mGet["status"] = 'accountinactive';
        } else {
            $mGet = false;
        }
    } else {
        $mGet = false;
    }

    return $mGet;
}
