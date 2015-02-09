<?php

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
