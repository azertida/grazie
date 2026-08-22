# Grazie

Donner des livres d'occasion, un colis à la fois, sans compte ni serveur.

Un colis d'un à trois livres, un lien, une boîte à livres. Le lien circule de personne à personne&#8239;: celui qui veut le colis le garde, les autres le transmettent à quelqu'un pour qui il compterait.

## Le principe

Rien n'est publié, rien n'est hébergé, rien n'est agrégé. Tout ce qui circule tient dans le **fragment** d'une URL — la partie après le `#`, que le navigateur ne transmet jamais au serveur. Le lien s'envoie par le canal qu'on utilise déjà&#8239;: Signal, WhatsApp, courriel, SMS.

Trois liens font un échange complet&#8239;:

1. **L'offre** — le colis et les boîtes à livres proposées, mise en circulation.
2. **La demande** — un prénom, une boîte choisie, des disponibilités.
3. **L'accord** — le jour, l'heure, et une fenêtre de trente minutes.

Le colis est un don, sans contrepartie ni réciprocité. Le déplacement est à la charge de qui reçoit. Le dépôt se fait emballé, avec un prénom écrit dessus, à l'heure convenue.

## La fiche d'un livre

Sept champs, dont le titre seul est nécessaire&#8239;: titre, auteur, langue, année, état, genre, et un mot de quatre-vingts caractères. La langue et l'état se choisissent parmi des valeurs fixes, affichées en toutes lettres plutôt que masquées dans un menu.

## Ce que l'appli ne fait pas

Pas de compte, pas de profil, pas de notation, pas de prix, pas de messagerie, pas de fil à faire défiler, pas d'annuaire. La découverte se fait de bouche à oreille, ce qui limite la portée — et c'est la condition qui rend le reste possible&#8239;: sans réputation à fabriquer, il faut que les gens se connaissent, au moins de proche en proche.

L'appli ne s'installe pas non plus. Sur iOS, les données d'un onglet Safari ne sont pas accessibles depuis une application installée&#8239;; comme l'usage démarre par un lien reçu, l'installation créerait deux bibliothèques divergentes. Il n'y a donc ni `manifest.json` ni service worker, délibérément.

## Ce qui peut rater

Un paquet peut disparaître d'une boîte à livres avant le retrait, malgré l'emballage. C'est prévu, et l'appli le dit à qui va se déplacer.

Deux personnes peuvent demander le même colis. En pratique c'est rare — qui veut le colis ne transmet pas le lien. La première demande l'emporte&#8239;; un mot dans la conversation règle le reste.

Un lien peut circuler longtemps. Passé un mois, l'appli signale que le colis est probablement parti, sans empêcher la demande.

## Technique

Un seul fichier, `index.html`. HTML, CSS et JavaScript natifs, aucune dépendance, aucune requête réseau, aucune police chargée depuis l'extérieur.

Les données restent dans le `localStorage` du navigateur, sous le préfixe `grazie.`&#8239;: le prénom, jusqu'à trois boîtes à livres, et le colis en cours de composition. Rien n'est transmis sans une action explicite.

Les charges utiles sont du JSON compact, compressé puis encodé en base64url, porté par le fragment. La compression utilise `CompressionStream("deflate-raw")`, native au navigateur&#8239;: aucun service tiers, aucun raccourcisseur, rien qui quitte l'appareil. Le lien reste autoportant — tout son contenu est dans ses caractères.

Le fragment commence par une lettre indiquant le mode&#160;: `z` compressé, `j` brut. Les navigateurs sans `CompressionStream` retombent sur le second, et les liens des deux formats restent lisibles.

La longueur compte, parce que les messageries cessent de rendre cliquables les URL trop longues. Un colis de trois livres tient sous 400 caractères, commentaires compris. Le mot de commentaire est plafonné à 80 caractères pour cette raison.

## Vie privée

Une liste de livres en dit long sur la personne qui la propose&#8239;: opinions, croyances, santé, langue, ce qu'on traverse. L'appli le rappelle au moment de fabriquer le lien.

Aucune donnée ne quitte l'appareil autrement que par un lien fabriqué et envoyé volontairement. Les lieux décrivent du mobilier urbain, pas un domicile. Le prénom suffit&#8239;; le nom n'est jamais demandé.

## À faire

Sélecteur de boîtes à livres sur fond OpenStreetMap, pour l'écran des réglages. Les données existent (`amenity=public_bookcase`) et la couverture bruxelloise est bonne&#8239;; l'intégration suppose soit une carte, soit un `boites.json` alimenté par une action planifiée. Les boîtes se saisissent à la main en attendant, ce qui suffit.

## Licence

MIT — voir `LICENSE`.
