<?php

header('Content-type:application/json');

echo CJSON::encode(array('error' => $error));