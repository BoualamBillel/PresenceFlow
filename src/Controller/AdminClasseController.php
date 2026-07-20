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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/classe')]
#[IsGranted('ROLE_ADMIN')]
final class AdminClasseController extends AbstractController
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
            if (!$this->isCsrfTokenValid('classe_add_students' . $classe->getId(), $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $studentIds = $request->request->all('students');
            $addedCount = 0;

            foreach ($studentIds as $id) {
                $student = $userRepository->find($id);

                if (!$student || $classe->getEtudiants()->contains($student)) {
                    continue;
                }

                $roles = $student->getRoles();
                if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_FORMATEUR', $roles, true)) {
                    continue;
                }

                $classe->addEtudiant($student);
                $addedCount++;
            }

            if ($addedCount > 0) {
                $entityManager->flush();
                $this->addFlash('success', $addedCount . ' étudiant(s) ajouté(s) à la classe.');
            } else {
                $this->addFlash('error', 'Aucun étudiant valide sélectionné.');
            }

            return $this->redirectToRoute('app_classe_add_students', ['id' => $classe->getId()]);
        }

        $search = trim((string) $request->query->get('q', ''));

        return $this->render('classe/add_students.html.twig', [
            'classe' => $classe,
            'membres' => $classe->getEtudiants(),
            'etudiants' => $userRepository->findEtudiantsDisponiblesPourClasse($classe, $search),
            'search_term' => $search,
        ]);
    }

    #[Route('/{id}/remove-student/{studentId}', name: 'app_classe_remove_student', methods: ['POST'])]
    public function removeStudent(
        Request $request,
        Classe $classe,
        int $studentId,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('classe_remove_student' . $studentId, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $student = $userRepository->find($studentId);

        if ($student && $classe->getEtudiants()->contains($student)) {
            $classe->removeEtudiant($student);
            $entityManager->flush();
            $this->addFlash('success', $student->getPrenom() . ' ' . $student->getNom() . ' a été retiré(e) de la classe.');
        }

        return $this->redirectToRoute('app_classe_add_students', ['id' => $classe->getId()]);
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
        if ($this->isCsrfTokenValid('delete' . $classe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($classe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_classe_index', [], Response::HTTP_SEE_OTHER);
    }
}
