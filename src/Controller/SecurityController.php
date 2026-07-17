<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ForcePasswordChangeType;
use App\Security\RoleHomeRedirector;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/forcer-mot-de-passe', name: 'app_force_password_change')]
    public function forcePasswordChange(
        Request $request,
        EntityManagerInterface $em,
        UserManager $userManager,
        RoleHomeRedirector $roleHomeRedirector,
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user || !$user->isMustChangePassword()) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ForcePasswordChangeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userManager->changePassword($user, $form->get('newPassword')->getData());
            $user->setMustChangePassword(false);
            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été sécurisé. Bienvenue !');

            return $this->redirectToRoute($roleHomeRedirector->dashboardRouteFor($user));
        }

        return $this->render('security/force_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
