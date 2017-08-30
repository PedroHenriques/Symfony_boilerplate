<?php

namespace tests\acceptance\AppBundle\Controller;

require_once(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'MinkTestCase.php');

use tests\acceptance\MinkTestCase;

class WelcomeControllerTest extends MinkTestCase {
  private $fixtures = [];
  private $websiteName = 'YOUR WEBSITE NAME';
  private $emailFrom = 'your@email.com';
  
  public function __construct() {
    parent::__construct();

    $this->fixtures = [
      'users_roles' => [
        [
          'id' => 1, 'role' => 'ROLE_USER'
        ]
      ],
      'users' => [
        [
          'id' => 1, 'userName' => 'inactive test username', 'email' => 'test@test.com',
          'password' => '$2y$15$/.9tL5uq.t.BjAxsHGn5feQG4gxe7fh8BZVgjQEN19W4sKg4RHywW',
          'isActive' => 0, 'roleId' => 1, 'activationHash' => '$2y$15$spcWknxLRFmfG3DbZzh4VeveM1xxLGQUYHNED7X2KglDDC/pEnKka',
          'activationHashGenTs' => time(), 'pwResetHash' => null, 'pwResetHashGenTs' => null, 'created' => time()-86400
        ],
        [
          'id' => 2, 'userName' => 'activated username', 'email' => 'activated@test.com',
          'password' => '$2y$15$/.9tL5uq.t.BjAxsHGn5feQG4gxe7fh8BZVgjQEN19W4sKg4RHywW',
          'isActive' => 1, 'roleId' => 1, 'activationHash' => null,
          'activationHashGenTs' => null, 'pwResetHash' => null, 'pwResetHashGenTs' => null, 'created' => time()-86400
        ],
        [
          'id' => 3, 'userName' => 'pw reset username', 'email' => 'pwreset@test.com',
          'password' => '$2y$15$/.9tL5uq.t.BjAxsHGn5feQG4gxe7fh8BZVgjQEN19W4sKg4RHywW',
          'isActive' => 1, 'roleId' => 1, 'activationHash' => null, 'activationHashGenTs' => null,
          'pwResetHash' => '$2y$15$T.86P2owPT7H9mLFx7D1uuHlDjwG10EjpwUaH8vwyFFfk8GWmiQ4C', 'pwResetHashGenTs' => time(), 'created' => time()-86400
        ],
        [
          'id' => 4, 'userName' => 'expired inactive test username', 'email' => 'expired.test@test.com',
          'password' => '$2y$15$/.9tL5uq.t.BjAxsHGn5feQG4gxe7fh8BZVgjQEN19W4sKg4RHywW',
          'isActive' => 0, 'roleId' => 1, 'activationHash' => '$2y$15$spcWknxLRFmfG3DbZzh4VeveM1xxLGQUYHNED7X2KglDDC/pEnKka',
          'activationHashGenTs' => time()-432000, 'pwResetHash' => null, 'pwResetHashGenTs' => null, 'created' => time()-86400
        ],
        [
          'id' => 5, 'userName' => 'expired pw reset username', 'email' => 'expired.pwreset@test.com',
          'password' => '$2y$15$/.9tL5uq.t.BjAxsHGn5feQG4gxe7fh8BZVgjQEN19W4sKg4RHywW',
          'isActive' => 1, 'roleId' => 1, 'activationHash' => null, 'activationHashGenTs' => null,
          'pwResetHash' => '$2y$15$T.86P2owPT7H9mLFx7D1uuHlDjwG10EjpwUaH8vwyFFfk8GWmiQ4C', 'pwResetHashGenTs' => time()-432000, 'created' => time()
        ],
      ],
    ];
  }

  public function getDataSet() {
    return($this->createArrayDataSet($this->fixtures));
  }

