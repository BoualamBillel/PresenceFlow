<?php

namespace App\Controller;

use App\Entity\SessionCours;
use App\Form\SessionCoursType;
use App\Repository\SessionCoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/session-cours')]
class SessionCoursController extends AbstractController
{
    #[Route('/', name: 'app_session_cours_index', methods: ['GET'])]
    public function index(SessionCoursRepository $sessionCoursRepository): Response
    {
        return $this->render('session_cours/index.html.twig', [
            'sessions' => $sessionCoursRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_session_cours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sessionCours = new SessionCours();
        
        $form = $this->createForm(SessionCoursType::class, $sessionCours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sessionCours);
            $entityManager->flush();

            $this->addFlash('success', 'La session a été planifiée avec succès.');

            return $this->redirectToRoute('app_session_cours_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('session_cours/new.html.twig', [
            'session_cours' => $sessionCours,
            'form' => $form,
        ]);
    }
}