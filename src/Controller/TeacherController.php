<?php

namespace App\Controller;

use App\Entity\Discipline;
use App\Entity\Grade;
use App\Entity\User;
use App\Form\GradeType;
use App\Form\AssignStudentsType;
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

        return $this->render('teacher/index.html.twig', ['disciplines' => $disciplines]);
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




    #[Route('/teacher/discipline/{disciplineId}/grade/{gradeId}/edit', name: 'teacher_edit_grade')]
    public function editGrade(
        int $disciplineId,
        int $gradeId,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $discipline = $em->getRepository(Discipline::class)->find($disciplineId);
        $grade = $em->getRepository(Grade::class)->find($gradeId);

        if (!$discipline || !$grade || $grade->getDiscipline() !== $discipline) {
            throw $this->createNotFoundException('Discipline or grade not found.');
        }

        $form = $this->createForm(GradeType::class, $grade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Grade updated successfully!');
            return $this->redirectToRoute('teacher_grades', ['id' => $disciplineId]);
        }

        return $this->render('teacher/edit_grade.html.twig', [
            'discipline' => $discipline,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/teacher/discipline/{id}/assign-students', name: 'teacher_assign_students')]
    public function assignStudents(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $discipline = $em->getRepository(Discipline::class)->find($id);
    
        if (!$discipline) {
            throw $this->createNotFoundException('Discipline not found.');
        }
    
        // Получить всех пользователей с ролью ROLE_STUDENT
        $students = $em->getRepository(User::class)->findByRole('ROLE_STUDENT');
    
        // Создать форму
        $form = $this->createForm(AssignStudentsType::class, $discipline, [
            'students' => $students,
        ]);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('students')->getData() as $student) {
                $discipline->addStudent($student);
            }
    
            $em->persist($discipline);
            $em->flush();
    
            $this->addFlash('success', 'Students successfully assigned to the discipline!');
            return $this->redirectToRoute('teacher_dashboard');
        }
    
        return $this->render('teacher/assign_students.html.twig', [
            'form' => $form->createView(),
            'discipline' => $discipline,
        ]);
    }
    

}
