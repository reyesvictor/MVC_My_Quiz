<?php

// src/Controller/MailerController.php
namespace App\Controller;

use App\Entity\User;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// // src/Controller/MailerController.php
// namespace App\Controller;

// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use Symfony\Component\Mailer\MailerInterface;
// use Symfony\Component\Mime\Email;

class MailerController extends AbstractController
{

  /**
   * @Route("/email")
   * @param MailerInterface $mailer
  //  * @return Response
   */
  public static function sendEmail(MailerInterface $mailer, User $user, $options = null)
  {

    if ($options == null || isset($options['confirm']) && $options['confirm'] == 'yes') {
      $options['from'] = null;
      $options['object'] = null;
      $options['context'] = null;
      $options['template'] = null;
      $options['email'] = null;
      $options['all'] = null;
      // $options['confirm'] = null;
    }
    
    if (isset($options['confirm']) && $options['confirm'] != null && $options['confirm'] == 'yes') {
      //generate authentification key and store it in cache
      $id = $user->getId();
      $vkey = md5((new \DateTime('now'))->format('Y-m-d H:i:s') . $id);
      $cache = new FilesystemAdapter();
      $productsCount = $cache->getItem('key.verification.' . $id);
      $productsCount->set($vkey);
      $cache->save($productsCount); // ['key.verification.1' => 'encodedstring']
    }
    
    //send email to confirm user email
    $email = (new TemplatedEmail())
      ->from(($options['from'] == null ? 'admin@admin.fr' : $options['from']))
      ->subject(($options['object'] == null ? 'Thanks for signing up!' : $options['object']))
      ->htmlTemplate('mail/' . ($options['template'] == null ? 'confirm_email' : $options['template']) . '.html.twig')
      ->context(($options['context'] == null ? [
        'expiration_date' => new \DateTime('+7 days'),
        'username' => $user->getName(),
        'id' => $id,
        'vkey' => $vkey,
      ] : $options['context'])); //content


    if (!isset($options['all'])) {
      $email->to(new Address($user->getEmail()));
    } else if ($options['all'] != null && count($options['all']) > 1) {
      foreach ($options['all'] as $mel) {
        $email->addTo($mel);
      }
    }
    $mailer->send($email);
    return true;



    $email = (new TemplatedEmail())
      ->from('hello@example.com')
      // ->to('you@example.com')
      ->to(new Address('ryan@example.com'))
      //->cc('cc@example.com')
      //->bcc('bcc@example.com')
      //->replyTo('fabien@example.com')
      //->priority(Email::PRIORITY_HIGH)
      ->subject('Thanks for signing up!')
      // ->text('Sending emails is fun again!')
      // ->html('<p>See Twig integration for better HTML integration!</p>')
      ->htmlTemplate('mail/confirm_email.html.twig')
      // pass variables (name => value) to the template
      ->context([
        'expiration_date' => new \DateTime('+7 days'),
        'username' => 'foo',
      ]);

    $mailer->send($email);

    //     $email = (new Email())
    //       ->from(new NamedAddress('mailtrap@example.com', 'Mailtrap'))
    //       ->to('newuser@example.com')
    //       ->cc('mailtrapqa@example.com')
    //       ->addCc('staging@example.com')
    //       ->bcc('mailtrapdev@example.com')
    //       ->replyTo('mailtrap@example.com')
    //       ->subject('Best practices of building HTML emails')
    //       ->embed(fopen('/path/to/newlogo.png', 'r'), 'logo')
    //       ->embedFromPath('/path/to/newcover.png', 'new-cover-image')
    //       ->text('Hey! Learn the best practices of building HTML emails and play with ready-to-go templates. Mailtrap’s Guide on How to Build HTML Email is live on our blog')
    //       ->html('<html>
    // <body>
    // 		<p><br>Hey</br>
    // 		Learn the best practices of building HTML emails and play with ready-to-go templates.</p>
    // 		<p><a href="https://blog.mailtrap.io/build-html-email/">Mailtrap’s Guide on How to Build HTML Email</a> is live on our blog</p>
    // 		<img src="cid:logo"> ... <img src="cid:new-cover-image">
    // 				</body>
    // 			</html>')
    //       ->attachFromPath('/path/to/offline-guide.pdf');

    //     $mailer->send($email);
  }
}
