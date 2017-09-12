<?php

namespace tests\unit\AppBundle\Model;

use AppBundle\Model\UserModel;
use AppBundle\Services\{DbInterface, EmailInterface, Utils};

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Routing\RouterInterface;

class UserModelTest extends TestCase {
  protected function setUp() {
    parent::setUp();

    $this->dbInterfaceMock = $this->createMock(DbInterface::class);
    $this->emailInterfaceMock = $this->createMock(EmailInterface::class);
    $this->encoderMock = $this->createMock(UserPasswordEncoderInterface::class);
    $this->containerMock = $this->createMock(CustomUserModelContainer::class);
    $this->routerInterfaceMock = $this->createMock(RouterInterface::class);
  }

  public function testPopulatefromdbItShouldQueryTheDbBasedOnTheProvidedBindDataThenPerformAnyNecessaryDataTransformationsThenCallPopulatefromarrayAndReturnVoid() {
    $uniqueCol = 'email';
    $uniqueId = 'test@test.com';
    $bindData = [$uniqueCol => [$uniqueId, \PDO::PARAM_STR]];

    $query = 'SELECT u.id, u.userName, u.email, u.password, u.isActive, u.created, ur.role '.
      "FROM users as u JOIN users_roles as ur ON ur.id = u.roleId WHERE u.${uniqueCol} = :${uniqueCol}";
    $paramData = [$bindData];

    $created = time();

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        "SELECT u.id, u.userName, u.email, u.password, u.isActive, u.created, ur.role FROM users as u JOIN users_roles as ur ON ur.id = u.roleId WHERE u.${uniqueCol} = :${uniqueCol}",
        [$bindData]
      )
      ->willReturn([[[
        'id' => '2',
        'userName' => 'test',
        'email' => 'test@test.com',
        'password' => 'testpassword',
        'isActive' => '1',
        'role' => 'ROLE_USER',
        'created' => $created
      ]]]);
    
    $userModel = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $actualValue = $userModel->populateFromDb($bindData);

