<?php

namespace AppBundle\Services;

use AppBundle\Services\EmailInterface;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Psr\Container\ContainerInterface;

class EmailHandler implements EmailInterface {
  private $mailer;
  private $renderEngine;
  private $parameters;

  /**
  * @param \Swift_Mailer $mailer An instance of Swift Mailer.
  * @param EngineInterface $renderEngine An instance of an EngineInterface implementing object.
  * @param ContainerInterface $container An instance of an ContainerInterface implementing object.
  */
  public function __construct(\Swift_Mailer $mailer, EngineInterface $renderEngine,
  ContainerInterface $container) {
    $this->mailer = $mailer;
    $this->renderEngine = $renderEngine;

    $this->parameters = $container->getParameter('EmailHandler');
  }

  /** {@inheritDoc} */
  public function sendEmail(string $type, string $subject, string $from,
  array $to, $content, string $contentType): bool {
    $message = $this->mailer->createMessage($type);

    $message->setSubject($subject)
      ->setFrom($from)
      ->setTo($to)
      ->setBody($content, $contentType);
    
    return((bool)$this->mailer->send($message));
  }

  /** {@inheritDoc} */
  public function activationEmail(string $userEmail, string $token): bool {
    return($this->sendEmail(
      'message',
      "{$this->parameters['websiteName']}: Account Activation",
      $this->parameters['from'],
      [$userEmail],
      $this->renderEngine->renderResponse(
        'AppBundle:Emails:activation.html.twig',
        [
          'email' => $userEmail, 'token' => $token,
          'websiteName' => $this->parameters['websiteName'],
        ]
      ),
      'text/html'
    ));
  }

  /** {@inheritDoc} */
  public function pwResetEmail(string $userEmail, string $token): bool {
    return($this->sendEmail(
      'message',
      "{$this->parameters['websiteName']}: Password Reset",
      $this->parameters['from'],
      [$userEmail],
      $this->renderEngine->renderResponse(
        'AppBundle:Emails:pwReset.html.twig',
        [
          'email' => $userEmail, 'token' => $token,
          'websiteName' => $this->parameters['websiteName'],
          'fromAddress' => $this->parameters['from'],
        ]
      ),
      'text/html'
    ));
  }
}