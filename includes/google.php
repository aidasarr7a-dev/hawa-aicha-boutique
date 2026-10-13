<?php
/**
 * Connexion des clients avec Google (OAuth 2.0 / OpenID Connect, « code d'autorisation »).
 * Aucune bibliothèque externe : échanges directs avec les serveurs de Google en HTTPS (cURL).
 *
 * Sécurité :
 *  - « state » aléatoire stocké en session : bloque les requêtes forgées (CSRF) au retour de Google ;
 *  - « nonce » aléatoire vérifié dans le jeton d'identité : empêche le rejeu d'un ancien jeton ;
 *  - le jeton d'identité est reçu directement de Google en HTTPS (pas via le navigateur),
 *    et ses champs iss / aud / exp / nonce / email_verified sont contrôlés.
 */

const GOOGLE_URL_AUTORISATION = 'https://accounts.google.com/o/oauth2/v2/auth';
const GOOGLE_URL_JETON        = 'https://oauth2.googleapis.com/token';

/** Vrai si les identifiants OAuth Google sont configurés (sinon le bouton n'est pas affiché). */
function google_actif(): bool
{
    return trim(GOOGLE_CLIENT_ID) !== '' && trim(GOOGLE_CLIENT_SECRET) !== '';
}

/** Pages qui reçoivent la réponse de Google (« URI de redirection autorisés » de la console Google). */
const GOOGLE_PAGES_RETOUR = ['connexion' => 'connexion.php', 'inscription' => 'inscription.php'];

/**
 * Adresse complète d'une page de retour (connexion.php ou inscription.php) :
 * doit être identique, caractère pour caractère, à celle enregistrée dans la console Google.
 */
function google_url_retour(string $depuis = 'connexion'): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443;
    $hote = preg_replace('/[^A-Za-z0-9.\-:]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return ($https ? 'https' : 'http') . '://' . $hote . base_url(GOOGLE_PAGES_RETOUR[$depuis] ?? GOOGLE_PAGES_RETOUR['connexion']);
}

/** Prépare la redirection vers Google et mémorise state / nonce / page de retour en session. */
function google_url_connexion(string $retour, string $depuis = 'connexion'): string
{
    $depuis = isset(GOOGLE_PAGES_RETOUR[$depuis]) ? $depuis : 'connexion';
    $_SESSION['google_oauth'] = [
        'state'        => bin2hex(random_bytes(16)),
        'nonce'        => bin2hex(random_bytes(16)),
        'retour'       => $retour,
        'redirect_uri' => google_url_retour($depuis),   // la même adresse doit être renvoyée lors de l'échange du code
        'debut'        => time(),
    ];
    return GOOGLE_URL_AUTORISATION . '?' . http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => $_SESSION['google_oauth']['redirect_uri'],
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $_SESSION['google_oauth']['state'],
        'nonce'         => $_SESSION['google_oauth']['nonce'],
        'prompt'        => 'select_account',
    ]);
}

/**
 * Traite le retour de Google : vérifie « state », échange le code contre le jeton d'identité,
 * contrôle le jeton et renvoie le profil [sub, email, nom]. Lève une RuntimeException en cas de problème.
 */
