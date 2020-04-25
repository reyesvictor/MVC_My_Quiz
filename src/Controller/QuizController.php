<?php

namespace App\Controller;

use DateTime;
use App\Entity\Quiz;
use App\Form\QuizType;
use App\Entity\Category;
use App\Repository\QuizRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/quiz")
 * @(repositoryClass="Blogger\BlogBundle\Repository\BlogRepository")
 */
class QuizController extends AbstractController
{
    /**
     * @Route("/", name="quiz_index", methods={"GET"})
     */
    public function index(QuizRepository $quizRepository): Response
    {
        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="quiz_new", methods={"GET","POST"})
     */
    // public function new(Request $request, ObjectManager $entityManager): Response
    public function new(Request $request): Response
    {
        //Passer un dernier param pour se servir de findAll dans QuizType
        $getRepo =  $this->getDoctrine()->getRepository(Category::class);
        $quiz = new Quiz();
        $form = $this->createForm(QuizType::class, $quiz, [
            'get_category_repo' => $getRepo,
        ]);
        //===============================================================

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($quiz);
            $entityManager->flush();

            return $this->redirectToRoute('quiz_index');
        }

        // dump($form->createView());
        // exit();


        return $this->render('quiz/new.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="quiz_edit", methods={"GET","POST"})
     * 
     */
    public function edit(Request $request, Quiz $quiz): Response
    {
        // dd($quiz);
        //Passer un dernier param pour se servir de findAll dans QuizType
        $getRepo =  $this->getDoctrine()->getRepository(Category::class);
        $quiz_temp = new Quiz();
        $form = $this->createForm(QuizType::class, $quiz_temp, [
            'get_category_repo' => $getRepo,
            'quiz' => $quiz,
        ]);
        //===============================================================

        // $form = $this->createForm(QuizType::class, $quiz);

        // dd($form);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // $this->getDoctrine()->getManager()->flush();
            // dd($quiz_temp);


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($quiz);
            // $entityManager->merge($quiz);

            $em = $this->getDoctrine()->getManager();


            //Quiz Name Must Be Unique
            if ( !empty($product = $em->getRepository(Quiz::class)->findOneByName($quiz_temp->getName())) 
            && strtolower($product->getName()) == strtolower($quiz_temp->getName()) 
            && strtolower($product->getName()) !== strtolower($quiz->getName()) ) {
                $this->addFlash('danger', 'The quiz name is already taken. Please choose another.');
            } else {
                if ($quiz_temp->getName() !== $quiz->getName() && $quiz_temp != null) {
                    $quiz->setName($quiz_temp->getName());
                }

                if ($quiz_temp->getData() !== $quiz->getData() && $quiz_temp != null) {
                    $quiz->setData($quiz_temp->getData());
                }
                if ($quiz_temp->getCategory() !== $quiz->getCategory() && $quiz_temp != null) {
                    $quiz->setcategory($quiz_temp->getCategory());
                }
                $quiz->setUpdatedAt(new \DateTime('now'));
                $quiz->createSlug($quiz->getName());
                $entityManager->flush();
                return $this->redirectToRoute('quiz_index');
            }
        }

        return $this->render('quiz/edit.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="quiz_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Quiz $quiz): Response
    {
        if ($this->isCsrfTokenValid('delete' . $quiz->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            // dd($quiz);
            $entityManager->remove($quiz);
            $entityManager->flush();
        }

        return $this->redirectToRoute('quiz_index');
    }


    /**
     * @Route("/{id}", name="quiz_show", methods={"GET"})
     */
    public function show(Quiz $quiz): Response
    {
        return $this->render('quiz/show.html.twig', [
            'quiz' => $quiz,
        ]);
    }
}
