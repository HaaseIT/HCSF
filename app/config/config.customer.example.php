<?php

/*
    Contanto - A modular CMS and Shopsystem
    Copyright (C) 2015  Marcus Haase - mail@marcus.haase.name

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
