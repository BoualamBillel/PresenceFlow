<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UpdateEmailType;
use App\Form\UpdatePasswordType;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ETUDIANT')]
class EtudiantProfilController extends AbstractController
{
    #[Route('/etudiant/profil', name: 'app_etudiant_profil')]
    public function index(Request $request, EntityManagerInterface $em, UserManager $userManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // --- 1. GESTION DU FORMULAIRE EMAIL ---
        $formEmail = $this->createForm(UpdateEmailType::class, $user);
        $formEmail->handleRequest($request);

        if ($formEmail->isSubmitted() && $formEmail->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Votre adresse email a été mise à jour.');
            return $this->redirectToRoute('app_etudiant_profil');
        }

        // --- 2. GESTION DU FORMULAIRE MOT DE PASSE ---
        $formPassword = $this->createForm(UpdatePasswordType::class);
        $formPassword->handleRequest($request);

        if ($formPassword->isSubmitted() && $formPassword->isValid()) {
            $userManager->changePassword($user, $formPassword->get('newPassword')->getData());

            $em->flush();
            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
            return $this->redirectToRoute('app_etudiant_profil');
        }

        // --- 3. RENDU DE LA VUE ---
        return $this->render('etudiant/profil.html.twig', [
            'formEmail' => $formEmail->createView(),
            'formPassword' => $formPassword->createView(),
        ]);
    }
}
