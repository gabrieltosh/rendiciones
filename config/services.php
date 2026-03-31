<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'siat' => [
        'api' => env('SIAT_API_URL', base64_decode('aHR0cHM6Ly9zaWF0cmVzdC5pbXB1ZXN0b3MuZ29iLmJvL3NyZS1zZmUtc2hhcmVkLXYyLXJlc3QvY29uc3VsdGEvZmFjdHVyYQ==')),
        'origin' => env('SIAT_ORIGIN_URL', base64_decode('aHR0cHM6Ly9zaWF0LmltcHVlc3Rvcy5nb2IuYm8=')),
    ],

];
