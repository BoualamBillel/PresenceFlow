<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\EmargementStatut;
use App\Repository\EmargementRepository;
use App\Repository\SessionCoursRepository;
use App\Service\PresenceManager;
use App\Service\QrCodeManager;
use App\Service\SessionManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/etudiant')]
#[IsGranted('ROLE_ETUDIANT')]
class EtudiantEmargementController extends AbstractController
{
    #[Route('/', name: 'app_etudiant_dashboard', methods: ['GET'])]
    public function dashboard(SessionManager $sessionManager, EmargementRepository $emargementRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        ['current' => $currentSession, 'prochaines' => $prochainesSessions] = $sessionManager->findCurrentAndUpcomingForEtudiant($user);

        return $this->render('etudiant/dashboard.html.twig', [
            'session' => $currentSession,
            'prochainesSessions' => $prochainesSessions,
            'emargements' => $emargementRepository->findForEtudiant($user),
        ]);
    }

    #[Route('/scanner', name: 'app_etudiant_scanner', methods: ['GET'])]
    public function scanner(): Response
    {
        return $this->render('etudiant/scanner.html.twig');
    }

    #[Route('/signer/{token}', name: 'app_etudiant_signer', methods: ['GET'])]
    public function signer(
        string $token,
        SessionCoursRepository $sessionRepository,
        EmargementRepository $emargementRepository,
        QrCodeManager $qrCodeManager,
        PresenceManager $presenceManager,
        EntityManagerInterface $em,
    ): Response {
        $session = $sessionRepository->findOneBy(['qrCodeToken' => $token]);

        if (!$session || !$qrCodeManager->isTokenValid($session)) {
            $this->addFlash('error', 'Le QR Code est invalide ou a expiré. Demandez au formateur de le régénérer.');
            return $this->redirectToRoute('app_etudiant_dashboard');
        }

        $emargement = $emargementRepository->findOneBy([
            'session' => $session,
            'etudiant' => $this->getUser(),
        ]);

        if (!$emargement) {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à ce cours.');
            return $this->redirectToRoute('app_etudiant_dashboard');
        }

        if ($emargement->getStatut() !== EmargementStatut::EN_ATTENTE) {
            $this->addFlash('error', 'Vous avez déjà validé votre présence pour ce cours.');
            return $this->redirectToRoute('app_etudiant_dashboard');
        }

        $presenceManager->marquer($emargement);
        $em->flush();

        $this->addFlash('success', 'Présence validée : ' . $emargement->getStatut()->label());

        return $this->redirectToRoute('app_etudiant_dashboard');
    }
}
