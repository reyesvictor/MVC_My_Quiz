<?php

namespace App\Controller;

use COM;
use App\Entity\User;
use App\Form\UserType;
use App\Entity\Category;
use App\Form\UserRegisterType;
use App\Controller\UserController;
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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class HomeController extends AbstractController
{

    private $security;
    private $getUser;

    public function __construct(Security $security)
    {
        // Avoid calling getUser() in the constructor: auth may not
        // be complete yet. Instead, store the entire Security object.
        $this->security = $security;
    }
    /**
     * @Route("/", name="homepage")
     */
    public function home(Request $request, TokenStorageInterface $token)
    {
        // dd($token);
        return $this->redirectToRoute('category_index');
        HomeController::countVisitors($request, $this->getUser());
        return $this->render('category/index.html.twig', [
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
        HomeController::countVisitors($request, $this->getUser());

        if ($this->security->getUser() !== null) {
            $this->addFlash('success', 'You are connected. Logout to register');
            return $this->redirectToRoute('homepage');
        }

        $user = new User();
        $form = $this->createForm(UserRegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password_hashed = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password_hashed);
            $manager->persist($user);
            $manager->flush();
            $this->addFlash('success', "You are registered. Please login.");
            $options['confirm'] = 'yes';
            MailerController::sendEmail($mailer, $user, $options);
            $this->addFlash('info', "An email has been sent to you. Please confirm your mail to log in.");
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    public static function sendEmail(User $user, $mailer)
    {
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
    }


    /**
     * Verify User
     * 
     * @Route("/verify/{id}/{vkey}", name="app_verify_user", methods={"GET","POST"})
     */
    public function verifyUser(User $user, $vkey, ObjectManager $manager)
    {
        $id = $user->getId();
        if ( $this->getUser() != null && $this->getUser()->getId() != $id) {
            $this->addFlash('danger', 'Another user is connected, you can\'t confirm the email. Logout first of this account.');
            return $this->redirectToRoute('homepage');
        }
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

    public function countVisitors(Request $request, $user)
    {
        $cache = new FilesystemAdapter();
        $name = 'visitors';
        if (!$cache->getItem($name)->isHit()) { // create if it doesnt exist
            $visitors = $cache->getItem($name);
            $visitors->set([]);
            $cache->save($visitors);
        } else {
            $visitors = $cache->getItem($name);
            $arr = $visitors->get('value');
            if (!$request->cookies->get('PHPSESSID')) {
                if (array_key_exists('Anonym', $arr)) {
                    $arr['Anonym'] = $arr['Anonym'] + 1;
                } else {
                    $arr['Anonym'] = 1;
                }
            } else {
                if ($user == null) {
                    $arr[$request->cookies->get('PHPSESSID')] = 'anonym';
                } else {
                    if (array_key_exists($request->cookies->get('PHPSESSID'), $arr)) {
                        unset($arr[$request->cookies->get('PHPSESSID')]);
                    }
                    $arr[$user->getId()] = $request->cookies->get('PHPSESSID');
                }
            }
            $visitors->set($arr);
            $cache->save($visitors);
        }
    }

}
