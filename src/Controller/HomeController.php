<?php

namespace App\Controller;

use COM;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HomeController extends AbstractController
{

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
     * @Route("/hello/{name}/{age}", name="hello")
     * @Route("/hello/")
     * @Route("/hello/{name}")
     * @return void
     */
    public function hello($name = 'Peter', $age = 10)
    {
        return new Response('Hello>>>' . ucfirst($name) . ' vous avez ' . $age);
    }
}
