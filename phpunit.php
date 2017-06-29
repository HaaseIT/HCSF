<?php

require_once __DIR__.'/vendor/autoload.php';

define('PATH_BASEDIR', dirname(dirname(dirname(filter_input(INPUT_SERVER, 'SCRIPT_FILENAME')))).DIRECTORY_SEPARATOR);
define('HCSF_BASEDIR', PATH_BASEDIR);

