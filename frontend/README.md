# e-Tontine — frontend

Application React 18, TypeScript et Vite. Elle communique avec l'API Laravel
sur `http://localhost:8001/api/v1` par défaut. Pour utiliser une autre URL,
définis `VITE_API_URL` dans un fichier `.env` à la racine du frontend.

## Démarrage local

Dans un premier terminal, démarre l'API depuis `backend` :

```powershell
php artisan serve --port=8001
```

Dans un second terminal, depuis `frontend` :

```powershell
npm install
npm run dev
```

## Parcours d'adhésion

- Les invitations sont accessibles à `/join/{token}`.
- Une demande d'adhésion nécessite un compte connecté et une adresse email
  vérifiée. Le statut de la demande est relu depuis l'API.
- L'inscription requiert une pièce d'identité et une photo de profil. Les
  documents sont transmis de manière privée et peuvent être consultés par le
  gérant de la tontine concernée lorsqu'il examine une demande en attente.
- Dans son profil, l'utilisateur voit son statut d'identité et peut transmettre
  une nouvelle pièce si la précédente a été refusée.
- Le gérant vérifie l'identité avant de décider séparément d'approuver ou de
  rejeter la demande d'adhésion.
- Depuis une tontine brouillon, son gérant peut démarrer le premier cycle ;
  l'API active alors la tontine et génère les échéances.

## Cotisations

- Le gérant peut modifier le montant attendu pour chaque membre et chaque tour.
  Le nouveau montant ne peut pas être inférieur aux sommes déjà encaissées.
- Le gérant enregistre séparément chaque paiement reçu, y compris les paiements
  partiels, avec son mode de paiement.

## Vérification

```powershell
npm run build
npm run lint
```
