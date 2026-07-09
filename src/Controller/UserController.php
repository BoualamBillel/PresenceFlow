<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
class UserController extends AbstractController
{

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $searchTerm = $request->query->get('q');
        $filter = $request->query->get('filter', 'all');
        
        $limit = $request->query->getInt('limit', 5);

        $paginator = $userRepository->findBySearchAndFilter($searchTerm, $filter, $limit);

        return $this->render('user/index.html.twig', [
            'users' => $paginator,
            'total_users' => count($paginator),
            'current_filter' => $filter,
            'search_term' => $searchTerm,
            'limit' => $limit
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $selectedRole = $form->get('role')->getData();
            $user->setRoles([$selectedRole]);

            $plainPassword = substr(bin2hex(random_bytes(8)), 0, 8);

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($hashedPassword);

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
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // On récupère le premier rôle significatif pour pré-remplir le champ 'role' non-mappé
        $currentRole = !empty($user->getRoles()) ? $user->getRoles()[0] : 'ROLE_ETUDIANT';

        $form = $this->createForm(UserType::class, $user, []);
        
        $form->get('role')->setData($currentRole);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedRole = $form->get('role')->getData();
            $user->setRoles([$selectedRole]);

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