  public function testAUserCanRegisterAnAccountAndReceiveTheActivationEmail() {
    $minkSession = $this->getSession();
    
    $this->visitUri($minkSession, '/register');
    $page = $minkSession->getPage();

    $form = $page->find('named', ['id_or_name', 'register']);
    $this->assertNotNull($form);
    $this->assertEquals('post', $form->getAttribute('method'));
    $this->assertEquals($this->getUrlFromUri('/register'), $form->getAttribute('action'));

    $fieldUserName = $form->findField('register_userName');
    $fieldEmail = $form->findField('register_email');
    $fieldPw = $form->findField('register_plainPassword_first');
    $fieldPwConf = $form->findField('register_plainPassword_second');
    $this->assertNotNull($fieldUserName);
    $this->assertNotNull($fieldEmail);
    $this->assertNotNull($fieldPw);
    $this->assertNotNull($fieldPwConf);

    $userInfo = [
      'id' => count($this->fixtures['users']) + 1,
      'userName' => 'Pedro Henriques',
      'email' => 'pedro@pedrojhenriques.com',
      'isActive' => 0,
      'roleId' => 1,
      'pwResetHash' => null,
      'pwResetHashGenTs' => null,
    ];
    $userPw = 'password';

    $fieldUserName->setValue($userInfo['userName']);
    $fieldEmail->setValue($userInfo['email']);
    $fieldPw->setValue($userPw);
    $fieldPwConf->setValue($userPw);
    $form->submit();

    $this->assertEquals($this->getUrlFromUri('/register'), $minkSession->getCurrentUrl());
    
    $page = $minkSession->getPage();

    $expectedHtml = '<p>Thank you for creating an account with YOUR WEBSITE NAME.</p><br>'.
      "<p>To complete your registration please activate your account by clicking on the link in the email sent to {$userInfo['email']}.</p>";

    $this->assertEquals($expectedHtml, parent::oneLineHtml($page->find('css', '#successMsg')->getHtml()));

    $emails = $this->getEmails();
    $this->assertEquals(1, count($emails));

    $expectedSubject = "{$this->websiteName}: Account Activation";

    $this->assertEquals($expectedSubject, $emails[0]['subject']);
    $this->assertEquals("<{$this->emailFrom}>", $emails[0]['sender']);
    $this->assertEquals(["<{$userInfo['email']}>"], $emails[0]['recipients']);
    
    $matches = [];
    preg_match(
      '@<a href="'.preg_quote(parent::getBaseUrl()).'/activation\?e=([^&]+)&t=([^&]+)">@',
      $this->getEmailBody($emails[0]['id']),
      $matches
    );

    $this->assertEquals($userInfo['email'], urldecode($matches[1]));

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT * FROM users WHERE email = '{$userInfo['email']}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(1, count($resultSet));

    $userDbInfo = $resultSet[0];
    unset($userDbInfo['password']);
    unset($userDbInfo['activationHash']);
    unset($userDbInfo['activationHashGenTs']);
    unset($userDbInfo['created']);
    $this->assertEquals($userInfo, $userDbInfo);
    $this->assertEquals(true, password_verify($userPw, $resultSet[0]['password']));
    $this->assertEquals(true, password_verify(urldecode($matches[2]), $resultSet[0]['activationHash']));
    $this->assertNotNull($resultSet[0]['activationHashGenTs']);
  }

  public function testAUserCanNotRegisterAnAccountWithAnAlreadyTakenEmail() {
    $minkSession = $this->getSession();
    
    $this->visitUri($minkSession, '/register');
    $page = $minkSession->getPage();

    $form = $page->find('named', ['id_or_name', 'register']);

    $fieldUserName = $form->findField('register_userName');
    $fieldEmail = $form->findField('register_email');
    $fieldPw = $form->findField('register_plainPassword_first');
    $fieldPwConf = $form->findField('register_plainPassword_second');

    $fieldUserName->setValue('username not taken');
    $fieldEmail->setValue($this->fixtures['users'][0]['email']);
    $fieldPw->setValue('password');
    $fieldPwConf->setValue('password');
    $form->submit();

    $this->assertEquals($this->getUrlFromUri('/register'), $minkSession->getCurrentUrl());
    
    $page = $minkSession->getPage();
    $this->assertContains(
      '<ul><li>Not available</li></ul><input type="email" id="register_email"',
      parent::oneLineHtml($page->getHtml()),
      '',
      true
    );

    $divElem = $page->find('css', '#successMsg');
    $this->assertNull($divElem);

    $emails = $this->getEmails();
    $this->assertEquals(0, count($emails));

    $pdo = parent::getPdo();
    $query = $pdo->query('SELECT count(id) as count FROM users');
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(count($this->fixtures['users']), $resultSet[0]['count']);
  }

