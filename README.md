# Wallet API : Laravel + MySQL + Docker

Petite API REST de comptes et de transactions, comme le back-end d'une app de paiement.
Projet d'entraînement pour pratiquer Docker, Laravel, MySQL et les tests automatisés.

## Ce que contient le dossier

| Fichier | Rôle |
|---|---|
| `docker-compose.yml` | Décrit les 3 conteneurs : `app` (PHP 8.4), `web` (Nginx), `db` (MySQL 8.4) |
| `docker/php/Dockerfile` | Recette de l'image PHP : extensions + Composer |
| `docker/nginx/default.conf` | Nginx envoie les fichiers `.php` à PHP-FPM (`app:9000`) |
| `src/` | Créé à l'étape 2 : le projet Laravel |

## Lancer le projet (PowerShell, dans ce dossier)

```powershell
# 1. Construire l'image PHP (première fois : 2-5 min)
docker compose build

# 2. Créer le projet Laravel dans src/ (le dossier src doit être vide ou absent)
docker compose run --rm app composer create-project laravel/laravel .

# 3. Démarrer les 3 conteneurs en arrière-plan
docker compose up -d
docker compose ps
```

**4. Brancher Laravel sur MySQL** : ouvre `src/.env` et remplace le bloc `DB_` par :

```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=payments
DB_USERNAME=app
DB_PASSWORD=secret
```

`DB_HOST=db` : dans Docker, les conteneurs se trouvent par le **nom du service**, pas par `localhost`.

**5. Ajouter le code** : idéalement, crée chaque fichier toi-même avec artisan, puis écris le code en lisant `solution/` :

```powershell
docker compose exec app php artisan make:model Account -mf
docker compose exec app php artisan make:model Transaction -m
docker compose exec app php artisan make:controller Api/AccountController --api
docker compose exec app php artisan make:request StoreTransactionRequest
docker compose exec app php artisan make:resource AccountResource
docker compose exec app php artisan make:test TransactionApiTest
```

Si tu manques de temps, copie directement tout le contenu de `solution/` dans `src/` (remplace les fichiers existants).
Dans ce cas, supprime les migrations vides créées par `make:model -m` pour éviter les doublons.

```powershell
# 6. Créer les tables + données de démo
docker compose exec app php artisan migrate --seed

# 7. Lancer les tests (ils utilisent SQLite en mémoire, voir src/phpunit.xml)
docker compose exec app php artisan test
```

## Essayer l'API

Navigateur : http://localhost:8080/api/accounts

PowerShell (utilise `curl.exe`, pas `curl` qui est un alias PowerShell) :

```powershell
# Créditer le compte 2 de 12,50 EUR (montants en centimes)
curl.exe -X POST http://localhost:8080/api/accounts/2/transactions -H "Accept: application/json" -H "Content-Type: application/json" -d '{\"type\":\"credit\",\"amount\":1250,\"reference\":\"REF-001\"}'

# Virement de 30 EUR du compte 1 vers le compte 2
curl.exe -X POST http://localhost:8080/api/transfers -H "Accept: application/json" -H "Content-Type: application/json" -d '{\"from_account_id\":1,\"to_account_id\":2,\"amount\":3000,\"reference\":\"TR-001\"}'

# Historique du compte 2
curl.exe http://localhost:8080/api/accounts/2/transactions
```

(Plus simple : Postman, Insomnia ou l'extension VS Code « REST Client ».)

## Routes

| Méthode | URL | Action |
|---|---|---|
| GET | `/api/accounts` | Liste paginée des comptes |
| POST | `/api/accounts` | Créer un compte |
| GET | `/api/accounts/{id}` | Voir un compte (404 si inconnu) |
| GET | `/api/accounts/{id}/transactions` | Historique, du plus récent au plus ancien |
| POST | `/api/accounts/{id}/transactions` | Crédit ou débit (422 si solde insuffisant) |
| POST | `/api/transfers` | Virement atomique entre deux comptes |

## Choix techniques

1. **Argent en centimes (entier)**, jamais en `float` : `0.1 + 0.2 != 0.3`.
2. **`DB::transaction`** : le débit et le crédit d'un virement réussissent ensemble ou échouent ensemble (rollback).
3. **`lockForUpdate()`** (`SELECT ... FOR UPDATE`) : deux débits simultanés ne peuvent pas lire le même solde.
4. **Idempotence** : la `reference` est unique. Si le client renvoie la même requête (timeout réseau), on ne débite pas deux fois.
5. **Séparation des rôles** : FormRequest = validation, Service = logique métier, Resource = format JSON, Controller = chef d'orchestre.

## Commandes Docker utiles

```powershell
docker compose ps                    # état des conteneurs
docker compose logs -f app           # logs PHP en direct (Ctrl+C pour quitter)
docker compose exec app bash         # ouvrir un terminal DANS le conteneur PHP
docker compose exec db mysql -uapp -psecret payments   # console MySQL
docker compose stop                  # arrêter (on garde tout)
docker compose down                  # supprimer les conteneurs (les données MySQL restent dans le volume)
docker compose down -v               # supprimer aussi le volume = base de données effacée
```

## Problèmes fréquents

| Message | Cause | Solution |
|---|---|---|
| `port is already allocated` / `address already in use` sur 3306 | Un MySQL (XAMPP, WAMP) tourne déjà sur ton PC | Arrête-le, ou change `"3306:3306"` en `"3307:3306"` dans `docker-compose.yml` |
| Même erreur sur 8080 | Un autre programme utilise le port 8080 | Change `"8080:80"` en `"8081:80"` et va sur http://localhost:8081 |
| `Permission denied` sur `storage/logs` | PHP-FPM ne peut pas écrire | `docker compose restart app` (la commande du service redonne les droits) |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL démarre encore (10-30 s) ou `DB_HOST` n'est pas `db` | Attends 30 s, vérifie `src/.env` |
| `Project directory is not empty` à l'étape 2 | Le dossier `src` contient déjà des fichiers | Supprime `src` et relance l'étape 2 |
| `docker: command not found` / daemon pas joignable | Docker Desktop n'est pas lancé | Ouvre Docker Desktop et attends « Engine running » |

Stack complète lancée sous Docker, migrations MySQL OK, 12 tests verts, API testée (201, 200, 404, 422).
