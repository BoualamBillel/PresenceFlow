<?php
// src/Controller/FormateurSessionController.php

namespace App\Controller;

use App\Entity\SessionCours;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        
        // Sécurité d'accès au niveau du rôle
        if (!$user || !in_array('ROLE_FORMATEUR', $user->getRoles())) {
            throw $this->createAccessDeniedException("Accès non autorisé : Vous devez être formateur.");
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        // Récupération de toutes les sessions du formateur prévues pour aujourd'hui
        $sessions = $em->getRepository(SessionCours::class)->createQueryBuilder('s')
            ->andWhere('s.formateur = :formateur')
            ->andWhere('s.dateCours = :today')
            ->setParameter('formateur', $user)
            ->setParameter('today', $today)
            ->orderBy('s.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();

        $currentSession = null;

        // Analyse temporelle pour trouver le cours actif ou le prochain à venir
        foreach ($sessions as $session) {
            if ($currentTime <= $session->getHeureFin()->format('H:i:s')) {
                $currentSession = $session;
                break; // On a trouvé le cours le plus pertinent pour le moment T
            }
        }

        // ÉTAT : Aucun cours restant ou prévu aujourd'hui (Affichage vide conforme)
        if (!$currentSession) {
            return $this->render('formateur/session_show.html.twig', [
                'session' => null,
                'qr_code_uri' => null,
                'time_left' => 0,
                'is_startable' => false,
            ]);
        }

        // Calcul de la fenêtre d'ouverture (Heure de début - 15 minutes)
        $debutAutorise = $currentSession->getHeureDebut()->modify('-15 minutes')->format('H:i:s');
        $isStartable = $currentTime >= $debutAutorise;

        $qrCodeUri = null;
        $timeLeft = 0;

        // Si la session est lancée et que le jeton de sécurité est valide
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

        // Sécurité redondante côté serveur face aux injections HTTP POST directes
        if ($session->getDateCours()->format('Y-m-d') !== $now->format('Y-m-d') || $currentTime < $debutAutorise) {
            $this->addFlash('error', 'Action impossible hors des plages horaires autorisées.');
            return $this->redirectToRoute('app_formateur_dashboard');
        }

        if ($this->isCsrfTokenValid('start'.$session->getId(), $request->request->get('_token'))) {
            $session->generateNewQrToken();
            $em->flush();
        }

        return $this->redirectToRoute('app_formateur_dashboard');
    }

    #[Route('/signer/{token}', name: 'app_etudiant_signer', methods: ['GET'])]
    public function mockSigner(string $token): Response
    {
        return new Response('Page de signature pour le token : ' . $token);
    }

    #[Route('/emargement/{id}/modifier', name: 'app_formateur_emargement_modifier', methods: ['POST'])]
    public function modifierEmargement(Request $request, \App\Entity\Emargement $emargement, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $session = $emargement->getSession();

        if (!$user || $session->getFormateur() !== $user) {
            throw $this->createAccessDeniedException("Action interdite : vous ne gérez pas cette session.");
        }

        // Vérification du jeton CSRF unique généré pour cette ligne d'émargement
        if ($this->isCsrfTokenValid('modifier'.$emargement->getId(), $request->request->get('_token'))) {
            $nouveauStatut = $request->request->get('statut');
            $statutsValides = ['PRESENT', 'RETARD', 'ABSENT', 'EN_ATTENTE'];

            if (in_array($nouveauStatut, $statutsValides)) {
                $emargement->setStatut($nouveauStatut);
                
                // Si on le marque présent/retard manuellement, on horodate à l'instant T si c'était vide
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