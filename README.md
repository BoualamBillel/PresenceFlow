# PresenceFlow

Application web de gestion des émargements par QR code, développée avec Symfony
dans le cadre du titre professionnel DWWM.

Le formateur démarre sa session : un QR code à durée limitée s'affiche.
Les apprenants scannent avec leur smartphone : la présence est enregistrée,
le retard détecté automatiquement. À la clôture, les absents sont marqués
sans intervention. L'administration suit ensuite les absences et traite
les justificatifs.

## Fonctionnalités

- **Administrateur** — gestion des filières, classes, utilisateurs et matières ;
  planification des sessions ; validation des justificatifs ; exports CSV ;
  tableau de bord de synthèse.
- **Formateur** — démarrage de session avec QR code régénérable (valide 5 min) ;
  émargement en direct ; correction manuelle des statuts ; bilan de présence ;
  suivi de l'assiduité par classe et par étudiant.
- **Apprenant** — scan du QR code depuis le navigateur (aucune application à
  installer) ; suivi de ses absences ; dépôt de justificatifs.

## Stack technique

- Symfony 7 (PHP 8.4), Twig, Tailwind CSS, Stimulus
- Doctrine ORM + PostgreSQL 16
- QR codes : endroid/qr-code (SVG, data-URI) ; scan : html5-qrcode
- Docker + Docker Compose ; tunnel ngrok pour la démonstration mobile

Les versions sont épinglées (PHP 8.4, PostgreSQL 16) plutôt que suivies en
`latest` : l'environnement d'exécution est reproductible et versionné avec
le code.

## Installation

Prérequis : Docker et Docker Compose.

1. **Cloner le dépôt et préparer l'environnement**

   ```bash
   git clone https://github.com/BoualamBillel/PresenceFlow.git
   cd PresenceFlow
   cp .env .env.local
   ```

   Renseigner dans `.env.local` : `DATABASE_URL`, `POSTGRES_DB`,
   `POSTGRES_USER`, `POSTGRES_PASSWORD` et `APP_SECRET`
   (`php -r "echo bin2hex(random_bytes(16));"` pour en générer un).

   `.env.local` est exclu du dépôt : aucun secret n'est versionné.

2. **Construire et démarrer les services**

   ```bash
   docker compose up -d --build
   ```

   Le service PHP attend que PostgreSQL soit prêt (healthcheck) avant de démarrer.

3. **Installer les dépendances**

   ```bash
   docker compose exec php composer install
   ```

4. **Créer le schéma de base**

   ```bash
   docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
   ```

5. **Charger le jeu de démonstration (optionnel)**

   ```bash
   docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
   ```

   Crée deux filières, deux classes, deux formateurs, quinze apprenants,
   six matières et environ soixante jours ouvrés de sessions (quatre par jour,
   09h00–12h30 et 14h00–17h30) avec leurs émargements et justificatifs.

6. **Vérifier l'installation**

   ```bash
   docker compose exec php php bin/phpunit
   ```

L'application est accessible sur http://localhost:8080

### Comptes de démonstration

Créés par les fixtures — à n'utiliser qu'en environnement local.

| Rôle           | Identifiant              | Mot de passe   |
| -------------- | ------------------------ | -------------- |
| Administrateur | `admin@presenceflow.com` | `admin123`     |
| Formateur      | `marie@curie.fr`         | `formateur123` |
| Formateur      | `arnault@bernard.fr`     | `formateur123` |
| Apprenant      | voir ci-dessous          | `etudiant123`  |

Les comptes apprenants sont générés aléatoirement à chaque chargement, sous la
forme `prenom.nom@presenceflow.com`. Pour en lister un :

```bash
docker compose exec database psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" \
  -c "SELECT email, nom, prenom FROM \"user\" WHERE roles::text LIKE '%ETUDIANT%' LIMIT 5;"
```

Répartition : dix apprenants en DWWM - Promo 2026, cinq en CDA - Promo 2026.

## Démonstration mobile (scan réel)

Le navigateur n'autorise l'accès à la caméra qu'en HTTPS : un tunnel
ngrok permet de tester le scan sur smartphone.

```bash
ngrok http 8080
```

Ouvrir l'URL `https://...ngrok-free.app` fournie sur le smartphone, puis :
connexion formateur → démarrer la session → affichage du QR code →
scan depuis un compte apprenant → clôture.

