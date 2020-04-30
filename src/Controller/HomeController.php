<?php

namespace App\Controller;

use COM;
use App\Entity\User;
use App\Form\UserType;
use App\Entity\Category;
use App\Form\UserRegisterType;
use Symfony\Component\Mime\Address;
use App\Repository\CategoryRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Security;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
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
    public function register(Request $request, ObjectManager $manager, UserPasswordEncoderInterface $encoder, MailerInterface $mailer): Response
    {
        if ($this->security->getUser() !== null) {
            $this->addFlash('success', 'You are connected. Logout to register');
            return $this->redirectToRoute('homepage');
        }

        $user = new User();
        $form = $this->createForm(UserRegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // dd($user);
            $password_hashed = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password_hashed);
            $manager->persist($user);
            $manager->flush();
            $this->addFlash('success', "You are registered. Please login.");

            //generate authentification key and store it in cache
            $id = $user->getId();
            $vkey = md5((new \DateTime('now'))->format('Y-m-d H:i:s') . $id);
            $cache = new FilesystemAdapter();
            $productsCount = $cache->getItem('key.verification.' . $id);
            $productsCount->set($vkey);
            $cache->save($productsCount); // ['key.verification.1' => 'encodedstring']

            //send email to confirm user email
            $email = (new TemplatedEmail())
                ->from('admin@admin.fr')
                ->to(new Address($user->getEmail()))
                ->subject('Thanks for signing up!')
                ->htmlTemplate('mail/confirm_email.html.twig')
                ->context([
                    'expiration_date' => new \DateTime('+7 days'),
                    'username' => $user->getName(),
                    'id' => $id,
                    'vkey' => $vkey,
                ]);
            $mailer->send($email);
            $this->addFlash('info', "An email has been sent to you. Please confirm your mail to log in.");
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
        for ($i = 0; $i < 1000; $i++) {
            $productsCount = $cache->getItem('quiz.game.' . $i);
            if ($productsCount->isHit()) {
                $cache->deleteItem('quiz.game.' . $i);
            }
        }
        $this->addFlash('info', 'All quiz cache was deleted');
        return $this->redirectToRoute('homepage');
    }


    /**
     * Delete All verification key from From Cache. It resets if fixture sur loaded
     * 
     * @Route("/delete_cache_key", name="app_cache_delete_key", methods={"GET","POST"})
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
     * Verify User
     * 
     * @Route("/verify/{id}/{vkey}", name="app_verify_user", methods={"GET","POST"})
     */
    public function verifyUser(User $user, $vkey, ObjectManager $manager)
    {
        $id = $user->getId();
        $cache = new FilesystemAdapter();
        $productsCount = $cache->getItem('key.verification.' . $id);
        if ($productsCount->isHit()) { //if cache exists, user isnt verified
            $cache->deleteItem('key.verification.' . $id);
            $user->setEmailIsVerified(1);
            $user->setEmailVerifiedAt(new \DateTime('now'));
            $manager->persist($user);
            $manager->flush();
            $this->addFlash('success', 'Your email is now verified. You can log in !');
            return $this->redirectToRoute('app_login');
        } else if ($user->getEmailIsVerified()) {
            $this->addFlash('info', 'Your email is already verified.');
            return $this->redirectToRoute('app_login');
        } else {
            $this->addFlash('warning', 'This link is not valid.');
            return $this->redirectToRoute('app_register');
        }
    }
}
