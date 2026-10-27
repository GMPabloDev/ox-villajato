<?php

return [

    'photos_disk' => env('PROPERTY_PHOTOS_DISK', 'public'),

    'photos_directory' => 'properties',

    'inquiry_recipient' => env('PROPERTY_INQUIRY_RECIPIENT', 'consultas@example.com'),

    'inquiry_rate_limit' => (int) env('PROPERTY_INQUIRY_RATE_LIMIT', 5),

];
