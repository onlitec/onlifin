<?php

return [
    'processors' => [
        'extratos' => [
            'id' => env('GOOGLE_CLOUD_PROCESSOR_ID', 'projects/compact-nirvana-457221-d6/locations/us/processors/873520394486692224'),
            'name' => 'Extratos Bancários',
            'description' => 'Processador especializado para extratos bancários',
            'model' => 'finance-document',
            'supported_types' => ['pdf'],
            'entities' => [
                'transaction.date',
                'transaction.amount',
                'transaction.description',
                'bank.name',
                'bank.account',
                'bank.balance'
            ]
        ]
    ]
];
