<?php

namespace App\Controller;

use App\Service\AdminDashboardProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_dashboard')]
    public function index(AdminDashboardProvider $dashboardProvider): Response
    {
        return $this->render('admin/dashboard.html.twig', $dashboardProvider->getDashboardData());
    }
}
