<?php

namespace tests\unit\AppBundle\Services;

use AppBundle\Services\EmailHandler;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;

class EmailHandlerTest extends TestCase {
  protected function setUp() {
    parent::setUp();

    $this->mailerMock = $this->createMock(\Swift_Mailer::class);
    $this->engineInterfaceMock = $this->createMock(EngineInterface::class);
    $this->containerMock = $this->createMock(CustomEmailContainer::class);
    $this->messageMock = $this->createMock(\Swift_Message::class);
    $this->responseMock = $this->createMock(Response::class);
  }

  public function testConstructIfTheEmailhandlerParameterDoesntExistItShouldLetTheExceptionBubbleUp() {
    $this->expectException(\Exception::class);

    $from = 'test@test.com';
    $websiteName = 'my website name';

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('EmailHandler')
      ->will($this->throwException(new \Exception));
    
    $emailHandler = new EmailHandler($this->mailerMock, $this->engineInterfaceMock, $this->containerMock);
    $emailHandler->activationEmail($expectedUserEmail, $expectedToken);
  }

  public function testSendemailItShouldCreateAMessageThenSetTheSubjectTheFromTheToAndTheBodyThenSendTheEmailAndReturnTrue() {
    $type = 'message';
    $subject = 'test subject';
    $from = 'from@from.com';
    $to = ['to@to.com', 'otherto@otherto.com'];
    $content = 'this is the email content';
    $contentType = 'text/html';

    $this->mailerMock->expects($this->once())
      ->method('createMessage')
      ->with($type)
      ->willReturn($this->messageMock);
    
    $this->messageMock->expects($this->once())
      ->method('setSubject')
      ->with($subject)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setFrom')
      ->with($from)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setTo')
      ->with($to)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setBody')
      ->with($content, $contentType)
      ->will($this->returnSelf());
    
    $this->mailerMock->expects($this->once())
      ->method('send')
      ->with($this->messageMock)
      ->willReturn(1);

    $emailHandler = new EmailHandler($this->mailerMock, $this->engineInterfaceMock, $this->containerMock);
    $actualValue = $emailHandler->sendEmail($type, $subject, $from, $to, $content, $contentType);

    $this->assertEquals(true, $actualValue);
  }

  public function testSendemailIfSendingTheEmailFailsItShouldReturnFalse() {
    $type = 'message';
    $subject = 'test subject';
    $from = 'from@from.com';
    $to = ['to@to.com', 'otherto@otherto.com'];
    $content = 'this is the email content';
    $contentType = 'text/html';

    $this->mailerMock->expects($this->once())
      ->method('createMessage')
      ->with($type)
      ->willReturn($this->messageMock);
    
    $this->messageMock->expects($this->once())
      ->method('setSubject')
      ->with($subject)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setFrom')
      ->with($from)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setTo')
      ->with($to)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setBody')
      ->with($content, $contentType)
      ->will($this->returnSelf());
    
    $this->mailerMock->expects($this->once())
      ->method('send')
      ->with($this->messageMock)
      ->willReturn(0);

    $emailHandler = new EmailHandler($this->mailerMock, $this->engineInterfaceMock, $this->containerMock);
    $actualValue = $emailHandler->sendEmail($type, $subject, $from, $to, $content, $contentType);

    $this->assertEquals(false, $actualValue);
  }

  public function testSendemailIfCreatemessageThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('createMessage test exception msg');

    $type = 'message';
    $subject = 'test subject';
    $from = 'from@from.com';
    $to = ['to@to.com', 'otherto@otherto.com'];
    $content = 'this is the email content';
    $contentType = 'text/html';

    $this->mailerMock->expects($this->once())
      ->method('createMessage')
      ->with($type)
      ->will($this->throwException(new \Exception('createMessage test exception msg')));

