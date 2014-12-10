<?php

define("DB_CUSTOMERTABLE", 'customer');
define("DB_CUSTOMERTABLE_PKEY", 'cust_id');
define("DB_CUSTOMERFIELD_NUMBER", 'cust_no');
define("DB_CUSTOMERFIELD_USER", 'cust_no');
define("DB_CUSTOMERFIELD_EMAIL", 'cust_email');
define("DB_CUSTOMERFIELD_CORP", 'cust_corp');
define("DB_CUSTOMERFIELD_NAME", 'cust_name');
define("DB_CUSTOMERFIELD_STREET", 'cust_street');
define("DB_CUSTOMERFIELD_ZIP", 'cust_zip');
define("DB_CUSTOMERFIELD_TOWN", 'cust_town');
define("DB_CUSTOMERFIELD_PHONE", 'cust_phone');
define("DB_CUSTOMERFIELD_CELLPHONE", 'cust_cellphone');
define("DB_CUSTOMERFIELD_FAX", 'cust_fax');
define("DB_CUSTOMERFIELD_COUNTRY", 'cust_country');
define("DB_CUSTOMERFIELD_GROUP", 'cust_group');
define("DB_CUSTOMERFIELD_PASSWORD", 'cust_password');
define("DB_CUSTOMERFIELD_ACTIVE", 'cust_active');
define("DB_CUSTOMERFIELD_REGISTRATIONTIMESTAMP", 'cust_registrationtimestamp');
define("DB_CUSTOMERFIELD_EMAILVERIFIED", 'cust_emailverified');
define("DB_CUSTOMERFIELD_EMAILVERIFICATIONCODE", 'cust_emailverificationcode');
define("DB_CUSTOMERFIELD_TOSACCEPTED", 'cust_tosaccepted');
define("DB_CUSTOMERFIELD_CANCELLATIONDISCLAIMERACCEPTED", 'cust_cancellationdisclaimeraccepted');
define("DB_ADDRESSFIELDS", 'cust_id, cust_no, cust_email, cust_corp, cust_name, cust_street, cust_zip, cust_town, cust_phone, cust_cellphone, cust_fax, cust_country, cust_group, cust_active, cust_emailverified, cust_tosaccepted, cust_cancellationdisclaimeraccepted');
define("DB_CUSTOMERFIELD_PWRESETCODE", 'cust_pwresetcode');
define("DB_CUSTOMERFIELD_PWRESETTIMESTAMP", 'cust_pwresettimestamp');

$TMP = array(
    'minimum_length_custno' => 4,
    'minimum_length_password' => 5,
    'maximum_length_password' => 128,
    'validate_corpname' => false,
    'validate_name' => true,
    'validate_street' => true,
    'validate_zip' => true,
    'validate_town' => true,
    'validate_phone' => true,
    'validate_cellphone' => false,
    'validate_fax' => false,
    'validate_country' => true,

    'register_require_manual_activation' => false,

    'allow_edituserprofile' => true,

    'customer_groups' => array(
        '',
        'grosskunde|Großkunde',
        'wiederverkaeufer|Wiederverkäufer',
    ),
);

$C = array_merge($C, $TMP);
unset($TMP);
