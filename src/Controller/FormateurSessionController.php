<?php

namespace App\Controller;

use App\Entity\Emargement;
use App\Entity\SessionCours;
use App\Enum\EmargementStatut;
use App\Service\PresenceManager;
use App\Service\QrCodeManager;
use App\Service\SessionManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formateur')]
#[IsGranted('ROLE_FORMATEUR')]
class FormateurSessionController extends AbstractController
{
    public function __construct(
        private readonly SessionManager $sessionManager,
        private readonly QrCodeManager $qrCodeManager,
        private readonly PresenceManager $presenceManager,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/', name: 'app_formateur_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $currentSession = $this->sessionManager->findCurrentSessionForFormateur($user);

        if (!$currentSession) {
            return $this->render('formateur/session_show.html.twig', [
                'session' => null,
                'qr_code_uri' => null,
                'time_left' => 0,
                'is_startable' => false,
            ]);
        }

        $qrCodeUri = null;
        $timeLeft = 0;

        if ($this->qrCodeManager->isTokenValid($currentSession)) {
            $qrCodeUri = $this->qrCodeManager->buildDataUri($this->generateUrl(
                'app_etudiant_signer',
                ['token' => $currentSession->getQrCodeToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ));
            $timeLeft = $currentSession->getQrTokenExpiresAt()->getTimestamp() - $this->clock->now()->getTimestamp();
        }

        return $this->render('formateur/session_show.html.twig', [
            'session' => $currentSession,
            'qr_code_uri' => $qrCodeUri,
            'time_left' => max(0, $timeLeft),
            'is_startable' => $this->sessionManager->isStartable($currentSession),
        ]);
    }

    #[Route('/session/{id}/lancer', name: 'app_formateur_session_start', methods: ['POST'])]
    public function start(Request $request, SessionCours $session, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessOwner($session);

        if ($this->isCsrfTokenValid('start' . $session->getId(), $request->request->get('_token'))) {
            if ($this->sessionManager->start($session)) {
                $em->flush();
            } else {
                $this->addFlash('error', 'Action impossible hors des plages horaires autorisées.');
            }
        }

        return $this->redirectToRoute('app_formateur_dashboard');
    }

    #[Route('/session/{id}/refresh-qr', name: 'app_formateur_session_refresh_qr', methods: ['POST'])]
    public function refreshQr(Request $request, SessionCours $session, EntityManagerInterface $em): JsonResponse
    {
        if ($session->getFormateur() !== $this->getUser()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!$this->isCsrfTokenValid('refresh' . $session->getId(), $data['_token'] ?? '')) {
            return $this->json(['error' => 'Token invalide'], 400);
        }

        $this->qrCodeManager->regenerateToken($session);
        $em->flush();

        $signerUrl = $this->generateUrl(
            'app_etudiant_signer',
            ['token' => $session->getQrCodeToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $timeLeft = $session->getQrTokenExpiresAt()->getTimestamp() - $this->clock->now()->getTimestamp();

        return $this->json([
            'qr_code_uri' => $this->qrCodeManager->buildDataUri($signerUrl),
            'time_left' => max(0, $timeLeft),
        ]);
    }

    #[Route('/emargement/{id}/modifier', name: 'app_formateur_emargement_modifier', methods: ['POST'])]
    public function modifierEmargement(Request $request, Emargement $emargement, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessOwner($emargement->getSession());

        if ($this->isCsrfTokenValid('modifier' . $emargement->getId(), $request->request->get('_token'))) {
            $nouveauStatut = EmargementStatut::tryFrom((string) $request->request->get('statut'));

            if ($nouveauStatut) {
                $this->presenceManager->corriger($emargement, $nouveauStatut);
                $em->flush();
            }
        }

        return $this->redirectToRoute('app_formateur_dashboard');
    }

    #[Route('/session/{id}/cloturer', name: 'app_formateur_session_close', methods: ['POST'])]
    public function cloturer(Request $request, SessionCours $session, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessOwner($session);

        if ($this->isCsrfTokenValid('close' . $session->getId(), $request->request->get('_token'))) {
            $this->sessionManager->close($session);
            $em->flush();

            $this->addFlash('success', 'La session a été clôturée. Les fiches d\'absences sont figées.');
        }

        return $this->redirectToRoute('app_formateur_dashboard');
    }

    /**
     * Seul le formateur titulaire de la session peut agir dessus.
     */
    private function denyAccessUnlessOwner(SessionCours $session): void
    {
        if ($session->getFormateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Action interdite.');
        }
    }
}
