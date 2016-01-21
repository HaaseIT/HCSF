<?php

/*
    HCSF - A multilingual CMS and Shopsystem
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

require __DIR__.'/../app/init.php';

//HaaseIT\Tools::debug($P);
//HaaseIT\Tools::debug($aURL);
//HaaseIT\Tools::debug($_SERVER);
//HaaseIT\Tools::debug($_REQUEST);
//HaaseIT\Tools::debug($aPath);
//HaaseIT\Tools::debug('Path: '.$sPath);

$aP = \HaaseIT\HCSF\Helper::generatePage($C, $P, $sLang, $oItem);

$response = new \Zend\Diactoros\Response();
$response->getBody()->write($twig->render($C["template_base"], $aP));

$emitter = new \Zend\Diactoros\Response\SapiEmitter();
$emitter->emit($response);
