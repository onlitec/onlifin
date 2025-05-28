<?php

return [
    'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', 'onlifin'),
    'location' => env('GOOGLE_CLOUD_LOCATION', 'us'),
    'processor_id' => env('GOOGLE_CLOUD_PROCESSOR_ID'),
    'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    'api_endpoint' => env('GOOGLE_CLOUD_API_ENDPOINT', 'https://documentai.googleapis.com'),
    'timeout' => env('GOOGLE_CLOUD_TIMEOUT', 30),
];
