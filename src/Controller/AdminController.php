<?php

namespace App\Controller;

use App\Controller\HomeController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin_index")
     * @Security("is_granted('ROLE_ADMIN')", message="Only Admins can access this feature. Sorry :-(")
     */
    public function index(Request $request)
    {

        // • Qui ont passé un ou plusieurs quiz en particulier.

        // $user = $this->get('security.token_storage')->getToken()->getSecret();
        HomeController::countVisitors($request, $this->getUser());
        $cache = new FilesystemAdapter();
        $visitors = $cache->getItem('visitors');

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'number_of_visitors' => count($visitors->get('value')),
        ]);
    }

    /**
     * Delete All verification key from From Cache. It resets if fixture sur loaded
     * 
     * @Route("/delete_cache_key", name="app_cache_delete_key", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')", message="Only Admins can access this feature. Sorry :-(")
     */
    public function deleteVKeyCache()
    {
        $cache = new FilesystemAdapter();
        for ($i = 0; $i < 1000; $i++) {
            $productsCount = $cache->getItem('key.verification.' . $i);
            if ($productsCount->isHit()) {
                $cache->deleteItem('key.verification.' . $i);
            }
        }
        $this->addFlash('warning', 'All key verification cache was deleted');
        return $this->redirectToRoute('homepage');
    }

    /**
     * Delete All Quizes From Cache. Importing and loading fixtures can cause errors with the cache system
     * 
     * @Route("/delete_cache", name="app_cache_delete", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')", message="Only Admins can access this feature. Sorry :-(")
     */
    public function deleteQuizCache()
    {
        $cache = new FilesystemAdapter();
        for ($quiz = 0; $quiz < 1000; $quiz++) {
            for ($user = 0; $user < 1000; $user++) {
                $productsCount = $cache->getItem('quiz.game.' . $quiz . '.' . $user);
                if ($productsCount->isHit()) {
                    $cache->deleteItem('quiz.game.' . $quiz . '.' . $user);
                }
            }
        }
        $this->addFlash('info', 'All quiz cache was deleted');
        return $this->redirectToRoute('homepage');
    }

    /**
     * 
     * @Route("/send_email", name="send_email", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')", message="Only Admins can access this feature. Sorry :-(")
     */
    public function sendEmail(Request $request)
    {
        if ($request->request->all() == [] || $request->request->all()['filter'] == "") {
            $this->addFlash('danger', 'You need to choose a filter');
            return $this->redirectToRoute('admin_index');
        }

        return $this->render('admin/send_email.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
