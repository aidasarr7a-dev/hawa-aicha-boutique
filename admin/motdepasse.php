<?php
// Le changement de mot de passe fait désormais partie de « Mon profil » : l'ancienne adresse y renvoie.
require_once __DIR__ . '/_auth.php';
redirect('profil.php#mot-de-passe');
