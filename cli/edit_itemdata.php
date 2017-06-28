<?php

$basedir = dirname(dirname(dirname(dirname(dirname(filter_input(INPUT_SERVER, 'SCRIPT_FILENAME')))))).DIRECTORY_SEPARATOR;

require_once $basedir.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$HCSF = new \HaaseIT\HCSF\HCSF(dirname($basedir));
$HCSF->init();

$object = new \HaaseIT\HCSF\Controller\CLI\Itemdata($HCSF->getServiceManager());

$object->fetchItems();

$object->addDataWhere(
    'groessentabelle',
    '<img src=\'/_img/misc/frauen-shirts-s-m-l-xl.jpg\' width=\'215\' height=\'151\'><img src=\'/_img/misc/shirts-guenstig.jpg\' width=\'179\' height=\'151\'>',
    'groessentext',
    'frauen'
);

//$object->removeDataWhere('groessentext', false, 'groessentabelle');

$object->writeItems();

//print_r($object->getItems());
