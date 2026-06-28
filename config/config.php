<?php
declare(strict_types=1);

return [
    'app_name'    => 'AfyaLink',
    'app_tagline' => 'Pata hospitali na huduma zinazopatikana sasa hivi',
    'app_version' => '1.0.0-mvp',
    'timezone'    => 'Africa/Dar_es_Salaam',
    'debug'       => filter_var(getenv('APP_DEBUG') ?: 'true', FILTER_VALIDATE_BOOLEAN),

    'openrouter' => [
        'api_key'  => getenv('OPENROUTER_API_KEY') ?: '',
        'model'    => getenv('OPENROUTER_MODEL') ?: 'liquid/lfm-2.5-1.2b-instruct:free',
        'fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', getenv('OPENROUTER_FALLBACK_MODELS') ?: 'google/gemma-4-31b-it:free,openrouter/free')
        ))),
        'base_url' => 'https://openrouter.ai/api/v1/chat/completions',
        'site_url' => getenv('APP_URL') ?: 'http://localhost/Afyalink',
        'app_name' => 'AfyaLink Tanzania',
    ],

    'regions_mvp' => ['Dar es Salaam', 'Pwani', 'Morogoro', 'Iringa'],

    'disclaimer' => [
        'sw' => 'AfyaLink SI badala ya daktari wala mtaalamu wa afya. Inaonyesha tu hospitali na huduma zinazopatikana kwa muda huu.',
        'en' => 'AfyaLink is NOT a replacement for doctors or clinicians. It only shows which hospitals offer which services right now.',
    ],
];
