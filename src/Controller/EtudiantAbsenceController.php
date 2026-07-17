<?php

namespace App\Controller;

use App\Entity\Emargement;
use App\Entity\Justificatif;
use App\Entity\User;
use App\Form\JustificatifType;
use App\Repository\EmargementRepository;
use App\Service\JustificatifManager;
use App\Service\PresenceManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/etudiant')]
#[IsGranted('ROLE_ETUDIANT')]
class EtudiantAbsenceController extends AbstractController
{
    #[Route('/absences', name: 'app_etudiant_absences', methods: ['GET'])]
    public function listerAbsences(EmargementRepository $emargementRepository, PresenceManager $presenceManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $absences = $presenceManager->filterJustifiables(
            $emargementRepository->findForEtudiant($user)
        );

        return $this->render('etudiant/absences.html.twig', [
            'absences' => $absences,
        ]);
    }

    #[Route('/absence/{id}/justifier', name: 'app_etudiant_justifier', methods: ['GET', 'POST'])]
    public function justifier(
        Emargement $emargement,
        Request $request,
        EntityManagerInterface $em,
        PresenceManager $presenceManager,
        JustificatifManager $justificatifManager,
    ): Response {
        if ($emargement->getEtudiant() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Action interdite.');
        }

        if (!$presenceManager->resolveStatut($emargement)?->estJustifiable()) {
            $this->addFlash('error', 'Aucune justification requise pour ce cours.');
            return $this->redirectToRoute('app_etudiant_absences');
        }

        $justificatif = new Justificatif();
        $form = $this->createForm(JustificatifType::class, $justificatif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fichier = $form->get('fichier')->getData();

            if ($fichier) {
                try {
                    $justificatifManager->soumettre($justificatif, $emargement, $fichier);
                } catch (FileException) {
                    $this->addFlash('error', 'Erreur système lors du transfert du fichier.');
                    return $this->redirectToRoute('app_etudiant_absences');
                }

                $em->persist($justificatif);
                $em->flush();

                $this->addFlash('success', 'Justificatif envoyé avec succès. Validation en attente.');
                return $this->redirectToRoute('app_etudiant_absences');
            }
        }

        return $this->render('etudiant/justifier.html.twig', [
            'emargement' => $emargement,
            'form' => $form->createView(),
        ]);
    }
}
