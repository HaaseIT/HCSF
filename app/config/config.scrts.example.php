<?php

/*
    Contanto - A modular CMS and Shopsystem
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
