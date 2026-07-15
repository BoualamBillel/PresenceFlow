<?php

namespace App\Controller;

use App\Entity\Justificatif;
use App\Repository\JustificatifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/justificatifs', name: 'admin_justificatif_')]
class AdminJustificatifController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, JustificatifRepository $justificatifRepository): Response
    {
        $filter = $request->query->get('filter', 'en_attente');

        if ($filter === 'traites') {
            $justificatifs = $justificatifRepository->createQueryBuilder('j')
                ->where('j.statut != :statut')
                ->setParameter('statut', 'EN_ATTENTE')
                ->getQuery()
                ->getResult();
        } else {
            $justificatifs = $justificatifRepository->findBy(['statut' => 'EN_ATTENTE']);
        }

        return $this->render('admin/justificatif/index.html.twig', [
            'justificatifs' => $justificatifs,
            'current_filter' => $filter,
        ]);
    }

    // --- ACTION : VALIDER ---
    #[Route('/{id}/valider', name: 'valider', methods: ['POST'])]
    public function valider(Justificatif $justificatif, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('valider' . $justificatif->getId(), $request->request->get('_token'))) {
            $justificatif->setStatut('VALIDE');
            $em->flush();
            $this->addFlash('success', 'Le justificatif a été validé.');
        }

        return $this->redirectToRoute('admin_justificatif_index');
    }

    // --- ACTION : REFUSER ---
    #[Route('/{id}/refuser', name: 'refuser', methods: ['POST'])]
    public function refuser(Justificatif $justificatif, EntityManagerInterface $em, Request $request): Response
    {
        $motif = $request->request->get('motif_refus');

        if ($this->isCsrfTokenValid('refuser' . $justificatif->getId(), $request->request->get('_token'))) {
            if (empty(trim($motif))) {
                $this->addFlash('error', 'Vous devez fournir un motif pour refuser ce justificatif.');
                return $this->redirectToRoute('admin_justificatif_index');
            }

            $justificatif->setStatut('REFUSE');
            $justificatif->setMotifRefus($motif);
            $em->flush();

            $this->addFlash('success', 'Le justificatif a été refusé.');
        }

        return $this->redirectToRoute('admin_justificatif_index');
    }
}