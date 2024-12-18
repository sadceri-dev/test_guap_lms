<?php

namespace App\Controller;

use App\Entity\Discipline;
use App\Entity\Grade;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StudentController extends AbstractController
{
    #[Route('/student', name: 'student_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        // Получаем текущего пользователя (студента)
        $student = $this->getUser();

        // Получаем дисциплины, к которым прикреплён студент
        $grades = $em->getRepository(Grade::class)->findBy(['student' => $student]);

        // Получаем все доступные дисциплины для прикрепления
        $disciplines = $em->getRepository(Discipline::class)->findAll();

        return $this->render('student/index.html.twig', [
            'grades' => $grades,
            'student' => $student,
            'disciplines' => $disciplines,

        ]);
    }

    #[Route('/student/attach/{id}', name: 'student_attach_discipline')]
    public function attachDiscipline(int $id, EntityManagerInterface $em): Response
    {
        // Получаем текущего пользователя (студента)
        $student = $this->getUser();

        // Находим дисциплину по ID
        $discipline = $em->getRepository(Discipline::class)->find($id);

        if (!$discipline) {
            throw $this->createNotFoundException('Discipline not found.');
        }

        // Проверяем, не прикреплён ли уже студент к этой дисциплине
        $existingGrade = $em->getRepository(Grade::class)->findOneBy([
            'student' => $student,
            'discipline' => $discipline,
        ]);

        if ($existingGrade) {
            $this->addFlash('warning', 'You are already attached to this discipline.');
            return $this->redirectToRoute('student_dashboard');
        }

        // Создаём новую запись оценки для прикрепления студента
        $grade = new Grade();
        $grade->setStudent($student);
        $grade->setDiscipline($discipline);
        $grade->setScore(0); // Начальная оценка

        $em->persist($grade);
        $em->flush();

        $this->addFlash('success', 'You have successfully attached to the discipline.');

        return $this->redirectToRoute('student_dashboard');
    }
}
