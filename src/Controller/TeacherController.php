<?php

namespace App\Controller;

use App\Entity\Discipline;
use App\Entity\Grade;
use App\Entity\Submission;
use App\Entity\User;
use App\Form\GradeType;
use App\Form\AssignStudentsType;
use App\Form\UserType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TeacherController extends AbstractController
{
    #[Route('/teacher', name: 'teacher_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
{
    $disciplines = $em->getRepository(Discipline::class)->findAll();

    $form = $this->createFormBuilder()
        ->add('students', EntityType::class, [
            'class' => User::class,
            'choice_label' => 'email',
            'multiple' => true,
            'expanded' => true,
        ])
        ->getForm();

    
    return $this->render('teacher/index.html.twig', [
        'disciplines' => $disciplines,
        'form' => $form->createView(),
    ]);
}

    #[Route('/teacher/discipline/add', name: 'teacher_add_discipline')]
    public function addDiscipline(Request $request, EntityManagerInterface $em): Response
    {
        $discipline = new Discipline();

        $form = $this->createFormBuilder($discipline)
            ->add('name', TextType::class, ['label' => 'Discipline Name'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($discipline);
            $em->flush();

            $this->addFlash('success', 'Discipline added successfully!');
            return $this->redirectToRoute('teacher_dashboard');
        }

        return $this->render('teacher/add_discipline.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/teacher/discipline/{id}/edit', name: 'teacher_edit_discipline')]
    public function editDiscipline(Discipline $discipline, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder($discipline)
            ->add('name', TextType::class, ['label' => 'Discipline Name'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Discipline updated successfully!');
            return $this->redirectToRoute('teacher_dashboard');
        }

        return $this->render('teacher/edit_discipline.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/teacher/discipline/{id}', name: 'teacher_discipline_details')]
    public function disciplineDetails(int $id, EntityManagerInterface $em): Response
    {
        // Найти дисциплину
        $discipline = $em->getRepository(Discipline::class)->find($id);

        if (!$discipline) {
            throw $this->createNotFoundException('Discipline not found.');
        }

        return $this->render('teacher/discipline_details.html.twig', [
            'discipline' => $discipline,
            'students' => $discipline->getStudents(),
            'grades' => $discipline->getGrades(),
        ]);
    }



    #[Route('/teacher/discipline/{disciplineId}/grade/{studentId}/edit', name: 'teacher_edit_grade')]
    public function editGrade(
        int $disciplineId,
        int $studentId,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $discipline = $em->getRepository(Discipline::class)->find($disciplineId);
        $student = $em->getRepository(User::class)->find($studentId);

        if (!$discipline || !$student) {
            throw $this->createNotFoundException('Discipline or student not found.');
        }

        $grade = $em->getRepository(Grade::class)->findOneBy([
            'discipline' => $discipline,
            'student' => $student,
        ]);

        if (!$grade) {
            $grade = new Grade();
            $grade->setDiscipline($discipline);
            $grade->setStudent($student);
        }

        $form = $this->createFormBuilder($grade)
            ->add('score', TextType::class, ['label' => 'Grade'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($grade);
            $em->flush();

            $this->addFlash('success', 'Grade updated successfully!');
            return $this->redirectToRoute('teacher_discipline_details', ['id' => $disciplineId]);
        }

        return $this->render('teacher/edit_grade.html.twig', [
            'form' => $form->createView(),
            'discipline' => $discipline,
            'student' => $student,
        ]);
    }

    #[Route('/teacher/discipline/{id}/submissions', name: 'teacher_view_submissions')]
    public function viewSubmissions(int $id, EntityManagerInterface $em): Response
    {
        $discipline = $em->getRepository(Discipline::class)->find($id);

        if (!$discipline) {
            throw $this->createNotFoundException('Discipline not found.');
        }

        $submissions = $em->getRepository(Submission::class)->findBy(['discipline' => $discipline]);

        return $this->render('teacher/submissions.html.twig', [
            'discipline' => $discipline,
            'submissions' => $submissions,
        ]);
    }

    

}
