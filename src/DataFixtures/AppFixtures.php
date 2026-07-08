<?php

namespace App\DataFixtures;

use App\Entity\Matiere;
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


        $manager->flush();
    }
}