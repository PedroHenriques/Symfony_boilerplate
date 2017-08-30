<?php

namespace tests\integration\AppBundle\Model;

require_once(dirname(dirname(__DIR__)).'/BaseIntegrationCase.php');

use tests\integration\BaseIntegrationCase;
use AppBundle\Model\UserModel;
use AppBundle\Services\{Utils, EmailHandler};
use AppBundle\Exceptions\TokenExpiredException;

class UserModelTest extends BaseIntegrationCase {
  private $fixtures = [];
  
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
          'pwResetHash' => '$2y$15$T.86P2owPT7H9mLFx7D1uuHlDjwG10EjpwUaH8vwyFFfk8GWmiQ4C', 'pwResetHashGenTs' => time(), 'created' => time()
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

  protected function setUp() {
    parent::setUp();

    $client = static::createClient();
    $this->container = $client->getContainer();

    $dbHandler = $this->container->get('service_container')->get('AppBundle\Services\DbInterface');
    $utils = new Utils();
    $emailHandler = new EmailHandler(
      $this->createCustomMailer(),
      new \Symfony\Bundle\TwigBundle\TwigEngine(
        $this->container->get('service_container')->get('twig'),
        new \Symfony\Component\Templating\TemplateNameParser(),
        new \Symfony\Component\Config\FileLocator()
      ),
      $this->container
    );
    $encoder = $this->container->get('security.password_encoder');

    $this->userModel = new UserModel($dbHandler, $utils, $emailHandler, $encoder, $this->container);
  }

  public function testPopulatefromdbIsQueryingTheDbAndPopulatingTheUsermodelProperties() {
    $this->userModel->populateFromDb(['email' => [$this->fixtures['users'][0]['email'], \PDO::PARAM_STR]]);

    $userData = $this->fixtures['users'][0];
    $userData['roles'] = ['ROLE_USER'];
    unset($userData['roleId']);
    unset($userData['activationHash']);
    unset($userData['activationHashGenTs']);
    unset($userData['pwResetHash']);
    unset($userData['pwResetHashGenTs']);

    $this->userModelData = [
      'id' => $this->userModel->getId(),
      'userName' => $this->userModel->getUserName(),
      'email' => $this->userModel->getEmail(),
      'password' => $this->userModel->getPassword(),
      'isActive' => $this->userModel->getIsActive(),
      'created' => $this->userModel->getCreated(),
      'roles' => $this->userModel->getRoles(),
    ];

    $this->assertEquals($userData, $this->userModelData);
  }
  
  public function testRegisterIsAddingANewUserToTheDbAndTheActivationEmailIsBeingSentWithTheCorrectData() {
    $userName = 'this is a new username12345';
    $email = 'new.email@address.of.user';
    $plainPassword = 'this is a new plain password';

    $this->userModel->setUserName($userName);
    $this->userModel->setEmail($email);
    $this->userModel->setPlainPassword($plainPassword);

    $this->userModel->register();

    $emails = $this->getEmails();

    $this->assertEquals(1, count($emails));
    $this->assertEquals($email, key($emails[0]->getTo()));

    $matches = [];
    preg_match(
      '/<a href="http:\/\/localhost\/activation\?e=([^&]+)&t=([^&]+)">/',
      $emails[0]->getBody()->getContent(),
      $matches
    );

    $this->assertEquals($email, urldecode($matches[1]));

    $pdo = parent::getPdo();
    $query = $pdo->query('SELECT * FROM users ORDER BY id DESC');
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(count($this->fixtures['users']) + 1, count($resultSet));
    $this->assertEquals($userName, $resultSet[0]['userName']);
    $this->assertEquals($email, $resultSet[0]['email']);
    $this->assertEquals(true, password_verify($plainPassword, $resultSet[0]['password']));
    $this->assertEquals(true, password_verify(urldecode($matches[2]), $resultSet[0]['activationHash']));
    $this->assertNotNull($resultSet[0]['activationHashGenTs']);
  }

