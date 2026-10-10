<?php

return [
    // Le CORS ne s'applique qu'aux routes de l'API.
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Seul le frontend React est autorisé à appeler l'API depuis un navigateur.
    'allowed_origins' => [],

    'allowed_origins_patterns' => [
        '#^'.preg_quote(env('FRONTEND_URL', 'http://localhost:5173'), '#').'$#',
    ],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    // Durée de mise en cache de la requête préliminaire (preflight), en secondes.
    'max_age' => 600,

    // Mode Bearer token : pas de cookies entre le front et l'API.
    'supports_credentials' => false,
];
