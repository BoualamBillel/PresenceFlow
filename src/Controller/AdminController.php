<?php

namespace App\Controller;

use App\Repository\JustificatifRepository;
use App\Repository\SessionCoursRepository;
use App\Repository\EmargementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_dashboard')]
    public function index(
        JustificatifRepository $justificatifRepo,
        SessionCoursRepository $sessionCoursRepo,
        EmargementRepository $emargementRepo
    ): Response {
        
        $countJustificatifs = $justificatifRepo->count(['statut' => 'EN_ATTENTE']);

        $today = new \DateTimeImmutable('today');
        $sessionsActives = $sessionCoursRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.dateCours = :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        $totalEmargements = $emargementRepo->count([]);
        $totalAbsences = $emargementRepo->count(['statut' => 'ABSENT']);
        
        $presenceGlobale = 100;
        if ($totalEmargements > 0) {
            $presenceGlobale = round((($totalEmargements - $totalAbsences) / $totalEmargements) * 100);
        }

        $activites = [];

        $derniersJustificatifs = $justificatifRepo->findBy([], ['dateSoumission' => 'DESC'], 3);
        foreach ($derniersJustificatifs as $j) {
            $etudiant = $j->getEmargement()->getEtudiant();
            $classe = $etudiant->getClasses()->first(); 
            
            $activites[] = [
                'type' => 'justificatif',
                'titre' => sprintf('Nouveau justificatif soumis : %s %s (%s)', 
                    $etudiant->getPrenom(), 
                    $etudiant->getNom(),
                    $classe ? $classe->getNom() : 'Sans classe'
                ),
                'temps' => 'Le ' . $j->getDateSoumission()->format('d/m à H:i'),
                'raw_date' => $j->getDateSoumission(),
                'couleur' => 'bg-blue-500',
                'route' => $this->generateUrl('admin_justificatif_index')
            ];
        }

        $recentesAbsences = $emargementRepo->findBy(['statut' => 'ABSENT'], ['id' => 'DESC'], 3);
        foreach ($recentesAbsences as $e) {
            if ($e->getJustificatifs()->isEmpty()) {
                $etudiant = $e->getEtudiant();
                $classe = $etudiant->getClasses()->first();
                $session = $e->getSession();

                $activites[] = [
                    'type' => 'absence',
                    'titre' => sprintf('Absence non justifiée : %s %s (%s)', 
                        $etudiant->getPrenom(), 
                        $etudiant->getNom(),
                        $classe ? $classe->getNom() : 'Sans classe'
                    ),
                    'temps' => 'Cours du ' . $session->getDateCours()->format('d/m'),
                    'raw_date' => \DateTimeImmutable::createFromInterface($session->getDateCours()),
                    'couleur' => 'bg-red-500',
                    'route' => '#' // Tu pourras pointer vers le profil de l'élève plus tard
                ];
            }
        }

        usort($activites, function ($a, $b) {
            return $b['raw_date'] <=> $a['raw_date'];
        });

        $activites = array_slice($activites, 0, 5);

        return $this->render('admin/dashboard.html.twig', [
            'count_justificatifs' => $countJustificatifs,
            'count_sessions' => $sessionsActives,
            'taux_presence' => $presenceGlobale,
            'activites' => $activites
        ]);
    }
}