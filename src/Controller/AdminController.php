<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\User;
use App\Controller\HomeController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{

    private $items = [
        "Qui ont joué un ou plusieurs quiz en particulier.",
        "Qui n’ont pas joué à un quiz particulier.",
        "Qui se sont connecté au moins une fois depuis 1 mois.",
        "Qui ne se sont pas connecté au moins une fois depuis 1 mois.",
    ];

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
            'form' => '_form_select',
            'number_of_visitors' => count($visitors->get('value')),
            'value0' => '--Users--',
            'items' => $this->items,
            'path' => "send_email",
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
    public function emailFilter1(Request $request)
    {
        if ($request->request->all() == [] || $request->request->all()['filter'] == "") {
            $this->addFlash('danger', 'You need to choose a filter');
            return $this->redirectToRoute('admin_index');
        }

        $index = $request->request->all()['filter'];
        $target = $this->items[$index];
        $value0 = '--Quel Quiz--';
        $items = [];
        $forbidden_names = ['Anonymous', 'deletedUser'];

        $cache = new FilesystemAdapter();
        $email_cache = $cache->getItem('mail');

        // dd($this->getDoctrine()->getRepository(User::class)->findAll()[0]->getHistorics()[0]->getQuizId()->getName());
        $list = [];
        if ($index == 0) {
            foreach ($this->getDoctrine()->getRepository(Quiz::class)->findAll() as $key => $quiz) {
                if ($quiz->getHistorics()[0] != null && !in_array($quiz->getHistorics()[0]->getUserId()->getName(), $forbidden_names)) {
                    $items[$quiz->getName()] = $quiz->getName();
                }
            }
            $email_cache->set('zero');
            $form = '_form_select';
        } else if ($index == 1) {
            foreach ($this->getDoctrine()->getRepository(Quiz::class)->findAll() as $key => $quiz) {
                foreach ($this->getDoctrine()->getRepository(User::class)->findAll() as $key2 => $user) {
                    if (!in_array($user->getName(), $forbidden_names)) {
                        $list[$quiz->getName() . '-' .  $user->getName()] = $quiz->getName();
                        foreach ($user->getHistorics() as $key3 => $historic) {
                            if ($historic->getQuizId()->getName() == $quiz->getName()) {
                                unset($list[$quiz->getName() . '-' .  $user->getName()]);
                            }
                        }
                    }
                }
                $email_cache->set('one');
            }
            $items =  array_combine(array_values(array_unique($list)), (array_unique($list)));
            $cache->save($email_cache);
            $path = 'send_email2';
            $form = '_form_select';
            // dd($list, array_values(array_unique($list)));
            // $all_quiz = $this->getDoctrine()->getRepository(Quiz::class)->findAll();
        } else if ($index == 2) {
            foreach ($this->getDoctrine()->getRepository(User::class)->findAll() as $key2 => $user) {
                if ($user->getLastConnectedAt() != null && !in_array($user->getName(), $forbidden_names)) {
                    if ((strtotime($user->getLastConnectedAt()->format('Y-m-d H:i:s')) - strtotime("-1 day")) > 0) {
                        $items[$user->getEmail()] = $user->getName();
                    }
                }
            }
            $path = 'send_email3';
            $form = '_form_mail';
        } else if ($index == 3) {
            foreach ($this->getDoctrine()->getRepository(User::class)->findAll() as $key2 => $user) {
                if ($user->getLastConnectedAt() != null && !in_array($user->getName(), $forbidden_names)) {
                    if ((strtotime($user->getLastConnectedAt()->format('Y-m-d H:i:s')) - strtotime("-1 day")) < 0) {
                        $items[$user->getEmail()] = $user->getName();
                    }
                }
            }
            $path = 'send_email3';
            $form = '_form_mail';
        }


        return $this->render('admin/send_email.html.twig', [
            'controller_name' => 'AdminController',
            'form' => $form,
            'target' => $target,
            'value0' => $value0,
            'items' => $items,
            'path' => $path,
        ]);
    }

    /**
     * 
     * @Route("/send_email2", name="send_email2", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')", message="Only Admins can access this feature. Sorry :-(")
     */
    public function emailFilter2(Request $request)
    {
        if ($request->request->all() == [] || $request->request->all()['filter'] == "") {
            $this->addFlash('danger', 'You need to choose a filter');
            return $this->redirectToRoute('admin_index');
        }

        $cache = new FilesystemAdapter();
        $email_cache = $cache->getItem('mail');
        $mail_cache_val = $email_cache->get('value');
        $value0 = '--Which User--';

        if ($mail_cache_val == 'zero') {
            $quiz_name = $request->request->all()['filter'];
            $target = 'Users that played' . $quiz_name;
            $items = [];
            foreach ($this->getDoctrine()->getRepository(Quiz::class)->findByName($quiz_name)[0]->getHistorics() as $key => $historic) {
                if ($historic->getUserId()->getName() !== 'Anonymous') {
                    $items[$historic->getUserId()->getEmail()] = $historic->getUserId()->getName() . " : " . $historic->getUserId()->getEmail();
                }
            }
        } else if ($mail_cache_val == 'one') {
            $quiz_name = $request->request->all()['filter'];
            $target = 'Users that did not play ' . $quiz_name;
            $items = [];
            $forbidden_names = ['Anonymous', 'deletedUser'];
            foreach ($this->getDoctrine()->getRepository(Quiz::class)->findByName($quiz_name) as $key => $quiz) {
                foreach ($this->getDoctrine()->getRepository(User::class)->findAll() as $key2 => $user) {
                    if (!in_array($user->getName(), $forbidden_names)) {
                        $list[$quiz->getName() . '-' .  $user->getName()] = $user->getName();
                        foreach ($user->getHistorics() as $key3 => $historic) {
                            if ($historic->getQuizId()->getName() == $quiz->getName()) {
                                unset($list[$quiz->getName() . '-' .  $user->getName()]);
                            }
                        }
                    }
                }
            }
            foreach ($list as $user_name) {
                $user = $this->getDoctrine()->getRepository(User::class)->findByName($user_name)[0];
                $items[$user->getEmail()] = $user_name;
            }
        }

        return $this->render('admin/send_email.html.twig', [
            'target' => $target,
            'value0' => $value0,
            'items' => $items,
            'path' => 'send_email3',
            'form' => '_form_mail',
        ]);
    }

    /**
     * 
     * @Route("/send_email3", name="send_email3", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')", message="Only Admins can access this feature. Sorry :-(")
     */
    public function emailFilter3(Request $request, MailerInterface $mailer)
    {
        //approve email and send
        if ($request->request->all() == [] || $request->request->all()['filter'] == "") {
            $this->addFlash('danger', 'You need to choose a filter');
            return $this->redirectToRoute('admin_index');
        }

        // dd($request->request);
        $email = $request->request->all()['filter'];
        $object = $request->request->all()['object'];
        $content = $request->request->all()['content'];

        $user = $this->getDoctrine()->getRepository(User::class)->findByEmail($email)[0];
        $name = $user->getName();
        $options = [
            'template' => 'admin_send_email',
            'from' => $this->getUser()->getEmail(),
            'object' => $object,
            'context' => [
                'content' => $content,
            ]
        ];

        MailerController::sendEmail($mailer, $user, $options);

        $this->addFlash('success', 'Email has been sent succesfully to ' . $email);
        return $this->redirectToRoute('admin_index');
    }
}