  public function testRegisterDoesntAllowAUserToRegisterWithAnAlreadyTakenUsername() {
    $userName = $this->fixtures['users'][0]['userName'];
    $email = 'new.email@address.of.user';
    $plainPassword = 'this is a new plain password';

    $this->userModel->setUserName($userName);
    $this->userModel->setEmail($email);
    $this->userModel->setPlainPassword($plainPassword);

    $exceptionThrown = false;
    $exceptionMsg = '';
    try {
      $this->userModel->register();
    } catch (\Exception $e) {
      $exceptionThrown = true;
      $exceptionMsg = $e->getMessage();
    }

    $this->assertEquals(true, $exceptionThrown);
    $this->assertEquals("1062 Duplicate entry '${userName}' for key 'userName'", $exceptionMsg);

    $this->assertEquals(0, count($this->getSpool()->getMessages()));

    $pdo = parent::getPdo();
    $query = $pdo->query('SELECT * FROM users ORDER BY id DESC');
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(count($this->fixtures['users']), count($resultSet));
  }
  
  public function testRegisterDoesntAllowAUserToRegisterWithAnAlreadyTakenEmail() {
    $userName = 'this is a new username12345';
    $email = $this->fixtures['users'][0]['email'];
    $plainPassword = 'this is a new plain password';

    $this->userModel->setUserName($userName);
    $this->userModel->setEmail($email);
    $this->userModel->setPlainPassword($plainPassword);

    $exceptionThrown = false;
    $exceptionMsg = '';
    try {
      $this->userModel->register();
    } catch (\Exception $e) {
      $exceptionThrown = true;
      $exceptionMsg = $e->getMessage();
    }

    $this->assertEquals(true, $exceptionThrown);
    $this->assertEquals("1062 Duplicate entry '${email}' for key 'email'", $exceptionMsg);

    $this->assertEquals(0, count($this->getSpool()->getMessages()));

    $pdo = parent::getPdo();
    $query = $pdo->query('SELECT * FROM users ORDER BY id DESC');
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(count($this->fixtures['users']), count($resultSet));
  }

  public function testActivateIsUpdatingTheDbWhenAUserVisitsAValidActivationLink() {
    $email = $this->fixtures['users'][0]['email'];
    $this->userModel->setEmail($email);

    $this->userModel->activate('3219b5fdbd5899cbcc97d7e7ad522b91', $this->container->get('router'));

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT isActive, activationHash, activationHashGenTs FROM users WHERE email = '${email}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(1, $resultSet[0]['isActive']);
    $this->assertEquals(null, $resultSet[0]['activationHash']);
    $this->assertEquals(null, $resultSet[0]['activationHashGenTs']);
  }

  public function testActivateDoesNothingIfThereIsNoInactiveUserWithTheProvidedEmailTokenPair() {
    $email = $this->fixtures['users'][1]['email'];
    $this->userModel->setEmail($email);

    $exceptionThrown = false;
    $exceptionMsg = '';
    try {
      $this->userModel->activate('3219b5fdbd5899cbcc97d7e7ad522b91', $this->container->get('router'));
    } catch (\Exception $e) {
      $exceptionThrown = true;
      $exceptionMsg = $e->getMessage();
    }
    $this->assertEquals(true, $exceptionThrown);
    $this->assertEquals("There is no inactive user with email '${email}'.", $exceptionMsg);

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT * FROM users WHERE email = '${email}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals($this->fixtures['users'][1]['id'], $resultSet[0]['id']);
    $this->assertEquals($this->fixtures['users'][1]['userName'], $resultSet[0]['userName']);
    $this->assertEquals($this->fixtures['users'][1]['email'], $resultSet[0]['email']);
    $this->assertEquals($this->fixtures['users'][1]['password'], $resultSet[0]['password']);
    $this->assertEquals($this->fixtures['users'][1]['isActive'], $resultSet[0]['isActive']);
    $this->assertEquals($this->fixtures['users'][1]['roleId'], $resultSet[0]['roleId']);
    $this->assertEquals($this->fixtures['users'][1]['activationHash'], $resultSet[0]['activationHash']);
    $this->assertEquals($this->fixtures['users'][1]['activationHashGenTs'], $resultSet[0]['activationHashGenTs']);
    $this->assertEquals($this->fixtures['users'][1]['pwResetHash'], $resultSet[0]['pwResetHash']);
    $this->assertEquals($this->fixtures['users'][1]['pwResetHashGenTs'], $resultSet[0]['pwResetHashGenTs']);
    $this->assertEquals($this->fixtures['users'][1]['created'], $resultSet[0]['created']);
  }