  public function testAUserCanNotRegisterAnAccountWithAnAlreadyTakenUsername() {
    $minkSession = $this->getSession();
    
    $this->visitUri($minkSession, '/register');
    $page = $minkSession->getPage();

    $form = $page->find('named', ['id_or_name', 'register']);

    $fieldUserName = $form->findField('register_userName');
    $fieldEmail = $form->findField('register_email');
    $fieldPw = $form->findField('register_plainPassword_first');
    $fieldPwConf = $form->findField('register_plainPassword_second');

    $fieldUserName->setValue($this->fixtures['users'][0]['userName']);
    $fieldEmail->setValue('not.taken@email.com');
    $fieldPw->setValue('password');
    $fieldPwConf->setValue('password');
    $form->submit();

    $this->assertEquals($this->getUrlFromUri('/register'), $minkSession->getCurrentUrl());
    
    $page = $minkSession->getPage();
    $this->assertContains(
      '<ul><li>Not available</li></ul><input type="text" id="register_userName"',
      parent::oneLineHtml($page->getHtml()),
      '',
      true
    );

    $divElem = $page->find('css', '#successMsg');
    $this->assertNull($divElem);

    $emails = $this->getEmails();
    $this->assertEquals(0, count($emails));

    $pdo = parent::getPdo();
    $query = $pdo->query('SELECT count(id) as count FROM users');
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(count($this->fixtures['users']), $resultSet[0]['count']);
  }

  public function testAUserCanActivateHisAccount() {
    $minkSession = $this->getSession();
    
    $this->visitUri($minkSession, '/activation?e='.urlencode($this->fixtures['users'][0]['email']).'&t=3219b5fdbd5899cbcc97d7e7ad522b91');
    $this->assertEquals($this->getUrlFromUri('/login'), $minkSession->getCurrentUrl());

    $page = $minkSession->getPage();
    $this->assertEquals(
      '<p>Congratulations! Your account is now activated and you can start using this website.</p>',
      parent::oneLineHtml($page->find('css', 'div.flash-success')->getHtml())
    );

    $userInfo = $this->fixtures['users'][0];
    $userInfo['isActive'] = 1;
    $userInfo['activationHash'] = null;
    $userInfo['activationHashGenTs'] = null;

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT * FROM users WHERE email = '{$this->fixtures['users'][0]['email']}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(1, count($resultSet));
    $this->assertEquals($userInfo, $resultSet[0]);
  }

  public function testAUserCanNotActivateHisAccountIfTheTokenIsExpiredAndANewActivationEmailIsSent() {
    $minkSession = $this->getSession();
    
    $this->visitUri($minkSession, '/activation?e='.urlencode($this->fixtures['users'][3]['email']).'&t=3219b5fdbd5899cbcc97d7e7ad522b91');
    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());

    $page = $minkSession->getPage();
    $this->assertEquals(
      '<p>The activation link used is expired. A new activation link was sent to this account\'s email address.</p>',
      parent::oneLineHtml($page->find('css', 'div.flash-error')->getHtml())
    );

    $emails = $this->getEmails();
    $this->assertEquals(1, count($emails));

    $expectedSubject = "{$this->websiteName}: Account Activation";

    $this->assertEquals($expectedSubject, $emails[0]['subject']);
    $this->assertEquals("<{$this->emailFrom}>", $emails[0]['sender']);
    $this->assertEquals(["<{$this->fixtures['users'][3]['email']}>"], $emails[0]['recipients']);
    
