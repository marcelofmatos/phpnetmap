<?php

echo CJSON::encode(array(
    'sysDescr' => $model->sysDescr,
    'sysName' => $model->sysName,
    'ip' => $model->ip,
));
