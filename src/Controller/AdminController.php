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
        
        // 1. Justificatifs en attente (Vrai décompte en base)
        $countJustificatifs = $justificatifRepo->count(['statut' => 'EN_ATTENTE']);

        // 2. Sessions actives aujourd'hui
        // On récupère la date du jour sans l'heure pour filtrer
        $today = new \DateTimeImmutable('today');
        $sessionsActives = $sessionCoursRepo->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.dateCours = :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        // 3. Calcul de la présence globale (Mathématiques réelles sur l'Émargement)
        // Formule : ((Total Émargements - Absences) / Total Émargements) * 100
        $totalEmargements = $emargementRepo->count([]);
        $totalAbsences = $emargementRepo->count(['statut' => 'ABSENT']);
        
        $presenceGlobale = 100; // Par défaut s'il n'y a aucun émargement en base
        if ($totalEmargements > 0) {
            $presenceGlobale = round((($totalEmargements - $totalAbsences) / $totalEmargements) * 100);
        }

        // 4. Flux des Activités Récentes (Fusion chronologique)
        $activites = [];

        // A. On récupère les 3 derniers justificatifs soumis
        $derniersJustificatifs = $justificatifRepo->findBy([], ['dateSoumission' => 'DESC'], 3);
        foreach ($derniersJustificatifs as $j) {
            $etudiant = $j->getEmargement()->getEtudiant();
            $classe = $etudiant->getClasses()->first(); // Récupère la première classe (ManyToMany)
            
            $activites[] = [
                'type' => 'justificatif',
                'titre' => sprintf('Nouveau justificatif soumis : %s %s (%s)', 
                    $etudiant->getPrenom(), 
                    $etudiant->getNom(),
                    $classe ? $classe->getNom() : 'Sans classe'
                ),
                'temps' => 'Le ' . $j->getDateSoumission()->format('d/m à H:i'),
                'raw_date' => $j->getDateSoumission(), // Utile pour le tri
                'couleur' => 'bg-blue-500',
                'route' => $this->generateUrl('admin_justificatif_index') // Envoie vers la page de traitement
            ];
        }

        // B. On récupère les 3 dernières absences enregistrées
        // (Pour l'exemple, on cible les émargements au statut 'ABSENT' sans justificatif valide ou en attente)
        $recentesAbsences = $emargementRepo->findBy(['statut' => 'ABSENT'], ['id' => 'DESC'], 3);
        foreach ($recentesAbsences as $e) {
            // On n'affiche l'alerte d'absence que si l'étudiant n'a pas déjà soumis de justificatif
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
                    // dateTime de référence pour le tri (on prend la date du cours)
                    'raw_date' => \DateTimeImmutable::createFromInterface($session->getDateCours()),
                    'couleur' => 'bg-red-500',
                    'route' => '#' // Tu pourras pointer vers le profil de l'élève plus tard
                ];
            }
        }

        // C. Tri chronologique décroissant (le plus récent en premier)
        usort($activites, function ($a, $b) {
            return $b['raw_date'] <=> $a['raw_date'];
        });

        // On ne garde que les 5 activités les plus fraîches
        $activites = array_slice($activites, 0, 5);

        return $this->render('admin/dashboard.html.twig', [
            'count_justificatifs' => $countJustificatifs,
            'count_sessions' => $sessionsActives,
            'taux_presence' => $presenceGlobale,
            'activites' => $activites
        ]);
    }
}