# Maintenance Manager

Plateforme web de gestion des demandes de maintenance d'**AFRICA INDUSTRIES** : les employés déclarent leurs pannes, l'administrateur affecte un technicien, le technicien traite et suit l'intervention, et chaque action est tracée.

> Dépôt : `github.com/<votre-nom>/maintenance-manager`

## Sommaire

1. [Stack technique](#stack-technique)
2. [Fonctionnalités](#fonctionnalités)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Commandes utiles](#commandes-utiles)
6. [Comptes de test et jeu de données](#comptes-de-test-et-jeu-de-données)
7. [Rôles et permissions](#rôles-et-permissions)
8. [Workflow des tickets](#workflow-des-tickets)
9. [API REST](#api-rest)
10. [Architecture](#architecture)
11. [Base de données](#base-de-données)
12. [Tests](#tests)
13. [Choix de conception](#choix-de-conception)

## Stack technique

| Domaine | Technologie |
|---|---|
| Backend | Laravel 13, PHP 8.3+ (développé avec PHP 8.4) |
| Base de données | MySQL 8 (SQLite en mémoire pour les tests) |
| Frontend | Blade + Bootstrap 5 (CDN) |
| Authentification | Laravel Breeze (web), Laravel Sanctum (API) |
| Exports | `maatwebsite/excel` (Excel), `barryvdh/laravel-dompdf` (PDF) |
| Tests | PHPUnit |

## Fonctionnalités

- **Authentification** : connexion, déconnexion, réinitialisation du mot de passe. Pas d'inscription publique : les comptes sont créés par l'administrateur.
- **Rôles** : administrateur, employé, technicien (middleware + policies). Un compte *inactif* ne peut plus se connecter, même s'il avait une session ouverte.
- **Gestion des utilisateurs** (CRUD complet : nom, prénom, email, téléphone, rôle, statut) et **des catégories** (CRUD).
- **Demandes de maintenance** : titre, description, catégorie, priorité (faible, normale, haute, critique), pièce jointe.
- **Workflow strict** Nouveau → Assigné → En cours → Résolu → Fermé, sans saut d'étape.
- **Affectation** d'un technicien actif avec date limite, notification automatique.
- **Historique** de chaque action : création, modification (champs modifiés), assignation, changement de statut, commentaire, temps enregistré.
- **Commentaires** avec pièce jointe.
- **Temps passé** : le technicien assigné enregistre ses interventions (en minutes) ; le total est affiché sur le ticket.
- **Tableau de bord par rôle** : total, ouverts, résolus, critiques ouverts, techniciens actifs, en retard, temps moyen de résolution, répartition par statut et par catégorie, charge des techniciens (admin).
- **Recherche et filtres** : texte, date (début/fin), statut, catégorie, priorité, technicien.
- **Bonus** :
  - notifications email (création, assignation, résolution) ;
  - **export Excel** à deux feuilles : *Incidents* (liste filtrable par période, statut, priorité, catégorie, technicien) et *Statistiques* (indicateurs, répartitions par statut/priorité/catégorie, charge des techniciens, temps moyen de résolution, temps passé), calculées sur les mêmes tickets ;
  - **rapport mensuel PDF** (mois au choix) : synthèse, répartitions, charge des techniciens, liste des tickets créés pendant le mois ;
  - temps moyen de résolution (tableau de bord et rapports) ;
  - API REST Sanctum.

## Installation

Prérequis : PHP 8.3+ (extensions `pdo_mysql`, `pdo_sqlite`, `mbstring`, `gd`, `zip`), Composer, MySQL 8, Node.js 20+.

```bash
git clone https://github.com/<votre-nom>/maintenance-manager.git
cd maintenance-manager

composer install
cp .env.example .env
php artisan key:generate

# Créer la base MySQL
mysql -u root -p -e "CREATE DATABASE maintenance_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# Puis renseigner DB_USERNAME / DB_PASSWORD dans .env

php artisan migrate --seed       # tables + jeu de données de démonstration
php artisan storage:link         # accès aux pièces jointes

npm install
npm run build                    # assets Vite (pages d'authentification Breeze)

php artisan serve                # http://localhost:8000
```

## Configuration

Variables principales du fichier `.env` :

| Variable | Rôle | Valeur par défaut |
|---|---|---|
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Connexion MySQL | `mysql`, `127.0.0.1`, `3306`, `maintenance_manager`, `root` |
| `MAIL_MAILER` | Envoi des notifications. `log` écrit les emails dans `storage/logs/laravel.log` | `log` |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Expéditeur des emails | `maintenance@africa-industries.test` |
| `APP_FAKER_LOCALE` | Langue des données générées par les seeders | `fr_FR` |
| `QUEUE_CONNECTION` | Les notifications sont envoyées de façon synchrone | `sync` |

Pour envoyer de vrais emails, remplacer `MAIL_MAILER=log` par une configuration SMTP (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`).

## Commandes utiles

| Commande | Description |
|---|---|
| `php artisan migrate:fresh --seed` | Recrée la base avec le jeu de données de démonstration |
| `php artisan test` | Lance toute la suite de tests |
| `php artisan test --filter=WorkflowTest` | Lance une classe de tests |
| `php artisan route:list` | Liste les routes (web et API) |
| `php artisan storage:link` | Crée le lien `public/storage` |
| `./vendor/bin/pint` | Formate le code (PSR-12 / Laravel) |

## Comptes de test et jeu de données

Mot de passe de **tous** les comptes : `password`

| Rôle | Email |
|---|---|
| Administrateur | `admin@maintenance.test` |
| Techniciens (5) | `technicien1@maintenance.test` … `technicien5@maintenance.test` |
| Employés (10) | `employe1@maintenance.test` … `employe10@maintenance.test` |
| Compte inactif | `inactif@maintenance.test` (la connexion est refusée) |

Le jeu de données (`php artisan migrate --seed`) contient 5 catégories (Climatisation, Electricité, Réseau, Plomberie, Imprimante), 17 utilisateurs et **50 tickets** répartis sur tous les statuts, avec historique, commentaires et temps passé cohérents avec le workflow. Il est reproductible : la même base est générée à chaque exécution.

## Rôles et permissions

| Action | Employé | Technicien | Administrateur |
|---|:---:|:---:|:---:|
| Créer une demande | ✓ | ✓ | ✓ |
| Voir les tickets | les siens | ceux qui lui sont assignés | tous |
| Modifier un ticket | le sien, tant qu'il est « Nouveau » | — | ✓ |
| Supprimer un ticket | — | — | ✓ |
| Commenter | sur ses tickets | sur ses tickets assignés | ✓ |
| Supprimer un commentaire | le sien | le sien | tous |
| Affecter un technicien | — | — | ✓ |
| Changer le statut | — | tickets assignés | ✓ |
| Enregistrer du temps passé | — | tickets assignés, « En cours » ou « Résolu » | — |
| Gérer utilisateurs et catégories | — | — | ✓ |
| Exports Excel et rapport PDF | — | — | ✓ |

## Workflow des tickets

```mermaid
flowchart LR
    N[Nouveau] -->|affectation d'un technicien| A[Assigné]
    A -->|technicien| E[En cours]
    E -->|technicien| R[Résolu]
    R -->|technicien ou admin| F[Fermé]
```

- Aucune étape ne peut être sautée (règle appliquée côté serveur dans `TicketService`, y compris via l'API).
- Le passage de *Nouveau* à *Assigné* se fait uniquement en affectant un technicien actif.
- Un ticket peut être réaffecté tant qu'il est *Nouveau* ou *Assigné*.
- Chaque transition est enregistrée dans l'historique ; le passage à *Résolu* enregistre la date de résolution.

## API REST

Authentification par token Sanctum (`Authorization: Bearer <token>`). Envoyer `Accept: application/json`.

```bash
# 1. Obtenir un token (5 essais par minute maximum)
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -d "email=admin@maintenance.test" -d "password=password"

# 2. Utiliser le token
curl http://localhost:8000/api/tickets \
  -H "Accept: application/json" -H "Authorization: Bearer <token>"
```

| Méthode | URL | Description |
|---|---|---|
| POST | `/api/login` | Retourne un token (comptes actifs uniquement) |
| POST | `/api/logout` | Révoque le token courant |
| GET | `/api/tickets` | Liste paginée des tickets visibles par l'utilisateur |
| POST | `/api/tickets` | Crée un ticket (statut « Nouveau », historique et notification) |
| GET | `/api/tickets/{id}` | Détail d'un ticket |
| PUT | `/api/tickets/{id}` | Modifie le contenu (titre, description, catégorie, priorité) |
| PATCH | `/api/tickets/{id}/status` | Change le statut en respectant le workflow |
| DELETE | `/api/tickets/{id}` | Supprime un ticket (administrateur) |

Les mêmes policies que l'interface web s'appliquent : un employé ne voit et ne modifie que ses propres tickets. Les erreurs de validation renvoient un code `422`, les accès refusés un code `403`.

## Architecture

```
app/
├── Http/
│   ├── Controllers/        # contrôleurs fins (+ Api/)
│   ├── Middleware/         # RoleMiddleware, EnsureUserIsActive
│   └── Requests/           # Form Requests : validation + autorisation
├── Models/                 # User, Category, Ticket, Comment, Activity, TimeEntry
├── Policies/               # Ticket, Comment, Category, User
├── Services/               # TicketService (workflow), ActivityService, DashboardService
├── Notifications/          # création, assignation, résolution
└── Exports/                # export Excel
database/
├── factories/              # états cohérents avec le workflow (assigned, inProgress, resolved, closed)
├── migrations/
└── seeders/                # Category, User, Ticket
tests/Feature/              # sécurité, workflow, CRUD, dashboard, API, seeders
```

Les règles métier (workflow, historique, notifications, temps passé) sont concentrées dans `TicketService` : l'interface web et l'API passent par le même code.

## Base de données

| Table | Colonnes principales |
|---|---|
| `users` | id, name (nom), first_name (prénom), email, phone, password, role, status |
| `categories` | id, name, description |
| `tickets` | id, title, description, priority, status, user_id, category_id, assigned_to, deadline, attachment, resolved_at |
| `comments` | id, ticket_id, user_id, message, attachment |
| `activities` | id, ticket_id, user_id, action, description, created_at |
| `time_entries` | id, ticket_id, user_id, minutes, note |

Colonnes ajoutées par rapport au schéma de l'énoncé : `first_name` et `phone` (champs demandés pour les utilisateurs), `resolved_at` (temps de résolution), `attachment` (pièces jointes) et la table `time_entries` (temps passé).

## Tests

```bash
php artisan test
```

Les tests utilisent SQLite en mémoire (aucune configuration MySQL nécessaire). Ils couvrent notamment :

- **Sécurité** : inscription désactivée, comptes inactifs, périmètre des données par rôle, policies sur les tickets, commentaires et utilisateurs.
- **Workflow** : transitions autorisées et interdites, affectation, historique, notifications.
- **CRUD et validation** : tickets (pièces jointes, filtres), commentaires, catégories, utilisateurs.
- **Dashboard** : statistiques par rôle, temps moyen de résolution.
- **Rapports** : statistiques, feuilles Excel, rapport mensuel PDF, validation des filtres, accès réservé à l'administrateur.
- **API** : authentification, périmètre, workflow, autorisations.
- **Seeders** : cohérence du jeu de données avec le workflow.

## Choix de conception

- **Pas d'inscription publique** : seul l'administrateur crée des comptes.
- **Désactiver plutôt que supprimer** : un utilisateur ou une catégorie liés à des tickets ne peuvent pas être supprimés, pour ne pas perdre l'historique (les suppressions en cascade effaceraient les tickets).
- **Tickets critiques** : le tableau de bord compte les tickets critiques encore à traiter.
- **Temps passé** : saisi par le technicien assigné, une fois le travail commencé.
- **Pièces jointes** : stockées sur le disque `public` (5 Mo maximum).
- **Non implémenté** : Repository Pattern (bonus facultatif) ; la logique d'accès aux données passe directement par Eloquent.
