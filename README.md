# Grazie

Un vide-grenier gratuit, juste entre nous. On donne, on cherche.

## Deux versions

Grazie a d'abord existé sous une forme où **tout tenait dans le lien**&#8239;: pas de compte, pas de serveur, un colis écrit en entier dans son URL et qui circulait de proche en proche. Cette première version reste en ligne et intacte&#8239;:

- à l'adresse [`/grazie/v1/`](https://azertida.github.io/grazie/v1/)&#8239;;
- dans le dossier `v1/` de ce dépôt&#8239;;
- sous l'étiquette Git `v1`.

Les liens déjà envoyés avec elle continuent de s'ouvrir dans `v1/`. Elle est décrite en bas de cette page.

## Pourquoi changer

Le principe du tout-dans-le-lien tient ses promesses&#8239;: rien n'est hébergé, rien n'est agrégé, rien ne quitte l'appareil sans un geste explicite. Mais à l'usage, l'appli n'était pas praticable.

Un échange demandait trois liens et deux allers-retours&#8239;: l'offre, puis la demande renvoyée par qui veut le colis, puis l'accord renvoyé par qui le donne. Chaque changement d'état obligeait quelqu'un à fabriquer et renvoyer un nouveau lien, et chaque aller-retour était une étape de plus à expliquer.

Cette lourdeur venait aussi du public visé&#8239;: des inconnus, au bout d'une chaîne de relais. D'où les boîtes à livres, les créneaux, la fenêtre de trente minutes, le dépôt sans se croiser. Entre proches, la plupart de ce dispositif n'a plus lieu d'être.

## La version actuelle&#8239;: un cercle de proches

### Comment ça marche

1. Garde le lien qui t'a fait entrer&#8239;: c'est par lui que tu reviens, il ne change pas.
2. Propose ce que tu donnes, ou dis ce que tu cherches.
3. Touche «&#8239;Je le prends&#8239;» ou «&#8239;J'en ai un&#8239;». La personne le voit, et vous vous arrangez entre vous.

La première fois, on donne seulement son prénom. L'appli ne gère ni lieu ni heure&#8239;: entre proches, un message suffit.

### Une annonce

- **Un don ou une demande**, dans une sorte&#8239;: livres, vêtements, autre.
- **Quelques mots** pour dire ce que c'est, et une précision facultative.
- **Une photo**, pour les dons seulement. Elle est réduite sur le téléphone (1200 pixels au plus) et débarrassée de ses métadonnées, position GPS comprise. Le serveur refait ce ménage à l'arrivée.
- **Un lien** facultatif, pour les dons comme pour les demandes&#8239;: une photo en ligne, la fiche d'un produit.

Un seul geste par annonce. Qui a publié voit qui a répondu, puis clôt l'annonce ou la remet disponible. Qui a répondu peut se désister.

### Le cercle

- **On entre tout de suite**, sans attendre l'accord de personne. Mais pour voir les annonces, il faut avoir donné son prénom&#8239;: personne ne lit le cercle sans y apparaître.
- **Chaque lien d'invitation porte la signature de qui l'envoie.** La personne qui gère le cercle sait ainsi par qui chacun est arrivé.
- **Seule la personne qui gère le cercle voit qui en fait partie.** Les membres ne voient pas la liste des autres, seulement les prénoms sur les annonces et les réponses.
- Quand quelqu'un arrive, elle le découvre à sa visite suivante, en haut du cercle&#8239;: «&#8239;Toc toc&#8239;! Marie est entrée dans le cercle, par le lien de Paul.&#8239;» Il n'y a pas de notification.

### Pour la personne qui gère le cercle

