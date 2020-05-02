<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Controller\HomeController;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/category")
 * @(repositoryClass="Blogger\BlogBundle\Repository\BlogRepository")
 */
class CategoryController extends AbstractController
{
    /**
     * @Route("/", name="category_index", methods={"GET"})
     */
    public function index(CategoryRepository $categoryRepository, Request $request): Response
    {
        HomeController::countVisitors($request, $this->getUser());


        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="category_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')", message="Only Admins can access this feature. Sorry :-(")
     */
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('category_index');
        }


        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}", name="category_show", methods={"GET"})
     */
    public function show(Category $category): Response
    {
        $quizzes =  $this->getDoctrine()->getRepository(Quiz::class)->findByCategory($category);
        foreach ($quizzes as $quiz) {
            $category->addQuiz($quiz);
        }
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="category_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')", message="Only Admins can access this feature. Sorry :-(")
     */
    public function edit(Request $request, Category $category): Response
    {
        //Passer un dernier param pour se servir de findAll dans QuizType
        $getRepo =  $this->getDoctrine()->getRepository(Category::class);
        $category_temp = new Category();
        $form = $this->createForm(CategoryType::class, $category_temp, [
            'category' => $category,
        ]);
        //===============================================================

        // $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($category);
            $em = $this->getDoctrine()->getManager();

            //Category Name Must Be Unique
            if (
                !empty($product = $em->getRepository(Category::class)->findOneByName($category_temp->getName()))
                && strtolower($product->getName()) == strtolower($category_temp->getName())
                && strtolower($product->getName()) !== strtolower($category->getName())
            ) {
                $this->addFlash('danger', 'The category name is already taken. Please choose another.');
            } else {
                if ($category_temp->getName() !== $category->getName() && $category_temp != null) {
                    $category->setName($category_temp->getName());
                }
                $category->setUpdatedAt(new \DateTime('now'));
                $category->createSlug($category->getName());
                $entityManager->flush();

                return $this->redirectToRoute('category_index');
            }
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="category_delete", methods={"DELETE"})
     * @Security("is_granted('ROLE_ADMIN')", message="Only Admins can access this feature. Sorry :-(")
     */
    public function delete(Request $request, Category $category): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('category_index');
    }
}
