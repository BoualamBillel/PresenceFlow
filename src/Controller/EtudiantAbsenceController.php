<?php
namespace App\Controller;

use App\Entity\Emargement;
use App\Entity\Justificatif;
use App\Form\JustificatifType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/etudiant')]
class EtudiantAbsenceController extends AbstractController
{
    #[Route('/absences', name: 'app_etudiant_absences', methods: ['GET'])]
    public function listerAbsences(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $allEmargements = $em->getRepository(Emargement::class)->createQueryBuilder('e')
            ->join('e.session', 's')
            ->where('e.etudiant = :user') 
            ->setParameter('user', $user)
            ->orderBy('s.dateCours', 'DESC')
            ->addOrderBy('s.heureDebut', 'DESC')
            ->getQuery()
            ->getResult();

        $absences = array_filter($allEmargements, function(Emargement $emargement) {
            return in_array($emargement->getStatut(), ['ABSENT', 'RETARD']);
        });

        return $this->render('etudiant/absences.html.twig', [
            'absences' => $absences,
        ]);
    }

    #[Route('/absence/{id}/justifier', name: 'app_etudiant_justifier', methods: ['GET', 'POST'])]
    public function justifier(Emargement $emargement, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        if ($emargement->getEtudiant() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Action interdite.");
        }

        if (!in_array($emargement->getStatut(), ['ABSENT', 'RETARD'])) {
            $this->addFlash('error', 'Aucune justification requise pour ce cours.');
            return $this->redirectToRoute('app_etudiant_absences');
        }

        $justificatif = new Justificatif();
        $form = $this->createForm(JustificatifType::class, $justificatif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fichier = $form->get('fichier')->getData();

            if ($fichier) {
                $originalFilename = pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$fichier->guessExtension();

                try {
                    $fichier->move(
                        $this->getParameter('justificatifs_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur système lors du transfert du fichier.');
                    return $this->redirectToRoute('app_etudiant_absences');
                }

                $justificatif->setUrlFichier($newFilename);
                $justificatif->setStatut('EN_ATTENTE');
                $justificatif->setDateSoumission(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
                
                $justificatif->setEmargement($emargement);
                
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