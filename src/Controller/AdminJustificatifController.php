<?php

namespace App\Controller;

use App\Entity\Justificatif;
use App\Repository\JustificatifRepository;
use App\Service\JustificatifManager;
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
    public function __construct(private readonly JustificatifManager $justificatifManager)
    {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, JustificatifRepository $justificatifRepository): Response
    {
        $filter = $request->query->get('filter', 'en_attente');

        $justificatifs = $filter === 'traites'
            ? $justificatifRepository->findTraites()
            : $justificatifRepository->findEnAttente();

        return $this->render('admin/justificatif/index.html.twig', [
            'justificatifs' => $justificatifs,
            'current_filter' => $filter,
        ]);
    }

    #[Route('/{id}/valider', name: 'valider', methods: ['POST'])]
    public function valider(Justificatif $justificatif, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('valider' . $justificatif->getId(), $request->request->get('_token'))) {
            $this->justificatifManager->valider($justificatif);
            $em->flush();

            $this->addFlash('success', 'Le justificatif a été validé.');
        }

        return $this->redirectToRoute('admin_justificatif_index');
    }

    #[Route('/{id}/refuser', name: 'refuser', methods: ['POST'])]
    public function refuser(Justificatif $justificatif, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('refuser' . $justificatif->getId(), $request->request->get('_token'))) {
            $motif = trim((string) $request->request->get('motif_refus'));

            if ($motif === '') {
                $this->addFlash('error', 'Vous devez fournir un motif pour refuser ce justificatif.');
                return $this->redirectToRoute('admin_justificatif_index');
            }

            $this->justificatifManager->refuser($justificatif, $motif);
            $em->flush();

            $this->addFlash('success', 'Le justificatif a été refusé.');
        }

        return $this->redirectToRoute('admin_justificatif_index');
    }
}