  public function testActivateDoesNothingIfTheTokenDoesntMatchTheHashInTheDb() {
    $email = $this->fixtures['users'][0]['email'];
    $this->userModel->setEmail($email);

    $exceptionThrown = false;
    $exceptionMsg = '';
    try {
      $this->userModel->activate('3219b5fdbd5899cbInvalidtoken', $this->container->get('router'));
    } catch (\Exception $e) {
      $exceptionThrown = true;
      $exceptionMsg = $e->getMessage();
    }
    $this->assertEquals(true, $exceptionThrown);
    $this->assertEquals("The activation token provided for the email '${email}' is not correct.", $exceptionMsg);

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT * FROM users WHERE email = '${email}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals($this->fixtures['users'][0]['id'], $resultSet[0]['id']);
    $this->assertEquals($this->fixtures['users'][0]['userName'], $resultSet[0]['userName']);
    $this->assertEquals($this->fixtures['users'][0]['email'], $resultSet[0]['email']);
    $this->assertEquals($this->fixtures['users'][0]['password'], $resultSet[0]['password']);
    $this->assertEquals($this->fixtures['users'][0]['isActive'], $resultSet[0]['isActive']);
    $this->assertEquals($this->fixtures['users'][0]['roleId'], $resultSet[0]['roleId']);
    $this->assertEquals($this->fixtures['users'][0]['activationHash'], $resultSet[0]['activationHash']);
    $this->assertEquals($this->fixtures['users'][0]['activationHashGenTs'], $resultSet[0]['activationHashGenTs']);
    $this->assertEquals($this->fixtures['users'][0]['pwResetHash'], $resultSet[0]['pwResetHash']);
    $this->assertEquals($this->fixtures['users'][0]['pwResetHashGenTs'], $resultSet[0]['pwResetHashGenTs']);
    $this->assertEquals($this->fixtures['users'][0]['created'], $resultSet[0]['created']);
  }

  public function testActivateGeneratesANewActivationTokenAndSendANewActivationEmailIfTheProvidedTokenIsExpired() {
    $email = $this->fixtures['users'][3]['email'];
    $this->userModel->setEmail($email);

    $exceptionThrown = false;
    $exceptionMsg = '';
    try {
      $this->userModel->activate('3219b5fdbd5899cbcc97d7e7ad522b91', $this->container->get('router'));
    } catch (\Exception $e) {
      $exceptionThrown = true;
      $exceptionMsg = $e->getMessage();
    }
    $this->assertEquals(true, $exceptionThrown);
    $this->assertEquals('[error]The activation link used is expired. A new activation link was sent to this account\'s email address.', $exceptionMsg);

    $emails = $this->getEmails();
    
    $this->assertEquals(1, count($emails));
    $this->assertEquals($email, key($emails[0]->getTo()));

    $matches = [];
    preg_match(
      '/<a href="http:\/\/localhost\/activation\?e=([^&]+)&t=([^&]+)">/',
      $emails[0]->getBody()->getContent(),
      $matches
    );

    $this->assertEquals($email, urldecode($matches[1]));

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT activationHash, activationHashGenTs FROM users WHERE email = '${email}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(true, password_verify(urldecode($matches[2]), $resultSet[0]['activationHash']));
    $this->assertNotNull($resultSet[0]['activationHashGenTs']);
  }

