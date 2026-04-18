<?php

return [
    'collect-worker' => [
        'handler' => \app\process\CollectWorker::class,
        'count' => 1,
    ],
];
