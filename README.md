# RMS Ticket — Guide d'installation

Application web de gestion de tickets de support informatique.  
Développée en PHP 8.2 / MariaDB 10.4 / JavaScript Vanilla.

---

## Prérequis

Avant de commencer, assurez-vous d'avoir installé sur votre machine :

| Logiciel | Version minimale | Téléchargement |
|---|---|---|
| **XAMPP** | 8.2+ | https://www.apachefriends.org/fr/index.html |
| Navigateur web | Chrome / Firefox / Edge | — |

> XAMPP inclut Apache, PHP 8.2 et MariaDB. Aucun autre logiciel n'est nécessaire.

---

## Étape 1 — Télécharger le code et l'enregistrer dans XAMPP

1. Téléchargez le code source depuis GitHub :
   **https://github.com/Sasukisse/RMS-Ticket/archive/refs/heads/main.zip**
2. Décompressez l'archive ZIP téléchargée.
3. Renommez le dossier extrait en `RMS-Ticket-main` (si ce n'est pas déjà le cas).
4. Déplacez ce dossier dans : `C:\xampp\htdocs\`

Vous devez obtenir la structure suivante dans `C:\xampp\htdocs\RMS-Ticket-main\` :

```
RMS-Ticket-main/
├── rms_ticket.sql
├── index.php
├── AdminPanel/
├── CreateTickets/
├── Database/
│   ├── config.php
│   └── connection.php
├── HomePage/
├── Login/
└── Tickets/
```

---

## Étape 2 — Démarrer XAMPP

1. Ouvrez le **Panneau de contrôle XAMPP**
2. Cliquez sur **Start** pour les modules **Apache** et **MySQL**
3. Les deux voyants doivent passer au **vert**

---

## Étape 3 — Créer la base de données

### 3a. Ouvrir phpMyAdmin

Dans votre navigateur, allez à :  
**http://localhost/phpmyadmin**

### 3b. Créer la base

1. Cliquez sur **Nouvelle base de données** (colonne de gauche)
2. Saisissez le nom : `rms_ticket`
3. Sélectionnez l'interclassement : `utf8mb4_unicode_ci`
4. Cliquez sur **Créer**

### 3c. Importer le fichier SQL

> **Important :** Le fichier `rms_ticket.sql` contient la base de données complète avec les comptes utilisateurs déjà créés.

1. Cliquez sur la base `rms_ticket` dans la colonne de gauche
2. Cliquez sur l'onglet **Importer** (en haut)
3. Cliquez sur **Choisir un fichier**
4. Sélectionnez le fichier : `C:\xampp\htdocs\RMS-Ticket-main\rms_ticket.sql`
5. Laissez tous les paramètres par défaut
6. Cliquez sur **Importer** (bouton en bas de page)

> Un message vert **"Importation réussie"** doit apparaître.

### 3d. Vérifier les tables créées

Vous devez voir **10 tables** dans la base `rms_ticket` :

- `admin_logs`
- `permissions`
- `roles`
- `role_permissions`
- `tickets`
- `ticket_comments`
- `ticket_responses`
- `users`
- `user_roles`
- `user_sessions`

---

## Étape 4 — Vérifier la configuration de connexion

Ouvrez le fichier `C:\xampp\htdocs\RMS-Ticket-main\Database\config.php` :

```php
const DB_HOST    = '127.0.0.1';
const DB_NAME    = 'rms_ticket';
const DB_USER    = 'root';
const DB_PASS    = '';          // Vide par défaut sur XAMPP
const DB_CHARSET = 'utf8mb4';
```

> Ces valeurs sont correctes pour une installation XAMPP standard. **Ne pas modifier** sauf si vous avez un mot de passe MySQL personnalisé.

---

## Étape 5 — Accéder au site web

Ouvrez votre navigateur et allez à :

**http://localhost/RMS-Ticket-main/**

Vous serez redirigé automatiquement vers la page de connexion.

---

## Étape 6 — Se connecter

Le fichier `rms_ticket.sql` contient plusieurs comptes déjà créés. Utilisez le compte jurys pour évaluer l'application :

| Champ | Valeur |
|---|---|
| **Email** | `jurys.sio@gmail.com` |
| **Mot de passe** | `Azerty123` |
| **Niveau d'accès** | Administrateur |

> Ce compte a accès à toutes les fonctionnalités, y compris le panneau d'administration.

---

## Récapitulatif des URLs de l'application

| Page | URL |
|---|---|
| Accueil | http://localhost/RMS-Ticket-main/ |
| Connexion | http://localhost/RMS-Ticket-main/Login/login.php |
| Créer un ticket | http://localhost/RMS-Ticket-main/CreateTickets/create_ticket.php |
| Mes tickets | http://localhost/RMS-Ticket-main/Tickets/my_tickets.php |
| Administration | http://localhost/RMS-Ticket-main/AdminPanel/adminpanel.php |

---

## Résolution des problèmes fréquents

| Problème | Solution |
|---|---|
| Page blanche ou erreur 500 | Vérifiez que Apache et MySQL sont bien démarrés dans XAMPP |
| "Erreur de connexion à la base de données" | Vérifiez que la base `rms_ticket` existe dans phpMyAdmin |
| "Table not found" | Relancez l'import du fichier `rms_ticket.sql` |
| Port 80 déjà utilisé | Dans XAMPP → Config Apache → changez le port en 8080, puis accédez via `http://localhost:8080/RMS-Ticket-main/` |
| Accès refusé phpMyAdmin | Identifiants par défaut XAMPP : login `root`, mot de passe vide |

---

## Structure du projet

```
RMS-Ticket-main/
│
├── rms_ticket.sql              → Base de données complète avec comptes pré-créés (jurys sio + autres utilisateurs)
├── index.php                   → Redirection vers la page d'accueil
│
├── Database/
│   ├── config.php              → Paramètres de connexion MySQL
│   └── connection.php          → Connexion PDO à la base de données
│
├── Login/
│   ├── login.php               → Authentification (email + mot de passe bcrypt)
│   ├── login.css               → Styles de la page de connexion
│   └── login.js                → Script JS (affichage/masquage mot de passe)
│
├── HomePage/
│   ├── index.php               → Page d'accueil (après connexion)
│   └── logout.php              → Déconnexion et destruction de session
│
├── CreateTickets/
│   ├── create_ticket.php       → Formulaire de création de ticket
│   ├── style.css               → Styles du formulaire
│   └── app.js                  → Validation JS côté client
│
├── Tickets/
│   ├── my_tickets.php          → Liste des tickets de l'utilisateur
│   ├── ticket.php              → Détail et chat d'un ticket
│   ├── chat_api.php            → API REST JSON (messages du chat)
│   ├── notifications_api.php   → API JSON (compteur de messages non lus)
│   ├── mark_read.php           → Marquer les messages comme lus
│   └── my_tickets.css          → Styles de la liste des tickets
│
└── AdminPanel/
    ├── adminpanel.php          → Panneau d'administration complet
    ├── adminpanel.css          → Styles du panneau admin
    └── adminpanel.js           → Scripts JS (modales, filtres, sélects)
```

---

## Technologies utilisées

| Couche | Technologie |
|---|---|
| Serveur web | Apache 2.4 (XAMPP) |
| Langage back-end | PHP 8.2 |
| Base de données | MariaDB 10.4 |
| Accès BDD | PDO (requêtes préparées) |
| Front-end | HTML5, CSS3, JavaScript Vanilla |
| Police | Google Fonts — Poppins |
| Versionnement | Git / GitHub |
