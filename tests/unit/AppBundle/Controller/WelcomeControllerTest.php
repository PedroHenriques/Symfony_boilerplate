<?php

namespace tests\unit\AppBundle\Controller;

use AppBundle\Controller\WelcomeController;
use AppBundle\Model\{UserModel, UserModelFactory};
use AppBundle\Services\{Utils};
use AppBundle\Form\{RegisterType, LoginType, LostPwType, PwResetType};

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{Request, Response, RedirectResponse, ParameterBag};
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Form\{FormFactoryInterface, FormInterface, FormView, FormError};
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class WelcomeControllerTest extends TestCase {
  protected function setUp() {
    parent::setUp();

    $this->requestMock = $this->createMock(Request::class);
    $this->parameterBagMock = $this->createMock(ParameterBag::class);
    $this->authUtilsMock = $this->createMock(AuthenticationUtils::class);
    $this->formFactoryMock = $this->createMock(FormFactoryInterface::class);
    $this->formInterfaceMock = $this->createMock(FormInterface::class);
    $this->formViewMock = $this->createMock(FormView::class);
    $this->engineInterfaceMock = $this->createMock(EngineInterface::class);
    $this->responseMock = $this->createMock(Response::class);
    $this->routerInterfaceMock = $this->createMock(RouterInterface::class);
    $this->userProviderMock = $this->createMock(UserProviderInterface::class);
    $this->authCheckerMock = $this->createMock(AuthorizationCheckerInterface::class);
    $this->utilsMock = $this->createMock(Utils::class);
    $this->userFactoryMock = $this->createMock(UserModelFactory::class);
    $this->userMock = $this->createMock(UserModel::class);
    $this->sessionMock = $this->createMock(Session::class);
    $this->flashBagInterfaceMock = $this->createMock(FlashBagInterface::class);
  }

  public function testLoginactionItShouldCheckIfTheUserIsLoggedInThenCreateALogintypeFormThenGetTheLastAuthErrorAndUsernameAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $expectedLastUsername = 'test username';
    
    $this->authUtilsMock->expects($this->once())
      ->method('getLastUsername')
      ->with()
      ->willReturn($expectedLastUsername);
    
    $formTokenId = 'dca6fedf8ee4ef6e2ebdf05aebadd3b1c0972a37';

    $routerUrl = '/app_dev.php/login';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('login', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LoginType::class, ['uniqueId' => $expectedLastUsername], [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $expectedError = null;

    $this->authUtilsMock->expects($this->once())
      ->method('getLastAuthenticationError')
      ->with()
      ->willReturn($expectedError);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:login.html.twig',
        ['last_username' => $expectedLastUsername,
        'accountDisabled' => false,
        'userEmail' => '',
        'errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->loginAction($this->requestMock, $this->authUtilsMock, $this->userProviderMock,
      $this->engineInterfaceMock, $this->formFactoryMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLoginactionIfTheUserIsLoggedInItShouldRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(true);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebiste.com');
    
    $controller = new WelcomeController();
    $actualValue = $controller->loginAction($this->requestMock, $this->authUtilsMock, $this->userProviderMock,
      $this->engineInterfaceMock, $this->formFactoryMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testLoginactionIfTheAuthcheckerThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('isGranted test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->will($this->throwException(new \Exception('isGranted test exception msg')));
    
    $controller = new WelcomeController();
    $controller->loginAction($this->requestMock, $this->authUtilsMock, $this->userProviderMock, $this->engineInterfaceMock,
      $this->formFactoryMock, $this->routerInterfaceMock, $this->authCheckerMock);
  }

  public function testLoginactionIfTheUserIsLoggedInAndTheRouterThrowsAnExceptionInItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('generated test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(true);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->will($this->throwException(new \Exception('generated test exception msg')));
    
    $controller = new WelcomeController();
    $controller->loginAction($this->requestMock, $this->authUtilsMock, $this->userProviderMock, $this->engineInterfaceMock,
      $this->formFactoryMock, $this->routerInterfaceMock, $this->authCheckerMock);
  }

  public function testLoginactionIfAuthUtilsGetlastusernameThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->authUtilsMock->expects($this->once())
      ->method('getLastUsername')
      ->with()
      ->will($this->throwException(new \Exception('getLastUsername test exception msg')));

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:login.html.twig',
        ['last_username' => '',
        'accountDisabled' => false,
        'userEmail' => '',
        'errorMsg' => 'An error occurred while processing your login. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->loginAction($this->requestMock, $this->authUtilsMock, $this->userProviderMock,
      $this->engineInterfaceMock, $this->formFactoryMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLoginactionIfTheRouterThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $expectedLastUsername = 'test username';
    
    $this->authUtilsMock->expects($this->once())
      ->method('getLastUsername')
      ->with()
      ->willReturn($expectedLastUsername);

    $formTokenId = 'dca6fedf8ee4ef6e2ebdf05aebadd3b1c0972a37';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('login', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->will($this->throwException(new \Exception));

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:login.html.twig',
        ['last_username' => $expectedLastUsername,
        'accountDisabled' => false,
        'userEmail' =>'',
        'errorMsg' => 'An error occurred while processing your login. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->loginAction($this->requestMock, $this->authUtilsMock, $this->userProviderMock,
      $this->engineInterfaceMock, $this->formFactoryMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLoginactionIfTheFormFactoryThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $expectedLastUsername = 'test username';
    
    $this->authUtilsMock->expects($this->once())
      ->method('getLastUsername')
      ->with()
      ->willReturn($expectedLastUsername);

    $formTokenId = 'dca6fedf8ee4ef6e2ebdf05aebadd3b1c0972a37';

    $routerUrl = '/app_dev.php/login';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('login', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);
    
    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LoginType::class, ['uniqueId' => $expectedLastUsername], [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->will($this->throwException(new \Exception));

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:login.html.twig',
        ['last_username' => $expectedLastUsername,
        'accountDisabled' => false,
        'userEmail' => '',
        'errorMsg' => 'An error occurred while processing your login. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->loginAction($this->requestMock, $this->authUtilsMock, $this->userProviderMock,
      $this->engineInterfaceMock, $this->formFactoryMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLoginactionIfAuthUtilsGetlastauthenticationerrorThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $expectedLastUsername = 'test username';
    
    $this->authUtilsMock->expects($this->once())
      ->method('getLastUsername')
      ->with()
      ->willReturn($expectedLastUsername);
    
    $formTokenId = 'dca6fedf8ee4ef6e2ebdf05aebadd3b1c0972a37';

    $routerUrl = '/app_dev.php/login';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('login', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LoginType::class, ['uniqueId' => $expectedLastUsername], [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->authUtilsMock->expects($this->once())
      ->method('getLastAuthenticationError')
      ->with()
      ->will($this->throwException(new \Exception));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:login.html.twig',
        ['last_username' => $expectedLastUsername,
        'accountDisabled' => false,
        'userEmail' => '',
        'errorMsg' => 'An error occurred while processing your login. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->loginAction($this->requestMock, $this->authUtilsMock, $this->userProviderMock,
      $this->engineInterfaceMock, $this->formFactoryMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLoginactionIfTheRenderresponseThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\RuntimeException::class);

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $expectedLastUsername = 'test username';
    
    $this->authUtilsMock->expects($this->once())
      ->method('getLastUsername')
      ->with()
      ->willReturn($expectedLastUsername);

    $formTokenId = 'dca6fedf8ee4ef6e2ebdf05aebadd3b1c0972a37';

    $routerUrl = '/app_dev.php/login';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('login', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LoginType::class, ['uniqueId' => $expectedLastUsername], [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $expectedError = null;

    $this->authUtilsMock->expects($this->once())
      ->method('getLastAuthenticationError')
      ->with()
      ->willReturn($expectedError);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:login.html.twig',
        ['last_username' => $expectedLastUsername,
        'accountDisabled' => false,
        'userEmail' => '',
        'errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->will($this->throwException(new \RuntimeException));

    $controller = new WelcomeController();
    $controller->loginAction($this->requestMock, $this->authUtilsMock, $this->userProviderMock,
      $this->engineInterfaceMock, $this->formFactoryMock, $this->routerInterfaceMock, $this->authCheckerMock);
  }

  public function testLoginactionIfTheAccountHasntBeenActivatedItShouldGetTheUserEmailThenInformTheViewToAddAMessageAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $expectedLastUsername = 'test username';
    
    $this->authUtilsMock->expects($this->once())
      ->method('getLastUsername')
      ->with()
      ->willReturn($expectedLastUsername);
    
    $formTokenId = 'dca6fedf8ee4ef6e2ebdf05aebadd3b1c0972a37';

    $routerUrl = '/app_dev.php/login';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('login', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LoginType::class, ['uniqueId' => $expectedLastUsername], [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->authUtilsMock->expects($this->once())
      ->method('getLastAuthenticationError')
      ->with()
      ->willReturn(new DisabledException);
    
    $this->userProviderMock->expects($this->once())
      ->method('loadUserByUsername')
      ->with($expectedLastUsername)
      ->willReturn($this->userMock);
    
    $userEmail = 'test@test.com';

    $this->userMock->expects($this->once())
      ->method('getEmail')
      ->with()
      ->willReturn($userEmail);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:login.html.twig',
        ['last_username' => $expectedLastUsername,
        'accountDisabled' => true,
        'userEmail' => $userEmail,
        'errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->loginAction($this->requestMock, $this->authUtilsMock, $this->userProviderMock,
      $this->engineInterfaceMock, $this->formFactoryMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLoginactionIfTheAccountHasntBeenActivatedAndTheUserProviderThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $expectedLastUsername = 'test username';
    
    $this->authUtilsMock->expects($this->once())
      ->method('getLastUsername')
      ->with()
      ->willReturn($expectedLastUsername);
    
    $formTokenId = 'dca6fedf8ee4ef6e2ebdf05aebadd3b1c0972a37';

    $routerUrl = '/app_dev.php/login';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('login', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LoginType::class, ['uniqueId' => $expectedLastUsername], [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->authUtilsMock->expects($this->once())
      ->method('getLastAuthenticationError')
      ->with()
      ->willReturn(new DisabledException);
    
    $this->userProviderMock->expects($this->once())
      ->method('loadUserByUsername')
      ->with($expectedLastUsername)
      ->will($this->throwException(new \Exception));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:login.html.twig',
        ['last_username' => $expectedLastUsername,
        'accountDisabled' => true,
        'userEmail' => '',
        'errorMsg' => 'An error occurred while processing your login. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->loginAction($this->requestMock, $this->authUtilsMock, $this->userProviderMock,
      $this->engineInterfaceMock, $this->formFactoryMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testRegisteractionItShouldCheckIfTheUserIsLoggedInThenCreateARegistertypeFormThenCallHandlerequestOnItAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '689ca5c4aeacc31f7ee996be2e8eb93224420b38';
    
    $routerUrl = '/app_dev.php/register';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(RegisterType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(false);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:register.html.twig',
        ['errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testRegisteractionIfTheUserIsLoggedInItShouldRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(true);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testRegisteractionIfTheAuthcheckerThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('isGranted test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->will($this->throwException(new \Exception('isGranted test exception msg')));

    $controller = new WelcomeController();
    $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock, $this->engineInterfaceMock,
      $this->routerInterfaceMock, $this->authCheckerMock);
  }

  public function testRegisteractionIfTheUserIsLoggedInAndTheRouterThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('generate test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(true);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->will($this->throwException(new \Exception('generate test exception msg')));

    $controller = new WelcomeController();
    $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock, $this->engineInterfaceMock,
      $this->routerInterfaceMock, $this->authCheckerMock);
  }

  public function testRegisteractionIfTheUsermodelThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->will($this->throwException(new \Exception));

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:register.html.twig',
        ['errorMsg' => 'An error occurred while processing your registration. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testRegisteractionIfTheCallToRouterForTheRegisterRouteThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->will($this->throwException(new \Exception));
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:register.html.twig',
        ['errorMsg' => 'An error occurred while processing your registration. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testRegisteractionIfTheFormFactoryThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '689ca5c4aeacc31f7ee996be2e8eb93224420b38';

    $routerUrl = '/app_dev.php/register';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(RegisterType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->will($this->throwException(new \Exception));
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:register.html.twig',
        ['errorMsg' => 'An error occurred while processing your registration. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testRegisteractionIfTheFormHandlerequestThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '689ca5c4aeacc31f7ee996be2e8eb93224420b38';
    
    $routerUrl = '/app_dev.php/register';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(RegisterType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->throwException(new \Exception));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:register.html.twig',
        ['errorMsg' => 'An error occurred while processing your registration. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }
  
  public function testRegisteractionIfTheFormHasBeenSubmittedItShouldCheckThatItIsValidThenCallTheUsermodelRegisterAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '689ca5c4aeacc31f7ee996be2e8eb93224420b38';

    $routerUrl = '/app_dev.php/register';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(RegisterType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);
    
    $this->userMock->expects($this->once())
      ->method('register')
      ->with()
      ->willReturn(true);
    
    $userEmail = 'test@test.com';

    $this->userMock->expects($this->once())
      ->method('getEmail')
      ->with()
      ->willReturn($userEmail);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:registered.html.twig',
        ['emailSent' => true, 'email' => $userEmail,]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testRegisteractionIfTheUsermodelRegisterReturnsFalseItShouldRenderTheRegisteredViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '689ca5c4aeacc31f7ee996be2e8eb93224420b38';

    $routerUrl = '/app_dev.php/register';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(RegisterType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);

    $this->userMock->expects($this->once())
      ->method('register')
      ->with()
      ->willReturn(false);
    
    $userEmail = 'test@test.com';

    $this->userMock->expects($this->once())
      ->method('getEmail')
      ->with()
      ->willReturn($userEmail);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:registered.html.twig',
        ['emailSent' => false, 'email' => $userEmail,]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testRegisteractionIfTheUsermodelRegisterThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '689ca5c4aeacc31f7ee996be2e8eb93224420b38';

    $routerUrl = '/app_dev.php/register';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(RegisterType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);

    $this->userMock->expects($this->once())
      ->method('register')
      ->with()
      ->will($this->throwException(new \Exception));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:register.html.twig',
        ['errorMsg' => 'An error occurred while processing your registration. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testRegisteractionIfTheUsermodelRegisterThrowsAnExceptionRelatedToAUniqueConstraintViolationItShouldCatchItAddAnErrorToTheRelevantFormFieldAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '689ca5c4aeacc31f7ee996be2e8eb93224420b38';

    $routerUrl = '/app_dev.php/register';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(RegisterType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);

    $duplicateValue = 'test username';
    $duplicateKey = 'userName';

    $this->userMock->expects($this->once())
      ->method('register')
      ->with()
      ->will($this->throwException(new \Exception("1062 Duplicate entry '${duplicateValue}' for key '${duplicateKey}'")));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('get')
      ->with($duplicateKey)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('addError')
      ->with($this->callback(function($value) {
        return(get_class($value) === FormError::class && $value->getMessage() === 'Not available');
      }))
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:register.html.twig',
        ['errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testRegisteractionIfTheUsermodelRegisterThrowsAnExceptionRelatedToAUniqueConstraintViolationAndTheFormGetThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '689ca5c4aeacc31f7ee996be2e8eb93224420b38';

    $routerUrl = '/app_dev.php/register';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(RegisterType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);

    $duplicateValue = 'test username';
    $duplicateKey = 'userName';

    $this->userMock->expects($this->once())
      ->method('register')
      ->with()
      ->will($this->throwException(new \Exception("1062 Duplicate entry '${duplicateValue}' for key '${duplicateKey}'")));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('get')
      ->with($duplicateKey)
      ->will($this->throwException(new \Exception));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:register.html.twig',
        ['errorMsg' => 'An error occurred while processing your registration. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testRegisteractionIfTheUsermodelRegisterThrowsAnExceptionRelatedToAUniqueConstraintViolationAndTheFormAdderrorThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '689ca5c4aeacc31f7ee996be2e8eb93224420b38';

    $routerUrl = '/app_dev.php/register';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(RegisterType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);

    $duplicateValue = 'test username';
    $duplicateKey = 'userName';

    $this->userMock->expects($this->once())
      ->method('register')
      ->with()
      ->will($this->throwException(new \Exception("1062 Duplicate entry '${duplicateValue}' for key '${duplicateKey}'")));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('get')
      ->with($duplicateKey)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('addError')
      ->with($this->callback(function($value) {
        return(get_class($value) === FormError::class && $value->getMessage() === 'Not available');
      }))
      ->will($this->throwException(new \Exception));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:register.html.twig',
        ['errorMsg' => 'An error occurred while processing your registration. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testRegisteractionIfTheFormHasBeenSubmittedButIsNotValidItShouldReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '689ca5c4aeacc31f7ee996be2e8eb93224420b38';

    $routerUrl = '/app_dev.php/register';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(RegisterType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(false);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:register.html.twig',
        ['errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->engineInterfaceMock, $this->routerInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testRegisteractionIfTheFormCreateviewThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('createView test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '689ca5c4aeacc31f7ee996be2e8eb93224420b38';
    
    $routerUrl = '/app_dev.php/register';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(RegisterType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(false);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->will($this->throwException(new \Exception('createView test exception msg')));

    $controller = new WelcomeController();
    $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock, $this->engineInterfaceMock,
      $this->routerInterfaceMock, $this->authCheckerMock);
  }

  public function testRegisteractionIfRenderresponseThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '689ca5c4aeacc31f7ee996be2e8eb93224420b38';

    $routerUrl = '/app_dev.php/register';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('register', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(RegisterType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(false);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:register.html.twig',
        ['errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->will($this->throwException(new \Exception));

    $controller = new WelcomeController();
    $controller->registerAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock, $this->engineInterfaceMock,
      $this->routerInterfaceMock, $this->authCheckerMock);
  }

  public function testActivationactionItShouldCheckIfTheUserIsLoggedInThenGetTheEmailAndTokenFromTheQuerystringThenCallUsermodelActivateThenAddAFlashMsgAndRedirectToTheLoginRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $this->userMock->expects($this->once())
      ->method('activate')
      ->with($expectedToken, $this->routerInterfaceMock)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('success', 'Congratulations! Your account is now activated and you can start using this website.')
      ->willReturn(null);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('login')
      ->willReturn('www.mywebsite.com/login');
    
    $controller = new WelcomeController();
    $actualValue = $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testActivationactionIfTheUserIsLoggedInItShouldRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(true);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');
    
    $controller = new WelcomeController();
    $actualValue = $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testActivationactionIfTheAuthcheckerThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('isGranted test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->will($this->throwException(new \Exception('isGranted test exception msg')));
    
    $controller = new WelcomeController();
    $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);
  }

  public function testActivationactionIfTheUserIsLoggedInAndTheRouterThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('generate test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(true);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->will($this->throwException(new \Exception('generate test exception msg')));
    
    $controller = new WelcomeController();
    $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);
  }

  public function testActivationactionIfTheQuerystringDoesntHaveAnEmailItShouldAddAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = null;
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'It was not possible to activate your account. Please confirm the activation link is correct and try again.')
      ->willReturn(null);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');
    
    $controller = new WelcomeController();
    $actualValue = $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testActivationactionIfTheQuerystringDoesntHaveATokenItShouldAddAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = null;

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'It was not possible to activate your account. Please confirm the activation link is correct and try again.')
      ->willReturn(null);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');
    
    $controller = new WelcomeController();
    $actualValue = $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testActivationactionIfTheParameterbagThrowsAnExceptionForTheEmailItShouldCatchItAddAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $this->parameterBagMock->expects($this->once())
      ->method('get')
      ->with('e')
      ->will($this->throwException(new \Exception));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'It was not possible to activate your account. Please confirm the activation link is correct and try again.')
      ->willReturn(null);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');
    
    $controller = new WelcomeController();
    $actualValue = $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testActivationactionIfTheParameterbagThrowsAnExceptionForTheTokenItShouldCatchItAddAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $this->throwException(new \Exception)));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'It was not possible to activate your account. Please confirm the activation link is correct and try again.')
      ->willReturn(null);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');
    
    $controller = new WelcomeController();
    $actualValue = $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testActivationactionIfTheUsermodelfactoryThrowsAnExceptionItShouldCatchItThenAddTheDefaultFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->will($this->throwException(new \Exception));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'It was not possible to activate your account. Please confirm the activation link is correct and try again.')
      ->willReturn(null);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');
    
    $controller = new WelcomeController();
    $actualValue = $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testActivationactionIfTheUserModelActivateThrowsAnExceptionWithACustomMessageItShouldCatchItThenAddTheExceptionMsgAsAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('activate')
      ->with($expectedToken, $this->routerInterfaceMock)
      ->will($this->throwException(new \Exception('[error]This is a custom exception message due to the syntax format.')));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'This is a custom exception message due to the syntax format.')
      ->willReturn(null);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');
    
    $controller = new WelcomeController();
    $actualValue = $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testActivationactionIfTheUserModelActivateThrowsAnExceptionWithoutACustomMessageItShouldCatchItThenAddTheDefaultFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('activate')
      ->with($expectedToken, $this->routerInterfaceMock)
      ->will($this->throwException(new \Exception('This is a NOT a custom exception message due to the syntax format.')));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'It was not possible to activate your account. Please confirm the activation link is correct and try again.')
      ->willReturn(null);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');
    
    $controller = new WelcomeController();
    $actualValue = $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testActivationactionIfAddingTheSuccessFlashMsgThrowsAnExceptionItShouldCatchItAndRedirectToTheLoginRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('activate')
      ->with($expectedToken, $this->routerInterfaceMock)
      ->willReturn(null);

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('success', 'Congratulations! Your account is now activated and you can start using this website.')
      ->will($this->throwException(new \Exception));
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('login')
      ->willReturn('www.mywebsite.com/login');
    
    $controller = new WelcomeController();
    $actualValue = $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testActivationactionIfAddingTheErrorFlashMsgThrowsAnExceptionItShouldCatchItAndRedirectToTheHomepage() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('activate')
      ->with($expectedToken, $this->routerInterfaceMock)
      ->will($this->throwException(new \Exception));
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'It was not possible to activate your account. Please confirm the activation link is correct and try again.')
      ->will($this->throwException(new \Exception));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');
    
    $controller = new WelcomeController();
    $actualValue = $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testActivationactionIfTheRouterThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $this->userMock->expects($this->once())
      ->method('activate')
      ->with($expectedToken, $this->routerInterfaceMock)
      ->willReturn(null);

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('success', 'Congratulations! Your account is now activated and you can start using this website.')
      ->willReturn(null);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('login')
      ->will($this->throwException(new \Exception));
    
    $controller = new WelcomeController();
    $controller->activationAction($this->requestMock, $this->routerInterfaceMock, $this->sessionMock,
      $this->userFactoryMock, $this->authCheckerMock);
    
    $this->assertEquals($expectedEmail, $this->userMock->getEmail());
  }

  public function testResendactivationactionItShouldCheckIfTheUserIsLoggedInThenGetTheEmailFromTheQuerystringThenCallUserModelRegenactivationtokenThenAddAFlashMsgAndRedirectToTheHomepage() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';

    $this->parameterBagMock->expects($this->once())
      ->method('get')
      ->with('e')
      ->willReturn($expectedEmail);
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('regenActivationToken')
      ->with()
      ->willReturn(null);

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('success', 'A new activation email was sent to this account\'s email address.')
      ->willReturn(null);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');

    $controller = new WelcomeController();
    $actualValue = $controller->resendActivationAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testResendactivationactionIfTheUserIsLoggedInItShouldRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(true);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');
    
    $controller = new WelcomeController();
    $actualValue = $controller->resendActivationAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testResendactivationactionIfTheAuthcheckerThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('isGranted test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->will($this->throwException(new \Exception('isGranted test exception msg')));
    
    $controller = new WelcomeController();
    $controller->resendActivationAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock, $this->sessionMock,
      $this->authCheckerMock);
  }

  public function testResendactivationactionIfTheUserIsLoggedInAndTheRouterThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('generate test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(true);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->will($this->throwException(new \Exception('generate test exception msg')));
    
    $controller = new WelcomeController();
    $controller->resendActivationAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock, $this->sessionMock,
      $this->authCheckerMock);
  }

  public function testResendactivationactionIfNoEmailIsProvidedItShouldAddAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $this->parameterBagMock->expects($this->once())
      ->method('get')
      ->with('e')
      ->willReturn(null);

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'The new activation email could not be sent. Please try again.')
      ->willReturn(null);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');

    $controller = new WelcomeController();
    $actualValue = $controller->resendActivationAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testResendactivationactionIfTheParameterbagThrowsAnExceptionItShouldCatchItAddAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $this->parameterBagMock->expects($this->once())
      ->method('get')
      ->with('e')
      ->will($this->throwException(new \Exception));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'The new activation email could not be sent. Please try again.')
      ->willReturn(null);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');

    $controller = new WelcomeController();
    $actualValue = $controller->resendActivationAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testResendactivationactionIfTheUsermodelfactoryThrowsAnExceptionItShouldCatchItThenAddAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';

    $this->parameterBagMock->expects($this->once())
      ->method('get')
      ->with('e')
      ->willReturn($expectedEmail);
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->will($this->throwException(new \Exception));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'The new activation email could not be sent. Please try again.')
      ->willReturn(null);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');

    $controller = new WelcomeController();
    $actualValue = $controller->resendActivationAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testResendactivationactionIfTheNewActivationTokenFailsToBeProcessedAndThrowsAnExceptionItShouldAddAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';

    $this->parameterBagMock->expects($this->once())
      ->method('get')
      ->with('e')
      ->willReturn($expectedEmail);
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('regenActivationToken')
      ->with()
      ->will($this->throwException(new \Exception));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'The new activation email could not be sent. Please try again.')
      ->willReturn(null);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');

    $controller = new WelcomeController();
    $actualValue = $controller->resendActivationAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testResendactivationactionIfAddingTheSuccessFlashMsgThrowsAnExceptionItShouldCatchItAndRedirectToTheHomepage() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';

    $this->parameterBagMock->expects($this->once())
      ->method('get')
      ->with('e')
      ->willReturn($expectedEmail);
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('regenActivationToken')
      ->with()
      ->willReturn(null);

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('success', 'A new activation email was sent to this account\'s email address.')
      ->will($this->throwException(new \Exception));
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');

    $controller = new WelcomeController();
    $actualValue = $controller->resendActivationAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testResendactivationactionIfAddingTheErrorFlashMsgThrowsAnExceptionItShouldCatchItAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $this->parameterBagMock->expects($this->once())
      ->method('get')
      ->with('e')
      ->will($this->throwException(new \Exception));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'The new activation email could not be sent. Please try again.')
      ->will($this->throwException(new \Exception));
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');

    $controller = new WelcomeController();
    $actualValue = $controller->resendActivationAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testResendactivationactionIfTheRouterThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';

    $this->parameterBagMock->expects($this->once())
      ->method('get')
      ->with('e')
      ->willReturn($expectedEmail);
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('regenActivationToken')
      ->with()
      ->willReturn(null);

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('success', 'A new activation email was sent to this account\'s email address.')
      ->willReturn(null);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->will($this->throwException(new \Exception));

    $controller = new WelcomeController();
    $controller->resendActivationAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock, $this->sessionMock,
      $this->authCheckerMock);
  }

  public function testLostpasswordactionItShouldCheckIfTheUserIsLoggedInThenCreateAnEmptyUsermodelThenCreateTheLostpwtypeFormThenCallHandlerequestOnItAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '3e77ac6ee9f8733b2f56cad16fb89baa27e995e2';
    
    $routerUrl = '/app_dev.php/lostPassword';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('lostPassword', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LostPwType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(false);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:lostpw.html.twig',
        ['errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLostpasswordactionIfTheUserIsLoggedInItShouldRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(true);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');

    $controller = new WelcomeController();
    $actualValue = $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testLostpasswordactionIfTheAuthcheckerThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('isGranted test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->will($this->throwException(new \Exception('isGranted test exception msg')));

    $controller = new WelcomeController();
    $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock, $this->sessionMock,
      $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);
  }

  public function testLostpasswordactionIfTheUserIsLoggedInAndTheRouterThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('generate test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(true);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->will($this->throwException(new \Exception('generate test exception msg')));

    $controller = new WelcomeController();
    $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock, $this->sessionMock,
      $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);
  }

  public function testLostpasswordactionIfTheUsermodelfactoryThrowsAnExceptionItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->will($this->throwException(new \Exception));
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:lostpw.html.twig',
        ['errorMsg' => 'An error occurred while initiating your account\'s password reset. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLostpasswordactionIfTheRouterForLostpasswordThrowsAnExceptionForTheLostpasswordRouteItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '3e77ac6ee9f8733b2f56cad16fb89baa27e995e2';
    
    $routerUrl = '/app_dev.php/lostPassword';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('lostPassword', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LostPwType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->will($this->throwException(new \Exception));
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:lostpw.html.twig',
        ['errorMsg' => 'An error occurred while initiating your account\'s password reset. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLostpasswordactionIfTheFormFactoryThrowsAnExceptionItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '3e77ac6ee9f8733b2f56cad16fb89baa27e995e2';
    
    $routerUrl = '/app_dev.php/lostPassword';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('lostPassword', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LostPwType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->will($this->throwException(new \Exception));
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:lostpw.html.twig',
        ['errorMsg' => 'An error occurred while initiating your account\'s password reset. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLostpasswordactionIfTheFormHandlerequestThrowsAnExceptionItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '3e77ac6ee9f8733b2f56cad16fb89baa27e995e2';
    
    $routerUrl = '/app_dev.php/lostPassword';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('lostPassword', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LostPwType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->throwException(new \Exception));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:lostpw.html.twig',
        ['errorMsg' => 'An error occurred while initiating your account\'s password reset. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLostpasswordactionIfTheFormHasBeenSubmittedItShouldCheckIfTheFormIsValidThenCallUsermodelInitpwresetprocessThenAddAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '3e77ac6ee9f8733b2f56cad16fb89baa27e995e2';
    
    $lostPwRouterUrl = '/app_dev.php/lostPassword';
    $homepageRouterUrl = '/app_dev.php';

    $this->routerInterfaceMock->expects($this->exactly(2))
      ->method('generate')
      ->withConsecutive(
        ['lostPassword'],
        ['homepage']
      )
      ->will($this->onConsecutiveCalls($lostPwRouterUrl, $homepageRouterUrl));

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LostPwType::class, $this->userMock, [
        'action' => $lostPwRouterUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);
    
    $this->userMock->expects($this->once())
      ->method('initPwResetProcess')
      ->with()
      ->willReturn(null);

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('success', 'An email was sent to this account\'s email address with the link where a new password can be set.')
      ->willReturn(null);

    $controller = new WelcomeController();
    $actualValue = $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);
    
    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testLostpasswordactionIfTheFormHasBeenSubmittedButIsNotValidItShouldReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '3e77ac6ee9f8733b2f56cad16fb89baa27e995e2';
    
    $routerUrl = '/app_dev.php/lostPassword';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('lostPassword', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LostPwType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(false);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:lostpw.html.twig',
        ['errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLostpasswordactionIfUsermodelInitpwresetprocessFailsAndThrowsAnExceptionItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '3e77ac6ee9f8733b2f56cad16fb89baa27e995e2';
    
    $lostPwRouterUrl = '/app_dev.php/lostPassword';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('lostPassword', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($lostPwRouterUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LostPwType::class, $this->userMock, [
        'action' => $lostPwRouterUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);
    
    $this->userMock->expects($this->once())
      ->method('initPwResetProcess')
      ->with()
      ->will($this->throwException(new \Exception));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:lostpw.html.twig',
        ['errorMsg' => 'An error occurred while initiating your account\'s password reset. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLostpasswordactionIfAddingTheSuccessFlashMsgThrowsAnExceptionItShouldCatchItAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '3e77ac6ee9f8733b2f56cad16fb89baa27e995e2';
    
    $lostPwRouterUrl = '/app_dev.php/lostPassword';
    $homepageRouterUrl = '/app_dev.php';

    $this->routerInterfaceMock->expects($this->exactly(2))
      ->method('generate')
      ->withConsecutive(
        ['lostPassword'],
        ['homepage']
      )
      ->will($this->onConsecutiveCalls($lostPwRouterUrl, $homepageRouterUrl));

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LostPwType::class, $this->userMock, [
        'action' => $lostPwRouterUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);
    
    $this->userMock->expects($this->once())
      ->method('initPwResetProcess')
      ->with()
      ->willReturn(null);

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('success', 'An email was sent to this account\'s email address with the link where a new password can be set.')
      ->will($this->throwException(new \Exception));

    $controller = new WelcomeController();
    $actualValue = $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);
    
    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testLostpasswordactionIfTheRouterForTheHomepageThrowsAnExceptionForTheHomepageRouteItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '3e77ac6ee9f8733b2f56cad16fb89baa27e995e2';
    
    $lostPwRouterUrl = '/app_dev.php/lostPassword';

    $this->routerInterfaceMock->expects($this->exactly(2))
      ->method('generate')
      ->withConsecutive(
        ['lostPassword'],
        ['homepage']
      )
      ->will($this->onConsecutiveCalls($lostPwRouterUrl, $this->throwException(new \Exception)));

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LostPwType::class, $this->userMock, [
        'action' => $lostPwRouterUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);
    
    $this->userMock->expects($this->once())
      ->method('initPwResetProcess')
      ->with()
      ->willReturn(null);

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('success', 'An email was sent to this account\'s email address with the link where a new password can be set.')
      ->willReturn(null);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:lostpw.html.twig',
        ['errorMsg' => 'An error occurred while initiating your account\'s password reset. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock,
      $this->sessionMock, $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);
    
    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testLostpasswordactionIfFormCreateviewThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('createView test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '3e77ac6ee9f8733b2f56cad16fb89baa27e995e2';
    
    $routerUrl = '/app_dev.php/lostPassword';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('lostPassword', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LostPwType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(false);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->will($this->throwException(new \Exception('createView test exception msg')));

    $controller = new WelcomeController();
    $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock, $this->sessionMock,
      $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);
  }

  public function testLostpasswordactionIfRenderresponseThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);

    $formTokenId = '3e77ac6ee9f8733b2f56cad16fb89baa27e995e2';
    
    $routerUrl = '/app_dev.php/lostPassword';

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('lostPassword', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(LostPwType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(false);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:lostpw.html.twig',
        ['errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->will($this->throwException(new \Exception));

    $controller = new WelcomeController();
    $controller->lostPasswordAction($this->requestMock, $this->routerInterfaceMock, $this->userFactoryMock, $this->sessionMock,
      $this->formFactoryMock, $this->engineInterfaceMock, $this->authCheckerMock);
  }

  public function testPasswordresetactionItShouldCheckIfTheUserIsLoggedInThenGetTheEmailAndTokenFromTheQuerystringThenCreateThePwresetFormAndReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $routerUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('pwReset', ['e' => $expectedEmail, 't' => $expectedToken], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(false);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testPasswordresetactionIfTheUserIsLoggedInItShouldRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(true);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn('www.mywebsite.com');
    
    $this->requestMock->query = $this->parameterBagMock;
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testPasswordresetactionIfTheAuthcheckerThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('isGranted test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->will($this->throwException(new \Exception('isGranted test exception msg')));
    
    $controller = new WelcomeController();
    $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock, $this->routerInterfaceMock,
      $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);
  }

  public function testPasswordresetactionIfTheUserIsLoggedInAndTheRouterThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('generate test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(true);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->will($this->throwException(new \Exception('generate test exception msg')));
    
    $controller = new WelcomeController();
    $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock, $this->routerInterfaceMock,
      $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);
  }

  public function testPasswordresetactionIfTheFormHasBeenSubmittedItShouldCheckIfTheFormIsValidThenStoreTheEmailAndCallForThePasswordToBeResetThenAddAFlashMsgAndRedirectToTheLoginRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $pwResetRouterUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->exactly(2))
      ->method('generate')
      ->withConsecutive(
        ['pwReset', ['e' => $expectedEmail, 't' => $expectedToken]],
        ['login']
      )
      ->will($this->onConsecutiveCalls($pwResetRouterUrl, 'www.mywebsite.com/login'));

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $pwResetRouterUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);
    
    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('resetPw')
      ->with($expectedToken)
      ->WillReturn(null);

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('success', 'Congratulations! Your new password is now active.')
      ->willReturn(null);
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testPasswordresetactionIfThereIsNoEmailInTheQuerystringItShouldAddAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls(null, $expectedToken));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'The password reset link is not valid. Please confirm the link and try again.')
      ->willReturn(null);
    
    $routerUrl = "www.mywebsite.com";

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn($routerUrl);

    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testPasswordresetactionIfTheParameterbagForTheEmailThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndRenderAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';

    $this->parameterBagMock->expects($this->once())
      ->method('get')
      ->with('e')
      ->will($this->throwException(new \Exception));
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => 'An error occurred while saving your new password. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testPasswordresetactionIfThereIsNoTokenInTheQuerystringItShouldAddAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, null));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'The password reset link is not valid. Please confirm the link and try again.')
      ->willReturn(null);
    
    $routerUrl = "www.mywebsite.com";

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn($routerUrl);

    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testPasswordresetactionIfTheParameterbagForTheTokenThrowsAnExceptionItShouldCatchItAddAnErrorMsgToTheViewAndRenderAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $this->throwException(new \Exception)));
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => 'An error occurred while saving your new password. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);

    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testPasswordresetactionIfThereIsNoEmailAndTokenInTheQuerystringItShouldAddAFlashMsgAndRedirectToTheHomepageRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls(null, null));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'The password reset link is not valid. Please confirm the link and try again.')
      ->willReturn(null);
    
    $routerUrl = "www.mywebsite.com";

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('homepage')
      ->willReturn($routerUrl);

    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testPasswordresetactionIfTheUserfactoryThrowsAnExceptionItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->will($this->throwException(new \Exception));

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => 'An error occurred while saving your new password. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testPasswordresetactionIfTheRouterForPwresetThrowsAnExceptionForThePwresetRouteItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('pwReset', ['e' => $expectedEmail, 't' => $expectedToken], UrlGeneratorInterface::ABSOLUTE_URL)
      ->will($this->throwException(new \Exception));

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => 'An error occurred while saving your new password. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testPasswordresetactionIfTheFormFactoryThrowsAnExceptionItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $routerUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('pwReset', ['e' => $expectedEmail, 't' => $expectedToken], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->will($this->throwException(new \Exception));

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => 'An error occurred while saving your new password. Please try again.',
        'formView' => null]
      )
      ->willReturn($this->responseMock);
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testPasswordresetactionIfTheFormHandlerequestThrowsAnExceptionItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $routerUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('pwReset', ['e' => $expectedEmail, 't' => $expectedToken], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->throwException(new \Exception));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);

    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => 'An error occurred while saving your new password. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testPasswordresetactionIfTheFormIsSubmittedButIsNotValidItShouldReturnAResponse() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $routerUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('pwReset', ['e' => $expectedEmail, 't' => $expectedToken], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(false);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testPasswordresetactionIfUserModelSetemailThrowsAnExceptionItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $pwResetRouterUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('pwReset', ['e' => $expectedEmail, 't' => $expectedToken], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($pwResetRouterUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $pwResetRouterUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);
    
    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->will($this->throwException(new \Exception));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => 'An error occurred while saving your new password. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testPasswordresetactionIfUserModelResetPwThrowsAnExceptionItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $pwResetRouterUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('pwReset', ['e' => $expectedEmail, 't' => $expectedToken], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($pwResetRouterUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $pwResetRouterUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);
    
    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('resetPw')
      ->with($expectedToken)
      ->will($this->throwException(new \Exception));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => 'An error occurred while saving your new password. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testPasswordresetactionIfUserModelResetPwThrowsATokenExpiredExceptionItShouldCatchItThenAddAFlashMsgAndRedirectToTheLostpasswordRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $pwResetRouterUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->exactly(2))
      ->method('generate')
      ->withConsecutive(
        ['pwReset', ['e' => $expectedEmail, 't' => $expectedToken]],
        ['lostPassword']
      )
      ->will($this->onConsecutiveCalls($pwResetRouterUrl, 'www.mywebsite.com/lost-password'));

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $pwResetRouterUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);
    
    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('resetPw')
      ->with($expectedToken)
      ->will($this->throwException(new \AppBundle\Exceptions\TokenExpiredException('custom message')));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'The password reset link is expired. Please initiate the lost password process again.')
      ->willReturn(null);
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testPasswordresetactionIfUserModelResetPwThrowsATokenExpiredExceptionAndAddingTheErrorFlashMsgThrowsAnExceptionItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $pwResetRouterUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('pwReset', ['e' => $expectedEmail, 't' => $expectedToken], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($pwResetRouterUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $pwResetRouterUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);
    
    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('resetPw')
      ->with($expectedToken)
      ->will($this->throwException(new \AppBundle\Exceptions\TokenExpiredException('custom message')));

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('error', 'The password reset link is expired. Please initiate the lost password process again.')
      ->will($this->throwException(new \Exception));
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => 'An error occurred while saving your new password. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testPasswordresetactionIfAddingTheSuccessFlashMsgThrowsAnExceptionItShouldCatchItAndRedirectToTheLoginRoute() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $pwResetRouterUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->exactly(2))
      ->method('generate')
      ->withConsecutive(
        ['pwReset', ['e' => $expectedEmail, 't' => $expectedToken]],
        ['login']
      )
      ->will($this->onConsecutiveCalls($pwResetRouterUrl, 'www.mywebsite.com/login'));

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $pwResetRouterUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);
    
    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('resetPw')
      ->with($expectedToken)
      ->willReturn(null);

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('success', 'Congratulations! Your new password is now active.')
      ->will($this->throwException(new \Exception));
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(RedirectResponse::class, get_class($actualValue));
  }

  public function testPasswordresetactionIfTheRouterForTheLoginRouteThrowsAnExceptionItShouldCatchItAndReturnAResponseWithAnErrorMsg() {
    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $pwResetRouterUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->exactly(2))
      ->method('generate')
      ->withConsecutive(
        ['pwReset', ['e' => $expectedEmail, 't' => $expectedToken]],
        ['login']
      )
      ->will($this->onConsecutiveCalls($pwResetRouterUrl, $this->throwException(new \Exception)));

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $pwResetRouterUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(true);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isValid')
      ->with()
      ->willReturn(true);
    
    $this->userMock->expects($this->once())
      ->method('setEmail')
      ->with($expectedEmail)
      ->willReturn(null);
    
    $this->userMock->expects($this->once())
      ->method('resetPw')
      ->with($expectedToken)
      ->willReturn(null);

    $this->sessionMock->expects($this->once())
      ->method('getFlashBag')
      ->with()
      ->willReturn($this->flashBagInterfaceMock);
    
    $this->flashBagInterfaceMock->expects($this->once())
      ->method('add')
      ->with('success', 'Congratulations! Your new password is now active.')
      ->willReturn(null);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => 'An error occurred while saving your new password. Please try again.',
        'formView' => $this->formViewMock]
      )
      ->willReturn($this->responseMock);
    
    $controller = new WelcomeController();
    $actualValue = $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock,
      $this->routerInterfaceMock, $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);

    $this->assertEquals(get_class($this->responseMock), get_class($actualValue));
  }

  public function testPasswordresetactionIfTheFormCreateviewThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('createView test exception msg');

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $routerUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('pwReset', ['e' => $expectedEmail, 't' => $expectedToken], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(false);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->will($this->throwException(new \Exception('createView test exception msg')));
    
    $controller = new WelcomeController();
    $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock, $this->routerInterfaceMock,
      $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);
  }

  public function testPasswordresetactionIfRenderresponseThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $this->authCheckerMock->expects($this->once())
      ->method('isGranted')
      ->with('ROLE_USER')
      ->willReturn(false);

    $this->requestMock->query = $this->parameterBagMock;

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $this->parameterBagMock->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['e'], ['t'])
      ->will($this->onConsecutiveCalls($expectedEmail, $expectedToken));
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $formTokenId = '4da5adf5d9ae65be9266757e583e4606a6a14f86';
    
    $routerUrl = "/app_dev.php/password-reset?e=${expectedEmail}&t=${expectedToken}";

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('pwReset', ['e' => $expectedEmail, 't' => $expectedToken], UrlGeneratorInterface::ABSOLUTE_URL)
      ->willReturn($routerUrl);

    $this->formFactoryMock->expects($this->once())
      ->method('create')
      ->with(PwResetType::class, $this->userMock, [
        'action' => $routerUrl,
        'method' => 'POST',
        'csrf_token_id' => $formTokenId
      ])
      ->willReturn($this->formInterfaceMock);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('handleRequest')
      ->with($this->requestMock)
      ->will($this->returnSelf());
    
    $this->formInterfaceMock->expects($this->once())
      ->method('isSubmitted')
      ->with()
      ->willReturn(false);
    
    $this->formInterfaceMock->expects($this->once())
      ->method('createView')
      ->with()
      ->willReturn($this->formViewMock);
    
    $this->engineInterfaceMock->expects($this->once())
      ->method('renderResponse')
      ->with(
        'AppBundle:Welcome:pwReset.html.twig',
        ['errorMsg' => '',
        'formView' => $this->formViewMock]
      )
      ->will($this->throwException(new \Exception));
    
    $controller = new WelcomeController();
    $controller->passwordResetAction($this->requestMock, $this->userFactoryMock, $this->formFactoryMock, $this->routerInterfaceMock,
      $this->engineInterfaceMock, $this->sessionMock, $this->authCheckerMock);
  }
}

Class CustomWelcomeControllerContainer implements \Psr\Container\ContainerInterface {
  public function get($id) {}
  public function has($id) {}
}