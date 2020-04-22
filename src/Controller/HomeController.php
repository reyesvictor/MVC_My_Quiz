<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{

    /**
     * @Route("/", name="homepage")
     */
    public function home()
    {
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
