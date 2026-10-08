# 🏺 The_legacy_house — Marketplace PHP

> Projet soutenance — Reproduction de site type Leboncoin  
> Stack : PHP · MySQL · Bootstrap 5 · HTML/CSS custom

---

## ✨ Fonctionnalités

| Module | Détail |
|--------|--------|
| **Auth** | Inscription, Connexion, Déconnexion, Session sécurisée |
| **Profil** | Affichage, Modification (pseudo, email, mdp, avatar) |
| **Annonces** | Créer, Lister, Modifier, Supprimer (propriétaire uniquement) |
| **Accueil** | Dernières annonces, recherche par titre, filtre catégorie |
| **Favoris** | Ajouter/retirer, page "Mes favoris", toggle AJAX |
| **Messagerie** | Discussions 1 fil = 1 annonce + 2 users, messages en temps réel |
| **Administration** | Liste users, Activer/Désactiver, Promouvoir admin, Supprimer |

---

## 🚀 Installation

### 1. Prérequis
- PHP ≥ 8.0
- MySQL ≥ 5.7 / MariaDB
- Apache (WAMP / XAMPP / Laragon) avec `mod_rewrite`

### 2. Base de données
```sql
-- Dans phpMyAdmin ou ligne de commande :
mysql -u root -p < the_legacy_house.sql
```

### 3. Configuration
Ouvrez `includes/config.php` et adaptez :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'the_legacy_house');
define('DB_USER', 'root');
define('DB_PASS', '');         // Votre mot de passe MySQL
define('BASE_URL', 'http://localhost/the_legacy_house');
```

### 4. Dossier uploads
```bash
chmod 755 uploads/
```
*(Sous Windows, vérifiez les droits d'écriture du dossier `uploads/`)*

### 5. Compte admin par défaut
| Champ | Valeur |
|-------|--------|
| Email | `admin@the_legacy_house.fr` |
| Mot de passe | `Admin1234!` |

> **⚠️ Changez le mot de passe admin dès la première connexion !**

---

## 📁 Structure du projet

```
the_legacy_house/
├── index.php               ← Accueil + recherche + catégories
├── login.php               ← Connexion
├── register.php            ← Inscription
├── logout.php              ← Déconnexion
├── profil.php              ← Profil + mes annonces + édition
├── favoris.php             ← Mes favoris (+ endpoint AJAX)
├── messagerie.php          ← Discussions & messages
│
├── annonce/
│   ├── create.php          ← Déposer une annonce
│   ├── detail.php          ← Détail annonce
│   ├── edit.php            ← Modifier
│   └── delete.php          ← Supprimer (POST)
│
├── admin/
│   └── index.php           ← Gestion utilisateurs + stats
│
├── includes/
│   ├── config.php          ← Configuration générale
│   ├── db.php              ← Connexion PDO singleton
│   ├── functions.php       ← Helpers (auth, upload, format…)
│   ├── header.php          ← Navbar + <head>
│   └── footer.php          ← Footer + scripts
│
├── css/
│   └── style.css           ← Design custom dark premium
│
├── js/
│   └── main.js             ← Favoris AJAX, animations, UX
│
├── uploads/                ← Images uploadées (vide au départ)
│
└── the_legacy_house.sql            ← Export base de données
```

---

## 🔒 Sécurité

- Mots de passe hashés avec `password_hash()` (bcrypt)
- Requêtes PDO préparées — protection SQL injection
- Upload sécurisé : vérification extension + MIME réel
- Contrôle d'accès : propriétaire/admin pour modifier/supprimer
- `htmlspecialchars()` sur toutes les sorties HTML

---

## 🎨 Choix techniques & design

- **Design dark premium** avec palette ambrée/dorée, unique par rapport au Leboncoin original
- **Google Fonts** : Playfair Display (serif) + DM Sans — élégant et lisible
- **Bootstrap 5** pour la grille responsive + composants (tabs, badges…)
- **CSS custom** : variables, animations CSS, scroll personnalisé
- **AJAX** pour le toggle favori (sans rechargement de page)
- **PDO singleton** pour éviter les connexions multiples
- **Catégories** avec emojis pour une navigation intuitive

---

## 💡 Améliorations possibles

1. **Géolocalisation** : annonces filtrables par ville/département
2. **Notation vendeur** : système d'avis après transaction
3. **Notifications email** : alerte nouveau message
4. **Pagination des messages** : infini scroll dans la messagerie
5. **Recherche avancée** : fourchette de prix, état, catégorie combinés
6. **Mode sombre/clair** : toggle utilisateur

---

*Projet réalisé dans le cadre d'un cours PHP/MySQL — © 2025*
