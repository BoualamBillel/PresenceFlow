<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Form\ClasseType;
use App\Repository\ClasseRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/classe')]
final class ClasseController extends AbstractController
{
    #[Route(name: 'app_classe_index', methods: ['GET'])]
   public function index(Request $request, ClasseRepository $classeRepository): Response
    {
        $searchTerm = $request->query->get('q');
        $filter = $request->query->get('filter', 'actives');

        $classes = $classeRepository->findBySearchAndFilter($searchTerm, $filter);

        return $this->render('classe/index.html.twig', [
            'classes' => $classes,
            'search_term' => $searchTerm,
            'current_filter' => $filter,
        ]);
    }

    #[Route('/new', name: 'app_classe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $classe = new Classe();
        $form = $this->createForm(ClasseType::class, $classe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($classe);
            $entityManager->flush();

            $this->addFlash('success', 'La classe "' . $classe->getNom() . '" a été créée avec succès.');


            return $this->redirectToRoute('app_classe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('classe/new.html.twig', [
            'classe' => $classe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/add-students', name: 'app_classe_add_students', methods: ['GET', 'POST'])]
    public function addStudents(
        Request $request, 
        Classe $classe, 
        UserRepository $userRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST')) {
            $studentIds = $request->request->all('students'); 

            if (!empty($studentIds)) {
                $addedCount = 0;
                foreach ($studentIds as $id) {
                    $student = $userRepository->find($id);
                    if ($student) {
                        $classe->addEtudiant($student);
                        $addedCount++;
                    }
                }
                $entityManager->flush();
                $this->addFlash('success', $addedCount . ' étudiant(s) ajouté(s) à la classe.');
            }

            return $this->redirectToRoute('app_classe_show', ['id' => $classe->getId()]);
        }

        $searchTerm = $request->query->get('q');
        $availableStudents = $userRepository->findAvailableForClasse($classe->getId(), $searchTerm);

        return $this->render('classe/add_students.html.twig', [
            'classe' => $classe,
            'students' => $availableStudents,
            'search_term' => $searchTerm,
        ]);
    }

    #[Route('/{id}', name: 'app_classe_show', methods: ['GET'])]
    public function show(Classe $classe): Response
    {
        return $this->render('classe/show.html.twig', [
            'classe' => $classe,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_classe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Classe $classe, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClasseType::class, $classe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_classe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('classe/edit.html.twig', [
            'classe' => $classe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_classe_delete', methods: ['POST'])]
    public function delete(Request $request, Classe $classe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$classe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($classe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_classe_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/remove-student/{studentId}', name: 'app_classe_remove_student', methods: ['POST'])]
    public function removeStudent(
        Request $request, 
        Classe $classe, 
        int $studentId, 
        UserRepository $userRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('remove'.$studentId, $request->request->get('_token'))) {
            
            $student = $userRepository->find($studentId);
            
            if ($student) {
                // Rupture de la relation ManyToMany
                $classe->removeEtudiant($student);
                $entityManager->flush();
                
                $this->addFlash('success', $student->getPrenom() . ' a été retiré(e) de la classe.');
            }
        }

        return $this->redirectToRoute('app_classe_show', ['id' => $classe->getId()], Response::HTTP_SEE_OTHER);
    }
}
