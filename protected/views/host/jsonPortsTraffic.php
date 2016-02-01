<?php

echo CJSON::encode(array(
    'ports' => array_values($model->ports),
    'time' => time(),
        ));
