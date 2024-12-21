<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Discipline;
use App\Form\UserType;
use App\Form\DisciplineType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/admin/users', name: 'admin_users')]
    public function manageUsers(Request $request, EntityManagerInterface $em): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
    
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user);
            $em->flush();
            return $this->redirectToRoute('admin_users');
        }
    
        $users = $em->getRepository(User::class)->findAll();
        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/users/add', name: 'admin_add_user')]
    public function addUser(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Хэширование пароля
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            );
            $user->setPassword($hashedPassword);

            // Привязка дисциплин для студентов
            if (in_array('ROLE_STUDENT', $user->getRoles())) {
                foreach ($form->get('disciplines')->getData() as $discipline) {
                    $discipline->addStudent($user);
                    $em->persist($discipline);
                }
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'User added successfully!');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/add_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/admin/discipline/{id}/assign-students', name: 'admin_assign_students')]
public function assignStudents(
    int $id,
    Request $request,
    EntityManagerInterface $em
): Response {
    $discipline = $em->getRepository(Discipline::class)->find($id);

    if (!$discipline) {
        throw $this->createNotFoundException('Discipline not found.');
    }

    // Получить всех студентов (роль ROLE_STUDENT)
    $students = $em->getRepository(User::class)->findByRole('ROLE_STUDENT');

    $form = $this->createFormBuilder($discipline)
        ->add('students', EntityType::class, [
            'class' => User::class,
            'choices' => $students,
            'choice_label' => 'email',
            'multiple' => true,
            'expanded' => true,
        ])
        ->setAction($this->generateUrl('admin_assign_students', ['id' => $discipline->getId()]))
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($discipline);
        $em->flush();

        $this->addFlash('success', 'Students assigned successfully!');
        return $this->redirectToRoute('admin_dashboard');
    }

    return $this->render('admin/assign_students.html.twig', [
        'discipline' => $discipline,
        'form' => $form->createView(),
    ]);
}

    
    #[Route('/admin/users/edit/{id}', name: 'admin_edit_user')]
    public function editUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/edit_user.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/users/delete/{id}', name: 'admin_delete_user')]
    public function deleteUser(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/disciplines', name: 'admin_disciplines')]
    public function manageDisciplines(Request $request, EntityManagerInterface $em): Response
    {
        $discipline = new Discipline();
        $form = $this->createForm(DisciplineType::class, $discipline);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($discipline);
            $em->flush();

            $this->addFlash('success', 'Discipline added successfully!');
            return $this->redirectToRoute('admin_disciplines');
        }

        $disciplines = $em->getRepository(Discipline::class)->findAll();

        return $this->render('admin/disciplines.html.twig', [
            'disciplines' => $disciplines,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/disciplines/edit/{id}', name: 'admin_edit_discipline')]
    public function editDiscipline(Discipline $discipline, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DisciplineType::class, $discipline);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Discipline updated successfully!');
            return $this->redirectToRoute('admin_disciplines');
        }

        return $this->render('admin/edit_discipline.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/disciplines/delete/{id}', name: 'admin_delete_discipline')]
    public function deleteDiscipline(Discipline $discipline, EntityManagerInterface $em): Response
    {
        $em->remove($discipline);
        $em->flush();

        $this->addFlash('success', 'Discipline deleted successfully!');
        return $this->redirectToRoute('admin_disciplines');
    }

    #[Route('/admin/disciplines/{id}/assign-teacher', name: 'admin_assign_teacher')]
    public function assignTeacher(
        Discipline $discipline,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $teachers = $em->getRepository(User::class)->findByRole('ROLE_TEACHER');

        $form = $this->createFormBuilder($discipline)
            ->add('teacher', EntityType::class, [
                'class' => User::class,
                'choices' => $teachers,
                'choice_label' => 'email',
                'label' => 'Assign Teacher',
            ])
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, [
                'label' => 'Save',
                'attr' => ['class' => 'btn btn-primary'],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($discipline);
            $em->flush();

            $this->addFlash('success', 'Teacher assigned successfully!');
            return $this->redirectToRoute('admin_disciplines');
        }

        return $this->render('admin/assign_teacher.html.twig', [
            'form' => $form->createView(),
            'discipline' => $discipline,
        ]);
    }

}
