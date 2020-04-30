<?php

// src/Controller/MailerController.php
namespace App\Controller;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
   * @return Response
   */
  public function sendEmail(MailerInterface $mailer)
  {

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
