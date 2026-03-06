<?php

return [
    // 許可する遷移（from => [to...])
    'transitions' => [
        'draft'     => ['submitted'],
        'submitted' => ['approved', 'rejected'],
        'approved'  => ['submitted', 'rejected'],
        'rejected'  => [],
    ],

    // 初期状態
    'initial_state' => 'draft',
];