  public function testRegenactivationtokenIsUpdatingTheDbWithTheNewActivationTokenHash() {
    $email = $this->fixtures['users'][0]['email'];
    $this->userModel->setEmail($email);

    $this->userModel->regenActivationToken();

    $emails = $this->getEmails();

    $this->assertEquals(1, count($emails));
    $this->assertEquals($email, key($emails[0]->getTo()));

    $matches = [];
    preg_match(
      '/<a href="http:\/\/localhost\/activation\?e=([^&]+)&t=([^&]+)">/',
      $emails[0]->getBody()->getContent(),
      $matches
    );

    $this->assertEquals($email, urldecode($matches[1]));

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT isActive, activationHash, activationHashGenTs FROM users WHERE email = '${email}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(0, $resultSet[0]['isActive']);
    $this->assertEquals(true, password_verify(urldecode($matches[2]), $resultSet[0]['activationHash']));
    $this->assertNotNull($resultSet[0]['activationHashGenTs']);
  }

  public function testRegenactivationtokenDoesNothingIfTheUserAccountIsActive() {
    $email = $this->fixtures['users'][1]['email'];
    $this->userModel->setEmail($email);

    $exceptionThrown = false;
    $exceptionMsg = '';

    try {
      $this->userModel->regenActivationToken();
    } catch (\Exception $e) {
      $exceptionThrown = true;
      $exceptionMsg = $e->getMessage();
    }
    
    $this->assertEquals(true, $exceptionThrown);
    $this->assertEquals("No user was found in the DB with email '${email}' and with an activation token.", $exceptionMsg);

    $this->assertEquals(0, count($this->getSpool()->getMessages()));
    
    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT isActive, activationHash, activationHashGenTs FROM users WHERE email = '${email}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals($this->fixtures['users'][1]['isActive'], $resultSet[0]['isActive']);
    $this->assertEquals($this->fixtures['users'][1]['activationHash'], $resultSet[0]['activationHash']);
    $this->assertEquals($this->fixtures['users'][1]['activationHashGenTs'], $resultSet[0]['activationHashGenTs']);
  }

  public function testInitpwresetprocessIsUpdatingTheDbWithTheNewPwResetTokenHash() {
    $email = $this->fixtures['users'][1]['email'];
    $this->userModel->setEmail($email);

    $this->userModel->initPwResetProcess();

    $emails = $this->getEmails();

    $this->assertEquals(1, count($emails));
    $this->assertEquals($email, key($emails[0]->getTo()));

    $matches = [];
    preg_match(
      '/<a href="http:\/\/localhost\/password-reset\?e=([^&]+)&t=([^&]+)">/',
      $emails[0]->getBody()->getContent(),
      $matches
    );

    $this->assertEquals($email, urldecode($matches[1]));

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = '${email}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(true, password_verify(urldecode($matches[2]), $resultSet[0]['pwResetHash']));
    $this->assertNotNull($resultSet[0]['pwResetHashGenTs']);
  }

  public function testInitpwresetprocessThrowsAnExceptionIfTheUserAccountIsNotActive() {
    $email = $this->fixtures['users'][0]['email'];
    $this->userModel->setEmail($email);

    $exceptionThrown = false;
    $exceptionMsg = '';
    try {
      $this->userModel->initPwResetProcess();
    } catch (\Exception $e) {
      $exceptionThrown = true;
      $exceptionMsg = $e->getMessage();
    }
    $this->assertEquals(true, $exceptionThrown);
    $this->assertEquals("No user was found in the DB with email '${email}' with an active account.", $exceptionMsg);

    $emails = $this->getEmails();
    $this->assertEquals(0, count($emails));

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = '${email}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(1, count($resultSet));
    $this->assertNull($resultSet[0]['pwResetHash']);
    $this->assertNull($resultSet[0]['pwResetHashGenTs']);
  }

