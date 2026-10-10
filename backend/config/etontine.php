<?php

return [
    // URL du frontend React : utilisée dans les emails (vérification, réinitialisation)
    // et pour les redirections après un clic sur un lien.
    'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/'),
];
