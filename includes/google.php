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

/** Adresse de retour déclarée chez Google : doit être identique à celle enregistrée dans la console Google. */
function google_url_retour(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443;
    $hote = preg_replace('/[^A-Za-z0-9.\-:]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return ($https ? 'https' : 'http') . '://' . $hote . base_url('google-retour.php');
}

/** Prépare la redirection vers Google et mémorise state / nonce / page de retour en session. */
function google_url_connexion(string $retour): string
{
    $_SESSION['google_oauth'] = [
        'state'  => bin2hex(random_bytes(16)),
        'nonce'  => bin2hex(random_bytes(16)),
        'retour' => $retour,
        'debut'  => time(),
    ];
    return GOOGLE_URL_AUTORISATION . '?' . http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => google_url_retour(),
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
            'redirect_uri'  => google_url_retour(),
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
