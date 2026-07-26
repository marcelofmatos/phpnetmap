<?php

// Fica em protected/data/ (não em protected/config/) porque é o único
// caminho que o Dockerfile declara como VOLUME persistente — gravar em
// protected/config/ fazia essas configurações desaparecerem sempre que o
// container era recriado (não só reiniciado), já que aquele diretório vive
// na camada gravável efêmera da imagem.
define('PARAMS_INI_FILE_PATH', realpath(__DIR__ . '/../data') . '/params.ini');

$iniData = @parse_ini_file(PARAMS_INI_FILE_PATH);

return (is_array($iniData)) ? $iniData : array();