function google_profil_depuis_retour(array $get): array
{
    $attente = $_SESSION['google_oauth'] ?? null;
    unset($_SESSION['google_oauth']);   // usage unique

    if (isset($get['error'])) {
        throw new RuntimeException($get['error'] === 'access_denied'
            ? 'Connexion avec Google annulée.'
            : 'Google a refusé la connexion. Merci de réessayer.');
    }
    if (!$attente || empty($get['state']) || !hash_equals($attente['state'], (string)$get['state'])
        || time() - (int)$attente['debut'] > 600) {
        throw new RuntimeException('La demande de connexion a expiré ou n\'est pas valide. Merci de réessayer.');
    }
    if (empty($get['code'])) {
        throw new RuntimeException('Réponse de Google incomplète. Merci de réessayer.');
    }

    // Échange du code contre les jetons (serveur à serveur, HTTPS vérifié)
    $ch = curl_init(GOOGLE_URL_JETON);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'code'          => (string)$get['code'],
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => $attente['redirect_uri'] ?? google_url_retour(),
            'grant_type'    => 'authorization_code',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $reponse = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $jetons = is_string($reponse) ? json_decode($reponse, true) : null;
    if ($code !== 200 || empty($jetons['id_token'])) {
        throw new RuntimeException('Impossible de vérifier votre compte Google pour le moment. Merci de réessayer.');
    }

    // Lecture et contrôle du jeton d'identité (reçu directement de Google en HTTPS)
    $parties = explode('.', $jetons['id_token']);
    $donnees = count($parties) === 3 ? json_decode(base64_decode(strtr($parties[1], '-_', '+/')), true) : null;
    if (!is_array($donnees)
        || !in_array($donnees['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)
        || ($donnees['aud'] ?? '') !== GOOGLE_CLIENT_ID
        || (int)($donnees['exp'] ?? 0) < time()
        || !hash_equals($attente['nonce'], (string)($donnees['nonce'] ?? ''))
        || empty($donnees['sub'])) {
        throw new RuntimeException('La réponse de Google n\'a pas pu être vérifiée. Merci de réessayer.');
    }
    if (empty($donnees['email']) || ($donnees['email_verified'] ?? false) !== true) {
        throw new RuntimeException('Votre adresse e-mail Google n\'est pas vérifiée : connexion impossible.');
    }

    return [
        'sub'    => (string)$donnees['sub'],
        'email'  => mb_strtolower((string)$donnees['email']),
        'nom'    => trim((string)($donnees['name'] ?? '')),
        'retour' => (string)$attente['retour'],
    ];
}

/** Ouvre la session du client (même fonctionnement que la connexion par mot de passe). */
function google_ouvrir_session_client(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['client_id'] = $id;
    db()->prepare('UPDATE clients SET derniere_connexion = NOW() WHERE id = ?')->execute([$id]);
}

/** Page de retour locale autorisée (même règle que compte.php). */
function google_retour_sur(string $retour): string
{
    return preg_match('/^[a-z_-]+\.php(\?[\w=&%.+-]*)?$/i', $retour) ? $retour : 'mon-compte.php';
}

/** Vrai si la requête actuelle est une réponse de Google (code ou erreur, avec « state »). */
function google_est_un_retour(array $get): bool
{
    return isset($get['state']) && (isset($get['code']) || isset($get['error']));
}

/**
 * Traite la réponse de Google puis redirige (ne revient jamais) :
 *  - compte déjà lié à ce Google → connexion ;
 *  - compte existant avec la même adresse e-mail (vérifiée par Google) → liaison puis connexion ;
 *  - sinon → dernière étape : le numéro de téléphone (identifiant des commandes de la boutique).
 */
function google_traiter_retour(array $get): void
{
    if (!google_actif()) {
        redirect(base_url('connexion.php'));
    }
    try {
        $profil = google_profil_depuis_retour($get);
    } catch (RuntimeException $e) {
        flash('danger', e($e->getMessage()));
        redirect(base_url('connexion.php'));
    }

    $pdo = db();
    $retour = google_retour_sur($profil['retour']);

    // 1. Compte déjà lié à ce compte Google
    $st = $pdo->prepare('SELECT * FROM clients WHERE google_id = ?');
    $st->execute([$profil['sub']]);
    $client = $st->fetch();

    // 2. Compte existant avec la même adresse e-mail : on le lie à Google
    if (!$client) {
        $st = $pdo->prepare('SELECT * FROM clients WHERE email = ? AND google_id IS NULL');
        $st->execute([$profil['email']]);
        $client = $st->fetch();
        if ($client) {
            // Une fiche « sans compte » devient un vrai compte ; un compte avec mot de passe le garde
            $hash = $client['mot_de_passe_hash'] ?: password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE clients SET google_id = ?, mot_de_passe_hash = ? WHERE id = ?')
                ->execute([$profil['sub'], $hash, $client['id']]);
        }
    }

    if ($client) {
        google_ouvrir_session_client((int)$client['id']);
        flash('success', 'Bienvenue ' . e(strtok($client['nom_complet'], ' ')) . ' ! Vous êtes connecté(e) avec Google.');
        redirect(base_url($retour));
    }

    // 3. Nouveau client : il ne reste que le numéro de téléphone à demander
    $_SESSION['google_inscription'] = $profil + ['debut' => time()];
    redirect(base_url('google-terminer.php'));
}
