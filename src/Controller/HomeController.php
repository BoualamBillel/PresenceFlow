<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\RoleHomeRedirector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(RoleHomeRedirector $roleHomeRedirector): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute($roleHomeRedirector->dashboardRouteFor($user));
    }
}
