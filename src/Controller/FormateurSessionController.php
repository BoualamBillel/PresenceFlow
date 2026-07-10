<?php

namespace App\Controller;

use App\Entity\SessionCours;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/formateur')]
class FormateurSessionController extends AbstractController
{
    #[Route('/', name: 'app_formateur_dashboard', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_FORMATEUR', $user->getRoles())) {
            throw $this->createAccessDeniedException("Accès non autorisé : Vous devez être formateur.");
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        $sessions = $em->getRepository(SessionCours::class)->createQueryBuilder('s')
            ->andWhere('s.formateur = :formateur')
            ->andWhere('s.dateCours = :today')
            ->setParameter('formateur', $user)
            ->setParameter('today', $today)
            ->orderBy('s.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();

        $currentSession = null;

        foreach ($sessions as $session) {
            if ($currentTime <= $session->getHeureFin()->format('H:i:s')) {
                $currentSession = $session;
                break;
            }
        }

        if (!$currentSession) {
            return $this->render('formateur/session_show.html.twig', [
                'session' => null,
                'qr_code_uri' => null,
                'time_left' => 0,
                'is_startable' => false,
            ]);
        }

        $debutAutorise = $currentSession->getHeureDebut()->modify('-15 minutes')->format('H:i:s');
        $isStartable = $currentTime >= $debutAutorise;

        $qrCodeUri = null;
        $timeLeft = 0;

        if ($currentSession->getQrCodeToken() && $currentSession->isQrTokenValid()) {
            
            $signerUrl = $this->generateUrl(
                'app_etudiant_signer', 
                ['token' => $currentSession->getQrCodeToken()], 
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $builder = new Builder(
                writer: new SvgWriter(),
                data: $signerUrl,
                size: 300,
                margin: 10
            );
            
            $result = $builder->build();
            $qrCodeUri = $result->getDataUri();
            $timeLeft = $currentSession->getQrTokenExpiresAt()->getTimestamp() - $now->getTimestamp();
        }

        return $this->render('formateur/session_show.html.twig', [
            'session' => $currentSession,
            'qr_code_uri' => $qrCodeUri,
            'time_left' => max(0, $timeLeft),
            'is_startable' => $isStartable,
        ]);
    }

   #[Route('/session/{id}/lancer', name: 'app_formateur_session_start', methods: ['POST'])]
    public function start(Request $request, SessionCours $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user || $session->getFormateur() !== $user) {
            throw $this->createAccessDeniedException("Action interdite.");
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $currentTime = $now->format('H:i:s');
        $debutAutorise = $session->getHeureDebut()->modify('-15 minutes')->format('H:i:s');

        if ($session->getDateCours()->format('Y-m-d') !== $now->format('Y-m-d') || $currentTime < $debutAutorise) {
            $this->addFlash('error', 'Action impossible hors des plages horaires autorisées.');
            return $this->redirectToRoute('app_formateur_dashboard');
        }

        if ($this->isCsrfTokenValid('start'.$session->getId(), $request->request->get('_token'))) {
            // 1. Génération du token QR Code
            $session->generateNewQrToken();

            // 2. Initialisation dynamique des fiches d'émergement pour chaque élève de la classe
            $classe = $session->getClasse();
            if ($classe) {
                foreach ($classe->getEtudiants() as $etudiant) {
                    // Sécurité anti-doublon si le formateur clique plusieurs fois sur le bouton
                    $existingEmargement = $em->getRepository(\App\Entity\Emargement::class)->findOneBy([
                        'session' => $session,
                        'etudiant' => $etudiant
                    ]);

                    if (!$existingEmargement) {
                        $emargement = new \App\Entity\Emargement();
                        $emargement->setSession($session);
                        $emargement->setEtudiant($etudiant);
                        $emargement->setStatut('EN_ATTENTE'); // Statut initialisation
                        $em->persist($emargement);
                    }
                }
            }

            $em->flush();
        }

        return $this->redirectToRoute('app_formateur_dashboard');
    }

    #[Route('/session/{id}/refresh-qr', name: 'app_formateur_session_refresh_qr', methods: ['POST'])]
    public function refreshQr(Request $request, SessionCours $session, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $session->getFormateur() !== $user) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!$this->isCsrfTokenValid('refresh'.$session->getId(), $data['_token'] ?? '')) {
            return $this->json(['error' => 'Token invalide'], 400);
        }

        $session->generateNewQrToken();
        $em->flush();

        $signerUrl = $this->generateUrl(
            'app_etudiant_signer', 
            ['token' => $session->getQrCodeToken()], 
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $builder = new Builder(
            writer: new SvgWriter(),
            data: $signerUrl,
            size: 300,
            margin: 10
        );

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $timeLeft = $session->getQrTokenExpiresAt()->getTimestamp() - $now->getTimestamp();

        return $this->json([
            'qr_code_uri' => $builder->build()->getDataUri(),
            'time_left' => max(0, $timeLeft)
        ]);
    }

    #[Route('/emargement/{id}/modifier', name: 'app_formateur_emargement_modifier', methods: ['POST'])]
    public function modifierEmargement(Request $request, \App\Entity\Emargement $emargement, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $session = $emargement->getSession();

        if (!$user || $session->getFormateur() !== $user) {
            throw $this->createAccessDeniedException("Action interdite : vous ne gérez pas cette session.");
        }

        if ($this->isCsrfTokenValid('modifier'.$emargement->getId(), $request->request->get('_token'))) {
            $nouveauStatut = $request->request->get('statut');
            $statutsValides = ['PRESENT', 'RETARD', 'ABSENT', 'EN_ATTENTE'];

            if (in_array($nouveauStatut, $statutsValides)) {
                $emargement->setStatut($nouveauStatut);
                
                if (!$emargement->getHeureSignature() && in_array($nouveauStatut, ['PRESENT', 'RETARD'])) {
                    $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
                    $emargement->setHeureSignature($now);
                }

                $em->flush();
            }
        }

        return $this->redirectToRoute('app_formateur_dashboard');
    }
}