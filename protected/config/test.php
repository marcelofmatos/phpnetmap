<?php

return CMap::mergeArray(
    require(dirname(__FILE__) . '/main.php'),
    array(
        'components' => array(
            'db' => array(
                'connectionString' => 'sqlite:' . dirname(__FILE__) . '/../runtime/test.db',
            ),
        ),
    )
);
