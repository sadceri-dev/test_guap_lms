<?php

namespace App\Controller;

use App\Entity\Discipline;
use App\Entity\Grade;
use App\Entity\Submission;
use App\Entity\User;
use App\Form\SubmissionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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

    
    #[Route('/student/submit/{id}', name: 'student_submit_file')]
    public function submitFile(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ParameterBagInterface $params
    ): Response {
        $uploadDir = $params->get('upload_dir');

        $discipline = $em->getRepository(Discipline::class)->find($id);

        if (!$discipline) {
            throw $this->createNotFoundException('Discipline not found.');
        }

        $student = $this->getUser();
        $form = $this->createForm(SubmissionType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();

            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = uniqid() . '.' . $file->guessExtension();

                // Перемещение файла в каталог загрузки
                $file->move($uploadDir, $newFilename);

                // Получение объекта Grade или создание нового
                $grade = $em->getRepository(Grade::class)->findOneBy([
                    'discipline' => $discipline,
                    'student' => $student,
                ]);

                if (!$grade) {
                    $grade = new Grade();
                    $grade->setDiscipline($discipline);
                    $grade->setStudent($student);
                    $grade->setScore(0); // Установите начальную оценку
                }

                $grade->setFile($newFilename); // Сохранение имени файла
                $em->persist($grade);
                $em->flush();

                $this->addFlash('success', 'Файл успешно прикреплён!');
                return $this->redirectToRoute('student_dashboard');
            }
        }

        return $this->render('student/submit_file.html.twig', [
            'form' => $form->createView(),
            'discipline' => $discipline,
        ]);
    }


}
