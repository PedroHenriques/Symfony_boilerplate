<?php

namespace tests\integration\AppBundle\Services\Security;

require_once(dirname(dirname(dirname(__DIR__))).'/BaseIntegrationCase.php');

use tests\integration\BaseIntegrationCase;
use AppBundle\Services\Security\UserProvider;
use AppBundle\Model\{UserModel, UserModelFactory};
use AppBundle\Services\{Utils, EmailHandler};

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProviderTest extends BaseIntegrationCase {
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
          'id' => 1, 'userName' => 'activated test username', 'email' => 'test@test.com',
          'password' => '$2y$15$/.9tL5uq.t.BjAxsHGn5feQG4gxe7fh8BZVgj.EN1AW4sKg4RHywW',
          'isActive' => 1, 'roleId' => 1, 'activationHash' => null,
          'activationHashGenTs' => null, 'pwResetHash' => null, 'pwResetHashGenTs' => null, 'created' => time()-86400
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
    $container = $client->getContainer();

    $dbHandler = $container->get('service_container')->get('AppBundle\Services\DbInterface');
    $utils = new Utils();
    $emailHandler = new EmailHandler(
      $this->createCustomMailer(),
      new \Symfony\Bundle\TwigBundle\TwigEngine(
        $container->get('service_container')->get('twig'),
        new \Symfony\Component\Templating\TemplateNameParser(),
        new \Symfony\Component\Config\FileLocator()
      ),
      $container
    );
    $encoder = $container->get('security.password_encoder');

    $userModelFactory = new UserModelFactory($dbHandler, $utils, $emailHandler, $encoder, $container);
    $this->userProvider = new UserProvider($userModelFactory);
  }

  public function testLoaduserbyusernameIsCreatingAUserModelInstanceFromAnEmailAddressAndReturnsIt() {
    $actualValue = $this->userProvider->loadUserByUsername($this->fixtures['users'][0]['email']);

    $this->assertEquals(UserModel::class, get_class($actualValue));
  }

  public function testLoaduserbyusernameIsCreatingAUserModelInstanceFromAUsernameAddressAndReturnsIt() {
    $actualValue = $this->userProvider->loadUserByUsername($this->fixtures['users'][0]['userName']);

    $this->assertEquals(UserModel::class, get_class($actualValue));
  }

  public function testLoaduserbyusernameThrowsAUsernamenotfoundexceptionIfTheProvidedUsernameIsNotValid() {
    $this->expectException(UsernameNotFoundException::class);
    $this->expectExceptionMessage("userName 'invalid username' does not exist.");

    $this->userProvider->loadUserByUsername('invalid username');
  }

  public function testLoaduserbyusernameThrowsAUsernamenotfoundexceptionIfTheProvidedEmailIsNotValid() {
    $this->expectException(UsernameNotFoundException::class);
    $this->expectExceptionMessage("email 'invalid@email.com' does not exist.");

    $this->userProvider->loadUserByUsername('invalid@email.com');
  }
}