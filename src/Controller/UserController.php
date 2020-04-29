<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{

    // /**
    //  * @Route("/login", name="user_login", methods={"GET"})
    //  */
    // public function login(UserRepository $userRepository): Response
    // {
    //     return $this->render('user/login.html.twig');
    // }


    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        // dd($request->request,$form->isSubmitted(), $form->isValid());
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            //also verify if is admin
            if (isset($request->request->get('user')['email_is_verified'])) {
                $em = $this->getDoctrine()->getManager();
                $user->setEmailVerifiedAt(new \DateTime('now'));
                $em->persist($user);
                $em->flush();
            } else if ($user->getEmailIsVerified() == false) { //erase time of verification if email is not verified (changed by Admin)
                $em = $this->getDoctrine()->getManager();
                $user->setEmailVerifiedAt();
                $em->persist($user);
                $em->flush();
            }

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $deletedUser = $this->getDoctrine()->getRepository(User::class)->findByName('deletedUser')[0];
            if (($id = $user->getId()) == $deletedUser->getId()) {
                $this->addFlash('danger', "This user can't be deleted, or it will break the website.");
                return $this->redirectToRoute('user_show', [
                    'id' => $id,
                ]);
            }

            $UserIdLoggedIn = $this->getUser()->getId();
            if ($UserIdLoggedIn == $user->getId()) {
                $s = $this->get('session');
                $s = new Session();
                $s->invalidate();
            }

            $entityManager = $this->getDoctrine()->getManager();
            //Set quizzes to user_deleted
            $quizzes = $this->getDoctrine()->getRepository(Quiz::class)->findByAuthor($user);
            foreach ($quizzes as $quiz) {
                $quiz->setAuthor($deletedUser);
            }
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_index');
    }
}