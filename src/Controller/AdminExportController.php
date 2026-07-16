<?php

namespace App\Controller;

use App\Form\ExportFilterType;
use App\Repository\EmargementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerInterface;

#[IsGranted('ROLE_ADMIN')]
class AdminExportController extends AbstractController
{
    #[Route('/admin/export', name: 'admin_export', methods: ['GET', 'POST'])]
    public function index(Request $request, EmargementRepository $repo, SerializerInterface $serializer): Response
    {
        $form = $this->createForm(ExportFilterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $emargements = $repo->findForExport($data['classe'], $data['etudiant']);

            $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $dateStr = $now->format('Y-m-d_H\hi');
            
            $filenameParts = ['export'];

            if ($data['classe']) {
                $classeClean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $data['classe']->getNom()));
                $classeClean = preg_replace('/_+/', '_', $classeClean);
                $filenameParts[] = trim($classeClean, '_');
            }

            if ($data['etudiant']) {
                $etudiantClean = strtolower($data['etudiant']->getNom() . '_' . $data['etudiant']->getPrenom());
                $filenameParts[] = $etudiantClean;
            }

            $filenameParts[] = $dateStr;

            $finalFilename = implode('_', $filenameParts) . '.csv';

            // Préparation des données plates pour le Serializer
            $csvData = [];
            foreach ($emargements as $e) {
                $csvData[] = [
                    'Date' => $e->getSession()->getDateCours()->format('d/m/Y'),
                    'Classe' => $e->getSession()->getClasse()->getNom(),
                    'Matiere' => $e->getSession()->getMatiere()->getNom(),
                    'Nom' => $e->getEtudiant()->getNom(),
                    'Prenom' => $e->getEtudiant()->getPrenom(),
                    'Statut' => $e->getStatut(),
                    'Heure de signature' => $e->getHeureSignature() ? $e->getHeureSignature()->format('H:i') : 'Absence',
                ];
            }

            if (empty($csvData)) {
                $csvData[] = ['Message' => 'Aucune donnee pour ces filtres'];
            }

            $response = new StreamedResponse(function () use ($csvData, $serializer) {
                echo $serializer->serialize($csvData, 'csv', [
                    CsvEncoder::DELIMITER_KEY => ';', 
                ]);
            });

            $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
            
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $finalFilename));

            return $response;
        }

        return $this->render('admin/export/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}