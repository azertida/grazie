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
 *  - aucune adresse IP ni autre trace n'est enregistrée par ce script ;
 *  - un don peut porter une photo : réduite sur le téléphone, sans métadonnées
 *    (position GPS comprise — le serveur refuse une photo qui en contient),
 *    rangée hors du site et montrée seulement à qui a le code du cercle ;
 *    elle est effacée avec l'annonce.
 *
 * Emplacement : www/grazie/api.php — les données vivent hors du site,
 * dans ~/grazie-data/grazie.sqlite, et les photos dans ~/grazie-data/photos/
 * (créés automatiquement).
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
const MAX_LIEN = 500;
const MAX_PHOTO_OCTETS = 350000;              // une photo réduite sur le téléphone pèse ~100 à 200 Ko
const MAX_REQUETE_OCTETS = 600000;

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

/* Un lien facultatif : http ou https seulement. */
function lien_valide($valeur): string {
    $l = texte($valeur, MAX_LIEN);
    if ($l === '') return '';
    if (!preg_match('#^https?://[^\s/$.?\#][^\s]*$#i', $l) || !filter_var($l, FILTER_VALIDATE_URL)) erreur('Ce lien n\'est pas une adresse web valable');
    return $l;
}

/* Une photo facultative : JPEG en base64, sans métadonnées EXIF (donc sans position GPS). */
function photo_valide($valeur): ?string {
    if ($valeur === null || $valeur === '') return null;
    $bin = base64_decode((string)$valeur, true);
    if ($bin === false || strlen($bin) < 100) erreur('Photo illisible');
    if (strlen($bin) > MAX_PHOTO_OCTETS) erreur('Photo trop lourde');
    if (substr($bin, 0, 3) !== "\xFF\xD8\xFF") erreur('Seules les photos JPEG sont acceptées');
    if (strpos($bin, "Exif\0\0") !== false) erreur('Cette photo contient encore ses métadonnées');
    return $bin;
}

function dossier_photos(): string {
    $d = dirname(__DIR__, 2) . '/grazie-data/photos';
    if (!is_dir($d) && !mkdir($d, 0700, true)) erreur('Stockage indisponible', 500);
    return $d;
}
function chemin_photo(string $id): string { return dossier_photos() . '/' . $id . '.jpg'; }
function effacer_photo(string $id): void { $f = chemin_photo($id); if (is_file($f)) @unlink($f); }

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
    // Version 2.1 : un lien et une photo facultatifs.
    $cols = array_column($db->query('PRAGMA table_info(annonces)')->fetchAll(), 'name');
    if (!in_array('lien', $cols, true))  $db->exec('ALTER TABLE annonces ADD COLUMN lien TEXT NOT NULL DEFAULT \'\'');
    if (!in_array('photo', $cols, true)) $db->exec('ALTER TABLE annonces ADD COLUMN photo INTEGER NOT NULL DEFAULT 0');

    // Effacement automatique des annonces oubliées, photos comprises.
    $st = $db->prepare('SELECT id FROM annonces WHERE cree < ? AND photo = 1');
    $st->execute([il_y_a(DUREE_JOURS)]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $vieille) effacer_photo($vieille);
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
    $brut = file_get_contents('php://input', false, null, 0, MAX_REQUETE_OCTETS);
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

    if (!in_array($action, ['liste', 'photo'], true) && $methode !== 'POST') erreur('POST attendu', 405);

    switch ($action) {

        /* ----- Lire les annonces du cercle ----- */
        case 'liste':
            $st = $db->prepare('SELECT id, sens, categorie, titre, mot, lien, photo, prenom, cree, rep_prenom, rep_date
                                FROM annonces WHERE cercle = ? ORDER BY cree DESC, rowid DESC');
            $st->execute([$code]);
            $annonces = array_map(function ($a) { $a['photo'] = (int)$a['photo'] === 1; return $a; }, $st->fetchAll());
            $rep = ['nom' => $cer['nom'], 'annonces' => $annonces, 'duree' => DUREE_JOURS];
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
            $lien = lien_valide($entree['lien'] ?? '');
            $photo = $sens === 'don' ? photo_valide($entree['photo'] ?? null) : null;   // photo réservée aux dons
            $prenom = prenom_valide($entree['prenom'] ?? '');
            $st = $db->prepare('SELECT COUNT(*) FROM annonces WHERE cercle = ?');
            $st->execute([$code]);
            if ((int)$st->fetchColumn() >= MAX_ANNONCES_PAR_CERCLE) erreur('Ce cercle a déjà beaucoup d\'annonces');
            $id = aleatoire(8);
            $jeton = aleatoire(16);
            if ($photo !== null && file_put_contents(chemin_photo($id), $photo, LOCK_EX) === false) erreur('Stockage indisponible', 500);
            $db->prepare('INSERT INTO annonces (id, cercle, sens, categorie, titre, mot, lien, photo, prenom, jeton_hash, cree)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
               ->execute([$id, $code, $sens, $categorie, $titre, $mot, $lien, $photo !== null ? 1 : 0, $prenom, empreinte($jeton), aujourdhui()]);
            repondre(['id' => $id, 'jeton' => $jeton]);

        /* ----- Montrer la photo d'une annonce (code du cercle requis) ----- */
        case 'photo':
            $a = annonce($db, $code, $_GET['id'] ?? '');
            $f = chemin_photo($a['id']);
            if ((int)$a['photo'] !== 1 || !is_file($f)) erreur('Pas de photo', 404);
            header('Content-Type: image/jpeg');
            header('Cache-Control: private, max-age=86400');
            header('Content-Length: ' . filesize($f));
            readfile($f);
            exit;

        /* ----- Clore son annonce : elle est effacée du serveur ----- */
        case 'clore':
            $a = annonce($db, $code, $entree['id'] ?? '');
            if (!jeton_ok($a['jeton_hash'], $entree['jeton'] ?? '') && !est_admin($cer, $entree['admin'] ?? '')) {
                erreur('Seule la personne qui a publié peut clore cette annonce', 403);
            }
            $db->prepare('DELETE FROM annonces WHERE id = ?')->execute([$a['id']]);
            effacer_photo($a['id']);
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
