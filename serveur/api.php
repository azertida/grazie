<?php
/*
 * Grazie — petit serveur du cercle de proches (version 2)
 *
 * Principes (repris de Corsia) :
 *  - un « cercle » = un code secret partagé par lien ; sans code, on ne voit rien ;
 *  - une annonce = un don (« je donne ») ou une demande (« je cherche ») ;
 *  - celle ou celui qui publie reçoit un jeton que seul son téléphone connaît
 *    (le serveur n'en garde qu'une empreinte) ; idem pour qui répond ;
 *  - aucune fiche de personne : un prénom par annonce ou par réponse, rien d'autre ;
 *  - une annonce close est effacée tout de suite ; une annonce oubliée
 *    disparaît d'elle-même après DUREE_JOURS ;
 *  - aucune adresse IP ni autre trace n'est enregistrée par ce script.
 *
 * Emplacement : www/grazie/api.php — les données vivent hors du site,
 * dans ~/grazie-data/grazie.sqlite (créé automatiquement).
 */

date_default_timezone_set('Europe/Brussels');

const ORIGINES_AUTORISEES = ['https://azertida.github.io'];
const SENS = ['don', 'demande'];
const CATEGORIES = ['l', 'v', 'o'];          // livres, vêtements, autre
const DUREE_JOURS = 90;                       // une annonce oubliée s'efface après 90 jours
const MAX_PRENOM = 20;
const MAX_TITRE = 60;
const MAX_MOT = 140;
const MAX_NOM_CERCLE = 40;
const MAX_ANNONCES_PAR_CERCLE = 150;

/* ---------- En-têtes ---------- */