    $matches = [];
    preg_match(
      '@<a href="'.preg_quote(parent::getBaseUrl()).'/activation\?e=([^&]+)&t=([^&]+)">@',
      $this->getEmailBody($emails[0]['id']),
      $matches
    );

    $this->assertEquals($this->fixtures['users'][3]['email'], urldecode($matches[1]));

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT * FROM users WHERE email = '{$this->fixtures['users'][3]['email']}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $expectedUserInfo = $this->fixtures['users'][3];
    unset($expectedUserInfo['activationHash']);
    unset($expectedUserInfo['activationHashGenTs']);
    $userDbInfo = $resultSet[0];
    unset($userDbInfo['activationHash']);
    unset($userDbInfo['activationHashGenTs']);
    $this->assertEquals($expectedUserInfo, $userDbInfo);
    $this->assertEquals(true, password_verify(urldecode($matches[2]), $resultSet[0]['activationHash']));
    $this->assertNotNull($resultSet[0]['activationHashGenTs']);
  }

  public function testAUserCanNotActivateHisAccountIfTheTokenIsInvalid() {
    $minkSession = $this->getSession();
    
    $this->visitUri($minkSession, '/activation?e='.urlencode($this->fixtures['users'][3]['email']).'&t=3219b5fdbd5899cbcc97d7e7ad522b91NotValid');
    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());

    $page = $minkSession->getPage();
    $this->assertEquals(
      '<p>It was not possible to activate your account. Please confirm the activation link is correct and try again.</p>',
      parent::oneLineHtml($page->find('css', 'div.flash-error')->getHtml())
    );

    $emails = $this->getEmails();
    $this->assertEquals(0, count($emails));

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT isActive, activationHash, activationHashGenTs FROM users WHERE email = '{$this->fixtures['users'][3]['email']}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(0, $resultSet[0]['isActive']);
    $this->assertEquals($this->fixtures['users'][3]['activationHash'], $resultSet[0]['activationHash']);
    $this->assertEquals($this->fixtures['users'][3]['activationHashGenTs'], $resultSet[0]['activationHashGenTs']);
  }
  
  public function testAUserCanLoginUsingTheEmailAsTheUniqueIdentifier() {
    $minkSession = $this->getSession();
    
    $this->visitUri($minkSession, '/login');
    $page = $minkSession->getPage();

    $form = $page->find('named', ['id_or_name', 'login']);
    $this->assertNotNull($form);
    $this->assertEquals('post', $form->getAttribute('method'));
    $this->assertEquals($this->getUrlFromUri('/login'), $form->getAttribute('action'));

    $fieldUniqueId = $form->findField('login_uniqueId');
    $fieldPassword = $form->findField('login_password');
    $this->assertNotNull($fieldUniqueId);
    $this->assertNotNull($fieldPassword);

    $fieldUniqueId->setValue($this->fixtures['users'][1]['email']);
    $fieldPassword->setValue('password');
    $form->submit();

    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());

    $this->visitUri($minkSession, '/login');
    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());
  }

  public function testAUserCanLoginUsingTheUsernameAsTheUniqueIdentifier() {
    $minkSession = $this->getSession();
    
    $this->visitUri($minkSession, '/login');
    $page = $minkSession->getPage();

    $form = $page->find('named', ['id_or_name', 'login']);
    $this->assertNotNull($form);
    $this->assertEquals('post', $form->getAttribute('method'));
    $this->assertEquals($this->getUrlFromUri('/login'), $form->getAttribute('action'));

    $fieldUniqueId = $form->findField('login_uniqueId');
    $fieldPassword = $form->findField('login_password');
    $this->assertNotNull($fieldUniqueId);
    $this->assertNotNull($fieldPassword);

    $fieldUniqueId->setValue($this->fixtures['users'][1]['userName']);
    $fieldPassword->setValue('password');
    $form->submit();

    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());

    $this->visitUri($minkSession, '/login');
    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());
  }

  public function testAUserCanNotLoginWithInvalidCredentials() {
    $minkSession = $this->getSession();
    
    $this->visitUri($minkSession, '/login');
    $page = $minkSession->getPage();

    $form = $page->find('named', ['id_or_name', 'login']);
    $fieldUniqueId = $form->findField('login_uniqueId');
    $fieldPassword = $form->findField('login_password');

    $fieldUniqueId->setValue($this->fixtures['users'][1]['email']);
    $fieldPassword->setValue('invalid password');
    $form->submit();

    $this->assertEquals($this->getUrlFromUri('/login'), $minkSession->getCurrentUrl());

    $page = $minkSession->getPage();
    $divElem = $page->find('css', '#errorMsg');

    $expectedHtml = "<p>An error occurred while processing your login. Please try again.</p>";

    $this->assertNotNull($divElem);
    $this->assertEquals($expectedHtml, parent::oneLineHtml($divElem->getHtml()));
  }

  public function testAUserCanNotLoginIfTheAccountIsNotActive() {
    $minkSession = $this->getSession();
    $this->authenticateUser($minkSession, $this->fixtures['users'][0]['email'], 'password');

    $this->assertEquals($this->getUrlFromUri('/login'), $minkSession->getCurrentUrl());

    $page = $minkSession->getPage();
    $divElem = $page->find('css', '#accountDisabledMsg');

    $urlEmail = $this->getUrlFromUri('/resend-activation?e='.urlencode($this->fixtures['users'][0]['email']));
    $expectedHtml = "<p>This account hasn't been activated after the registration process.</p>".
      "<p>An email was sent to this account's email address with the link to activate the account.</p>".
      "<p>In order to resend the activation email <a href=\"${urlEmail}\">click here</a></p>";

    $this->assertNotNull($divElem);
    $this->assertEquals($expectedHtml, parent::oneLineHtml($divElem->getHtml()));
  }

  public function testAnAuthenticatedUserCanNotAccessTheLoginPage() {
    $minkSession = $this->getSession();
    $this->authenticateUser($minkSession, $this->fixtures['users'][1]['email'], 'password');

    $this->visitUri($minkSession, '/login');
    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());
  }

  public function testAnAuthenticatedUserCanNotAccessTheRegisterPage() {
    $minkSession = $this->getSession();
    $this->authenticateUser($minkSession, $this->fixtures['users'][1]['email'], 'password');

    $this->visitUri($minkSession, '/register');
    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());
  }
  
  public function testAnAuthenticatedUserCanNotAccessTheActivationPage() {
    $minkSession = $this->getSession();
    $this->authenticateUser($minkSession, $this->fixtures['users'][1]['email'], 'password');

    $this->visitUri($minkSession, '/activation');
    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());
  }
  
  public function testAnAuthenticatedUserCanNotAccessTheResendActivationPage() {
    $minkSession = $this->getSession();
    $this->authenticateUser($minkSession, $this->fixtures['users'][1]['email'], 'password');

    $this->visitUri($minkSession, '/resend-activation');
    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());
  }
  
  public function testAnAuthenticatedUserCanNotAccessTheLostPasswordPage() {
    $minkSession = $this->getSession();
    $this->authenticateUser($minkSession, $this->fixtures['users'][1]['email'], 'password');

    $this->visitUri($minkSession, '/lost-password');
    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());
  }
  
  public function testAnAuthenticatedUserCanNotAccessThePasswordResetPage() {
    $minkSession = $this->getSession();
    $this->authenticateUser($minkSession, $this->fixtures['users'][1]['email'], 'password');

    $this->visitUri($minkSession, '/password-reset');
    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());
  }

  public function testAUserCanRequestANewActivationEmail() {
    $minkSession = $this->getSession();

    $this->visitUri($minkSession, '/resend-activation?e='.urlencode($this->fixtures['users'][0]['email']));
    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());

    $page = $minkSession->getPage();
    $this->assertEquals(
      '<p>A new activation email was sent to this account\'s email address.</p>',
      parent::oneLineHtml($page->find('css', 'div.flash-success')->getHtml())
    );

    $emails = $this->getEmails();
    $this->assertEquals(1, count($emails));

    $expectedSubject = "{$this->websiteName}: Account Activation";

    $this->assertEquals($expectedSubject, $emails[0]['subject']);
    $this->assertEquals("<{$this->emailFrom}>", $emails[0]['sender']);
    $this->assertEquals(["<{$this->fixtures['users'][0]['email']}>"], $emails[0]['recipients']);
    
    $matches = [];
    preg_match(
      '@<a href="'.preg_quote(parent::getBaseUrl()).'/activation\?e=([^&]+)&t=([^&]+)">@',
      $this->getEmailBody($emails[0]['id']),
      $matches
    );

    $this->assertEquals($this->fixtures['users'][0]['email'], urldecode($matches[1]));

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT * FROM users WHERE email = '{$this->fixtures['users'][0]['email']}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(1, count($resultSet));

    $expectedUserInfo = $this->fixtures['users'][0];
    unset($expectedUserInfo['activationHash']);
    unset($expectedUserInfo['activationHashGenTs']);
    $userDbInfo = $resultSet[0];
    unset($userDbInfo['activationHash']);
    unset($userDbInfo['activationHashGenTs']);
    $this->assertEquals($expectedUserInfo, $userDbInfo);
    $this->assertEquals(true, password_verify(urldecode($matches[2]), $resultSet[0]['activationHash']));
    $this->assertNotNull($resultSet[0]['activationHashGenTs']);
  }

  public function testAUserCanInitiateThePasswordResetProcessIfTheAccountIsActive() {
    $minkSession = $this->getSession();
    
    $this->visitUri($minkSession, '/lost-password');
    $page = $minkSession->getPage();

    $form = $page->find('named', ['id_or_name', 'lost_pw']);
    $this->assertNotNull($form);
    $this->assertEquals('post', $form->getAttribute('method'));
    $this->assertEquals($this->getUrlFromUri('/lost-password'), $form->getAttribute('action'));

    $fieldEmail = $form->findField('lost_pw_email');
    $this->assertNotNull($fieldEmail);

    $fieldEmail->setValue($this->fixtures['users'][1]['email']);
    $form->submit();

    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());

    $page = $minkSession->getPage();
    $this->assertEquals(
      '<p>An email was sent to this account\'s email address with the link where a new password can be set.</p>',
      parent::oneLineHtml($page->find('css', 'div.flash-success')->getHtml())
    );

    $emails = $this->getEmails();
    $this->assertEquals(1, count($emails));

    $expectedSubject = "{$this->websiteName}: Password Reset";

    $this->assertEquals($expectedSubject, $emails[0]['subject']);
    $this->assertEquals("<{$this->emailFrom}>", $emails[0]['sender']);
    $this->assertEquals(["<{$this->fixtures['users'][1]['email']}>"], $emails[0]['recipients']);

    $matches = [];
    preg_match(
      '@<a href="'.preg_quote(parent::getBaseUrl()).'/password-reset\?e=([^&]+)&t=([^&]+)">@',
      $this->getEmailBody($emails[0]['id']),
      $matches
    );

    $this->assertEquals($this->fixtures['users'][1]['email'], urldecode($matches[1]));

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT * FROM users WHERE email = '{$this->fixtures['users'][1]['email']}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(1, count($resultSet));
    $this->assertEquals(true, password_verify(urldecode($matches[2]), $resultSet[0]['pwResetHash']));
    $this->assertNotNull($resultSet[0]['pwResetHashGenTs']);

    $expectedUserInfo = $this->fixtures['users'][1];
    unset($expectedUserInfo['pwResetHash']);
    unset($expectedUserInfo['pwResetHashGenTs']);
    $userDbInfo = $resultSet[0];
    unset($userDbInfo['pwResetHash']);
    unset($userDbInfo['pwResetHashGenTs']);
    $this->assertEquals($expectedUserInfo, $userDbInfo);
  }

  public function testAUserCanNotInitiateThePasswordResetProcessIfTheAccountIsNotActive() {
    $minkSession = $this->getSession();
    
    $this->visitUri($minkSession, '/lost-password');
    $page = $minkSession->getPage();

    $form = $page->find('named', ['id_or_name', 'lost_pw']);

    $fieldEmail = $form->findField('lost_pw_email');

    $fieldEmail->setValue($this->fixtures['users'][0]['email']);
    $form->submit();

    $this->assertEquals($this->getUrlFromUri('/lost-password'), $minkSession->getCurrentUrl());

    $page = $minkSession->getPage();
    $this->assertEquals(
      '<p>An error occurred while initiating your account\'s password reset. Please try again.</p>',
      parent::oneLineHtml($page->find('css', '#errorMsg')->getHtml())
    );

    $emails = $this->getEmails();
    $this->assertEquals(0, count($emails));

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = '{$this->fixtures['users'][0]['email']}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals($this->fixtures['users'][0]['pwResetHash'], $resultSet[0]['pwResetHash']);
    $this->assertEquals($this->fixtures['users'][0]['pwResetHashGenTs'], $resultSet[0]['pwResetHashGenTs']);
  }

  public function testAUserCanChangeTheAccountPasswordAfterInitiatingThePasswordResetProcess() {
    $minkSession = $this->getSession();

    $relUrl = '/password-reset?e='.urlencode($this->fixtures['users'][2]['email']).'&t=605e520b4586e991712b12720cd571b6';
    $this->visitUri($minkSession, $relUrl);
    $page = $minkSession->getPage();

    $form = $page->find('named', ['id_or_name', 'pw_reset']);
    $this->assertNotNull($form);
    $this->assertEquals('post', $form->getAttribute('method'));
    $this->assertEquals($this->getUrlFromUri($relUrl), $form->getAttribute('action'));

    $fieldPw = $form->findField('pw_reset_plainPassword_first');
    $fieldPwConf = $form->findField('pw_reset_plainPassword_second');
    $this->assertNotNull($fieldPw);
    $this->assertNotNull($fieldPwConf);

    $newPw = 'newpassword';
    $fieldPw->setValue($newPw);
    $fieldPwConf->setValue($newPw);
    $form->submit();

    $this->assertEquals($this->getUrlFromUri('/login'), $minkSession->getCurrentUrl());

    $page = $minkSession->getPage();
    $this->assertEquals(
      '<p>Congratulations! Your new password is now active.</p>',
      parent::oneLineHtml($page->find('css', 'div.flash-success')->getHtml())
    );

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT password, pwResetHash, pwResetHashGenTs FROM users WHERE email = '{$this->fixtures['users'][2]['email']}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);
    
    $this->assertEquals(true, password_verify($newPw, $resultSet[0]['password']));
    $this->assertEquals(null, $resultSet[0]['pwResetHash']);
    $this->assertEquals(null, $resultSet[0]['pwResetHashGenTs']);
  }

  public function testAUserCanNotChangeTheAccountPasswordIfTheTokenIsExpired() {
    $minkSession = $this->getSession();

    $relUrl = '/password-reset?e='.urlencode($this->fixtures['users'][4]['email']).'&t=605e520b4586e991712b12720cd571b6';
    $this->visitUri($minkSession, $relUrl);
    $page = $minkSession->getPage();

    $form = $page->find('named', ['id_or_name', 'pw_reset']);

    $fieldPw = $form->findField('pw_reset_plainPassword_first');
    $fieldPwConf = $form->findField('pw_reset_plainPassword_second');

    $newPw = 'newpassword';
    $fieldPw->setValue($newPw);
    $fieldPwConf->setValue($newPw);
    $form->submit();

    $this->assertEquals($this->getUrlFromUri('/lost-password'), $minkSession->getCurrentUrl());

    $page = $minkSession->getPage();
    $this->assertEquals(
      '<p>The password reset link is expired. Please initiate the lost password process again.</p>',
      parent::oneLineHtml($page->find('css', 'div.flash-error')->getHtml())
    );

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT password, pwResetHash, pwResetHashGenTs FROM users WHERE email = '{$this->fixtures['users'][4]['email']}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);
    
    $this->assertEquals($this->fixtures['users'][4]['password'], $resultSet[0]['password']);
    $this->assertEquals(null, $resultSet[0]['pwResetHash']);
    $this->assertEquals(null, $resultSet[0]['pwResetHashGenTs']);
  }

  public function testAUserCanNotChangeTheAccountPasswordIfTheTokenIsInvalid() {
    $minkSession = $this->getSession();

    $relUrl = '/password-reset?e='.urlencode($this->fixtures['users'][2]['email']).'&t=605e520b4586e991712b12720cd571b6NotValid';
    $this->visitUri($minkSession, $relUrl);
    $page = $minkSession->getPage();
    
    $form = $page->find('named', ['id_or_name', 'pw_reset']);

    $fieldPw = $form->findField('pw_reset_plainPassword_first');
    $fieldPwConf = $form->findField('pw_reset_plainPassword_second');

    $fieldPw->setValue('newpassword');
    $fieldPwConf->setValue('newpassword');
    $form->submit();
    
    $this->assertEquals($this->getUrlFromUri($relUrl), $minkSession->getCurrentUrl());

    $page = $minkSession->getPage();
    $this->assertEquals(
      '<p>An error occurred while saving your new password. Please try again.</p>',
      parent::oneLineHtml($page->find('css', '#errorMsg')->getHtml())
    );

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT password, pwResetHash, pwResetHashGenTs FROM users WHERE email = '{$this->fixtures['users'][2]['email']}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);
    
    $this->assertEquals($this->fixtures['users'][2]['password'], $resultSet[0]['password']);
    $this->assertEquals($this->fixtures['users'][2]['pwResetHash'], $resultSet[0]['pwResetHash']);
    $this->assertEquals($this->fixtures['users'][2]['pwResetHashGenTs'], $resultSet[0]['pwResetHashGenTs']);
  }

  public function testAUserCanStillLoginAfterInitiatingThePasswordResetProcess() {
    $minkSession = $this->getSession();
    
    $this->visitUri($minkSession, '/login');
    $page = $minkSession->getPage();

    $form = $page->find('named', ['id_or_name', 'login']);
    $fieldUniqueId = $form->findField('login_uniqueId');
    $fieldPassword = $form->findField('login_password');

    $fieldUniqueId->setValue($this->fixtures['users'][2]['email']);
    $fieldPassword->setValue('password');
    $form->submit();

    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());

    $this->visitUri($minkSession, '/login');
    $this->assertEquals($this->getUrlFromUri('/'), $minkSession->getCurrentUrl());
  }

  public function testAllSessionsOfAnAuthenticatedUserWillBeLoggedOutIfAnyOfItsSignificantDataIsChanged() {
    $minkSessionOne = $this->getSession();
    $minkSessionTwo = parent::createGoutteSession();

    $this->authenticateUser($minkSessionOne, $this->fixtures['users'][1]['email'], 'password');
    $this->authenticateUser($minkSessionTwo, $this->fixtures['users'][1]['email'], 'password');
    
    $this->visitUri($minkSessionOne, '/login');
    $this->visitUri($minkSessionTwo, '/login');
    $this->assertEquals($this->getUrlFromUri('/'), $minkSessionOne->getCurrentUrl());
    $this->assertEquals($this->getUrlFromUri('/'), $minkSessionTwo->getCurrentUrl());

    $pdo = parent::getPdo();
    $numAffectedRows = $pdo->exec("UPDATE users SET email = 'new.email@gmail.com' WHERE id = '{$this->fixtures['users'][1]['id']}'");

    $this->assertEquals(1, $numAffectedRows);

    $this->visitUri($minkSessionOne, '/login');
    $this->visitUri($minkSessionTwo, '/login');
    $this->assertEquals($this->getUrlFromUri('/login'), $minkSessionOne->getCurrentUrl());
    $this->assertEquals($this->getUrlFromUri('/login'), $minkSessionTwo->getCurrentUrl());
  }
}