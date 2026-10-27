# Handover — Plateforme de Gestion des Demandes de Maintenance (AFRICA INDUSTRIES)

## 1. Vue d'ensemble du projet

### Contexte métier
AFRICA INDUSTRIES souhaite digitaliser la gestion des demandes de maintenance interne. La plateforme permet aux employés de déclarer des incidents/demandes, aux techniciens de les traiter selon un workflow strict, et aux administrateurs de piloter les utilisateurs, les catégories, les tickets et les rapports.

### Objectifs fonctionnels
- Centraliser les demandes de maintenance.
- Tracer l'historique complet de chaque demande.
- Encadrer le traitement par un workflow de statuts.
- Donner une vue synthétique via un tableau de bord.
- Fournir des exports et une API REST pour intégration externe.

### Stack technique exacte
- **PHP** : 8.4.1 (contrainte Composer `^8.3`)
- **Laravel** : 13.38.0 (contrainte `^13.17`)
- **Base de données** : MySQL 8 (base `maintenance_manager`, utilisateur `maintenance`)
- **Frontend** : Blade + Bootstrap 5, Vite 8, Tailwind CSS 3, Alpine.js 3
- **Node.js** : 22.20.0 / npm 11.6.2
- **Authentification web** : Laravel Breeze `^2.4`
- **API / tokens** : Laravel Sanctum `^4.3`
- **Export Excel** : Maatwebsite Excel `^4.0`
- **Export PDF** : Barryvdh Laravel DomPDF `^3.1`
- **Tests** : PHPUnit `^12.5`
- **Stockage fichiers** : disque `public` (`storage/app/public`), lien symbolique `public/storage`
- **Mailer** : driver `log` (développement)

## 2. Architecture et choix techniques

### Architecture logicielle
- **MVC** Laravel classique.
- **Contrôleurs** fins : délégation de la logique métier aux services.
- **Form Requests** pour la validation (`StoreTicketRequest`, `UpdateTicketRequest`, `StoreCategoryRequest`, `StoreCommentRequest`, `StoreUserRequest`, `UpdateUserRequest`).
- **Policies** pour l'autorisation métier (`TicketPolicy`, `CategoryPolicy`, `UserPolicy`).
- **Services** :
  - `App\Services\TicketService` : création, assignation, changement de statut, enregistrement des pièces jointes.
  - `App\Services\ActivityService` : journalisation des actions sur les tickets.
- **Notifications** : `TicketCreatedNotification`, `TicketAssignedNotification`, `TicketResolvedNotification`.
- **Middleware** : `RoleMiddleware` alias `role` enregistré dans `bootstrap/app.php`.

### Sécurité
- Authentification web via Laravel Breeze, routes protégées par `auth` et `verified`.
- Rôles : `admin`, `technician`, `employee` (colonne `users.role`).
- Accès admin restreint par middleware `role:admin`.
- Protection CSRF sur toutes les requêtes web.
- Validation serveur systématique via Form Requests et validations inline.
- Autorisation fine via Policies (modification/suppression de ticket selon rôle et statut).
- API protégée par Sanctum (`auth:sanctum`).
- Fichiers joints stockés sur le disque `public`.

### Base de données et relations Eloquent
Tables principales :
- `users` : id, name, email, password, role, phone, status, email_verified_at, timestamps.
- `categories` : id, name, description, timestamps.
- `tickets` : id, title, description, priority, status, user_id, category_id, assigned_to, deadline, attachment, resolved_at, timestamps.
- `comments` : id, ticket_id, user_id, message, attachment, timestamps.
- `activities` : id, ticket_id, user_id, action, description, timestamps.
- `personal_access_tokens` : tokens Sanctum.
- Tables framework : `cache`, `jobs`, etc.

Relations clés :
- `User` hasMany `Ticket` (créés), hasMany `Ticket` assignés (`assigned_to`), hasMany `Comment`, hasMany `Activity`.
- `Ticket` belongsTo `User` (créateur), belongsTo `Category`, belongsTo `User` (assigné), hasMany `Comment`, hasMany `Activity`.
- `Category` hasMany `Ticket`.
- `Comment` belongsTo `Ticket`, belongsTo `User`.
- `Activity` belongsTo `Ticket`, belongsTo `User`.

## 3. État des fonctionnalités

