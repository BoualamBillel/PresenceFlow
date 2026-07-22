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
   docker compose exec php bin/console doctrine:migrations:migrate
   ```

5. **Charger le jeu de démonstration (optionnel)**

   ```bash
   docker compose exec php bin/console doctrine:fixtures:load
   ```

   Crée une filière, une classe, un compte par rôle et quelques sessions.

L'application est accessible sur http://localhost:8080

## Démonstration mobile (scan réel)

Le navigateur n'autorise l'accès à la caméra qu'en HTTPS : un tunnel
ngrok permet de tester le scan sur smartphone.

```bash
ngrok http 8080
```

Ouvrir l'URL `https://...ngrok-free.app` fournie sur le smartphone, puis :
connexion formateur → démarrer la session → affichage du QR code →
scan depuis un compte apprenant → clôture.

## Architecture

- `src/Service/` — logique métier isolée : cycle de vie du jeton QR
  (`QrCodeManager`), règles de présence et tolérance de retard
  (`PresenceManager`), démarrage/clôture des sessions (`SessionManager`),
  statistiques d'assiduité, exports CSV.
- `src/Controller/` — un contrôleur par espace (admin, formateur, apprenant) ;
  contrôle d'accès par rôle (`#[IsGranted]`) puis par propriété de la donnée.
- `src/EventSubscriber/ForcePasswordChangeSubscriber.php` — changement de
  mot de passe obligatoire à la première connexion.
- `assets/controllers/scanner_controller.js` — scan QR côté navigateur
  (caméra arrière, une seule lecture, validation 100 % côté serveur).

Les statuts « à venir / en cours / terminé » des sessions et « absent »
des émargements non signés sont calculés à la lecture, jamais stockés :
aucune désynchronisation possible.

## Licence

Projet pédagogique réalisé dans le cadre du titre DWWM.
