<?php
/**
 * Comptes administrateurs : règles de validation communes à « Mon profil » et « Administrateurs ».
 */

const MDP_LONGUEUR_MIN = 8;

/** Mot de passe sécurisé : 8 caractères minimum, au moins une lettre et un chiffre. */
function erreurs_mot_de_passe(string $mdp, string $confirmation): array
{
    $erreurs = [];
    if (mb_strlen($mdp) < MDP_LONGUEUR_MIN || !preg_match('/\p{L}/u', $mdp) || !preg_match('/\d/', $mdp)) {
        $erreurs[] = 'Le mot de passe doit contenir au moins ' . MDP_LONGUEUR_MIN . ' caractères, dont au moins une lettre et un chiffre.';
    }
    if (mb_strlen($mdp) > 72) {
        $erreurs[] = 'Le mot de passe est trop long (72 caractères maximum).';
    }
    if ($mdp !== $confirmation) {
        $erreurs[] = 'La confirmation du mot de passe ne correspond pas.';
    }
    return $erreurs;
}

/**
 * Vérifie le nom, l'identifiant et l'e-mail d'un compte, ainsi que l'absence de doublon
 * (identifiant ou e-mail déjà utilisés par un autre compte — $idExclu = le compte modifié).
 */
function erreurs_compte(array $v, int $idExclu = 0): array
{
    $erreurs = [];
    foreach ($v as $texte) {
        if (!mb_check_encoding((string)$texte, 'UTF-8')) {
            return ['Certains caractères envoyés sont invalides. Merci de ressaisir le formulaire.'];
        }
    }
    if (mb_strlen($v['prenom']) < 2 || mb_strlen($v['prenom']) > 100) {
        $erreurs[] = 'Le prénom doit contenir entre 2 et 100 caractères.';
    }
    if (mb_strlen($v['nom']) < 2 || mb_strlen($v['nom']) > 100) {
        $erreurs[] = 'Le nom doit contenir entre 2 et 100 caractères.';
    }
    if ($v['telephone'] !== '' && !preg_match('/^\+?[0-9 .\-()]{6,30}$/', $v['telephone'])) {
        $erreurs[] = 'Le numéro de téléphone n\'est pas valide (chiffres, espaces et + uniquement).';
    }
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $v['username'])) {
        $erreurs[] = 'Le nom d\'utilisateur doit contenir 3 à 50 caractères : lettres sans accent, chiffres, point, tiret ou tiret bas.';
    }
    if ($v['email'] === '' || !filter_var($v['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($v['email']) > 150) {
        $erreurs[] = 'L\'adresse e-mail n\'est pas valide.';
    }
    if (!$erreurs) {
        // La connexion accepte l'identifiant OU l'e-mail : aucun des deux ne doit pouvoir désigner un autre compte
        $st = db()->prepare('SELECT username, email FROM admins
                              WHERE id <> ? AND (username IN (?, ?) OR email IN (?, ?))');
        $st->execute([$idExclu, $v['username'], $v['email'], $v['username'], $v['email']]);
        foreach ($st->fetchAll() as $autre) {
            if (strcasecmp($autre['username'], $v['username']) === 0 || strcasecmp((string)$autre['email'], $v['username']) === 0) {
                $erreurs[] = 'Ce nom d\'utilisateur est déjà utilisé par un autre compte.';
            }
            if (strcasecmp((string)$autre['email'], $v['email']) === 0 || strcasecmp($autre['username'], $v['email']) === 0) {
                $erreurs[] = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
            }
        }
    }
    return array_values(array_unique($erreurs));
}

/** Nombre d'administrateurs principaux actifs (protection du dernier compte principal). */
function nb_principaux_actifs(): int
{
    return (int)db()->query("SELECT COUNT(*) FROM admins WHERE role = 'principal' AND actif = 1")->fetchColumn();
}

/** Récupère les champs texte d'un formulaire de compte. */
function champs_compte(): array
{
    return [
        'prenom'    => trim((string)($_POST['prenom'] ?? '')),
        'nom'       => trim((string)($_POST['nom'] ?? '')),
        'username'  => trim((string)($_POST['username'] ?? '')),
        'email'     => mb_strtolower(trim((string)($_POST['email'] ?? ''))),
        'telephone' => trim((string)($_POST['telephone'] ?? '')),
    ];
}

/** Nom affiché d'un compte : « Prénom Nom », ou l'identifiant à défaut. */
function nom_complet_admin(array $a): string
{
    $complet = trim(($a['prenom'] ?? '') . ' ' . ($a['nom'] ?? ''));
    return $complet !== '' ? $complet : (string)($a['username'] ?? '');
}

/** Champ mot de passe avec bouton « afficher / masquer » (géré par admin.js). */
function champ_mot_de_passe(string $id, string $name, string $autocomplete, bool $requis = false, string $attrs = ''): string
{
    return '<div class="input-group champ-mdp">'
         . '<input class="form-control" id="' . e($id) . '" name="' . e($name) . '" type="password" autocomplete="' . e($autocomplete) . '"' . ($requis ? ' required' : '') . ' ' . $attrs . '>'
         . '<button class="btn btn-voir-mdp" type="button" data-voir-mdp="' . e($id) . '" aria-label="Afficher le mot de passe" title="Afficher le mot de passe"><i class="fa-regular fa-eye"></i></button>'
         . '</div>';
}
