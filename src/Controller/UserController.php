<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\Role;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    
    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserManager $userManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = Role::from($form->get('role')->getData());

            $plainPassword = $userManager->createUser($user, $role);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash(
                'success',
                sprintf('Compte créé. Le mot de passe temporaire est : %s', $plainPassword)
            );

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserManager $userManager): Response
    {
        // On récupère le premier rôle significatif pour pré-remplir le champ 'role' non-mappé
        $currentRole = !empty($user->getRoles()) ? $user->getRoles()[0] : Role::ETUDIANT->value;

        $form = $this->createForm(UserType::class, $user);
        $form->get('role')->setData($currentRole);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userManager->updateRole($user, Role::from($form->get('role')->getData()));

            $entityManager->flush();

            $this->addFlash('success', 'Le profil de ' . $user->getPrenom() . ' a été mis à jour.');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}
