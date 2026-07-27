<?php

return [
    'default'         => 'sqlite',

    'time_query_rule' => [],

    'auto_timestamp'  => true,

    'datetime_format' => 'Y-m-d H:i:s',

    'datetime_field'  => '',

    'connections'     => [
        'sqlite' => [
            'type'            => 'sqlite',
            'database'        => app()->getRootPath() . 'database/shop.db',
            'prefix'          => '',
            'deploy'          => 0,
            'rw_separate'     => false,
            'master_num'      => 1,
            'slave_no'        => '',
            'fields_strict'   => true,
            'break_reconnect' => false,
            'trigger_sql'     => true,
            'fields_cache'    => false,
        ],
    ],
];