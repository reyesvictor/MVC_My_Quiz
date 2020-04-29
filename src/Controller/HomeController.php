<?php

namespace App\Controller;

use COM;
use App\Entity\User;
use App\Form\UserType;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class HomeController extends AbstractController
{

    private $security;

    public function __construct(Security $security)
    {
        // Avoid calling getUser() in the constructor: auth may not
        // be complete yet. Instead, store the entire Security object.
        $this->security = $security;
    }
    /**
     * @Route("/", name="homepage")
     */
    public function home()
    {
        // $repository = $this->getDoctrine()->getRepository(Category::class);
        // $data = $repository->find(['id' => 14]);
        // $return  = new Category();
        // $repository = $this->getDoctrine()->getRepository(Category::class);

        // $product = $repository->find(14);
        // echo '--------<pre>';
        // var_dump($product);
        // echo '<br>--------</br></pre>';
        // exit();
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }


    /**
     * Visitor can create an account
     * 
     * @Route("/register", name="app_register", methods={"GET","POST"})
     */
    public function register(Request $request, ObjectManager $manager, UserPasswordEncoderInterface $encoder): Response
    {
        if ($this->security->getUser() !== null) {
            $this->addFlash('success', 'You are connected. Logout to register');
            return $this->redirectToRoute('homepage');
        }

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password_hashed = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password_hashed);
            $manager->persist($user);
            $manager->flush();
            $this->addFlash('success', "You are registered. Please login.");
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Delete All Quizes From Cache. Importing and loading fixtures can cause errors with the cache system
     * 
     * @Route("/delete_cache", name="app_cache_delete", methods={"GET","POST"})
     */
    public function deleteQuizCache()
    {
        $cache = new FilesystemAdapter();

        for ($i = 0; $i < 100; $i++) {

            $productsCount = $cache->getItem('quiz.game.' . $i);
            if ($productsCount->isHit()) {
                $cache->deleteItem('quiz.game.' . $i);
            }
        }
        $this->addFlash('success', 'All cache was deleted');
        return $this->redirectToRoute('homepage');
    }
}
