<?php

namespace App\Controller;

use App\Entity\SessionCours;
use App\Entity\Emargement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[Route('/etudiant')]
class EtudiantEmargementController extends AbstractController
{
   #[Route('/', name: 'app_etudiant_dashboard', methods: ['GET'])]
    public function dashboard(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        // Sécurité supplémentaire pour calmer l'IDE et valider le type à l'exécution
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException("Session utilisateur invalide.");
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        $classes = $user->getClasses();
        $currentSession = null;

        if (!$classes->isEmpty()) {
            $sessions = $em->getRepository(SessionCours::class)->createQueryBuilder('s')
                ->where('s.classe IN (:classes)')
                ->andWhere('s.dateCours = :today')
                ->setParameter('classes', $classes)
                ->setParameter('today', $today)
                ->orderBy('s.heureDebut', 'ASC')
                ->getQuery()
                ->getResult();

            foreach ($sessions as $session) {
                if ($currentTime <= $session->getHeureFin()->format('H:i:s')) {
                    $currentSession = $session;
                    break;
                }
            }
        }

        return $this->render('etudiant/dashboard.html.twig', [
            'session' => $currentSession,
        ]);
    }

    #[Route('/scanner', name: 'app_etudiant_scanner', methods: ['GET'])]
    public function scanner(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        return $this->render('etudiant/scanner.html.twig');
    }

    #[Route('/signer/{token}', name: 'app_etudiant_signer', methods: ['GET'])]
    public function signer(string $token, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        // 1. Recherche de la session via le Token unique
        $session = $em->getRepository(SessionCours::class)->findOneBy(['qrCodeToken' => $token]);

        if (!$session || !$session->isQrTokenValid()) {
            $this->addFlash('error', 'Le QR Code est invalide ou a expiré. Demandez au formateur de le régénérer.');
            return $this->redirectToRoute('app_etudiant_dashboard'); // Retour au dashboard
        }

        // 2. Recherche de la ligne d'émargement de cet élève précis
        $emargement = $em->getRepository(Emargement::class)->findOneBy([
            'session' => $session,
            'etudiant' => $user
        ]);

        if (!$emargement) {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à ce cours.');
            return $this->redirectToRoute('app_etudiant_dashboard');
        }

        if ($emargement->getStatut() !== 'EN_ATTENTE') {
            $this->addFlash('error', 'Vous avez déjà validé votre présence pour ce cours.');
            return $this->redirectToRoute('app_etudiant_dashboard');
        }

        // 3. Logique Métier : L'entité capture l'heure et décide (Présent ou Retard)
        $emargement->marquerPresence();
        $em->flush();

        // 4. MERCURE : On pousse l'info en temps réel au Dashboard du formateur CANCEL POUR L'INSTANT
        // $topic = 'http://presenceflow.com/session/' . $session->getId();
        // $payload = json_encode([
        //     'emargement_id' => $emargement->getId(),
        //     'statut' => $emargement->getStatut(),
        //     'heure' => $emargement->getHeureSignature()->format('H:i')
        // ]);
        
        // $update = new Update($topic, $payload);
        // $hub->publish($update);

        // 5. Retour UI pour l'élève (sur le dashboard)
        $this->addFlash('success', 'Présence validée : ' . $emargement->getStatut());
        
        return $this->redirectToRoute('app_etudiant_dashboard');
    }
}