  public function testResetpwIsUpdatingTheUserPasswordInTheDb() {
    $email = $this->fixtures['users'][2]['email'];
    $plainPassword = 'newPlainpassword';
    $this->userModel->setEmail($email);
    $this->userModel->setPlainPassword($plainPassword);

    $this->userModel->resetPw('605e520b4586e991712b12720cd571b6');

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT password, pwResetHash, pwResetHashGenTs FROM users WHERE email = '${email}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(true, password_verify($plainPassword, $resultSet[0]['password']));
    $this->assertEquals(null, $resultSet[0]['pwResetHash']);
    $this->assertEquals(null, $resultSet[0]['pwResetHashGenTs']);
  }

  public function testResetpwThrowsAnExceptionIfTheUserWithTheProvidedEmailHasntInitiatedThePasswordResetProcess() {
    $email = $this->fixtures['users'][1]['email'];
    $plainPassword = 'newPlainpassword';
    $this->userModel->setEmail($email);
    $this->userModel->setPlainPassword($plainPassword);

    $exceptionThrown = false;
    $exceptionMsg = '';
    try {
      $this->userModel->resetPw('605e520b4586e991712b12720cd571b6');
    } catch (\Exception $e) {
      $exceptionThrown = true;
      $exceptionMsg = $e->getMessage();
    }
    $this->assertEquals(true, $exceptionThrown);
    $this->assertEquals("No user was found in the DB with email '${email}' and with a password reset token.", $exceptionMsg);

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT password, pwResetHash, pwResetHashGenTs FROM users WHERE email = '${email}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals($this->fixtures['users'][1]['password'], $resultSet[0]['password']);
    $this->assertEquals($this->fixtures['users'][1]['pwResetHash'], $resultSet[0]['pwResetHash']);
    $this->assertEquals($this->fixtures['users'][1]['pwResetHashGenTs'], $resultSet[0]['pwResetHashGenTs']);
  }

  public function testResetpwThrowsAnExceptionIfTheTokenDoesntMatchTheHashInTheDb() {
    $email = $this->fixtures['users'][2]['email'];
    $plainPassword = 'newPlainpassword';
    $this->userModel->setEmail($email);
    $this->userModel->setPlainPassword($plainPassword);

    $exceptionThrown = false;
    $exceptionMsg = '';
    try {
      $this->userModel->resetPw('605e520b4586e991712b12720cd571b6NotvalidPart');
    } catch (\Exception $e) {
      $exceptionThrown = true;
      $exceptionMsg = $e->getMessage();
    }
    $this->assertEquals(true, $exceptionThrown);
    $this->assertEquals("The password reset token provided for the email '${email}' is not correct.", $exceptionMsg);

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT password, pwResetHash, pwResetHashGenTs FROM users WHERE email = '${email}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals($this->fixtures['users'][2]['password'], $resultSet[0]['password']);
    $this->assertEquals($this->fixtures['users'][2]['pwResetHash'], $resultSet[0]['pwResetHash']);
    $this->assertEquals($this->fixtures['users'][2]['pwResetHashGenTs'], $resultSet[0]['pwResetHashGenTs']);
  }

  public function testResetpwDeletesTheTokenAndThrowsATokenexpiredexceptionIfTheTokenIsExpired() {
    $email = $this->fixtures['users'][4]['email'];
    $plainPassword = 'newPlainpassword';
    $this->userModel->setEmail($email);
    $this->userModel->setPlainPassword($plainPassword);

    $exceptionClass = '';
    $exceptionMsg = '';
    try {
      $this->userModel->resetPw('605e520b4586e991712b12720cd571b6');
    } catch (\Exception $e) {
      $exceptionClass = get_class($e);
      $exceptionMsg = $e->getMessage();
    }
    $this->assertEquals(TokenExpiredException::class, $exceptionClass);
    $this->assertEquals("The password reset token provided for the email '${email}' is expired.", $exceptionMsg);

    $pdo = parent::getPdo();
    $query = $pdo->query("SELECT password, pwResetHash, pwResetHashGenTs FROM users WHERE email = '${email}'");
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals($this->fixtures['users'][4]['password'], $resultSet[0]['password']);
    $this->assertEquals(null, $resultSet[0]['pwResetHash']);
    $this->assertEquals(null, $resultSet[0]['pwResetHashGenTs']);
  }
}