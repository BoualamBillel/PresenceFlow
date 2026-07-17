<?php

namespace App\Controller;

use App\Form\ExportFilterType;
use App\Repository\EmargementRepository;
use App\Service\EmargementExporter;
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
    public function index(
        Request $request,
        EmargementRepository $emargementRepository,
        EmargementExporter $exporter,
        SerializerInterface $serializer,
    ): Response {
        $form = $this->createForm(ExportFilterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $emargements = $emargementRepository->findForExport($data['classe'], $data['etudiant']);

            $filename = $exporter->buildFilename($data['classe'], $data['etudiant']);
            $csvData = $exporter->toRows($emargements);

            $response = new StreamedResponse(function () use ($csvData, $serializer) {
                echo $serializer->serialize($csvData, 'csv', [
                    CsvEncoder::DELIMITER_KEY => ';',
                ]);
            });

            $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

            return $response;
        }

        return $this->render('admin/export/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