    $emailHandler = new EmailHandler($this->mailerMock, $this->engineInterfaceMock, $this->containerMock);
    $emailHandler->sendEmail($type, $subject, $from, $to, $content, $contentType);
  }

  public function testSendemailIfSetsubjectThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('setSubject test exception msg');

    $type = 'message';
    $subject = 'test subject';
    $from = 'from@from.com';
    $to = ['to@to.com', 'otherto@otherto.com'];
    $content = 'this is the email content';
    $contentType = 'text/html';

    $this->mailerMock->expects($this->once())
      ->method('createMessage')
      ->with($type)
      ->willReturn($this->messageMock);
    
    $this->messageMock->expects($this->once())
      ->method('setSubject')
      ->with($subject)
      ->will($this->throwException(new \Exception('setSubject test exception msg')));

    $emailHandler = new EmailHandler($this->mailerMock, $this->engineInterfaceMock, $this->containerMock);
    $emailHandler->sendEmail($type, $subject, $from, $to, $content, $contentType);
  }

  public function testSendemailIfSetfromThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('setFrom test exception msg');
    
    $type = 'message';
    $subject = 'test subject';
    $from = 'from@from.com';
    $to = ['to@to.com', 'otherto@otherto.com'];
    $content = 'this is the email content';
    $contentType = 'text/html';

    $this->mailerMock->expects($this->once())
      ->method('createMessage')
      ->with($type)
      ->willReturn($this->messageMock);
    
    $this->messageMock->expects($this->once())
      ->method('setSubject')
      ->with($subject)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setFrom')
      ->with($from)
      ->will($this->throwException(new \Exception('setFrom test exception msg')));

    $emailHandler = new EmailHandler($this->mailerMock, $this->engineInterfaceMock, $this->containerMock);
    $emailHandler->sendEmail($type, $subject, $from, $to, $content, $contentType);
  }

  public function testSendemailIfSettoThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('setTo test exception msg');
    
    $type = 'message';
    $subject = 'test subject';
    $from = 'from@from.com';
    $to = ['to@to.com', 'otherto@otherto.com'];
    $content = 'this is the email content';
    $contentType = 'text/html';

    $this->mailerMock->expects($this->once())
      ->method('createMessage')
      ->with($type)
      ->willReturn($this->messageMock);
    
    $this->messageMock->expects($this->once())
      ->method('setSubject')
      ->with($subject)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setFrom')
      ->with($from)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setTo')
      ->with($to)
      ->will($this->throwException(new \Exception('setTo test exception msg')));

    $emailHandler = new EmailHandler($this->mailerMock, $this->engineInterfaceMock, $this->containerMock);
    $emailHandler->sendEmail($type, $subject, $from, $to, $content, $contentType);
  }

  public function testSendemailIfSetbodyThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('setBody test exception msg');
    
    $type = 'message';
    $subject = 'test subject';
    $from = 'from@from.com';
    $to = ['to@to.com', 'otherto@otherto.com'];
    $content = 'this is the email content';
    $contentType = 'text/html';

    $this->mailerMock->expects($this->once())
      ->method('createMessage')
      ->with($type)
      ->willReturn($this->messageMock);
    
    $this->messageMock->expects($this->once())
      ->method('setSubject')
      ->with($subject)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setFrom')
      ->with($from)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setTo')
      ->with($to)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setBody')
      ->with($content, $contentType)
      ->will($this->throwException(new \Exception('setBody test exception msg')));

    $emailHandler = new EmailHandler($this->mailerMock, $this->engineInterfaceMock, $this->containerMock);
    $emailHandler->sendEmail($type, $subject, $from, $to, $content, $contentType);
  }

  public function testSendemailIfSendThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    
    $type = 'message';
    $subject = 'test subject';
    $from = 'from@from.com';
    $to = ['to@to.com', 'otherto@otherto.com'];
    $content = 'this is the email content';
    $contentType = 'text/html';

    $this->mailerMock->expects($this->once())
      ->method('createMessage')
      ->with($type)
      ->willReturn($this->messageMock);
    
    $this->messageMock->expects($this->once())
      ->method('setSubject')
      ->with($subject)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setFrom')
      ->with($from)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setTo')
      ->with($to)
      ->will($this->returnSelf());
    
    $this->messageMock->expects($this->once())
      ->method('setBody')
      ->with($content, $contentType)
      ->will($this->returnSelf());
    
    $this->mailerMock->expects($this->once())
      ->method('send')
      ->with($this->messageMock)
      ->will($this->throwException(new \Exception));

    $emailHandler = new EmailHandler($this->mailerMock, $this->engineInterfaceMock, $this->containerMock);
    $emailHandler->sendEmail($type, $subject, $from, $to, $content, $contentType);
  }

  public function testActivationemailItShouldRenderAResponseUsingActivationTwigFileThenCallSendemailAndReturnTrue() {
    $from = 'test@test.com';
    $websiteName = 'my website name';
    $expectedUserEmail = 'test@test.com';
    $expectedToken = 'testactivationtoken';

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('EmailHandler')
      ->willReturn([
        'from' => $from,
        'websiteName' => $websiteName,
      ]);

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Emails:activation.html.twig',
        ['email' => $expectedUserEmail, 'token' => $expectedToken, 'websiteName' => $websiteName]
      )
      ->willReturn($this->responseMock);
    
    $emailHandlerMock = $this->getMockBuilder(EmailHandler::class)
      ->setConstructorArgs([$this->mailerMock, $this->engineInterfaceMock, $this->containerMock])
      ->setMethods(['sendEmail'])
      ->getMock();
    
    $emailHandlerMock->expects($this->once())
      ->method('sendEmail')
      ->with(
        'message',
        "${websiteName}: Account Activation",
        $from,
        [$expectedUserEmail],
        $this->responseMock,
        'text/html'
      )
      ->willReturn(true);
    
    $actualValue = $emailHandlerMock->activationEmail($expectedUserEmail, $expectedToken);

    $this->assertEquals(true, $actualValue);
  }

  public function testActivationemailIfSendingTheEmailFailsItShouldReturnFalse() {
    $from = 'test@test.com';
    $websiteName = 'my website name';
    $expectedUserEmail = 'test@test.com';
    $expectedToken = 'testactivationtoken';

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('EmailHandler')
      ->willReturn([
        'from' => $from,
        'websiteName' => $websiteName,
      ]);

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Emails:activation.html.twig',
        ['email' => $expectedUserEmail, 'token' => $expectedToken, 'websiteName' => $websiteName]
      )
      ->willReturn($this->responseMock);
    
    $emailHandlerMock = $this->getMockBuilder(EmailHandler::class)
      ->setConstructorArgs([$this->mailerMock, $this->engineInterfaceMock, $this->containerMock])
      ->setMethods(['sendEmail'])
      ->getMock();
    
    $emailHandlerMock->expects($this->once())
      ->method('sendEmail')
      ->with(
        'message',
        "${websiteName}: Account Activation",
        $from,
        [$expectedUserEmail],
        $this->responseMock,
        'text/html'
      )
      ->willReturn(false);
    
    $actualValue = $emailHandlerMock->activationEmail($expectedUserEmail, $expectedToken);

    $this->assertEquals(false, $actualValue);
  }

  public function testActivationemailIfRenderResponseThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('renderResponse test exception msg');

    $from = 'test@test.com';
    $websiteName = 'my website name';
    $expectedUserEmail = 'test@test.com';
    $expectedToken = 'testactivationtoken';

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('EmailHandler')
      ->willReturn([
        'from' => $from,
        'websiteName' => $websiteName,
      ]);

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Emails:activation.html.twig',
        ['email' => $expectedUserEmail, 'token' => $expectedToken, 'websiteName' => $websiteName]
      )
      ->will($this->throwException(new \Exception('renderResponse test exception msg')));
    
    $emailHandler = new EmailHandler($this->mailerMock, $this->engineInterfaceMock, $this->containerMock);
    $emailHandler->activationEmail($expectedUserEmail, $expectedToken);
  }

  public function testPwresetemailItShouldRenderAResponseUsingPwresetTwigFileThenCallSendemailAndReturnTrue() {
    $from = 'test@test.com';
    $websiteName = 'my website name';
    $expectedUserEmail = 'test@test.com';
    $expectedToken = 'testactivationtoken';

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('EmailHandler')
      ->willReturn([
        'from' => $from,
        'websiteName' => $websiteName,
      ]);

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Emails:pwReset.html.twig',
        [
          'email' => $expectedUserEmail, 'token' => $expectedToken,
          'websiteName' => $websiteName, 'fromAddress' => $from
        ]
      )
      ->willReturn($this->responseMock);
    
    $emailHandlerMock = $this->getMockBuilder(EmailHandler::class)
      ->setConstructorArgs([$this->mailerMock, $this->engineInterfaceMock, $this->containerMock])
      ->setMethods(['sendEmail'])
      ->getMock();
    
    $emailHandlerMock->expects($this->once())
      ->method('sendEmail')
      ->with(
        'message',
        "${websiteName}: Password Reset",
        $from,
        [$expectedUserEmail],
        $this->responseMock,
        'text/html'
      )
      ->willReturn(true);
    
    $actualValue = $emailHandlerMock->pwResetEmail($expectedUserEmail, $expectedToken);

    $this->assertEquals(true, $actualValue);
  }

  public function testPwresetemailIfSendingTheEmailFailsItShouldReturnFalse() {
    $from = 'test@test.com';
    $websiteName = 'my website name';
    $expectedUserEmail = 'test@test.com';
    $expectedToken = 'testactivationtoken';

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('EmailHandler')
      ->willReturn([
        'from' => $from,
        'websiteName' => $websiteName,
      ]);

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Emails:pwReset.html.twig',
        [
          'email' => $expectedUserEmail, 'token' => $expectedToken,
          'websiteName' => $websiteName, 'fromAddress' => $from
        ]
      )
      ->willReturn($this->responseMock);
    
    $emailHandlerMock = $this->getMockBuilder(EmailHandler::class)
      ->setConstructorArgs([$this->mailerMock, $this->engineInterfaceMock, $this->containerMock])
      ->setMethods(['sendEmail'])
      ->getMock();
    
    $emailHandlerMock->expects($this->once())
      ->method('sendEmail')
      ->with(
        'message',
        "${websiteName}: Password Reset",
        $from,
        [$expectedUserEmail],
        $this->responseMock,
        'text/html'
      )
      ->willReturn(false);
    
    $actualValue = $emailHandlerMock->pwResetEmail($expectedUserEmail, $expectedToken);

    $this->assertEquals(false, $actualValue);
  }

  public function testPwresetemailIfRenderResponseThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('renderResponse test exception msg');

    $from = 'test@test.com';
    $websiteName = 'my website name';
    $expectedUserEmail = 'test@test.com';
    $expectedToken = 'testactivationtoken';

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('EmailHandler')
      ->willReturn([
        'from' => $from,
        'websiteName' => $websiteName,
      ]);

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Emails:pwReset.html.twig',
        [
          'email' => $expectedUserEmail, 'token' => $expectedToken,
          'websiteName' => $websiteName, 'fromAddress' => $from
        ]
      )
      ->will($this->throwException(new \Exception('renderResponse test exception msg')));
    
    $emailHandler = new EmailHandler($this->mailerMock, $this->engineInterfaceMock, $this->containerMock);
    $emailHandler->pwResetEmail($expectedUserEmail, $expectedToken);
  }
}

Class CustomEmailContainer implements \Psr\Container\ContainerInterface {
  public function getParameter(string $name) {}
  public function get($id) {}
  public function has($id) {}
}