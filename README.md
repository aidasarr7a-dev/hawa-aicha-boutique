# Hawa Aïcha Boutique — site e-commerce PHP / MySQL

> *L’élégance au quotidien, en gros comme au détail.*

Boutique en ligne dynamique (PHP 8, MySQL/MariaDB, PDO, HTML5, CSS3, Bootstrap 5, JavaScript, Font Awesome),
conforme au cahier des charges `Cahier_des_charges_Hawa_Aicha_Boutique.pdf`.
Palette rose : `#FDEEF3` · `#F6C9D9` · `#E895B3` · `#C5628C` · `#9F3E63` · texte `#333333`.

---

## 1. Lancer le site sur XAMPP

1. Copiez le dossier `hawa-aicha-boutique` dans `C:\xampp\htdocs\`.
2. Ouvrez le **XAMPP Control Panel** et cliquez sur **Start** pour **Apache** et **MySQL**.
3. Ouvrez phpMyAdmin : <http://localhost/phpmyadmin>
   - **Installation neuve** (la base `hawa_aicha` n'existe pas) : onglet *Importer* → `schema.sql`.
   - **Base déjà existante** : faites d'abord un export (onglet *Exporter*), puis importez
     les scripts du dossier `migrations/` dans l'ordre (`…_v2_…` puis `…_v3_…`). Ils ne suppriment aucune
     donnée et peuvent être relancés sans risque. **Ne réimportez pas `schema.sql` sur une base existante.**
4. Ouvrez le site : <http://localhost/hawa-aicha-boutique/>

Les réglages de connexion MySQL se trouvent dans `config.php` (par défaut : `localhost`, `root`, sans mot de passe, base `hawa_aicha`).

## 2. Identifiants de démonstration (environnement local)

| Espace | Adresse | Identifiant | Mot de passe |
|---|---|---|---|
| Administration | <http://localhost/hawa-aicha-boutique/admin/login.php> | `admin` | `HawaDemo2026!` |

- Le mot de passe est stocké haché (`password_hash`), jamais en clair.
- **Changez-le dès la première connexion** : bouton « Mot de passe » de l'administration.
- Après 5 erreurs de connexion depuis la même adresse, la connexion est bloquée 15 minutes.

## 3. Informations à compléter avant la mise en ligne (`config.php`)

| Réglage | Valeur actuelle |
|---|---|
| `WHATSAPP_NUMBER`, `CONTACT_TELEPHONE` | 78 545 25 44 — **numéro de présentation du projet** |
| `WAVE_NUMERO`, `ORANGE_MONEY_NUMERO` | 78 545 25 44 — **numéro de présentation du projet** |
| `BOUTIQUE_ADRESSE` | Golf Sud, arrêt BRT de Golf Sud |
| `BOUTIQUE_LAT`, `BOUTIQUE_LON` | 14.76705, -17.41415 (arrêt « Golf Sud », Route de Niayes, d'après OpenStreetMap) — à ajuster au point exact de la boutique |
| `CONTACT_EMAIL`, `ZONE_LIVRAISON` | vides → le site affiche « à compléter » |
| Prix des 14 produits | prix de démonstration, à fixer dans l'administration |
| Logo | provisoire (`assets/img/logo.svg`) ; déposez le vrai logo sous `assets/img/logo.png`, il sera utilisé automatiquement |

Le tableau de bord de l'administration rappelle les informations encore vides.

## 4. Fonctionnalités

**Site public** : barre d'information (adresse, paiements, téléphone), accueil (slogan, mosaïque de photos, atouts, univers en photos, produits vedettes, présentation, **modes de paiement**, **carte de la boutique**), À propos,
catalogue (recherche, catégories, prix min/max, disponibilité), fiche produit (photo, stock, quantité),
panier en session (quantités, suppression, sous-totaux, total, message WhatsApp prérempli),
commande en 3 étapes (**nom complet, téléphone, adresse de livraison**, ville/commune, livraison à domicile ou retrait en boutique, paiement, instructions),
confirmation avec numéro de commande et consignes de paiement, reçu imprimable, contact avec carte, bouton WhatsApp.
La carte utilise OpenStreetMap (sans clé) ; le bouton « Itinéraire » ouvre Google Maps.

**Commande** : prix et quantités recalculés côté serveur depuis la base, transaction SQL
(stock décrémenté seulement s'il est suffisant), numéro unique `HA-AAAA-NNNNN`, jeton à usage unique
contre les doubles envois, commande bloquée si le stock a changé avant la validation.

**Paiements** (Wave, Orange Money, Espèces) : sans API officielle, chaque commande crée un paiement
**« en attente »**. L'administrateur le confirme **manuellement** après vérification réelle
(mode, date, référence facultative). Statuts : En attente, Payé, Échec/annulé, Remboursé —
indépendants du statut de la commande. Aucun paiement n'est marqué « Payé » automatiquement.

**Administration** : tableau de bord (produits, commandes, nouvelles commandes, paiements en attente,
ruptures, ventes réellement payées), produits (ajout, modification, photo, désactivation, suppression),
stock (à réapprovisionner, épuisés, masqués), catégories, commandes (recherche par numéro, téléphone
ou nom ; statuts Nouvelle → En préparation → Prête → Livrée/remise, ou Annulée ; frais de livraison),
paiements, reçu imprimable, changement de mot de passe, déconnexion sécurisée.

**Reçu** : récapitulatif A4 imprimable ou enregistrable en PDF depuis le navigateur. Il affiche
« Payé » uniquement après confirmation, sinon « Paiement en attente — non payé ».
Ce document n'est pas présenté comme une facture fiscale.

## 5. Base de données (`hawa_aicha`)

Le nom proposé par le cahier des charges (`hawaaicha_boutique`) n'a pas été repris : la base
`hawa_aicha` existait déjà et a été conservée. Correspondance avec le cahier des charges :

| Cahier des charges | Projet |
|---|---|
| `admins.mot_de_passe_hash` | `admins.password_hash` |
| `commandes.date_commande`, `montant_total`, `statut_commande`, `instructions` | `created_at`, `total`, `statut`, `remarque` |
| `details_commande` (`prix_unitaire`, `sous_total`) | `commande_items` (`prix`, `sous_total` calculé) |
| `produits.date_creation` | `produits.created_at` |
| `clients.nom_complet`, adresse de livraison | `commandes.nom_complet`, `commandes.adresse_livraison` (les anciens champs `prenom`, `nom`, `quartier` sont conservés pour l'historique) |
| `clients`, `paiements` | identiques |

Tables : `admins`, `tentatives_connexion`, `categories`, `produits`, `clients`, `commandes`,
`commande_items`, `paiements` (InnoDB, clés étrangères, index). Le nom et le prix de chaque
produit sont copiés dans la commande : modifier le catalogue ne change pas l'historique.
Le stock et le prix ne peuvent pas être négatifs (contraintes `CHECK`).

## 6. Sécurité

Requêtes préparées PDO · `password_hash` / `password_verify` · régénération de l'identifiant de session
à la connexion · déconnexion par formulaire POST protégé · protection CSRF sur tous les formulaires ·
échappement des sorties (`htmlspecialchars`) · validation serveur des champs, prix, quantités et statuts ·
photos contrôlées (type réel, extension, 3 Mo, nom aléatoire), exécution de scripts interdite dans `uploads/` ·
transactions SQL pour commande, stock et paiements · limitation des tentatives de connexion ·
fichiers internes (`.sql`, `.md`, `.pdf`, `migrations/`, `includes/`, `.git`) inaccessibles depuis le navigateur ·
messages d'erreur sans détail technique.

## 7. Structure

    index.php  apropos.php  produits.php  produit.php  panier.php  commander.php  merci.php  recu.php  contact.php
    admin/        connexion, tableau de bord, produits, catégories, commandes, paiements, mot de passe
    includes/     db.php (PDO), functions.php, header.php, footer.php
    assets/       css/style.css, css/recu.css, js/app.js, img/logo.svg
    uploads/      photos des produits (modifiables depuis l'administration)
    migrations/   scripts de mise à jour d'une base existante
    schema.sql    installation neuve        config.php    réglages

## 8. Photos de démonstration (14 produits)

Photos libres de droits issues d'[Unsplash](https://unsplash.com/license), sans marque visible,
remplaçables depuis l'administration (Produits → modifier → Photo) :
parfum d'intérieur `n8BjsYWTH8w`, parfum corporel `D1P-vxlB_K0`, crème solaire `Xp0bTCLD07k`,
sac à main `jKoRZkv2o0U`, voile `65v_6djIAFs`, jalabé `BGQDXS73ktE`, encens `fHT4wSJ-M04`, lait corporel `LtGwgq7r_mc`,
huile de soin `io6vt4rAFo0`, pochette `7VAGwA7ZpYI`, bijoux `aldDZePniqg`, lunettes `tUyg4nIQHPk`, sandales `b_k6k7McqcE`,
grand boubou `LWG50SyyNVw`
(page de chaque photo : `https://unsplash.com/photos/<identifiant>`).

## 9. Tests réalisés (XAMPP, PHP 8.2, MariaDB 10.4)

Accueil sans erreur et catalogue chargé depuis MySQL · photos, recherche, catégories, filtres prix et
disponibilité · ajout, modification et suppression dans le panier · commande enregistrée avec ses lignes,
son client, son paiement et un numéro unique · double envoi sans doublon · stock insuffisant bloqué ·
trois modes de paiement, confirmation manuelle, échec, remboursement · annulation : stock rétabli une
seule fois · reçu « en attente » puis « Payé » · accès au reçu limité · connexion, blocage après 5 échecs,
déconnexion, protection de toutes les pages admin · ajout, modification, désactivation, suppression d'un
produit, refus d'un faux fichier image · affichage ordinateur et téléphone (390 px).

## Pour aller plus loin

Intégration des API marchandes Wave / Orange Money (confirmation automatique), calcul automatique des
frais de livraison par zone, e-mail de confirmation de commande.
