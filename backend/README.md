# e-Tontine — API backend

L'API est construite avec Laravel 11 et PHP 8.2. Les routes versionnées sont
préfixées par `/api/v1`.

## Pré-requis

- PHP 8.2 ou supérieur avec les extensions requises par Laravel
- Composer
- MySQL ou MariaDB
- Node.js et npm pour le frontend

## Démarrage local

Depuis `backend`, installe les dépendances et prépare la configuration :

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Configure ensuite la connexion MySQL dans `.env` et crée la base correspondante.
Par défaut, le frontend utilise `http://localhost:8001/api/v1` ; démarre l'API
sur le port `8001` :

```powershell
php artisan migrate
php artisan serve --port=8001
```

En développement, les emails sont écrits dans `storage/logs/laravel.log`.
L'URL du frontend est configurable via `FRONTEND_URL`.

## Identité et demandes d'adhésion

À l'inscription, le candidat transmet une carte nationale (recto-verso) ou un
passeport, ainsi qu'une photo de profil et son consentement. Les fichiers sont
chiffrés et ne sont pas exposés dans les ressources JSON. Un candidat peut
consulter sa photo ; le gérant concerné peut consulter les pièces uniquement
pour une demande d'adhésion en attente.

La vérification de l'identité et la décision d'adhésion sont deux étapes
distinctes. Une identité vérifiée est requise avant d'approuver une demande.
Après un refus de pièce, le candidat peut en soumettre une nouvelle.

## Tests

Les tests backend sont dans `tests/Feature`. La configuration de test utilise
la base MySQL `etontine_test` ; configure une base de test dédiée avant de
lancer :

```powershell
vendor\bin\phpunit tests\Feature
```

## Données de démonstration fictives

Pour ajouter une tontine locale de démonstration avec cinq faux participants,
un cycle clôturé avec paiements et tirages fictifs, et un deuxième cycle en
cours, définis d'abord `DEMO_TONTINE_PASSWORD` dans l'environnement. Le mot de
passe doit contenir au moins 12 caractères. Puis lance :

```powershell
$env:DEMO_TONTINE_PASSWORD = Read-Host "Mot de passe des comptes de démonstration"
php artisan db:seed --class=DemoTontineSeeder
```

Le seeder est réexécutable sans dupliquer la tontine, les participants,
échéances, paiements ou tirages. Les comptes utilisent les adresses
`@etontine.test` ; le premier, `awa.demo@etontine.test`, gère la tontine.
Toutes les données de démonstration sont fictives et ne représentent pas des
transactions réelles.
