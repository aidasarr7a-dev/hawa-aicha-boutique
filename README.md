# Hawa Aïcha Boutique — site e-commerce

Boutique en ligne mobile-first (HTML, CSS, Bootstrap 5, JavaScript, PHP, MySQL, PDO, Font Awesome).
Thème rose et blanc (#fdf2f7 · #d63384 · #f8c8dc).

## Installation (XAMPP / WAMP / hébergement PHP)

1. Copiez le dossier `hawa-aicha-boutique` dans `htdocs` (XAMPP) ou à la racine de votre hébergement.
2. Dans phpMyAdmin, importez `schema.sql` (crée la base `hawa_aicha`, les tables, les catégories et des produits d'exemple).
3. Ouvrez `config.php` et vérifiez : identifiants MySQL, **numéro WhatsApp** (format international sans « + », ex. `221770000000`).
4. Ouvrez `http://localhost/hawa-aicha-boutique/`.

## Espace administrateur

- Adresse : `/admin/login.php` (lien discret en bas du site)
- Identifiant : `admin` — mot de passe : `admin123`
- **Changez le mot de passe dès la première connexion** (bouton « Mot de passe »).

L'administrateur peut : ajouter / modifier / supprimer les produits (prix, stock, photo, « Nouveau », visible/masqué), gérer les catégories, consulter les commandes, changer leur statut (une commande annulée remet les articles en stock) et contacter le client sur WhatsApp.

## Logo

Un logo provisoire est fourni (`assets/img/logo.svg`). Pour utiliser le vrai logo Hawa Aïcha, déposez-le sous le nom `assets/img/logo.png` : il sera utilisé automatiquement.

## Parcours client

Produit → Ajouter au panier → Panier → Informations client → Commande → Confirmation (+ envoi WhatsApp pré-rempli).
Le stock est décrémenté à la commande ; les prix sont toujours recalculés côté serveur.

## Sécurité incluse

Requêtes préparées PDO, protection CSRF, mots de passe hachés, échappement des sorties, validation et renommage des images envoyées (JPG/PNG/WebP, 3 Mo), exécution de scripts interdite dans `uploads/`.

## Structure

    index.php  produits.php  produit.php  panier.php  commander.php  merci.php  contact.php
    admin/     connexion, tableau de bord, produits, catégories, commandes, mot de passe
    includes/  db.php, functions.php, header.php, footer.php
    assets/    css/style.css, js/app.js, img/logo.svg
    uploads/   photos des produits
    schema.sql, config.php

## Pour aller plus loin

Intégration du paiement Wave / Orange Money via leur API marchande, calcul automatique des frais de livraison par zone.
