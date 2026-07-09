<?php
// src/Controller/FormateurSessionController.php

namespace App\Controller;

use App\Entity\SessionCours;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/formateur/session')]
class FormateurSessionController extends AbstractController
{
    #[Route('/{id}', name: 'app_formateur_session_show', methods: ['GET'])]
    public function show(SessionCours $session): Response 
    {
        $qrCodeUri = null;
        $timeLeft = 0;

        if ($session->getQrCodeToken() && $session->isQrTokenValid()) {
            
            $signerUrl = $this->generateUrl(
                'app_etudiant_signer', 
                ['token' => $session->getQrCodeToken()], 
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $signerUrl = $this->generateUrl(
                'app_etudiant_signer', 
                ['token' => $session->getQrCodeToken()], 
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $builder = new Builder(
                writer: new PngWriter(),
                data: $signerUrl,
                size: 300,
                margin: 10
            );
            
            $result = $builder->build();
            $qrCodeUri = $result->getDataUri();

            $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $timeLeft = $session->getQrTokenExpiresAt()->getTimestamp() - $now->getTimestamp();
        }

        return $this->render('formateur/session_show.html.twig', [
            'session' => $session,
            'qr_code_uri' => $qrCodeUri,
            'time_left' => max(0, $timeLeft),
        ]);
    }

    #[Route('/{id}/lancer', name: 'app_formateur_session_start', methods: ['POST'])]
    public function start(Request $request, SessionCours $session, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('start'.$session->getId(), $request->request->get('_token'))) {
            $session->generateNewQrToken();
            $em->flush();
        }

        return $this->redirectToRoute('app_formateur_session_show', ['id' => $session->getId()]);
    }

    #[Route('/signer/{token}', name: 'app_etudiant_signer', methods: ['GET'])]
    public function mockSigner(string $token): Response
    {
        return new Response('Page de signature pour le token : ' . $token);
    }
}