    $this->assertEquals(null, $actualValue);
    $this->assertEquals(2, $userModel->getId());
    $this->assertEquals('test', $userModel->getUserName());
    $this->assertEquals('test@test.com', $userModel->getEmail());
    $this->assertEquals('testpassword', $userModel->getPassword());
    $this->assertEquals(1, $userModel->getIsActive());
    $this->assertEquals(['ROLE_USER'], $userModel->getRoles());
    $this->assertEquals($created, $userModel->getCreated());
  }

  public function testPopulatefromdbIfTheNumberOfElementsInBindDataIsntOneItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The number of elements in $bindData isn\'t valid.');

    $uniqueId = 'test@test.com';
    $bindData = [
      'email' => [$uniqueId, \PDO::PARAM_STR],
      'anotherCol' => [$uniqueId, \PDO::PARAM_STR],
    ];
    
    $userModel = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $userModel->populateFromDb($bindData);
  }

  public function testPopulatefromdbIfDbinterfaceSelectThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $uniqueCol = 'email';
    $uniqueId = 'test@test.com';
    $bindData = [$uniqueCol => [$uniqueId, \PDO::PARAM_STR]];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        "SELECT u.id, u.userName, u.email, u.password, u.isActive, u.created, ur.role FROM users as u JOIN users_roles as ur ON ur.id = u.roleId WHERE u.${uniqueCol} = :${uniqueCol}",
        [$bindData]
      )
      ->will($this->throwException(new \Exception));
    
    $userModel = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $userModel->populateFromDb($bindData);

    $this->assertEquals(null, $userModel->getId());
    $this->assertEquals(null, $userModel->getUserName());
    $this->assertEquals(null, $userModel->getEmail());
    $this->assertEquals(null, $userModel->getPassword());
    $this->assertEquals(null, $userModel->getIsActive());
    $this->assertEquals(null, $userModel->getRoles());
    $this->assertEquals(null, $userModel->getCreated());
  }

  public function testPopulatefromdbIfDbinterfaceSelectDoesntReturnExactlyOneRowItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Unable to select this user\'s data from the database.');

    $uniqueCol = 'email';
    $uniqueId = 'test@test.com';
    $bindData = [$uniqueCol => [$uniqueId, \PDO::PARAM_STR]];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        "SELECT u.id, u.userName, u.email, u.password, u.isActive, u.created, ur.role FROM users as u JOIN users_roles as ur ON ur.id = u.roleId WHERE u.${uniqueCol} = :${uniqueCol}",
        [$bindData]
      )
      ->willReturn([[]]);
    
    $userModel = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $userModel->populateFromDb($bindData);

    $this->assertEquals(null, $userModel->getId());
    $this->assertEquals(null, $userModel->getUserName());
    $this->assertEquals(null, $userModel->getEmail());
    $this->assertEquals(null, $userModel->getPassword());
    $this->assertEquals(null, $userModel->getIsActive());
    $this->assertEquals(null, $userModel->getRoles());
    $this->assertEquals(null, $userModel->getCreated());
  }

  public function testPopulatefromdbIfTheModelAlreadyHasAnIdItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('UserModel already has an ID.');

    $uniqueCol = 'email';
    $uniqueId = 'test@test.com';
    $bindData = [$uniqueCol => [$uniqueId, \PDO::PARAM_STR]];

    $this->dbInterfaceMock->expects($this->exactly(2))
      ->method('select')
      ->with(
        "SELECT u.id, u.userName, u.email, u.password, u.isActive, u.created, ur.role FROM users as u JOIN users_roles as ur ON ur.id = u.roleId WHERE u.${uniqueCol} = :${uniqueCol}",
        [$bindData]
      )
      ->willReturn([[[
        'id' => '2',
        'userName' => 'test',
        'email' => 'test@test.com',
        'password' => 'testpassword',
        'isActive' => '1',
        'role' => 'ROLE_USER',
        'created' => time() - 86400
      ]]]);
    
    $userModel = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $userModel->populateFromDb($bindData);
    $userModel->populateFromDb($bindData);
  }

  public function testRegisterItShouldQueryTheDbForTheUsersWithTheProvidedUsernameAndEmailThenCreateTheHashedVersionOfThePlainPwAndStoringItInThePasswordPropertyThenGenerateATokenAndHashItThenInsertTheUserIntoTheDbThenSendTheActivationEmailAndReturnABoolean() {
    $userName = 'test username';
    $email = 'test@test.com';
    $plainPw = 'test plain password';
    $hashedPw = 'hashedtestpassword';
    $created = time();

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->setCreated($created);

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        '(select count(id) as count from users where userName=:userName) union all (select count(id) as count from users where email=:email)',
        [[
          'userName' => [$userName, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('changeFromModel')
      ->with(
        $this->matchesRegularExpression(
          "/^INSERT INTO users VALUES\(null,:userName,:email,:password,0,1,'[[:ascii:]]+',\d+,null,null,:created\)$/"
        ),
        ['userName', 'email', 'password', 'created'],
        [$user],
        false
      )
      ->willReturn([1]);
    
    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($email, $this->matchesRegularExpression('/^[[:ascii:]]+$/'))
      ->willReturn(true);
    
    $actualValue = $user->register();
    
    $this->assertEquals($hashedPw, $user->getPassword());

    $this->assertEquals(true, $actualValue);
  }

  public function testRegisterIfDbinterfaceSelectThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $userName = 'test username';
    $email = 'test@test.com';

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        '(select count(id) as count from users where userName=:userName) union all (select count(id) as count from users where email=:email)',
        [[
          'userName' => [$userName, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->will($this->throwException(new \Exception));
    
    $user->register();
  }

  public function testRegisterIfTheUsernameIsTakenItShouldThrowAnExceptionWithASpecificText() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("1062 Duplicate entry 'test username' for key 'userName'");

    $userName = 'test username';
    $email = 'test@test.com';

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        '(select count(id) as count from users where userName=:userName) union all (select count(id) as count from users where email=:email)',
        [[
          'userName' => [$userName, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->WillReturn([[
        ['count' => 1],
        ['count' => 0],
      ]]);
    
    $user->register();
  }

  public function testRegisterIfTheEmailIsTakenItShouldThrowAnExceptionWithASpecificText() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("1062 Duplicate entry 'test@test.com' for key 'email'");

    $userName = 'test username';
    $email = 'test@test.com';

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        '(select count(id) as count from users where userName=:userName) union all (select count(id) as count from users where email=:email)',
        [[
          'userName' => [$userName, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->WillReturn([[
        ['count' => 0],
        ['count' => 1],
      ]]);
    
    $user->register();
  }

  public function testRegisterIfTheUsernameAndEmailAreTakenItShouldThrowAnExceptionWithASpecificText() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("1062 Duplicate entry 'test username' for key 'userName'");

    $userName = 'test username';
    $email = 'test@test.com';

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        '(select count(id) as count from users where userName=:userName) union all (select count(id) as count from users where email=:email)',
        [[
          'userName' => [$userName, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->WillReturn([[
        ['count' => 1],
        ['count' => 1],
      ]]);
    
    $user->register();
  }

  public function testRegisterIfTheEncoderThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('encodePassword test exception msg');

    $userName = 'test username';
    $email = 'test@test.com';
    $plainPw = 'test plain password';

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        '(select count(id) as count from users where userName=:userName) union all (select count(id) as count from users where email=:email)',
        [[
          'userName' => [$userName, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->will($this->throwException(new \Exception('encodePassword test exception msg')));

    $user->register();
  }

  public function testRegisterIfTheInsertQueryThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $userName = 'test username';
    $email = 'test@test.com';
    $plainPw = 'test plain password';
    $hashedPw = 'hashedtestpassword';
    $created = time();

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->setCreated($created);

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        '(select count(id) as count from users where userName=:userName) union all (select count(id) as count from users where email=:email)',
        [[
          'userName' => [$userName, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);

    $this->dbInterfaceMock->expects($this->once())
      ->method('changeFromModel')
      ->with(
        $this->matchesRegularExpression(
          "/^INSERT INTO users VALUES\(null,:userName,:email,:password,0,1,'[[:ascii:]]+',\d+,null,null,:created\)$/"
        ),
        ['userName', 'email', 'password', 'created'],
        [$user],
        false
      )
      ->will($this->throwException(new \Exception));
    
    $user->register();
  }

  public function testRegisterIfTheInsertQueryFailsItShouldThrowAnException() {
    $this->expectException(\Exception::class);

    $userName = 'test username';
    $email = 'test@test.com';
    $plainPw = 'test plain password';
    $hashedPw = 'hashedtestpassword';
    $created = time();

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->setCreated($created);

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        '(select count(id) as count from users where userName=:userName) union all (select count(id) as count from users where email=:email)',
        [[
          'userName' => [$userName, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('changeFromModel')
      ->with(
        $this->matchesRegularExpression(
          "/^INSERT INTO users VALUES\(null,:userName,:email,:password,0,1,'[[:ascii:]]+',\d+,null,null,:created\)$/"
        ),
        ['userName', 'email', 'password', 'created'],
        [$user],
        false
      )
      ->willReturn([null]);
    
    $user->register();
  }

  public function testRegisterIfTheActivationEmailFailsToBeSentItShouldReturnFalse() {
    $userName = 'test username';
    $email = 'test@test.com';
    $plainPw = 'test plain password';
    $hashedPw = 'hashedtestpassword';
    $created = time();

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->setCreated($created);

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        '(select count(id) as count from users where userName=:userName) union all (select count(id) as count from users where email=:email)',
        [[
          'userName' => [$userName, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('changeFromModel')
      ->with(
        $this->matchesRegularExpression(
          "/^INSERT INTO users VALUES\(null,:userName,:email,:password,0,1,'[[:ascii:]]+',\d+,null,null,:created\)$/"
        ),
        ['userName', 'email', 'password', 'created'],
        [$user],
        false
      )
      ->willReturn([1]);
    
    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($email, $this->matchesRegularExpression('/^[[:ascii:]]+$/'))
      ->willReturn(false);
    
    $actualValue = $user->register();
    
    $this->assertEquals($hashedPw, $user->getPassword());
    $this->assertEquals(false, $actualValue);
  }

  public function testRegisterIfSendingTheActivationEmailThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $userName = 'test username';
    $email = 'test@test.com';
    $plainPw = 'test plain password';
    $hashedPw = 'hashedtestpassword';
    $created = time();

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->setCreated($created);

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        '(select count(id) as count from users where userName=:userName) union all (select count(id) as count from users where email=:email)',
        [[
          'userName' => [$userName, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('changeFromModel')
      ->with(
        $this->matchesRegularExpression(
          "/^INSERT INTO users VALUES\(null,:userName,:email,:password,0,1,'[[:ascii:]]+',\d+,null,null,:created\)$/"
        ),
        ['userName', 'email', 'password', 'created'],
        [$user],
        false
      )
      ->willReturn([1]);
    
    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($email, $this->matchesRegularExpression('/^[[:ascii:]]+$/'))
      ->will($this->throwException(new \Exception));
    
    $user->register();
  }

  public function testActivateItShouldQueryTheDbForAnInactiveUserWithTheProvidedEmailThenCheckIfTheProvidedTokenIsActiveAndIsValidThenActivateTheUsersAccountInTheDbAndReturnVoid() {
    $expectedEmail = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'activationHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'activationHashGenTs' => time() - 24*3600,
      ]]]);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn(72);

    $query = 'UPDATE users SET isActive = 1, activationHash = null, activationHashGenTs = null WHERE email = :email';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([1]);
    
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $actualValue = $user->activate('supersecrettoken', $this->routerInterfaceMock);

    $this->assertEquals(null, $actualValue);
  }

  public function testActivateIfNoInactiveUserIsFoundItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("There is no inactive user with email 'test@test.com'.");

    $expectedEmail = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[]]);
    
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate('supersecrettoken', $this->routerInterfaceMock);
  }

  public function testActivateIfDbinterfaceSelectThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('select test exception msg');

    $expectedEmail = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->will($this->throwException(new \Exception('select test exception msg')));
    
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate('supersecrettoken', $this->routerInterfaceMock);
  }

  public function testActivateIfTheTokenIsNotValidItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("The activation token provided for the email 'test@test.com' is not correct.");

    $expectedEmail = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'activationHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'activationHashGenTs' => time() - 24*3600,
      ]]]);
    
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate('invalidsupersecrettoken', $this->routerInterfaceMock);
  }

  public function testActivateIfContainerGetparameterThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('getParameter test exception msg');

    $expectedEmail = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'activationHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'activationHashGenTs' => time() - 90*3600,
      ]]]);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->will($this->throwException(new \Exception('getParameter test exception msg')));
    
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate('supersecrettoken', $this->routerInterfaceMock);
  }

  public function testActivateIfTheQueryToActivateTheUserFailsItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Failed to UPDATE the user with email 'test@test.com' with the active account values.");

    $expectedEmail = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'activationHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'activationHashGenTs' => time() - 24*3600,
      ]]]);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn(72);

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET isActive = 1, activationHash = null, activationHashGenTs = null WHERE email = :email',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([0]);
    
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate('supersecrettoken', $this->routerInterfaceMock);
  }

  public function testActivateIfDbinterfaceChangeThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $expectedEmail = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'activationHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'activationHashGenTs' => time() - 24*3600,
      ]]]);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn(72);

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET isActive = 1, activationHash = null, activationHashGenTs = null WHERE email = :email',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->will($this->throwException(new \Exception));
    
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate('supersecrettoken', $this->routerInterfaceMock);
  }

  public function testActivateIfTheTokenIsExpiredItShouldGenerateAndProcessANewTokenAndThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The activation link used is expired. A new activation link was sent to this account\'s email address.');

    $expectedEmail = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'activationHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'activationHashGenTs' => time() - 90*3600,
      ]]]);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn(72);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET isActive = 0, activationHash = :hash, activationHashGenTs = :hashTs WHERE email = :email',
        $this->callback(function($subject) use ($expectedEmail) {
          return(
            (count($subject) === 1) &&
            (['hash', 'hashTs', 'email'] == array_keys($subject[0])) &&
            (preg_match('/^[[:ascii:]]+$/', $subject[0]['hash'][0]) === 1) &&
            ($subject[0]['hash'][1] === \PDO::PARAM_STR) &&
            (preg_match('/^\d+$/', $subject[0]['hashTs'][0]) === 1) &&
            ($subject[0]['hashTs'][1] === \PDO::PARAM_INT) &&
            ($expectedEmail === $subject[0]['email'][0]) &&
            ($subject[0]['email'][1] === \PDO::PARAM_STR)
          );
        })
      )
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($expectedEmail, $this->matchesRegularExpression('/^[[:ascii:]]+$/'))
      ->willReturn(true);
    
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate('supersecrettoken', $this->routerInterfaceMock);
  }

  public function testActivateIfTheTokenIsExpiredAndTheDbFailsToBeUpdatedItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(
      'The activation link used is expired. An attempt was made to send a new activation link to '.
      'this account\'s email address, but the email could not be sent. Please <a href="resendLink">click here</a> to resend the activation email.'
    );

    $expectedEmail = 'test@test.com';
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'activationHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'activationHashGenTs' => time() - 90*3600,
      ]]]);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn(72);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET isActive = 0, activationHash = :hash, activationHashGenTs = :hashTs WHERE email = :email',
        $this->callback(function($subject) use ($expectedEmail) {
          return(
            (count($subject) === 1) &&
            (['hash', 'hashTs', 'email'] == array_keys($subject[0])) &&
            (preg_match('/^[[:ascii:]]+$/', $subject[0]['hash'][0]) === 1) &&
            ($subject[0]['hash'][1] === \PDO::PARAM_STR) &&
            (preg_match('/^\d+$/', $subject[0]['hashTs'][0]) === 1) &&
            ($subject[0]['hashTs'][1] === \PDO::PARAM_INT) &&
            ($expectedEmail === $subject[0]['email'][0]) &&
            ($subject[0]['email'][1] === \PDO::PARAM_STR)
          );
        })
      )
      ->will($this->throwException(new \Exception));

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('resendActivation', ['e' => $expectedEmail])
      ->willReturn('resendLink');
    
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate('supersecrettoken', $this->routerInterfaceMock);
  }

  public function testActivateIfTheTokenIsExpiredAndTheEmailFailsToSendItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(
      'The activation link used is expired. An attempt was made to send a new activation link to '.
      'this account\'s email address, but the email could not be sent. Please <a href="resendLink">click here</a> to resend the activation email.'
    );

    $expectedEmail = 'test@test.com';
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'activationHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'activationHashGenTs' => time() - 90*3600,
      ]]]);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn(72);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET isActive = 0, activationHash = :hash, activationHashGenTs = :hashTs WHERE email = :email',
        $this->callback(function($subject) use ($expectedEmail) {
          return(
            (count($subject) === 1) &&
            (['hash', 'hashTs', 'email'] == array_keys($subject[0])) &&
            (preg_match('/^[[:ascii:]]+$/', $subject[0]['hash'][0]) === 1) &&
            ($subject[0]['hash'][1] === \PDO::PARAM_STR) &&
            (preg_match('/^\d+$/', $subject[0]['hashTs'][0]) === 1) &&
            ($subject[0]['hashTs'][1] === \PDO::PARAM_INT) &&
            ($expectedEmail === $subject[0]['email'][0]) &&
            ($subject[0]['email'][1] === \PDO::PARAM_STR)
          );
        })
      )
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($expectedEmail, $this->matchesRegularExpression('/^[[:ascii:]]+$/'))
      ->willReturn(false);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('resendActivation', ['e' => $expectedEmail])
      ->willReturn('resendLink');
    
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate('supersecrettoken', $this->routerInterfaceMock);
  }

  public function testActivateIfIfTheTokenIsExpiredAndTheProcessingOfANewTokenFailsAndTheRouterThrowsAnExceptionToSendItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('router exception message bubbled up');

    $expectedEmail = 'test@test.com';
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'activationHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'activationHashGenTs' => time() - 90*3600,
      ]]]);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn(72);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET isActive = 0, activationHash = :hash, activationHashGenTs = :hashTs WHERE email = :email',
        $this->callback(function($subject) use ($expectedEmail) {
          return(
            (count($subject) === 1) &&
            (['hash', 'hashTs', 'email'] == array_keys($subject[0])) &&
            (preg_match('/^[[:ascii:]]+$/', $subject[0]['hash'][0]) === 1) &&
            ($subject[0]['hash'][1] === \PDO::PARAM_STR) &&
            (preg_match('/^\d+$/', $subject[0]['hashTs'][0]) === 1) &&
            ($subject[0]['hashTs'][1] === \PDO::PARAM_INT) &&
            ($expectedEmail === $subject[0]['email'][0]) &&
            ($subject[0]['email'][1] === \PDO::PARAM_STR)
          );
        })
      )
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($expectedEmail, $this->matchesRegularExpression('/^[[:ascii:]]+$/'))
      ->willReturn(false);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('resendActivation', ['e' => $expectedEmail])
      ->will($this->throwException(new \Exception('router exception message bubbled up')));
    
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate('supersecrettoken', $this->routerInterfaceMock);
  }

  public function testRegenactivationtokenItShouldCheckThatTheUserAccountIsInactiveThenProcessANewActivationTokenAndReturnVoid() {
    $expectedEmail = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT count(id) as count FROM users WHERE email = :email AND activationHash IS NOT NULL',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[['count' => 1]]]);

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET isActive = 0, activationHash = :hash, activationHashGenTs = :hashTs WHERE email = :email',
        $this->callback(function($subject) use ($expectedEmail) {
          return(
            (count($subject) === 1) &&
            (['hash', 'hashTs', 'email'] == array_keys($subject[0])) &&
            (preg_match('/^[[:ascii:]]+$/', $subject[0]['hash'][0]) === 1) &&
            ($subject[0]['hash'][1] === \PDO::PARAM_STR) &&
            (preg_match('/^\d+$/', $subject[0]['hashTs'][0]) === 1) &&
            ($subject[0]['hashTs'][1] === \PDO::PARAM_INT) &&
            ($expectedEmail === $subject[0]['email'][0]) &&
            ($subject[0]['email'][1] === \PDO::PARAM_STR)
          );
        })
      )
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($expectedEmail, $this->matchesRegularExpression('/^[[:ascii:]]+$/'))
      ->willReturn(true);

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $actualValue = $user->regenActivationToken();

    $this->assertEquals(null, $actualValue);
  }

  public function testRegenactivationtokenIfTheUserAccountIsActiveItShouldThrowAnExceptionWithAnErrorMsg() {
    $expectedEmail = 'test@test.com';

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(
      "No user was found in the DB with email '${expectedEmail}' and with an activation token."
    );

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT count(id) as count FROM users WHERE email = :email AND activationHash IS NOT NULL',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[['count' => 0]]]);

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->regenActivationToken();
  }

  public function testRegenactivationtokenIfTheProcessingOfTheNewTokenThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $expectedEmail = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT count(id) as count FROM users WHERE email = :email AND activationHash IS NOT NULL',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[['count' => 1]]]);

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET isActive = 0, activationHash = :hash, activationHashGenTs = :hashTs WHERE email = :email',
        $this->callback(function($subject) use ($expectedEmail) {
          return(
            (count($subject) === 1) &&
            (['hash', 'hashTs', 'email'] == array_keys($subject[0])) &&
            (preg_match('/^[[:ascii:]]+$/', $subject[0]['hash'][0]) === 1) &&
            ($subject[0]['hash'][1] === \PDO::PARAM_STR) &&
            (preg_match('/^\d+$/', $subject[0]['hashTs'][0]) === 1) &&
            ($subject[0]['hashTs'][1] === \PDO::PARAM_INT) &&
            ($expectedEmail === $subject[0]['email'][0]) &&
            ($subject[0]['email'][1] === \PDO::PARAM_STR)
          );
        })
      )
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($expectedEmail, $this->matchesRegularExpression('/^[[:ascii:]]+$/'))
      ->willReturn(false);

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->regenActivationToken();
  }

  public function testInitpwresetprocessItShouldCallGentokensendemailAndReturnVoid() {
    $expectedEmail = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT id FROM users WHERE email = :email AND isActive = 1',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[['id' => '1']]]);

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET pwResetHash = :hash, pwResetHashGenTs = :hashTs WHERE email = :email',
        $this->callback(function($subject) use ($expectedEmail) {
          return(
            (count($subject) === 1) &&
            (['hash', 'hashTs', 'email'] == array_keys($subject[0])) &&
            (preg_match('/^[[:ascii:]]+$/', $subject[0]['hash'][0]) === 1) &&
            ($subject[0]['hash'][1] === \PDO::PARAM_STR) &&
            (preg_match('/^\d+$/', $subject[0]['hashTs'][0]) === 1) &&
            ($subject[0]['hashTs'][1] === \PDO::PARAM_INT) &&
            ($expectedEmail === $subject[0]['email'][0]) &&
            ($subject[0]['email'][1] === \PDO::PARAM_STR)
          );
        })
      )
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('pwResetEmail')
      ->with($expectedEmail, $this->matchesRegularExpression('/^[[:ascii:]]+$/'))
      ->willReturn(true);

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $actualValue = $user->initPwResetProcess();

    $this->assertEquals(null, $actualValue);
  }

  public function testInitpwresetprocessIfTheProcessingOfTheNewTokenThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $expectedEmail = 'test@test.com';
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT id FROM users WHERE email = :email AND isActive = 1',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[['id' => '1']]]);

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET pwResetHash = :hash, pwResetHashGenTs = :hashTs WHERE email = :email',
        $this->callback(function($subject) use ($expectedEmail) {
          return(
            (count($subject) === 1) &&
            (['hash', 'hashTs', 'email'] == array_keys($subject[0])) &&
            (preg_match('/^[[:ascii:]]+$/', $subject[0]['hash'][0]) === 1) &&
            ($subject[0]['hash'][1] === \PDO::PARAM_STR) &&
            (preg_match('/^\d+$/', $subject[0]['hashTs'][0]) === 1) &&
            ($subject[0]['hashTs'][1] === \PDO::PARAM_INT) &&
            ($expectedEmail === $subject[0]['email'][0]) &&
            ($subject[0]['email'][1] === \PDO::PARAM_STR)
          );
        })
      )
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('pwResetEmail')
      ->with($expectedEmail, $this->matchesRegularExpression('/^[[:ascii:]]+$/'))
      ->willReturn(false);

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->initPwResetProcess();
  }

  public function testInitpwresetprocessIfSelectReturnsEmptyItShouldThrowAnException() {
    $expectedEmail = 'test@test.com';

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("No user was found in the DB with email '${expectedEmail}' with an active account.");

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT id FROM users WHERE email = :email AND isActive = 1',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->willReturn([[]]);

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->initPwResetProcess();
  }

  public function testInitpwresetprocessIfSelectThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('select test exception msg');

    $expectedEmail = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT id FROM users WHERE email = :email AND isActive = 1',
        [['email' => [$expectedEmail, \PDO::PARAM_STR]]]
      )
      ->will($this->throwException(new \Exception('select test exception msg')));

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->initPwResetProcess();
  }

  public function testResetpwItShouldQueryTheDbForThePwresethashAndItsTimestampThenCheckIfTheProvidedTokenMatchesTheDbHashThenCheckIfTheLinkIsNotExpiredThenEncodeTheNewPasswordThenUpdateTheDbWithItAndReturnVoid() {
    $email = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'pwResetHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'pwResetHashGenTs' => time() - 0.5*3600,
      ]]]);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn(2);

    $plainPw = 'newpassword';
    $pwHash = '$2y$15$dAw27m4ED0vKNJVpK1WZWenichZf1b7thAEL59MHzZErgJD1J5CM6';
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($pwHash);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET password = :pw, pwResetHash = null, pwResetHashGenTs = null WHERE email = :email',
        [[
          'pw' => [$pwHash, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->willReturn([1]);

    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $actualValue = $user->resetPw('supersecrettoken');

    $this->assertEquals(null, $actualValue);
  }

  public function testResetpwIfDbinterfaceSelectThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('select test exception msg');

    $email = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->will($this->throwException(new \Exception('select test exception msg')));

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $user->setEmail($email);
    $user->setPlainPassword('newpassword');
    $user->resetPw('supersecrettoken');
  }

  public function testResetpwIfThereIsNoUserWithTheProvidedEmailOrItHasNotInitiatedThePwResetProcessItShouldThrowAnExceptionWithACustomMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("No user was found in the DB with email 'test@test.com' and with a password reset token.");

    $email = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->willReturn([[]]);

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($email);
    $user->setPlainPassword('newpassword');
    $user->resetPw('supersecrettoken');
  }

  public function testResetpwIfTheProvidedTokenDoesntMatchTheDbHashItShouldThrowAnExceptionWithACustomMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("The password reset token provided for the email 'test@test.com' is not correct.");

    $email = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'pwResetHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'pwResetHashGenTs' => time() - 0.5*3600,
      ]]]);

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $user->setEmail($email);
    $user->setPlainPassword('newpassword');
    $user->resetPw('invalidsupersecrettoken');
  }

  public function testResetpwIfTheParameterbagThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('getParameter test exception msg');

    $email = 'test@test.com';
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'pwResetHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'pwResetHashGenTs' => time() - 0.5*3600,
      ]]]);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->will($this->throwException(new \Exception('getParameter test exception msg')));
    
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($email);
    $user->resetPw('supersecrettoken');
  }

  public function testResetpwIfTheTokenIsExpiredItShouldUpdateTheDbToRemoveTheTokenAndItsTimestampAndThrowATokenExpiredExceptionWithACustomMsg() {
    $this->expectException(\AppBundle\Exceptions\TokenExpiredException::class);
    $this->expectExceptionMessage("The password reset token provided for the email 'test@test.com' is expired.");

    $email = 'test@test.com';

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'pwResetHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'pwResetHashGenTs' => time() - 4*3600,
      ]]]);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn(2);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET pwResetHash = null, pwResetHashGenTs = null WHERE email = :email',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->willReturn([1]);

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $user->setEmail($email);
    $user->setPlainPassword('newpassword');
    $user->resetPw('supersecrettoken');
  }

  public function testResetpwIfTheTokenIsExpiredAndTheQueryToRemoveTheTokenThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('change test exception msg');

    $email = 'test@test.com';
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'pwResetHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'pwResetHashGenTs' => time() - 4*3600,
      ]]]);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn(2);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET pwResetHash = null, pwResetHashGenTs = null WHERE email = :email',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->will($this->throwException(new \Exception('change test exception msg')));

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $user->setEmail($email);
    $user->setPlainPassword('newpassword');
    $user->resetPw('supersecrettoken');
  }

  public function testResetpwIfTheTokenIsExpiredAndTheQueryToRemoveTheTokenFailsItShouldIgnoreThatFailAndThrowATokenExpiredExceptionWithACustomMsg() {
    $this->expectException(\AppBundle\Exceptions\TokenExpiredException::class);
    $this->expectExceptionMessage("The password reset token provided for the email 'test@test.com' is expired.");

    $email = 'test@test.com';
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'pwResetHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'pwResetHashGenTs' => time() - 4*3600,
      ]]]);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn(2);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET pwResetHash = null, pwResetHashGenTs = null WHERE email = :email',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->willReturn([0]);

    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $user->setEmail($email);
    $user->setPlainPassword('newpassword');
    $user->resetPw('supersecrettoken');
  }

  public function testResetpwIfTheEncoderThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('encodePassword test exception msg');

    $email = 'test@test.com';
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'pwResetHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'pwResetHashGenTs' => time() - 0.5*3600,
      ]]]);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn(2);

    $plainPw = 'newpassword';
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->will($this->throwException(new \Exception('encodePassword test exception msg')));

    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw('supersecrettoken');
  }

  public function testResetpwIfTheQueryToUpdateWithTheNewPasswordThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $email = 'test@test.com';
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'pwResetHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'pwResetHashGenTs' => time() - 0.5*3600,
      ]]]);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn(2);

    $plainPw = 'newpassword';
    $pwHash = '$2y$15$dAw27m4ED0vKNJVpK1WZWenichZf1b7thAEL59MHzZErgJD1J5CM6';
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($pwHash);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET password = :pw, pwResetHash = null, pwResetHashGenTs = null WHERE email = :email',
        [[
          'pw' => [$pwHash, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->will($this->throwException(new \Exception));

    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw('supersecrettoken');
  }

  public function testResetpwIfTheQueryToUpdateWithTheNewPasswordReturnsZeroAffectedRowsItShouldThrowAnExceptionWithACustomMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("The new password for the email 'test@test.com' failed to be stored in the DB.");

    $email = 'test@test.com';
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with(
        'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL',
        [['email' => [$email, \PDO::PARAM_STR]]]
      )
      ->willReturn([[[
        'pwResetHash' => '$2y$15$nxQyMKUw7pQ13h1fn4P1GODkqwKu1rrpCrY.DbjnbQksppM/IjpA6',
        'pwResetHashGenTs' => time() - 0.5*3600,
      ]]]);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn(2);

    $plainPw = 'newpassword';
    $pwHash = '$2y$15$dAw27m4ED0vKNJVpK1WZWenichZf1b7thAEL59MHzZErgJD1J5CM6';
    $user = new UserModel($this->dbInterfaceMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($pwHash);
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with(
        'UPDATE users SET password = :pw, pwResetHash = null, pwResetHashGenTs = null WHERE email = :email',
        [[
          'pw' => [$pwHash, \PDO::PARAM_STR],
          'email' => [$email, \PDO::PARAM_STR],
        ]]
      )
      ->willReturn([0]);

    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw('supersecrettoken');
  }
}

Class CustomUserModelContainer implements \Psr\Container\ContainerInterface {
  public function getParameter(string $name) {}
  public function get($id) {}
  public function has($id) {}
}