<?php

namespace tests\integration\AppBundle\Services;

require_once(dirname(dirname(__DIR__)).'/BaseIntegrationCase.php');

use tests\integration\BaseIntegrationCase;
use AppBundle\Services\EmailHandler;

class EmailHandlerTest extends BaseIntegrationCase {
  private $fixtures = [];
  private $websiteName = 'YOUR WEBSITE NAME';
  private $emailFrom = 'your@email.com';

  public function getDataSet() {
    return($this->createArrayDataSet($this->fixtures));
  }

  protected function setUp() {
    parent::setUp();

    $client = static::createClient();
    $container = $client->getContainer();

    $this->emailHandler = new EmailHandler(
      $this->createCustomMailer(),
      new \Symfony\Bundle\TwigBundle\TwigEngine(
        $container->get('service_container')->get('twig'),
        new \Symfony\Component\Templating\TemplateNameParser(),
        new \Symfony\Component\Config\FileLocator()
      ),
      $container
    );
  }

  public function testSendemailIsCommunicatingCorrectlyWithSwiftmailer() {
    $actualValue = $this->emailHandler->sendEmail(
      'message',
      'test subject',
      'sender@email.com',
      ['destination1@email.com', 'destination2@email.com'],
      '<p>test email body\'s content</p>',
      'text/html'
    );

    $this->assertEquals(true, $actualValue);

    $emails = $this->getEmails();
    $this->assertEquals(1, count($emails));
    $this->assertEquals('test subject', $emails[0]->getSubject());
    $this->assertEquals('sender@email.com', key($emails[0]->getFrom()));
    $this->assertEquals(['destination1@email.com', 'destination2@email.com'], array_keys($emails[0]->getTo()));
    $this->assertEquals('<p>test email body\'s content</p>', $emails[0]->getBody());
    $this->assertEquals('text/html', $emails[0]->getContentType());
  }

  public function testActivationemailIsCommunicatingCorrectlyWithSwiftmailer() {
    $actualValue = $this->emailHandler->activationEmail('destination@email.com', 'activationToken');

    $this->assertEquals(true, $actualValue);

    $emails = $this->getEmails();
    $this->assertEquals(1, count($emails));
    $this->assertEquals("{$this->websiteName}: Account Activation", $emails[0]->getSubject());
    $this->assertEquals($this->emailFrom, key($emails[0]->getFrom()));
    $this->assertEquals(['destination@email.com'], array_keys($emails[0]->getTo()));
    $this->assertEquals(\Symfony\Component\HttpFoundation\Response::class, get_class($emails[0]->getBody()));
    $this->assertEquals('text/html', $emails[0]->getContentType());
  }

  public function testPwresetemailIsCommunicatingCorrectlyWithSwiftmailer() {
    $actualValue = $this->emailHandler->pwResetEmail('destination@email.com', 'activationToken');

    $this->assertEquals(true, $actualValue);

    $emails = $this->getEmails();
    $this->assertEquals(1, count($emails));
    $this->assertEquals("{$this->websiteName}: Password Reset", $emails[0]->getSubject());
    $this->assertEquals($this->emailFrom, key($emails[0]->getFrom()));
    $this->assertEquals(['destination@email.com'], array_keys($emails[0]->getTo()));
    $this->assertEquals(\Symfony\Component\HttpFoundation\Response::class, get_class($emails[0]->getBody()));
    $this->assertEquals('text/html', $emails[0]->getContentType());
  }
}