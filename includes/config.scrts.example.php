<?php

$TMP = array(
    'db_type' => 'mysql',
    'db_server' => 'localhost',
    'db_user' => '',
    'db_password' => '',
    'db_name' => '',

    'blowfish_salt' => '$2a$07$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx$',
);

$C = array_merge($C, $TMP);
unset($TMP);
