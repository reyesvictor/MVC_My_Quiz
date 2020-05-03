<?php

namespace App\Controller;

use DateTime;
use App\Entity\Quiz;
use App\Entity\Role;
use App\Entity\User;
use App\Form\UserType;
use App\Form\AdminType;
use App\Form\UserEditType;
use App\Entity\UpdatePassword;
use App\Form\UserRegisterType;
use App\Form\AdminEditUserType;
use App\Form\UpdatePasswordType;
use App\Repository\UserRepository;
use App\Controller\MailerController;
use Symfony\Component\Form\FormError;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    private $users_modify_email_only = ['admin', 'deleteduser', 'anonymous'];
    // Le login se trouve dans SecurityController 
    // Le register se trouve dans HomeController     

    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(UserRepository $userRepository, Request $request): Response
    {
        HomeController::countVisitors($request, $this->getUser());
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="user_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')", message="Only Admins can access this feature. Sorry :-(")
     */
    public function new(Request $request, ObjectManager $manager, UserPasswordEncoderInterface $encoder, MailerInterface $mailer): Response
    {
        $user = new User();
        $form = $this->createForm(UserRegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $password_hashed = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password_hashed);
            $manager->persist($user);
            $manager->flush();
            $this->addFlash('success', "The user {$user->getName()} has been created");

            if ($user->getIsAdmin()) { //si l'user est admin lui ajouter les droits
                $this->addAdminRole($user, $manager);
            }
            //if email of user is marked as verified by the admin, it will not send a message
            if (!$user->getEmailIsVerified()) {
                MailerController::sendEmail($mailer, $user);
                $this->addFlash('info', "An email has been sent to you. Please confirm your mail to log in.");
            } else {
                $this->addFlash('info', "User with email '{$user->getEmail()}' is confirmed.");
            }
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
     * @Security("is_granted('ROLE_USER')", message="You can't modify this user information !")
     */
    public function edit(Request $request, User $user, UserPasswordEncoderInterface $encoder, MailerInterface $mailer): Response
    {
        //Sécurité
        if (!$this->userIsSameAsConnected($user)) {
            return $this->redirectToRoute('homepage');
        }
        $old_email = $user->getEmail();
        $old_name = $user->getName();
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $new_email = $user->getEmail();
            $new_name = $user->getName();
            if ($old_email == $new_email && $old_name == $new_name) {
                $this->addFlash('warning', 'There are no changes');
                return $this->redirect($request->getUri());
            }

            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'User information has been updated.');
            $em = $this->getDoctrine()->getManager();

            if ($old_email != $new_email) { //si email differente envoyer mail confirmation
                MailerController::sendEmail($mailer, $user);
                $user->setEmailIsVerified(0);
                $em->persist($user);
                $em->flush();
                if ($this->getUser() === $user) {
                    $this->addFlash('info', "An email has been sent to you. Please confirm your mail to log in again.");
                    $session = new Session();
                    $session->getFlashbag()->add('warning', 'Check your mails. You need to verify your email to log in.');
                    return $this->redirectToRoute('app_logout');
                } else {
                    $this->addFlash('info', "An email has been sent to the user {$user->getEmail()}. He needs to confirm his mail to log in.");
                }
            }
            return $this->redirect($request->getUri());
        }

        return $this->render("user/edit.html.twig", [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit_special_admin", name="edit_special_admin", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')", message="You can't access this feature it is only for admins !")
     */
    public function edit_special_admin(Request $request, User $user, UserPasswordEncoderInterface $encoder, MailerInterface $mailer): Response
    {
        $page = 'user/edit_admin';
        $form = $this->createForm(AdminType::class, $user);
        $old_email = $user->getEmail();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // dd($old_email, $user->getEmail());   
            $new_email = $user->getEmail();
            if ($old_email == $new_email) {
                $this->addFlash('warning', 'There are no changes for this user');
            } else {
                MailerController::sendEmail($mailer, $user);
                $user->setEmailIsVerified(0);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                if ($this->getUser() === $user) {
                    $this->addFlash('info', "An email has been sent to you. Please confirm your mail to log in again.");
                    $session = new Session();
                    $session->getFlashbag()->add('warning', 'Check your mails. You need to verify your email to log in.');
                    return $this->redirectToRoute('app_logout');
                } else {
                    $this->addFlash('info', "An email has been sent to the user {$user->getEmail()}. He needs to confirm his mail to log in.");
                }
            }
            return $this->redirect($request->getUri());
        }
        return $this->render("$page.html.twig", [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit_as_admin", name="admin_edit_user", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')", message="You can't access this feature it is only for admins !")
     */
    public function edit_as_admin(Request $request, User $user, UserPasswordEncoderInterface $encoder, MailerInterface $mailer): Response
    {
        $form = $this->createForm(AdminEditUserType::class, $user);
        $old_email = $user->getEmail();
        $old_name = $user->getName();
        $old_isverified = $user->getEmailIsVerified();
        $old_isadmin = $user->getIsAdmin();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $new_email = $user->getEmail();
            $new_name = $user->getName();
            $new_isverified = $user->getEmailIsVerified();
            $new_isadmin = $user->getIsAdmin();
            $main_modification = false;

            if ($old_email == $new_email && $old_name == $new_name && $old_isverified == $new_isverified && $old_isadmin == $new_isadmin) {
                $this->addFlash('warning', 'There are no changes');
                return $this->redirect($request->getUri());
            }

            // dd($old_email, $new_email, $old_name, $new_name, $old_isverified, $new_isverified, $old_isadmin, $new_isadmin);
            // $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'User information has been updated.');
            $em = $this->getDoctrine()->getManager();
            $old_user = $this->getDoctrine()->getRepository(User::class)->findById($user->getId())[0];

            // $em->flush();
            if ($old_email != $new_email) {
                // $user->setEmailVerifiedAt(NULL);
                // $user->setEmailIsVerified(0);
                //envoi email
                MailerController::sendEmail($mailer, $user);
                // $em = $this->getDoctrine()->getManager();
                // $em->persist($user);
                if ($this->getUser() === $user) {
                    $main_modification = true;
                } else {
                    $this->addFlash('info', "An email has been sent to the user {$user->getEmail()}. He needs to confirm his mail to log in.");
                }
            }


            if ($old_isverified != $new_isverified && $new_isverified == true) {
                $user->setEmailVerifiedAt(new \DateTime('now'));
                // $user->setEmailIsVerified(1);
                // $em->persist($user);
                // $em->flush();
            } else if ($old_isverified != $new_isverified && $new_isverified == false) {
                $user->setEmailVerifiedAt(NULL);
                // $user->setEmailIsVerified(0);
                //envoi email
                MailerController::sendEmail($mailer, $user);
                // $em = $this->getDoctrine()->getManager();
                // $em->persist($user);
                if ($this->getUser()->getId() === $user->getId()) {
                    $this->addFlash('info', "An email has been sent to you. Please confirm your mail to log in again.");
                    $session = new Session();
                    $session->getFlashbag()->add('warning', 'Check your mails. You need to verify your email to log in.');
                    $main_modification = true;
                } else {
                    $this->addFlash('info', "An email has been sent to the user {$user->getEmail()}. He needs to confirm his mail to log in.");
                }
                // $em->persist($user);
            }

            if ($old_isadmin != $new_isadmin && $new_isadmin == true) { // si cette valeur a été modifiée
                $this->addAdminRole($user, $em);
                if ($this->getUser() === $user) {
                    $main_modification = true;
                }
            } else if ($old_isadmin != $new_isadmin && $new_isadmin == false) { //sinon enlever les droits
                $this->removeAdminRole($user, $em);
                if ($this->getUser() === $user) {
                    $main_modification = true;
                }
            }

            $em->flush();
            if ($main_modification) {
                $this->addFlash('success', 'You need to log again to see the changes.');
                return $this->redirectToRoute('app_logout');
            }

            // if (isset($request->request->get('user')['email_is_verified'])) {
            //     $user->setEmailVerifiedAt(new \DateTime('now'));
            //     $em->persist($user);
            //     $em->flush();
            // } else if ($user->getEmailIsVerified() == false) { //erase time of verification if email is not verified (changed by Admin)
            //     // dd('test');
            //     MailerController::sendEmail($mailer, $user);
            //     $this->addFlash('info', "An email has been sent to the user {$user->getEmail()}. He needs to confirm his email to log in.");
            //     $marker = false;
            //     $user->setEmailVerifiedAt();
            //     $em->persist($user);
            //     $em->flush();
            // }

            // //si cest ladmin
            // if ($old_user->getIsAdmin() !== $user->getIsAdmin()) { // si cette valeur a été modifiée
            //     if ($user->getIsAdmin()) { // et si l'user est admin lui ajouter les droits
            //         $this->addAdminRole($user, $em);
            //         if ($this->getUser() === $user) {
            //             $this->addFlash('success', 'You need to log again to see the changes.');
            //             return $this->redirectToRoute('logout');
            //         }
            //     } else { //sinon enlever les droits
            //         $this->removeAdminRole($user, $em);
            //         if ($this->getUser() === $user) {
            //             $this->addFlash('success', 'You need to log again to see the changes.');
            //             return $this->redirectToRoute('app_logout');
            //         }
            //     }
            // }

            // dd($old_email, $new_email, $this->getUser() === $user);

            // if ($old_email != $new_email) { //si email differente envoyer mail confirmation
            //     MailerController::sendEmail($mailer, $user);
            //     $user->setEmailIsVerified(0);
            //     $em->persist($user);
            //     $em->flush();
            //     if ($this->getUser() === $user) {
            //         $this->addFlash('info', "An email has been sent to you. Please confirm your mail to log in again.");
            //         return $this->redirectToRoute('app_logout');
            //     } else {
            //         $this->addFlash('info', "An email has been sent to the user {$user->getEmail()}. He needs to confirm his mail to log in.");
            //     }
            // }
            return $this->redirect($request->getUri());
        }

        return $this->render("admin/edit_user.html.twig", [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    private function addAdminRole(User $user, $em)
    {
        $admin_role = $this->getDoctrine()->getRepository(Role::class)->findByTitle('ROLE_ADMIN')[0];
        $user->addUserRoles($admin_role);
        $em->persist($user);
        $em->flush();
        $this->addFlash('success', "The user {$user->getName()} has been granted the Admin Role. What an honor !");
    }

    private function removeAdminRole(User $user, $em)
    {
        $admin_role = $this->getDoctrine()->getRepository(Role::class)->findByTitle('ROLE_ADMIN')[0];
        $user->removeUserRoles($admin_role);
        $em->persist($user);
        $em->flush();
        $this->addFlash('warning', "The user {$user->getName()} has been destituted of the Admin Role.");
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     * @Security("is_granted('ROLE_USER')", message="You can't delete this user information !") 
     */
    public function delete(Request $request, User $user): Response
    {
        if (!$this->userIsSameAsConnected($user)) {
            return $this->redirectToRoute('homepage');
        }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $deletedUser = $this->getDoctrine()->getRepository(User::class)->findByName('deletedUser')[0];
            if (in_array(strtolower($user->getName()), $this->users_modify_email_only)) {
                $this->addFlash('danger', "This user can't be deleted, or it will break the website database structure.");
                return $this->redirectToRoute('user_show', [
                    'id' => $user->getId(),
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

    /**
     * @Route("/{id}/update-password", name="user_update_password", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_USER')", message="You can't access and modify this user information !")
     */
    public function updatePassword(User $user, Request $request, UserPasswordEncoderInterface $encoder, ObjectManager $manager): Response
    {

        $pwdUpdt = new UpdatePassword();
        $form = $this->createForm(UpdatePasswordType::class, $pwdUpdt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // dd($pwdUpdt, $user->getPassword(), password_verify($pwdUpdt->getOldPassword(), $user->getPassword()));
            if (!password_verify($pwdUpdt->getOldPassword(), $user->getPassword())) {
                $form->get('oldPassword')->addError(new FormError('Your password is not valid.'));
            } else {
                $new = $pwdUpdt->getNewPassword();
                $pwd_hashed = $encoder->encodePassword($user, $new);
                $user->setPassword($pwd_hashed);
                $manager->persist($user);
                $manager->flush();
                $this->addFlash('success', 'Password has been updated.');
            }
        }
        return !$this->userIsSameAsConnected($user) ? $this->redirectToRoute('homepage') : $this->render('user/password_update.html.twig', [
            'form' => $form->createView(),
        ]);;

        // return $this->render('user/password_update.html.twig', [
        //     'form' => $form->createView(),
        // ]);
        // return $this->redirectToRoute('user_index');
    }

    /**
     * Activated on login by LoginFormAuthenticator method onAuthenticationSuccess()
     * 
     * @Route("updateLastConnectedAt", name="user_updateLastConnectedAt", methods={"GET", "POST"})
     *
     */
    public function updateLastVisitedAt(Request $request): Response
    {
        if (array_reverse(explode('/', $request->headers->get('referer')))[0] == 'login') {
            if ($this->getUser() !== null) {
                $user = $this->getUser();
                $user->setLastConnectedAt();
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($user);
                $manager->flush();
            }
            $this->addFlash('success', 'You are logged in. Yeah !');
        } else {
            $this->addFlash('danger', "You can't access this feature on your own.");
        }
        return $this->redirectToRoute('homepage');
    }

    private function userIsSameAsConnected(User $user)
    {
        if ($user == $this->getUser() || in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return true;
        }
        $this->addFlash("warning", "You can't access another user info. No hacking allowed here !");
    }
}
