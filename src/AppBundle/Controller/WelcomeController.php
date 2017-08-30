<?php

namespace AppBundle\Controller;

use AppBundle\Model\{UserModelFactory, UserModel};
use AppBundle\Form\{RegisterType, LoginType, LostPwType, PwResetType};

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\{Request, Response, RedirectResponse};
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Form\{FormFactoryInterface, FormError};
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class WelcomeController extends Controller {
  /**
  * @Route("/", name="homepage")
  */
  public function indexAction() {
    return $this->render('AppBundle:Welcome:index.html.twig', array(
      // ...
    ));
  }

  /**
  * @route("/login", name="login")
  */
  public function loginAction(Request $request, AuthenticationUtils $authUtils,
  UserProviderInterface $userProvider, EngineInterface $renderEngine,
  FormFactoryInterface $formFactory, RouterInterface $router,
  AuthorizationCheckerInterface $authChecker) {
    if ($authChecker->isGranted('ROLE_USER')) {
      return(new RedirectResponse($router->generate('homepage')));
    }
    
    $accountDisabled = false;
    $userEmail = '';
    $errorMsg = '';
    $form = null;
    $formView = null;
    $lastUsername = '';

    try {
      $lastUsername = $authUtils->getLastUsername();

      $form = $formFactory->create(LoginType::class, ['uniqueId' => $lastUsername],
        [
          'action' => $router->generate('login', [],
            UrlGeneratorInterface::ABSOLUTE_URL),
          'method' => 'POST',
          'csrf_token_id' => 'dca6fedf8ee4ef6e2ebdf05aebadd3b1c0972a37',
        ]
      );

      $error = $authUtils->getLastAuthenticationError();

      if ($error !== null) {
        $accountDisabled = get_class($error) ===
          'Symfony\Component\Security\Core\Exception\DisabledException';

        if ($accountDisabled) {
          $userEmail = $userProvider->loadUserByUsername($lastUsername)->getEmail();
        } else {
          throw new \Exception;
        }
      }
    } catch (\Exception $e) {
      $errorMsg = 'An error occurred while processing your login. '.
        'Please try again.';
    }

    if ($form !== null) {
      $formView = $form->createView();
    }

    return($renderEngine->renderResponse('AppBundle:Welcome:login.html.twig', [
      'last_username' => $lastUsername,
      'accountDisabled' => $accountDisabled,
      'userEmail' => $userEmail,
      'errorMsg' => $errorMsg,
      'formView' => $formView,
    ]));
  }

  /**
  * @route("/register", name="register")
  */
  public function registerAction(Request $request, UserModelFactory $userFactory,
  FormFactoryInterface $formFactory, EngineInterface $renderEngine,
  RouterInterface $router, AuthorizationCheckerInterface $authChecker) {
    if ($authChecker->isGranted('ROLE_USER')) {
      return(new RedirectResponse($router->generate('homepage')));
    }

    $errorMsg = '';
    $form = null;
    $formView = null;

    try {
      $user = $userFactory->create();

      $form = $formFactory->create(RegisterType::class, $user, [
        'action' => $router->generate('register', [], UrlGeneratorInterface::ABSOLUTE_URL),
        'method' => 'POST',
        'csrf_token_id' => '689ca5c4aeacc31f7ee996be2e8eb93224420b38',
      ]);

      $form->handleRequest($request);
      
      if ($form->isSubmitted() && $form->isValid()) {
        return($renderEngine->renderResponse(
          'AppBundle:Welcome:registered.html.twig',
          [
            'emailSent' => $user->register(),
            'email' => $user->getEmail(),
          ]
        ));
      }
    } catch (\Exception $e) {
      $errorMsg = 'An error occurred while processing your registration. '.
        'Please try again.';

      $reMatch = [];

      if (preg_match('/1062 Duplicate entry \'[^\']+\' for key \'([^\']+)\'/i',
      $e->getMessage(), $reMatch) === 1) {
        try {
          $form->get($reMatch[1])->addError(new FormError('Not available'));
          $errorMsg = '';
        } catch (\Exception $e) {}
      }
    }

    if ($form !== null) {
      $formView = $form->createView();
    }

    return($renderEngine->renderResponse('AppBundle:Welcome:register.html.twig',
      ['errorMsg' => $errorMsg, 'formView' => $formView]
    ));
  }

  /**
  * @route("/activation", name="activation")
  */
  public function activationAction(Request $request, RouterInterface $router,
  SessionInterface $session, UserModelFactory $userFactory,
  AuthorizationCheckerInterface $authChecker) {
    if ($authChecker->isGranted('ROLE_USER')) {
      return(new RedirectResponse($router->generate('homepage')));
    }

    try {
      $email = $request->query->get('e');
      $token = $request->query->get('t');

      if ($email === null || $token === null) {
        throw new \Exception('The User\'s account couldn\'t be activated '.
          'because the provided email and/or token is null.');
      }

      $user = $userFactory->createFromArray(['email' => $email]);

      $user->activate($token, $router);

      try {
        $session->getFlashBag()->add(
          'success',
          'Congratulations! Your account is now activated and you can start '.
            'using this website.'
        );
      } catch (\Exception $e) { /* let the redirect to the login happen */ }

      $redirectRoute = 'login';
    } catch (\Exception $e) {
      $re_matches = [];
      if (preg_match('/^\[(\w+)\](.+)$/i', $e->getMessage(), $re_matches) === 1) {
        $type = $re_matches[1];
        $msg = $re_matches[2];
      } else {
        $type = 'error';
        $msg = 'It was not possible to activate your account. Please confirm '.
          'the activation link is correct and try again.';
      }

      try {
        $session->getFlashBag()->add($type, $msg);
      } catch (\Exception $e) { /* let the redirect to the homepage happen */ }

      $redirectRoute = 'homepage';
    }

    return(new RedirectResponse($router->generate($redirectRoute)));
  }
  
  /**
  * @route("/resend-activation", name="resendActivation")
  */
  public function resendActivationAction(Request $request, RouterInterface $router,
  UserModelFactory $userFactory, SessionInterface $session,
  AuthorizationCheckerInterface $authChecker) {
    if ($authChecker->isGranted('ROLE_USER')) {
      return(new RedirectResponse($router->generate('homepage')));
    }

    try {
      $email = $request->query->get('e');

      if ($email === null) {
        throw new \Exception('The activation account email couldn\'t be resent '.
          'because the provided email is null.');
      }

      $user = $userFactory->createFromArray(['email' => $email]);

      $user->regenActivationToken();

      try {
        $session->getFlashBag()->add('success',
          'A new activation email was sent to this account\'s email address.');
      } catch (\Exception $e) { /* let the redirect to the homepage happen */ }
    } catch (\Exception $e) {
      try {
        $session->getFlashBag()->add('error',
          'The new activation email could not be sent. Please try again.');
      } catch (\Exception $e) { /* let the redirect to the homepage happen */ }
    }

    return(new RedirectResponse($router->generate('homepage')));
  }

  /**
  * @route("/lost-password", name="lostPassword")
  */
  public function lostPasswordAction(Request $request, RouterInterface $router,
  UserModelFactory $userFactory, SessionInterface $session,
  FormFactoryInterface $formFactory, EngineInterface $renderEngine,
  AuthorizationCheckerInterface $authChecker) {
    if ($authChecker->isGranted('ROLE_USER')) {
      return(new RedirectResponse($router->generate('homepage')));
    }

    $errorMsg = '';
    $form = null;
    $formView = null;

    try {
      $user = $userFactory->create();

      $form = $formFactory->create(LostPwType::class, $user, [
        'action' => $router->generate('lostPassword', [],
          UrlGeneratorInterface::ABSOLUTE_URL),
        'method' => 'POST',
        'csrf_token_id' => '3e77ac6ee9f8733b2f56cad16fb89baa27e995e2',
      ]);

      $form->handleRequest($request);
      
      if ($form->isSubmitted() && $form->isValid()) {
        $user->initPwResetProcess();

        try {
          $session->getFlashBag()->add('success', 'An email was sent to this account\'s email '.
            'address with the link where a new password can be set.');
        } catch (\Exception $e) { /* let the redirect to the homepage happen */ }
        
        return(new RedirectResponse($router->generate('homepage')));
      }
    } catch (\Exception $e) {
      $errorMsg = 'An error occurred while initiating your account\'s password '.
        'reset. Please try again.';
    }

    if ($form !== null) {
      $formView = $form->createView();
    }

    return($renderEngine->renderResponse('AppBundle:Welcome:lostpw.html.twig', [
      'errorMsg' => $errorMsg,
      'formView' => $formView,
    ]));
  }

  /**
  * @route("/password-reset", name="pwReset")
  */
  public function passwordResetAction(Request $request, UserModelFactory $userFactory,
  FormFactoryInterface $formFactory, RouterInterface $router,
  EngineInterface $renderEngine, SessionInterface $session,
  AuthorizationCheckerInterface $authChecker) {
    if ($authChecker->isGranted('ROLE_USER')) {
      return(new RedirectResponse($router->generate('homepage')));
    }

    $errorMsg = '';
    $form = null;
    $formView = null;

    try {
      $email = $request->query->get('e');
      $token = $request->query->get('t');

      if ($email === null || $token === null) {
        $session->getFlashBag()->add(
          'error',
          'The password reset link is not valid. Please confirm the link and '.
            'try again.'
        );
        
        return(new RedirectResponse($router->generate('homepage')));
      }

      $user = $userFactory->create();

      $form = $formFactory->create(PwResetType::class, $user, [
        'action' => $router->generate('pwReset', ['e' => $email, 't' => $token],
          UrlGeneratorInterface::ABSOLUTE_URL),
        'method' => 'POST',
        'csrf_token_id' => '4da5adf5d9ae65be9266757e583e4606a6a14f86',
      ]);

      $form->handleRequest($request);
      
      if ($form->isSubmitted() && $form->isValid()) {
        $user->setEmail($email);

        $flashType = 'success';
        $flashMsg = 'Congratulations! Your new password is now active.';
        $routeName = 'login';

        try {
          $user->resetPw($token);
        } catch (\AppBundle\Exceptions\TokenExpiredException $e) {
          $flashType = 'error';
          $routeName = 'lostPassword';
          $flashMsg = 'The password reset link is expired. Please initiate the '.
            'lost password process again.';
        }

        try {
          $session->getFlashBag()->add($flashType, $flashMsg);
        } catch (\Exception $e) {
          if ($flashType === 'error') {
            throw $e;
          }
        }
        
        return(new RedirectResponse($router->generate($routeName)));
      }
    } catch (\Exception $e) {
      $errorMsg = 'An error occurred while saving your new password. '.
        'Please try again.';
    }

    if ($form !== null) {
      $formView = $form->createView();
    }

    return($renderEngine->renderResponse('AppBundle:Welcome:pwReset.html.twig', [
      'errorMsg' => $errorMsg,
      'formView' => $formView,
    ]));
  }
}