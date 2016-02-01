<?php

define('PARAMS_INI_FILE_PATH', realpath(__DIR__ . '/params.ini'));

$iniData = @parse_ini_file(PARAMS_INI_FILE_PATH);

return ($iniData) ? $iniData : array();