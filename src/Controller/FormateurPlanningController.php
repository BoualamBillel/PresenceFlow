<?php

namespace App\Controller;

use App\Entity\SessionCours;
use App\Repository\SessionCoursRepository;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formateur')]
#[IsGranted('ROLE_FORMATEUR')]
class FormateurPlanningController extends AbstractController
{
    #[Route('/planning', name: 'app_formateur_planning', methods: ['GET'])]
    public function index(Request $request, SessionCoursRepository $sessionCoursRepository, ClockInterface $clock): Response
    {
        $dateParam = $request->query->get('date');
        if (is_string($dateParam) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateParam)) {
            $selectedDate = \DateTimeImmutable::createFromFormat('Y-m-d', $dateParam)->setTime(0, 0);
        } else {
            $selectedDate = $clock->now();
        }

        $monday = $selectedDate->modify('-' . ((int) $selectedDate->format('N') - 1) . ' days');
        $start = $monday->modify('-7 days');

        $daysCarousel = [];
        for ($i = 0; $i < 21; $i++) {
            $daysCarousel[] = $start->modify('+' . $i . ' days');
        }

        return $this->render('formateur/planning.html.twig', [
            'sessions' => $sessionCoursRepository->findByFormateurAndDate($this->getUser(), $selectedDate),
            'days_carousel' => $daysCarousel,
            'selected_date' => $selectedDate->format('Y-m-d'),
            'today' => $clock->now()->format('Y-m-d'),
        ]);
    }

    #[Route('/planning/{id}', name: 'app_formateur_planning_show', methods: ['GET'])]
    public function show(SessionCours $session): Response
    {
        if ($session->getFormateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('formateur/planning_show.html.twig', [
            'session' => $session,
        ]);
    }
}