<?php

include_once('base.php');

//debug($P);
//debug($aURL);
//debug($_SERVER);
//debug($_REQUEST);
//debug($aPath);
//debug('Path: '.$sPath);

$aP = generatePage($C, $P, $sLang, $DB, $oItem);

echo $twig->render($C["template_base"], $aP);
