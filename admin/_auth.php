<?php
require_once __DIR__ . '/../includes/functions.php';

/**
 * Contrôle d'accès de l'administration.
 * Le compte est relu en base à chaque page : une désactivation ou un changement de rôle
 * s'applique immédiatement, même pour une session déjà ouverte.
 */
function require_admin(): void
{
    global $adminConnecte;
    if (empty($_SESSION['admin_id'])) {
        redirect(base_url('admin/login.php'));
    }
    $st = db()->prepare('SELECT id, username, prenom, nom, email, telephone, role, actif, derniere_connexion, date_creation FROM admins WHERE id = ?');
    $st->execute([(int)$_SESSION['admin_id']]);
    $adminConnecte = $st->fetch() ?: null;
    if (!$adminConnecte || !(int)$adminConnecte['actif']) {
        unset($_SESSION['admin_id'], $_SESSION['admin_nom'], $_SESSION['admin_role']);
        session_regenerate_id(true);
        flash('warning', 'Votre compte administrateur est désactivé ou n\'existe plus.');
        redirect(base_url('admin/login.php'));
    }
    $_SESSION['admin_nom'] = trim(($adminConnecte['prenom'] ?? '') . ' ' . $adminConnecte['nom']) ?: $adminConnecte['username'];
    $_SESSION['admin_role'] = $adminConnecte['role'];
}

/** Vrai si l'administrateur connecté est l'administrateur principal. */
function est_principal(): bool
{
    global $adminConnecte;
    return ($adminConnecte['role'] ?? '') === 'principal';
}

/**
 * Bloque une page ou une action réservée à l'administrateur principal (contrôle côté serveur).
 * Les employés sont renvoyés vers $retour avec une notification d'erreur.
 */
function exiger_principal(string $retour = 'index.php', string $message = 'Action réservée à l\'administrateur principal.'): void
{
    if (!est_principal()) {
        flash('danger', $message);
        redirect($retour);
    }
}

/** Libellés et icônes des rôles. */
const ROLES_ADMIN = [
    'principal' => ['Administrateur principal', 'fa-crown'],
    'employe'   => ['Employé', 'fa-user-tie'],
];

require_admin();