### Fonctionnalités core implémentées
- **Authentification** : inscription/connexion/réinitialisation via Breeze, email vérifié.
- **Rôles** : admin, technicien, employé.
- **Gestion des utilisateurs (admin)** : création, modification, suppression, activation/désactivation, rôle.
- **Gestion des catégories (admin)** : création, modification, suppression.
- **Création de demande** : titre, description, priorité, catégorie, pièce jointe, créateur.
- **Workflow strict des statuts** : `new -> assigned -> in_progress -> resolved -> closed`.
- **Assignation** : admin assigne un technicien et une deadline.
- **Commentaires** : ajout de commentaires et pièces jointes sur un ticket.
- **Historique/activités** : journal des actions (création, assignation, changement de statut).
- **Tableau de bord** : KPI, tickets récents, temps moyen de résolution, filtres (statut, priorité, catégorie, technicien, dates, recherche).
- **Liste et détail des tickets** : pagination, filtres, vue détaillée avec commentaires et historique.

### Bonus implémentés
- **Notifications email** : à la création, à l'assignation et à la résolution. Mailer en mode `log` en développement.
- **Export Excel** : export des tickets via Maatwebsite Excel.
- **Export PDF** : rapport mensuel via DomPDF.
- **Calcul du temps de traitement** : méthode `Ticket::resolutionTimeInHours()` basée sur `created_at` et `resolved_at`.
- **API REST Sanctum** : CRUD tickets + endpoint `/api/user`.

### Bonus non implémentés / limites
- Pas de files d'attente asynchrones pour les emails (envoi synchrone).
- Pas de notifications temps réel (WebSocket/Pusher).
- Pas de graphiques avancés sur le dashboard.
- Pas de tests unitaires/fonctionnels dédiés au domaine (seuls les tests Breeze par défaut existent).
- Pas de policy dédiée pour la suppression de commentaires.
- Pas de restriction stricte côté API selon le rôle (l'API expose le CRUD tickets à tout utilisateur authentifié Sanctum).
- Les techniciens sont générés avec des emails aléatoires par la factory.

## 4. Guide d'installation et démarrage rapide

### Prérequis
- PHP >= 8.3
- Composer
- Node.js >= 18
- MySQL 8
- Extensions PHP usuelles Laravel (pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo)

### Installation
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configurer `.env` :
```env
APP_NAME="Maintenance Manager"
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=maintenance_manager
DB_USERNAME=maintenance
DB_PASSWORD=secret
MAIL_MAILER=log
```

Créer la base MySQL et l'utilisateur, puis :
```bash
php artisan migrate --seed
php artisan storage:link
npm run build
```

### Lancement développement
```bash
php artisan serve
npm run dev
```
En option pour les queues (si activation future) :
```bash
php artisan queue:work
```

### Tests
```bash
php artisan test
```

## 5. Comptes de test et jeu de données

Mot de passe pour tous les comptes seedés : `password`.

- **Administrateur** : `admin@maintenance.test` (rôle `admin`, nom `Admin Principal`)
- **Techniciens** (5) :
  - `guichard.georges@example.com`
  - `kblanc@example.net`
  - `jerome.blanchard@example.org`
  - `lambert.dominique@example.com`
  - `marc.charrier@example.com`
- **Employés** (10) : emails générés automatiquement par la factory (rôles `employee`).

Données seedées :
- 5 catégories : Climatisation, Electricité, Réseau, Plomberie, Imprimante.
- 50 tickets avec statuts, priorités, assignations et deadlines aléatoires.

Pour récupérer les emails employés :
```bash
php artisan tinker --execute='echo App\Models\User::where("role","employee")->pluck("email")->implode(", ");'
```

## 6. Points d'amélioration et dette technique (Roadmap V2)

- Ajouter des tests Feature/Unit pour les contrôleurs, policies, services et workflow.
- Passer les notifications en files d'attente (`ShouldQueue`) avec worker dédié.
- Ajouter des graphiques et statistiques avancées au dashboard.
- Renforcer la sécurité API : permissions par rôle, rate limiting, validation complète.
- Ajouter une policy de suppression/modification des commentaires.
- Améliorer l'UX (filtres AJAX, modales, feedback temps réel).
- Mettre en place l'upload sécurisé et la validation MIME des pièces jointes.
- Ajouter la gestion des notifications in-app.
- Mettre en place CI/CD, linting (Pint) et analyse statique (PHPStan/Larastan).
- Documenter l'API (OpenAPI/Swagger).
