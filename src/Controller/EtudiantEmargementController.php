<?php

namespace App\Controller;

use App\Entity\SessionCours;
use App\Entity\Emargement;
use App\Repository\SessionCoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[Route('/etudiant')]
class EtudiantEmargementController extends AbstractController
{
    #[Route('/', name: 'app_etudiant_dashboard', methods: ['GET'])]
    public function dashboard(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException("Session utilisateur invalide.");
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        $classes = $user->getClasses();

        $currentSession = null;
        $prochainesSessions = [];

        if (!$classes->isEmpty()) {
            $sessions = $em->getRepository(SessionCours::class)->createQueryBuilder('s')
                ->where('s.classe IN (:classes)')
                ->andWhere('s.dateCours = :today')
                ->setParameter('classes', $classes)
                ->setParameter('today', $today)
                ->orderBy('s.heureDebut', 'ASC')
                ->getQuery()
                ->getResult();

            foreach ($sessions as $session) {
                $debut = $session->getHeureDebut()->format('H:i:s');
                $fin = $session->getHeureFin()->format('H:i:s');

                if ($currentTime >= $debut && $currentTime <= $fin) {
                    $currentSession = $session;
                } elseif ($currentTime < $debut) {
                    $prochainesSessions[] = $session;
                }
            }
        }

        // Récupération des émargements pour calculer les absences à justifier
        $emargements = $em->getRepository(Emargement::class)
            ->createQueryBuilder('e')
            ->join('e.session', 's')
            ->where('e.etudiant = :user')
            ->setParameter('user', $user)
            ->orderBy('s.dateCours', 'DESC')
            ->addOrderBy('s.heureDebut', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('etudiant/dashboard.html.twig', [
            'session' => $currentSession,
            'prochainesSessions' => $prochainesSessions,
            'emargements' => $emargements,
        ]);
    }


    #[Route('/scanner', name: 'app_etudiant_scanner', methods: ['GET'])]
    public function scanner(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        return $this->render('etudiant/scanner.html.twig');
    }

    #[Route('/signer/{token}', name: 'app_etudiant_signer', methods: ['GET'])]
    public function signer(string $token, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        $session = $em->getRepository(SessionCours::class)->findOneBy(['qrCodeToken' => $token]);

        if (!$session || !$session->isQrTokenValid()) {
            $this->addFlash('error', 'Le QR Code est invalide ou a expiré. Demandez au formateur de le régénérer.');
            return $this->redirectToRoute('app_etudiant_dashboard');
        }

        $emargement = $em->getRepository(Emargement::class)->findOneBy([
            'session' => $session,
            'etudiant' => $user
        ]);

        if (!$emargement) {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à ce cours.');
            return $this->redirectToRoute('app_etudiant_dashboard');
        }

        if ($emargement->getStatut() !== 'EN_ATTENTE') {
            $this->addFlash('error', 'Vous avez déjà validé votre présence pour ce cours.');
            return $this->redirectToRoute('app_etudiant_dashboard');
        }

        $emargement->marquerPresence();
        $em->flush();

        $this->addFlash('success', 'Présence validée : ' . $emargement->getStatut());

        return $this->redirectToRoute('app_etudiant_dashboard');
    }
}