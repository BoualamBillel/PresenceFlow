<?php

namespace App\Controller;

use App\Repository\UserRepository;
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
}