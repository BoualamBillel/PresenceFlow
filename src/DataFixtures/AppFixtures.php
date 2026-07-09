<?php

namespace App\DataFixtures;

use App\Entity\Classe;
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
        $existingAdmin = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@presenceflow.com']);

        if (!$existingAdmin) {


            $admin = new User();
            $admin->setEmail('admin@presenceflow.com');
            $admin->setNom('Admin');
            $admin->setPrenom('Système');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setIsArchived(false);

            $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
            $admin->setPassword($hashedPassword);

            $manager->persist($admin);
        }

        $nomsMatieres = [
            'Algorithmie Avancée & Structures de Données',
            'Introduction à la Cybersécurité',
            'Développement Web Front-End',
            'Architecture des Bases de Données',
            'Gestion de Projet Agile',
            'Programmation Orientée Objet'
        ];

        foreach ($nomsMatieres as $nom) {
            $matiere = new Matiere();
            $matiere->setNom($nom);

            if (method_exists($matiere, 'setIsArchived')) {
                $matiere->setIsArchived(false);
            }

            $manager->persist($matiere);
        }

        $userRepo = $manager->getRepository(User::class);
        $classeRepo = $manager->getRepository(Classe::class);

        $marie = $userRepo->findOneBy(['email' => 'marie@curie.fr']);
        $arnault = $userRepo->findOneBy(['email' => 'Arnault@Arnault.fr']);
        $classes = $classeRepo->findAll();

        if ($marie && $arnault && count($classes) > 0) {
            $classeParDefaut = $classes[0]; // On prend la première classe trouvée
            $matieres = $manager->getRepository(Matiere::class)->findAll();

            $sessionsData = [
                [
                    'date' => '-1 day',
                    'debut' => '09:00:00',
                    'fin' => '12:30:00',
                    'formateur' => $marie,
                    'matiere' => $matieres[0] ?? $matiere,
                    'salle' => 'Amphi Turing'
                ],
                [
                    'date' => 'today',
                    'debut' => '08:00:00',
                    'fin' => '12:00:00',
                    'formateur' => $arnault,
                    'matiere' => $matieres[1] ?? $matiere,
                    'salle' => 'Salle 304'
                ],
                [
                    'date' => 'today',
                    'debut' => '14:00:00',
                    'fin' => '17:30:00',
                    'formateur' => $marie,
                    'matiere' => $matieres[2] ?? $matiere,
                    'salle' => 'Amphi Lovelace'
                ],
                [
                    'date' => '+1 day',
                    'debut' => '09:00:00',
                    'fin' => '12:30:00',
                    'formateur' => $arnault,
                    'matiere' => $matieres[3] ?? $matiere,
                    'salle' => 'Salle 305'
                ]
            ];

            foreach ($sessionsData as $data) {
                $session = new SessionCours();
                $session->setDateCours(new \DateTimeImmutable($data['date']));
                $session->setHeureDebut(new \DateTimeImmutable($data['debut']));
                $session->setHeureFin(new \DateTimeImmutable($data['fin']));
                $session->setToleranceRetard(15);
                $session->setEmplacement($data['salle']);

                $session->setFormateur($data['formateur']);
                $session->setMatiere($data['matiere']);
                $session->setClasse($classeParDefaut);

                $manager->persist($session);
            }
        }


        $manager->flush();
    }
}