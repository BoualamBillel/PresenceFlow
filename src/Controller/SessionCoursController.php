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
  public function index(
        Request $request, 
        SessionCoursRepository $sessionCoursRepository,
        \App\Repository\ClasseRepository $classeRepo,
        \App\Repository\UserRepository $userRepo
    ): Response {
        
        $dateParam = $request->query->get('date');
        try {
            $selectedDate = $dateParam ? new \DateTimeImmutable($dateParam) : new \DateTimeImmutable('today');
        } catch (\Exception $e) {
            $selectedDate = new \DateTimeImmutable('today');
        }

        // Récupération des filtres actifs
        $classeId = $request->query->get('classe');
        $formateurId = $request->query->get('formateur');

        $daysCarousel = [];
        $anchorDate = (new \DateTimeImmutable('today'))->modify('-3 days');
        for ($i = 0; $i < 14; $i++) {
            $daysCarousel[] = $anchorDate->modify("+$i days");
        }

        // Requête filtrée
        $sessions = $sessionCoursRepository->findSessionsByDate(
            $selectedDate, 
            $classeId ? (int)$classeId : null, 
            $formateurId ? (int)$formateurId : null
        );

        $classes = $classeRepo->findAll();
        $formateurs = $userRepo->createQueryBuilder('u')
            ->andWhere('CAST_AS_TEXT(u.roles) LIKE :role')
            ->andWhere('u.isArchived = false')
            ->setParameter('role', '%"ROLE_FORMATEUR"%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()->getResult();

        return $this->render('session_cours/index.html.twig', [
            'sessions' => $sessions,
            'days_carousel' => $daysCarousel,
            'selected_date' => $selectedDate->format('Y-m-d'),
            'classes' => $classes,
            'formateurs' => $formateurs,
            'current_classe' => $classeId,
            'current_formateur' => $formateurId,
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

    #[Route('/{id}/edit', name: 'app_session_cours_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SessionCours $session, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SessionCoursType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Le créneau a été mis à jour avec succès.');

            return $this->redirectToRoute('app_session_cours_index', ['date' => $session->getDateCours()->format('Y-m-d')], Response::HTTP_SEE_OTHER);
        }

        return $this->render('session_cours/edit.html.twig', [
            'session' => $session,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_session_cours_delete', methods: ['POST'])]
    public function delete(Request $request, SessionCours $session, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$session->getId(), $request->request->get('_token'))) {
            $dateRedirect = $session->getDateCours()->format('Y-m-d');
            
            $entityManager->remove($session);
            $entityManager->flush();
            $this->addFlash('success', 'La session a été supprimée définitivement.');
            
            return $this->redirectToRoute('app_session_cours_index', ['date' => $dateRedirect], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_session_cours_index', [], Response::HTTP_SEE_OTHER);
    }
}