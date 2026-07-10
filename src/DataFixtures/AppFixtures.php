<?php

namespace App\DataFixtures;

use App\Entity\Classe;
use App\Entity\Emargement;
use App\Entity\Filiere;
use App\Entity\Matiere;
use App\Entity\SessionCours;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // ---------------------------------------------------------
        // 1. UTILISATEURS PRIVILÉGIÉS (Admin & Formateurs)
        // ---------------------------------------------------------
        $admin = new User();
        $admin->setEmail('admin@presenceflow.com');
        $admin->setNom('Admin');
        $admin->setPrenom('Système');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsArchived(false);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        $marie = new User();
        $marie->setEmail('marie@curie.fr');
        $marie->setNom('Curie');
        $marie->setPrenom('Marie');
        $marie->setRoles(['ROLE_FORMATEUR']);
        $marie->setIsArchived(false);
        $marie->setPassword($this->passwordHasher->hashPassword($marie, 'formateur123'));
        $manager->persist($marie);

        $arnault = new User();
        $arnault->setEmail('arnault@bernard.fr');
        $arnault->setNom('Bernard');
        $arnault->setPrenom('Arnault');
        $arnault->setRoles(['ROLE_FORMATEUR']);
        $arnault->setIsArchived(false);
        $arnault->setPassword($this->passwordHasher->hashPassword($arnault, 'formateur123'));
        $manager->persist($arnault);

        // ---------------------------------------------------------
        // 2. FILIÈRES
        // ---------------------------------------------------------
        $filiereDWWM = new Filiere();
        $filiereDWWM->setNom('Développement Web et Web Mobile');
        $filiereDWWM->setDescription('Titre RNCP Niveau 5');
        $filiereDWWM->setNiveau('Bac+2');
        $filiereDWWM->setIsArchived(false);
        $manager->persist($filiereDWWM);

        $filiereCDA = new Filiere();
        $filiereCDA->setNom('Concepteur Développeur d\'Applications');
        $filiereCDA->setDescription('Titre RNCP Niveau 6');
        $filiereCDA->setNiveau('Bac+3');
        $filiereCDA->setIsArchived(false);
        $manager->persist($filiereCDA);

        // ---------------------------------------------------------
        // 3. CLASSES (Raccordées aux filières)
        // ---------------------------------------------------------
        $classeDWWM = new Classe();
        $classeDWWM->setNom('DWWM - Promo 2026');
        $classeDWWM->setAnnee('2025-2026');
        $classeDWWM->setFiliere($filiereDWWM);
        $classeDWWM->setIsArchived(false);
        $manager->persist($classeDWWM);

        $classeCDA = new Classe();
        $classeCDA->setNom('CDA - Promo 2026');
        $classeCDA->setAnnee('2025-2026');
        $classeCDA->setFiliere($filiereCDA);
        $classeCDA->setIsArchived(false);
        $manager->persist($classeCDA);

        // ---------------------------------------------------------
        // 4. MATIÈRES
        // ---------------------------------------------------------
        $nomsMatieres = [
            'Algorithmie Avancée',
            'Architecture Logicielle',
            'Développement React/Node',
            'Administration PostgreSQL'
        ];
        
        $matieres = [];
        foreach ($nomsMatieres as $nom) {
            $matiere = new Matiere();
            $matiere->setNom($nom);
            $matiere->setIsArchived(false);
            $manager->persist($matiere);
            $matieres[] = $matiere;
        }

        // ---------------------------------------------------------
        // 5. ÉTUDIANTS (Hydratation des tables de jointure)
        // ---------------------------------------------------------
        $etudiantsDWWM = [];
        for ($i = 1; $i <= 10; $i++) {
            $etudiant = new User();
            $etudiant->setEmail("dwwm$i@presenceflow.com");
            $etudiant->setNom("NomDWWM$i");
            $etudiant->setPrenom("Prenom$i");
            $etudiant->setRoles(['ROLE_ETUDIANT']);
            $etudiant->setIsArchived(false);
            $etudiant->setPassword($this->passwordHasher->hashPassword($etudiant, 'etudiant123'));
            
            $classeDWWM->addEtudiant($etudiant);
            
            $manager->persist($etudiant);
            $etudiantsDWWM[] = $etudiant;
        }

        $etudiantsCDA = [];
        for ($i = 1; $i <= 5; $i++) {
            $etudiant = new User();
            $etudiant->setEmail("cda$i@presenceflow.com");
            $etudiant->setNom("NomCDA$i");
            $etudiant->setPrenom("Prenom$i");
            $etudiant->setRoles(['ROLE_ETUDIANT']);
            $etudiant->setIsArchived(false);
            $etudiant->setPassword($this->passwordHasher->hashPassword($etudiant, 'etudiant123'));
            
            $classeCDA->addEtudiant($etudiant);
            
            $manager->persist($etudiant);
            $etudiantsCDA[] = $etudiant;
        }

        // ---------------------------------------------------------
        // 6. SESSIONS DE COURS & GÉNÉRATION DES ÉMARGEMENTS
        // ---------------------------------------------------------
        $sessionsData = [
            ['date' => '-1 day', 'debut' => '09:00:00', 'fin' => '12:30:00', 'formateur' => $marie, 'matiere' => $matieres[0], 'classe' => $classeDWWM, 'etudiants' => $etudiantsDWWM],
            ['date' => 'today', 'debut' => '08:00:00', 'fin' => '12:00:00', 'formateur' => $arnault, 'matiere' => $matieres[1], 'classe' => $classeCDA, 'etudiants' => $etudiantsCDA],
            ['date' => 'today', 'debut' => '14:00:00', 'fin' => '17:30:00', 'formateur' => $marie, 'matiere' => $matieres[2], 'classe' => $classeDWWM, 'etudiants' => $etudiantsDWWM],
            ['date' => '+1 day', 'debut' => '09:00:00', 'fin' => '12:30:00', 'formateur' => $arnault, 'matiere' => $matieres[3], 'classe' => $classeDWWM, 'etudiants' => $etudiantsDWWM],
        ];

        foreach ($sessionsData as $data) {
            $session = new SessionCours();
            $session->setDateCours(new \DateTimeImmutable($data['date']));
            $session->setHeureDebut(new \DateTimeImmutable($data['debut']));
            $session->setHeureFin(new \DateTimeImmutable($data['fin']));
            $session->setToleranceRetard(15);
            $session->setEmplacement('Salle ' . rand(100, 305));
            $session->setFormateur($data['formateur']);
            $session->setMatiere($data['matiere']);
            $session->setClasse($data['classe']);

            $manager->persist($session);

            foreach ($data['etudiants'] as $etudiant) {
                $emargement = new Emargement();
                $emargement->setSession($session);
                $emargement->setEtudiant($etudiant);
                $emargement->setStatut('EN_ATTENTE');
                
                $manager->persist($emargement);
            }
        }

        $manager->flush();
    }
}