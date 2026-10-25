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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v25.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', '1088196013963166'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'template_name' => env('WHATSAPP_TEMPLATE_NAME', 'wedding_invitation'),
        'invitation_templates' => array_values(array_filter(array_map('trim', explode(',', env(
            'WHATSAPP_INVITATION_TEMPLATES',
            env('WHATSAPP_TEMPLATE_NAME', 'wedding_invitation')
        ))))),
        'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en_US'),
        'default_image_url' => env('WHATSAPP_DEFAULT_IMAGE_URL'),

        // Reminder template: body params are guest name, contribution, event name,
        // payment details. Language policy "deterministic" matches the Cloud API.
        'reminder_template_name' => env('WHATSAPP_REMINDER_TEMPLATE', 'reminder_sw'),
        'reminder_template_language' => env('WHATSAPP_REMINDER_TEMPLATE_LANGUAGE', 'sw'),
        'reminder_language_policy' => env('WHATSAPP_REMINDER_LANGUAGE_POLICY', 'deterministic'),
        'reminder_payment_details' => env('WHATSAPP_REMINDER_PAYMENT_DETAILS', ''),
        'reminder_amount_prefix' => env('WHATSAPP_REMINDER_AMOUNT_PREFIX', 'Mchango'),
    ],

];