> **Préparer une démonstration.** Une session ne peut être démarrée que le
> jour même, et au plus tôt 15 minutes avant son heure de début
> (`SessionManager::START_ADVANCE_MINUTES`). Les fixtures ne créent des
> sessions que du lundi au vendredi.
>
> Surtout, les fixtures pré-remplissent les émargements des sessions déjà
> commencées (présent, retard ou absent). Pour démontrer un scan réel, il
> faut viser une session **à venir** : charger les fixtures le matin puis
> démontrer sur la session de 14h00, ou les recharger juste avant la
> démonstration.

## Architecture

- `src/Service/` — logique métier isolée : cycle de vie du jeton QR
  (`QrCodeManager`), règles de présence et tolérance de retard
  (`PresenceManager`), démarrage/clôture des sessions (`SessionManager`),
  état temporel d'une session (`SessionStatutCalculator`), statistiques
  d'assiduité, exports CSV.
- `src/Controller/` — un contrôleur par espace (admin, formateur, apprenant) ;
  contrôle d'accès par rôle (`#[IsGranted]`) puis par propriété de la donnée.
- `src/EventSubscriber/ForcePasswordChangeSubscriber.php` — changement de
  mot de passe obligatoire à la première connexion.
- `assets/controllers/scanner_controller.js` — scan QR côté navigateur
  (caméra arrière, une seule lecture, validation 100 % côté serveur).

L'état temporel d'une session — à venir, en cours, terminé — n'est jamais
stocké : il est recalculé à la lecture par `SessionStatutCalculator`, à
partir d'une horloge injectée (`ClockInterface`) et du fuseau `Europe/Paris`.
Aucune désynchronisation n'est donc possible entre les écrans.

Le statut d'un émargement, lui, est persisté : il constitue le registre de
présence et doit rester figé après la clôture de la session. Les fiches sont
créées en `EN_ATTENTE` au démarrage, passent en `PRESENT` ou `RETARD` au scan,
et les fiches restées sans signature basculent en `ABSENT` à la clôture.
L'absence est l'état par défaut : la présence doit être prouvée par le scan.

### Contraintes garanties en base

- `UNIQUE (etudiant_id, session_id)` sur `emargement` — un apprenant ne peut
  avoir qu'un seul émargement par session, garanti par le SGBD et non
  seulement par l'application.
- `UNIQUE (qr_code_token)` sur `session_cours`.
- `UNIQUE (email)` sur `user`, doublée d'une validation applicative
  (`UniqueEntity`) pour un message d'erreur lisible dans le formulaire.

## Tests

```bash
docker compose exec php php bin/phpunit
```

Les tests unitaires portent sur `SessionStatutCalculator`, la logique la plus
critique de l'application : si le calcul d'état dérive, le QR code s'affiche au
mauvais moment. Ils utilisent `MockClock` pour fixer l'heure et rester
déterministes quelle que soit la date d'exécution.

## Sauvegarde et restauration

```bash
# Sauvegarde
docker compose exec database pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB" > backup.sql

# Restauration
docker compose exec -T database psql -U "$POSTGRES_USER" "$POSTGRES_DB" < backup.sql
```

Les données de la base sont conservées dans un volume nommé : elles survivent
à `docker compose down` et à la recréation des conteneurs. Elles sont en
revanche supprimées par `docker compose down -v`.

## Sécurité et veille

- Mots de passe hachés (algorithme `auto`), protection CSRF sur les
  formulaires et les actions sensibles, requêtes paramétrées via Doctrine,
  échappement automatique des vues Twig.
- Contrôle d'accès par rôle sur les préfixes d'URL, complété en contrôleur
  par une vérification de la propriété de la donnée.
- Vulnérabilités des dépendances vérifiées avant chaque livraison :

  ```bash
  docker compose exec php composer audit
  ```

## Limites connues

- Les fixtures référencent un fichier de justificatif fictif
  (`dummy_certificat.pdf`) : le lien « voir la pièce jointe » ne renvoie
  aucun document sur un jeu de démonstration.
- La clôture d'une session est déclenchée manuellement par le formateur ;
  la clôture automatique des sessions passées reste à implémenter.
- Aucune limitation du nombre de tentatives de connexion
  (composant `RateLimiter` de Symfony envisagé).

## Licence

Projet pédagogique réalisé dans le cadre du titre DWWM.