- **La clé de gestion** est remise une seule fois, à la création du cercle. Le serveur n'en garde qu'une empreinte&#8239;: elle ne peut pas être retrouvée. Sur un autre appareil, «&#8239;Réglages › Gestion du cercle › Je gère ce cercle&#8239;» permet de la saisir.
- **Qui est dans le cercle**&#8239;: chaque prénom, sa date d'arrivée et le lien par lequel il est entré. Un bouton permet de retirer quelqu'un&#8239;; ses annonces et ses réponses sont effacées avec lui.
- **Retirer quelqu'un ne suffit pas s'il a gardé le lien**&#8239;: il pourrait revenir, et réapparaîtrait aussitôt dans le «&#8239;Toc toc&#8239;».
- **Changer le lien du cercle** referme alors la porte&#8239;:
  1. retirer d'abord la personne, sinon elle suivra comme les autres&#8239;;
  2. «&#8239;Réglages › Gestion du cercle › Changer le lien du cercle&#8239;»&#8239;;
  3. l'ancien lien ne mène plus nulle part. Les membres restants suivent tout seuls à leur prochaine visite, grâce à leur place de membre, et voient une fois un bandeau «&#8239;Le lien du cercle a changé&#8239;» qui les invite à garder le nouveau&#8239;;
  4. leur envoyer quand même le nouveau lien («&#8239;Réglages › Inviter&#8239;»), sans dire pourquoi il a changé. L'ancien ne marche que sur un téléphone qui se souvient de Grazie&#8239;: pas sur un autre appareil, ni si le navigateur a effacé les données du site.
- Seul le **premier** cercle d'un serveur se crée sans clé. Les suivants demandent la clé de gestion d'un cercle existant.

### Ce qui peut surprendre

Safari, sur iPhone, efface les données d'un site qu'on n'a pas ouvert depuis environ une semaine. Un proche qui n'ouvre Grazie que rarement devra alors redonner son prénom, et réapparaîtra dans le «&#8239;Toc toc&#8239;» comme s'il était nouveau. Ce n'est pas un intrus&#8239;: c'est son navigateur qui l'a oublié.

Grazie ne s'installe pas, comme la première version&#8239;: on rouvre le lien du cercle. Sur iPhone, Safari et une appli installée ne partagent pas leurs données&#8239;; l'installation créerait deux places de membre différentes.

### Données

| Où | Quoi | Combien de temps |
|---|---|---|
| Le téléphone | le code du cercle, le prénom, la place de membre, les jetons qui prouvent que tes annonces et tes réponses sont les tiennes | jusqu'à «&#8239;Tout effacer de ce téléphone&#8239;» |
| Le serveur | le nom du cercle&#8239;; pour chaque membre&#8239;: prénom, date d'arrivée, lien par lequel il est entré&#8239;; pour chaque annonce&#8239;: ce qu'elle dit, son lien et sa photo s'il y en a, un prénom, sa date, le prénom de qui a répondu | une annonce close est effacée tout de suite, photo comprise&#8239;; une annonce oubliée au bout de 90 jours&#8239;; un membre qui quitte le cercle ou en est retiré, avec tout ce qui le concerne |

- Ni téléphone, ni adresse, ni compte, ni nom de famille.
- Les jetons sont gardés sur le téléphone&#8239;; le serveur n'en conserve qu'une empreinte.
- Le script du serveur n'enregistre ni adresse IP ni autre trace. L'hébergeur tient, comme tout hébergeur, ses propres journaux techniques.
- Pas de statistiques, pas de cookies, pas de traceurs, aucune police chargée depuis l'extérieur.
- La page «&#8239;Ce que Grazie garde&#8239;», dans les réglages, explique tout cela à chaque membre, y compris que la personne qui gère le cercle voit qui en fait partie.

### Architecture

- **L'appli**&#8239;: `index.html` (HTML, CSS et JavaScript, sans dépendance), servi par GitHub Pages. Le numéro de version (`VERSION`) s'affiche en bas de chaque écran.
- **Les polices**&#8239;: Fraunces (titres) et Atkinson Hyperlegible (texte), hébergées avec l'appli dans `polices/`, licence SIL OFL 1.1 (voir les fichiers `LICENCE-*.txt`). Aucun appel à Google ni à un autre service.
- **Le serveur**&#8239;: `serveur/api.php` (PHP 8 + SQLite), hébergé chez alwaysdata (France), sur le modèle de Corsia. Il est placé dans `www/grazie/api.php`, et ses données dans `~/grazie-data/` (base et photos), hors de la partie publique du site. La base se crée et se met à jour toute seule.
- Le serveur n'accepte que les requêtes venant de `https://azertida.github.io`.
- Ce dépôt contient une copie de `serveur/api.php` pour garder son historique. GitHub Pages ne l'exécute pas&#8239;: seule la copie déposée chez l'hébergeur est active.

### Mise à jour

- **Serveur d'abord**&#8239;: redéposer `api.php` chez l'hébergeur (WebDAV), puis sa copie dans `serveur/`.
- **Appli ensuite**&#8239;: remplacer `index.html` à la racine. GitHub Pages republie tout seul&#8239;; seule la dernière publication compte.
- **Hébergement gratuit**&#8239;: se connecter à l'administration alwaysdata au moins une fois tous les 120 jours la première année, sinon le compte est suspendu.

---

## Première version&#160;: tout dans le lien

*Ce qui suit décrit la version conservée dans `v1/`.*

Donner des livres d'occasion, un colis à la fois, sans compte ni serveur.

Un colis d'un à trois livres, un lien, une boîte à livres. Le lien circule de personne à personne&#8239;: celui qui veut le colis le garde, les autres le transmettent à quelqu'un pour qui il compterait.

### Le principe

Rien n'est publié, rien n'est hébergé, rien n'est agrégé. Tout ce qui circule tient dans le **fragment** d'une URL — la partie après le `#`, que le navigateur ne transmet jamais au serveur. Le lien s'envoie par le canal qu'on utilise déjà&#8239;: Signal, WhatsApp, courriel, SMS.

Trois liens font un échange complet&#8239;:

1. **L'offre** — le colis et les boîtes à livres proposées, mise en circulation.
2. **La demande** — un prénom, une boîte choisie, des disponibilités.
3. **L'accord** — le jour, l'heure, et une fenêtre de trente minutes.

Le colis est un don, sans contrepartie ni réciprocité. Le déplacement est à la charge de qui reçoit. Le dépôt se fait emballé, avec un prénom écrit dessus, à l'heure convenue.

### Le nom du colis

Facultatif, quarante caractères. Il voyage dans le lien, contrairement au mot qui accompagne l'envoi&#8239;: celui-ci s'arrête au premier destinataire, le nom du colis arrive intact au troisième relais. C'est donc lui qui oriente la circulation — «&#160;Trois romans italiens&#160;», ou mieux, «&#160;Pour quelqu'un qui apprend l'italien&#160;».

### La fiche d'un livre

Sept champs, dont le titre seul est nécessaire&#8239;: titre, auteur, langue, année, état, genre, et un mot de quatre-vingts caractères. La langue et l'état se choisissent parmi des valeurs fixes, affichées en toutes lettres plutôt que masquées dans un menu.

### Ce que l'appli ne fait pas

Pas de compte, pas de profil, pas de notation, pas de prix, pas de messagerie, pas de fil à faire défiler, pas d'annuaire. La découverte se fait de bouche à oreille, ce qui limite la portée — et c'est la condition qui rend le reste possible&#8239;: sans réputation à fabriquer, il faut que les gens se connaissent, au moins de proche en proche.

L'appli ne s'installe pas non plus. Sur iOS, les données d'un onglet Safari ne sont pas accessibles depuis une application installée&#8239;; comme l'usage démarre par un lien reçu, l'installation créerait deux bibliothèques divergentes. Il n'y a donc ni `manifest.json` ni service worker, délibérément.

### Ce qui peut rater

Un paquet peut disparaître d'une boîte à livres avant le retrait, malgré l'emballage. C'est prévu, et l'appli le dit à qui va se déplacer.

Deux personnes peuvent demander le même colis. En pratique c'est rare — qui veut le colis ne transmet pas le lien. La première demande l'emporte&#8239;; un mot dans la conversation règle le reste.

Un lien peut circuler longtemps. Passé un mois, l'appli signale que le colis est probablement parti, sans empêcher la demande.

### Langues

Français et anglais, sélecteur en tête de page. La langue retenue est celle enregistrée sur l'appareil&#8239;; à défaut, celle du navigateur&#8239;; à défaut, le français. Un lien n'impose jamais la langue de qui l'a envoyé.

Le français reste la langue source dans le code&#160;: les traductions sont un dictionnaire indexé par la phrase française, appliqué au chargement. Ajouter le néerlandais revient à ajouter un objet `TRAD.nl` et une entrée dans `LANGUES_UI`&#8239;; une phrase sans traduction s'affiche en français plutôt que de disparaître.

Les dates et les heures suivent la locale, sans table de jours ni de mois.

### Technique

Un seul fichier, `v1/index.html`. HTML, CSS et JavaScript natifs, aucune dépendance, aucune requête réseau, aucune police chargée depuis l'extérieur.

Les données restent dans le `localStorage` du navigateur, sous le préfixe `grazie.`&#8239;: le prénom, jusqu'à trois boîtes à livres, et le colis en cours de composition. Rien n'est transmis sans une action explicite.

Les charges utiles sont du JSON compact, compressé puis encodé en base64url, porté par le fragment. La compression utilise `CompressionStream("deflate-raw")`, native au navigateur&#8239;: aucun service tiers, aucun raccourcisseur, rien qui quitte l'appareil. Le lien reste autoportant — tout son contenu est dans ses caractères.

Le fragment commence par une lettre indiquant le mode&#160;: `z` compressé, `j` brut. Les navigateurs sans `CompressionStream` retombent sur le second, et les liens des deux formats restent lisibles.

La longueur compte, parce que les messageries cessent de rendre cliquables les URL trop longues. Un colis de trois livres tient sous 400 caractères, commentaires compris. Le mot de commentaire est plafonné à 80 caractères pour cette raison.

### Vie privée

Une liste de livres en dit long sur la personne qui la propose&#8239;: opinions, croyances, santé, langue, ce qu'on traverse. L'appli le rappelle au moment de fabriquer le lien.

Aucune donnée ne quitte l'appareil autrement que par un lien fabriqué et envoyé volontairement. Les lieux décrivent du mobilier urbain, pas un domicile. Le prénom suffit&#8239;; le nom n'est jamais demandé.

### Les boîtes à livres

Grazie ne tient aucune liste de boîtes et n'en intègre aucune base de données. Les trois boîtes se saisissent à la main, une fois, dans les réglages.

C'est un choix, pas un manque. Les gens connaissent déjà les boîtes de leur quartier ou de leur trajet, et les décrivent mieux qu'une base — trois quarts des nœuds OpenStreetMap sont anonymes. Une base recensée servirait à découvrir des boîtes inconnues, ce qui n'est pas le besoin&#8239;: on dépose là où l'on passe. Et une liste embarquée serait fausse dès qu'une boîte disparaît, sans que personne s'en aperçoive.

Les réglages renvoient vers [la carte MapComplete](https://mapcomplete.org/bookcases), construite sur les données OpenStreetMap et qui sert aussi d'éditeur. L'appli fonctionne entièrement sans elle.

---

## Licence

MIT — voir `LICENSE`.
