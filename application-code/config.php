<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '<your-db-host-name>',
        'name' => getenv('DB_NAME') ?: '<your-db-name>',
        'user' => getenv('DB_USER') ?: '<your-db-user-name>',
        'pass' => getenv('DB_PASS') ?: '<your-db-user-password>',
    ],
    'aws' => [
        'region' => getenv('AWS_REGION') ?: '<your-region>',
        'bucket' => getenv('S3_BUCKET') ?: '<your-bucket-name>',
        'dynamo_table' => getenv('DYNAMO_TABLE') ?: '<your-dynamodb-table-name>',
    ]
];
