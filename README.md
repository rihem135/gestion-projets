# 📊 Gestion Collaborative de Projets

## 📋 Description

Application web de **gestion collaborative de projets** permettant aux équipes de planifier, suivre et organiser efficacement leurs tâches.

Dans de nombreux environnements professionnels, la gestion de projet est souvent dispersée : outils de communication non intégrés, absence de suivi centralisé et mauvaise visibilité sur l'avancement des tâches. Cette application répond à ces problématiques en offrant une solution complète et centralisée.

## ✨ Fonctionnalités

### 🔐 Authentification et Sécurité
- 📝 **Inscription** : Création de compte avec validation des champs
- 🔑 **Connexion** : Authentification sécurisée avec gestion des sessions
- 👑 **Gestion des rôles** : Distinction Administrateur / Membre
- 🔄 **Réinitialisation de mot de passe** : Fonctionnalité "Mot de passe oublié"
- 🚪 **Déconnexion** : Fermeture sécurisée des sessions

### 👑 Module Administrateur

| Fonctionnalité | Description |
|----------------|-------------|
| 📊 **Tableau de bord** | Vue d'ensemble avec statistiques (nombre de projets, tâches, utilisateurs) |
| 📋 **Gestion des projets** | Ajouter, modifier, supprimer des projets avec nom, description et échéance |
| ✅ **Gestion des tâches** | Créer, modifier, supprimer et assigner des tâches aux membres |
| 📈 **Suivi des statuts** | Modifier les statuts : À faire, En cours, Terminé |
| 💬 **Messagerie interne** | Communication directe avec les membres de l'équipe |
| 📄 **Export de rapports PDF** | Génération de rapports d'avancement détaillés |
| 📅 **Calendrier interactif** | Visualisation des projets et échéances sur un calendrier |

### 👤 Module Membre

| Fonctionnalité | Description |
|----------------|-------------|
| 📊 **Tableau de bord** | Vue personnalisée des tâches assignées et de leur progression |
| ✅ **Gestion des tâches** | Visualiser et mettre à jour les statuts des tâches assignées |
| 🎯 **Tâches publiques** | Prendre en charge de nouvelles tâches disponibles |
| 💬 **Messagerie interne** | Communication avec l'administrateur et l'équipe |
| 📅 **Calendrier** | Visualisation des échéances personnelles |
| 📄 **Rapports PDF** | Export de ses rapports d'avancement |

### 💬 Messagerie Interne
- Communication fluide entre Administrateur et Membres
- Échanges entre membres de l'équipe
- Interface intuitive avec historique des messages

### 📅 Calendrier
- Visualisation des projets et leurs échéances
- Affichage des tâches par date
- Suivi des deadlines importantes
- Vue d'ensemble des plannings

### 📄 Rapports PDF
- Génération automatique de rapports d'avancement
- Statistiques détaillées des projets
- Taux d'achèvement des tâches
- Suivi individuel des performances

## 🛠️ Technologies Utilisées

### Frontend
| Technologie | Version | Utilisation |
|-------------|---------|-------------|
| **HTML5** | - | Structure des pages web |
| **CSS3** | - | Styles et mise en page responsive |
| **JavaScript** | ES6 | Interactions dynamiques et animations |
| **Bootstrap** | 5.x | Framework CSS pour une interface moderne |

### Backend
| Technologie | Version | Utilisation |
|-------------|---------|-------------|
| **PHP** | 8.x | Logique serveur et traitement des données |
| **MySQL** | 5.7+ | Base de données relationnelle |

### Environnement de Développement
| Outil | Utilisation |
|-------|-------------|
| **XAMPP** | Serveur local (Apache, PHP, MySQL) |
| **phpMyAdmin** | Gestion de la base de données |
| **Visual Studio Code** | Éditeur de code |

📊 Base de données
Structure principale
users : Gestion des utilisateurs (id, name, email, password, rôle, created_at, last_project_id)

projects : Gestion des projets (id, name, description, created_by, deadline, created_at)

tasks : Gestion des tâches (id, project_id, title, description, statut, deadline, is_public, assigned_to, created_at, updated_at)

messages : Messagerie interne (id, sender_id, receiver_id, subject, message, file, sent_at)
