<?php

namespace App\DataFixtures;

use App\Entity\Classe;
use App\Entity\Emargement;
use App\Entity\Filiere;
use App\Entity\Justificatif;
use App\Entity\Matiere;
use App\Entity\SessionCours;
use App\Entity\User;
use App\Enum\EmargementStatut;
use App\Enum\JustificatifStatut;
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
        // 3. CLASSES
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
            'Architecture Logicielle',
            'Développement React/Node',
            'Administration PostgreSQL',
            'Programmation bas niveau en C',
            'Architecture des Systèmes Embarqués',
            'Administration Arch Linux / EndeavourOS'
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
        // 5. ÉTUDIANTS
        // ---------------------------------------------------------
        $prenomsFoot = ['Kylian', 'Antoine', 'Olivier', 'Zinedine', 'N\'Golo', 'Thierry', 'Karim', 'Hugo', 'Eduardo', 'Aurélien', 'Lionel', 'Jude', 'Kevin', 'Erling', 'Luka'];
        $nomsFoot = ['Mbappé', 'Griezmann', 'Giroud', 'Zidane', 'Kanté', 'Henry', 'Benzema', 'Lloris', 'Camavinga', 'Tchouaméni', 'Messi', 'Bellingham', 'De Bruyne', 'Haaland', 'Modric'];

        shuffle($prenomsFoot);
        shuffle($nomsFoot);

        $etudiantsDWWM = [];
        for ($i = 0; $i < 10; $i++) {
            $etudiant = new User();
            $etudiant->setEmail(strtolower(str_replace('\'', '', $prenomsFoot[$i])) . '.' . strtolower($nomsFoot[$i]) . '@presenceflow.com');
            $etudiant->setNom($nomsFoot[$i]);
            $etudiant->setPrenom($prenomsFoot[$i]);
            $etudiant->setRoles(['ROLE_ETUDIANT']);
            $etudiant->setIsArchived(false);
            $etudiant->setPassword($this->passwordHasher->hashPassword($etudiant, 'etudiant123'));
            
            $classeDWWM->addEtudiant($etudiant);
            $manager->persist($etudiant);
            $etudiantsDWWM[] = $etudiant;
        }

        $etudiantsCDA = [];
        for ($i = 10; $i < 15; $i++) {
            $etudiant = new User();
            $etudiant->setEmail(strtolower(str_replace('\'', '', $prenomsFoot[$i])) . '.' . strtolower($nomsFoot[$i]) . '@presenceflow.com');
            $etudiant->setNom($nomsFoot[$i]);
            $etudiant->setPrenom($prenomsFoot[$i]);
            $etudiant->setRoles(['ROLE_ETUDIANT']);
            $etudiant->setIsArchived(false);
            $etudiant->setPassword($this->passwordHasher->hashPassword($etudiant, 'etudiant123'));
            
            $classeCDA->addEtudiant($etudiant);
            $manager->persist($etudiant);
            $etudiantsCDA[] = $etudiant;
        }

        // ---------------------------------------------------------
        // 6. GÉNÉRATION MASSIVE DES SESSIONS & ÉMARGEMENTS
        // ---------------------------------------------------------
        $motifsAbsence = [
            "Malade, certificat médical joint.", 
            "Panne de transports en commun.", 
            "Urgence familiale.", 
            "Blessure lors du match de foot ce week-end.",
            "Rendez-vous médical spécialiste."
        ];

        // On définit une période : d'il y a 30 jours jusqu'à dans 30 jours
        $startDate = new \DateTimeImmutable('-30 days');
        $endDate = new \DateTimeImmutable('+30 days');
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($startDate, $interval, $endDate);

        $formateurs = [$marie, $arnault];

        foreach ($period as $date) {
            // Exclusion des week-ends (1 = Lundi, 7 = Dimanche)
            if ((int)$date->format('N') >= 6) {
                continue; 
            }

            // Génération de 2 sessions par jour pour DWWM (Matin et Après-midi)
            $this->generateSession($manager, $date, '09:00:00', '12:30:00', $formateurs[array_rand($formateurs)], $matieres[array_rand($matieres)], $classeDWWM, $etudiantsDWWM, $motifsAbsence);
            $this->generateSession($manager, $date, '14:00:00', '17:30:00', $formateurs[array_rand($formateurs)], $matieres[array_rand($matieres)], $classeDWWM, $etudiantsDWWM, $motifsAbsence);

            // Génération de 2 sessions par jour pour CDA (Matin et Après-midi)
            $this->generateSession($manager, $date, '09:00:00', '12:30:00', $formateurs[array_rand($formateurs)], $matieres[array_rand($matieres)], $classeCDA, $etudiantsCDA, $motifsAbsence);
            $this->generateSession($manager, $date, '14:00:00', '17:30:00', $formateurs[array_rand($formateurs)], $matieres[array_rand($matieres)], $classeCDA, $etudiantsCDA, $motifsAbsence);
        }

        $manager->flush();
    }

    /**
     * Méthode utilitaire pour générer une session, ses émargements et justificatifs
     */
    private function generateSession(ObjectManager $manager, \DateTimeImmutable $date, string $heureDebut, string $heureFin, User $formateur, Matiere $matiere, Classe $classe, array $etudiants, array $motifsAbsence): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $debutSession = new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $heureDebut, new \DateTimeZone('Europe/Paris'));
        $isSessionPasse = $now > $debutSession;

        $session = new SessionCours();
        $session->setDateCours($date);
        $session->setHeureDebut($debutSession);
        $session->setHeureFin(new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $heureFin, new \DateTimeZone('Europe/Paris')));
        $session->setToleranceRetard(15);
        $session->setEmplacement('Salle ' . rand(100, 305));
        $session->setFormateur($formateur);
        $session->setMatiere($matiere);
        $session->setClasse($classe);
        $manager->persist($session);

        foreach ($etudiants as $etudiant) {
            $emargement = new Emargement();
            $emargement->setSession($session);
            $emargement->setEtudiant($etudiant);
            
            if ($isSessionPasse) {
                $rand = mt_rand(1, 100);
                if ($rand <= 80) { // 80% de présence
                    $emargement->setStatut(EmargementStatut::PRESENT);
                    $emargement->setHeureSignature($debutSession);
                } elseif ($rand <= 90) { // 10% de retard
                    $emargement->setStatut(EmargementStatut::RETARD);
                    $emargement->setHeureSignature($debutSession->modify('+' . mt_rand(16, 45) . ' minutes'));
                } else { // 10% d'absence
                    $emargement->setStatut(EmargementStatut::ABSENT);
                }
            } else {
                $emargement->setStatut(EmargementStatut::EN_ATTENTE);
            }

            $manager->persist($emargement);

            // Génération des justificatifs pour les absents et les retards
            if ($emargement->getStatut()->estJustifiable() && mt_rand(1, 100) <= 60) {
                $justificatif = new Justificatif();
                $justificatif->setEmargement($emargement);
                $justificatif->setUrlFichier('dummy_certificat.pdf');
                $justificatif->setMotifAbsence($motifsAbsence[array_rand($motifsAbsence)]);
                
                // Soumission dans les 48h suivant le cours
                $justificatif->setDateSoumission($debutSession->modify('+' . mt_rand(1, 48) . ' hours'));

                $jRand = mt_rand(1, 100);
                if ($jRand <= 30) {
                    $justificatif->setStatut(JustificatifStatut::EN_ATTENTE);
                } elseif ($jRand <= 80) {
                    $justificatif->setStatut(JustificatifStatut::VALIDE);
                } else {
                    $justificatif->setStatut(JustificatifStatut::REFUSE);
                    $justificatif->setMotifRefus('Document non conforme. Merci de fournir un justificatif officiel.');
                }

                $manager->persist($justificatif);
            }
        }
    }
}