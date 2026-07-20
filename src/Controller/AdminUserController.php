<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'app_admin_users', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $limit = $request->query->getInt('limit', 10);
        $filter = $request->query->get('filter', 'tous');
        $search = $request->query->get('q', null);

        $paginator = $userRepository->findBySearchAndFilter($search, $filter, $limit);

        $totalUsers = count($paginator);

        return $this->render('admin/user/index.html.twig', [
            'users' => $paginator,
            'hasMore' => $totalUsers > $limit,
            'currentLimit' => $limit,
            'currentFilter' => $filter,
            'currentSearch' => $search,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AdminUserType::class, $user);

        $roles = $user->getRoles();
        $isTargetAdmin = in_array('ROLE_ADMIN', $roles, true);

        $form->get('role')->setData(in_array('ROLE_FORMATEUR', $roles, true) ? 'formateur' : 'apprenant');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user === $this->getUser()) {
                $user->setIsArchived(false);
                $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre rôle ni archiver votre compte.');
            } else {
                if (!$isTargetAdmin) {
                    $user->setRoles($form->get('role')->getData() === 'formateur' ? ['ROLE_FORMATEUR'] : []);
                }
                $em->flush();
                $this->addFlash('success', 'Utilisateur mis à jour.');
                return $this->redirectToRoute('app_admin_users');
            }
        }

        return $this->render('admin/user/user_edit.html.twig', [
            'form' => $form->createView(),
            'edited_user' => $user,
        ]);
    }
}