$origine = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origine, ORIGINES_AUTORISEES, true)) {
    header('Access-Control-Allow-Origin: ' . $origine);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Max-Age: 86400');
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/* ---------- Outils ---------- */

function repondre($donnees, int $code = 200): void {
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}

function erreur(string $message, int $code = 400): void {
    repondre(['erreur' => $message], $code);
}

function aleatoire(int $octets): string {
    return bin2hex(random_bytes($octets));
}

function empreinte(string $jeton): string {
    return hash('sha256', $jeton);
}

function jeton_ok(string $hash, $jeton): bool {
    return $hash !== '' && is_string($jeton) && $jeton !== '' && hash_equals($hash, empreinte($jeton));
}

function texte($valeur, int $max): string {
    $t = trim(preg_replace('/\s+/u', ' ', (string)($valeur ?? '')));
    $t = preg_replace('/[\x00-\x1F\x7F]/u', '', $t);
    if (mb_strlen($t) > $max) $t = mb_substr($t, 0, $max);
    return $t;
}

function prenom_valide($valeur): string {
    $p = texte($valeur, MAX_PRENOM);
    if ($p === '') erreur('Prénom manquant');
    return $p;
}

function aujourdhui(): string { return date('Y-m-d'); }
function il_y_a(int $jours): string { return date('Y-m-d', strtotime("-$jours days")); }

function base(): PDO {
    $dossier = dirname(__DIR__, 2) . '/grazie-data';
    if (!is_dir($dossier) && !mkdir($dossier, 0700, true)) {
        erreur('Stockage indisponible', 500);
    }
    $db = new PDO('sqlite:' . $dossier . '/grazie.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA busy_timeout = 3000');
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('
        CREATE TABLE IF NOT EXISTS cercles (
            code        TEXT PRIMARY KEY,
            admin_hash  TEXT NOT NULL,
            nom         TEXT NOT NULL DEFAULT \'\'
        );
        CREATE TABLE IF NOT EXISTS annonces (
            id          TEXT PRIMARY KEY,
            cercle      TEXT NOT NULL REFERENCES cercles(code) ON DELETE CASCADE,
            sens        TEXT NOT NULL,              -- "don" ou "demande"
            categorie   TEXT NOT NULL,              -- "l", "v" ou "o"
            titre       TEXT NOT NULL,
            mot         TEXT NOT NULL DEFAULT \'\',
            prenom      TEXT NOT NULL,
            jeton_hash  TEXT NOT NULL,
            cree        TEXT NOT NULL,              -- "2026-10-19"
            rep_prenom  TEXT NOT NULL DEFAULT \'\',  -- qui a répondu (« Je le prends » / « J\'en ai un »)
            rep_hash    TEXT NOT NULL DEFAULT \'\',
            rep_date    TEXT NOT NULL DEFAULT \'\'
        );
        CREATE INDEX IF NOT EXISTS idx_annonces ON annonces(cercle, cree);
    ');
    // Effacement automatique des annonces oubliées.
    $db->prepare('DELETE FROM annonces WHERE cree < ?')->execute([il_y_a(DUREE_JOURS)]);
    return $db;
}

function cercle(PDO $db, string $code): array {
    $st = $db->prepare('SELECT * FROM cercles WHERE code = ?');
    $st->execute([$code]);
    $c = $st->fetch();
    if (!$c) erreur('Cercle inconnu', 404);
    return $c;
}

function est_admin(array $cercle, $cle): bool {
    return is_string($cle) && $cle !== '' && hash_equals($cercle['admin_hash'], empreinte($cle));
}

function annonce(PDO $db, string $cercle, $id): array {
    $st = $db->prepare('SELECT * FROM annonces WHERE id = ? AND cercle = ?');
    $st->execute([(string)($id ?? ''), $cercle]);
    $a = $st->fetch();
    if (!$a) erreur('Cette annonce n\'existe plus', 404);
    return $a;
}

/* ---------- Lecture de la requête ---------- */

$methode = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$entree = [];
if ($methode === 'POST') {
    $brut = file_get_contents('php://input', false, null, 0, 8192);
    $entree = json_decode($brut ?: '[]', true);
    if (!is_array($entree)) erreur('Requête illisible');
} elseif ($methode !== 'GET') {
    erreur('Méthode non prise en charge', 405);
}
$action = $methode === 'GET' ? ($_GET['action'] ?? '') : ($entree['action'] ?? '');
$code   = $methode === 'GET' ? ($_GET['c'] ?? '')      : ($entree['c'] ?? '');

try {
    $db = base();

    /* ----- Créer un cercle -----
       Le tout premier cercle peut être créé librement.
       Ensuite, il faut la clé de gestion d'un cercle existant. */
    if ($action === 'creer_cercle' && $methode === 'POST') {
        $nb = (int)$db->query('SELECT COUNT(*) FROM cercles')->fetchColumn();
        if ($nb > 0) {
            $ok = false;
            $cle = $entree['cle_existante'] ?? '';
            if (is_string($cle) && $cle !== '') {
                $st = $db->prepare('SELECT 1 FROM cercles WHERE admin_hash = ?');
                $st->execute([empreinte($cle)]);
                $ok = (bool)$st->fetchColumn();
            }
            if (!$ok) erreur('Création réservée', 403);
        }
        $nouveau = aleatoire(9);
        $admin = aleatoire(16);
        $db->prepare('INSERT INTO cercles (code, admin_hash, nom) VALUES (?, ?, ?)')
           ->execute([$nouveau, empreinte($admin), texte($entree['nom'] ?? '', MAX_NOM_CERCLE)]);
        repondre(['c' => $nouveau, 'admin' => $admin]);
    }

    if (!is_string($code) || !preg_match('/^[0-9a-f]{18}$/', $code)) erreur('Code de cercle manquant', 400);
    $cer = cercle($db, $code);

    if ($action !== 'liste' && $methode !== 'POST') erreur('POST attendu', 405);

    switch ($action) {

        /* ----- Lire les annonces du cercle ----- */
        case 'liste':
            $st = $db->prepare('SELECT id, sens, categorie, titre, mot, prenom, cree, rep_prenom, rep_date
                                FROM annonces WHERE cercle = ? ORDER BY cree DESC, rowid DESC');
            $st->execute([$code]);
            $rep = ['nom' => $cer['nom'], 'annonces' => $st->fetchAll(), 'duree' => DUREE_JOURS];
            if (est_admin($cer, $_GET['admin'] ?? '')) $rep['admin'] = true;
            repondre($rep);

        /* ----- Publier un don ou une demande ----- */
        case 'publier':
            $sens = (string)($entree['sens'] ?? '');
            if (!in_array($sens, SENS, true)) erreur('Sens inconnu');
            $categorie = (string)($entree['categorie'] ?? 'o');
            if (!in_array($categorie, CATEGORIES, true)) erreur('Catégorie inconnue');
            $titre = texte($entree['titre'] ?? '', MAX_TITRE);
            if ($titre === '') erreur('Titre manquant');
            $mot = texte($entree['mot'] ?? '', MAX_MOT);
            $prenom = prenom_valide($entree['prenom'] ?? '');
            $st = $db->prepare('SELECT COUNT(*) FROM annonces WHERE cercle = ?');
            $st->execute([$code]);
            if ((int)$st->fetchColumn() >= MAX_ANNONCES_PAR_CERCLE) erreur('Ce cercle a déjà beaucoup d\'annonces');
            $id = aleatoire(8);
            $jeton = aleatoire(16);
            $db->prepare('INSERT INTO annonces (id, cercle, sens, categorie, titre, mot, prenom, jeton_hash, cree)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
               ->execute([$id, $code, $sens, $categorie, $titre, $mot, $prenom, empreinte($jeton), aujourdhui()]);
            repondre(['id' => $id, 'jeton' => $jeton]);

        /* ----- Clore son annonce : elle est effacée du serveur ----- */
        case 'clore':
            $a = annonce($db, $code, $entree['id'] ?? '');
            if (!jeton_ok($a['jeton_hash'], $entree['jeton'] ?? '') && !est_admin($cer, $entree['admin'] ?? '')) {
                erreur('Seule la personne qui a publié peut clore cette annonce', 403);
            }
            $db->prepare('DELETE FROM annonces WHERE id = ?')->execute([$a['id']]);
            repondre(['ok' => true]);

        /* ----- Répondre : « Je le prends » (don) ou « J'en ai un » (demande) ----- */
        case 'prendre':
            $a = annonce($db, $code, $entree['id'] ?? '');
            if (jeton_ok($a['jeton_hash'], $entree['jeton_auteur'] ?? '')) erreur('C\'est ta propre annonce');
            $prenom = prenom_valide($entree['prenom'] ?? '');
            $jeton = aleatoire(16);
            // La première réponse l'emporte : la mise à jour n'a lieu que si personne n'a encore répondu.
            $st = $db->prepare('UPDATE annonces SET rep_prenom = ?, rep_hash = ?, rep_date = ? WHERE id = ? AND rep_hash = \'\'');
            $st->execute([$prenom, empreinte($jeton), aujourdhui(), $a['id']]);
            if ($st->rowCount() === 0) erreur('Quelqu\'un a répondu juste avant toi', 409);
            repondre(['jeton' => $jeton]);

        /* ----- Retirer sa réponse (qui a répondu) ou la libérer (qui a publié) ----- */
        case 'liberer':
            $a = annonce($db, $code, $entree['id'] ?? '');
            $permis = jeton_ok($a['rep_hash'], $entree['jeton'] ?? '')
                   || jeton_ok($a['jeton_hash'], $entree['jeton'] ?? '')
                   || est_admin($cer, $entree['admin'] ?? '');
            if (!$permis) erreur('Action réservée', 403);
            $db->prepare('UPDATE annonces SET rep_prenom = \'\', rep_hash = \'\', rep_date = \'\' WHERE id = ?')
               ->execute([$a['id']]);
            repondre(['ok' => true]);

        /* ----- Changer le nom du cercle (gestion) ----- */
        case 'renommer':
            if (!est_admin($cer, $entree['admin'] ?? '')) erreur('Réservé à la personne qui gère le cercle', 403);
            $db->prepare('UPDATE cercles SET nom = ? WHERE code = ?')
               ->execute([texte($entree['nom'] ?? '', MAX_NOM_CERCLE), $code]);
            repondre(['ok' => true]);

        default:
            erreur('Action inconnue');
    }
} catch (Throwable $e) {
    erreur('Erreur du serveur', 500);
}
