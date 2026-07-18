<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Repository\ClasseRepository;
use App\Repository\EmargementRepository;
use App\Repository\SessionCoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formateur')]
#[IsGranted('ROLE_FORMATEUR')]
class FormateurClassesController extends AbstractController
{
    #[Route('/classes', name: 'app_formateur_classes', methods: ['GET'])]
    public function index(ClasseRepository $classeRepository, EmargementRepository $emargementRepository): Response
    {
        $formateur = $this->getUser();

        $statsParClasse = [];
        foreach ($emargementRepository->findStatistiquesParClasse($formateur) as $row) {
            $statsParClasse[(int) $row['classeId']] = [
                'total' => (int) $row['total'],
                'presents' => (int) $row['presents'],
            ];
        }

        return $this->render('formateur/classes.html.twig', [
            'classes' => $classeRepository->findByFormateur($formateur),
            'stats' => $statsParClasse,
        ]);
    }

    #[Route('/classes/{id}', name: 'app_formateur_classes_show', methods: ['GET'])]
    public function show(Classe $classe, SessionCoursRepository $sessionCoursRepository, EmargementRepository $emargementRepository): Response
    {
        $formateur = $this->getUser();

        if ($sessionCoursRepository->count(['classe' => $classe, 'formateur' => $formateur]) === 0) {
            throw $this->createAccessDeniedException();
        }

        $statsParEtudiant = [];
        $totalEmargements = 0;
        $totalPresents = 0;
        foreach ($emargementRepository->findStatistiquesParEtudiant($classe, $formateur) as $row) {
            $statsParEtudiant[(int) $row['etudiantId']] = [
                'total' => (int) $row['total'],
                'presents' => (int) $row['presents'],
                'absents' => (int) $row['absents'],
            ];
            $totalEmargements += (int) $row['total'];
            $totalPresents += (int) $row['presents'];
        }

        $etudiants = [];
        foreach ($classe->getEtudiants() as $etudiant) {
            if ($etudiant->isArchived()) {
                continue;
            }
            $s = $statsParEtudiant[$etudiant->getId()] ?? ['total' => 0, 'presents' => 0, 'absents' => 0];
            $etudiants[] = [
                'etudiant' => $etudiant,
                'absents' => $s['absents'],
                'taux' => $s['total'] > 0 ? (int) round(($s['presents'] / $s['total']) * 100) : null,
            ];
        }

        usort($etudiants, fn($a, $b) => ($a['taux'] ?? 101) <=> ($b['taux'] ?? 101));

        return $this->render('formateur/classes_show.html.twig', [
            'classe' => $classe,
            'etudiants' => $etudiants,
            'taux_global' => $totalEmargements > 0 ? (int) round(($totalPresents / $totalEmargements) * 100) : null,
        ]);
    }
}