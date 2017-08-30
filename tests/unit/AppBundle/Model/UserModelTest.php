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
    $this->utilsMock = $this->createMock(Utils::class);
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
      ->with($query, $paramData)
      ->willReturn([[[
        'id' => '1',
        'userName' => 'test',
        'email' => 'test@test.com',
        'password' => 'testpassword',
        'isActive' => '1',
        'role' => 'ROLE_USER',
        'created' => $created
      ]]]);
    
    $userModel = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $actualValue = $userModel->populateFromDb($bindData);

    $this->assertEquals(null, $actualValue);
    $this->assertEquals(1, $userModel->getId());
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

    $uniqueCol = 'email';
    $uniqueId = 'test@test.com';
    $bindData = [
      $uniqueCol => [$uniqueId, \PDO::PARAM_STR],
      'anotherCol' => [$uniqueId, \PDO::PARAM_STR],
    ];
    
    $userModel = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $userModel->populateFromDb($bindData);
  }

  public function testPopulatefromdbIfDbinterfaceSelectThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $uniqueCol = 'email';
    $uniqueId = 'test@test.com';
    $bindData = [$uniqueCol => [$uniqueId, \PDO::PARAM_STR]];

    $query = 'SELECT u.id, u.userName, u.email, u.password, u.isActive, u.created, ur.role '.
      "FROM users as u JOIN users_roles as ur ON ur.id = u.roleId WHERE u.${uniqueCol} = :${uniqueCol}";
    $paramData = [$bindData];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception));
    
    $userModel = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
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

    $query = 'SELECT u.id, u.userName, u.email, u.password, u.isActive, u.created, ur.role '.
      "FROM users as u JOIN users_roles as ur ON ur.id = u.roleId WHERE u.${uniqueCol} = :${uniqueCol}";
    $paramData = [$bindData];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[]]);
    
    $userModel = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
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

    $query = 'SELECT u.id, u.userName, u.email, u.password, u.isActive, u.created, ur.role '.
      "FROM users as u JOIN users_roles as ur ON ur.id = u.roleId WHERE u.${uniqueCol} = :${uniqueCol}";
    $paramData = [$bindData];

    $created = time() - 86400;

    $this->dbInterfaceMock->expects($this->exactly(2))
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'id' => '1',
        'userName' => 'test',
        'email' => 'test@test.com',
        'password' => 'testpassword',
        'isActive' => '1',
        'role' => 'ROLE_USER',
        'created' => $created
      ]]]);
    
    $userModel = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $userModel->populateFromDb($bindData);
    $userModel->populateFromDb($bindData);
  }

  public function testRegisterItShouldQueryTheDbForTheUsersWithTheProvidedUsernameAndEmailThenCreateTheHashedVersionOfThePlainPwAndStoringItInThePasswordPropertyThenGenerateATokenAndHashItThenInsertTheUserIntoTheDbThenSendTheActivationEmailAndReturnABoolean() {
    $userName = 'test username';
    $email = 'test@test.com';
    $plainPw = 'test plain password';
    $hashedPw = 'hashedtestpassword';
    $created = time();

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->setCreated($created);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);
    
    $token = 'test token';
    $tokenGenTs = time();
    $tokenHash = 'hashofthetesttoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn([
        'token' => $token,
        'ts' => $tokenGenTs
      ]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($token)
      ->willReturn($tokenHash);
    
    $query = 'INSERT INTO users '.
      "VALUES(null,:userName,:email,:password,0,1,'${tokenHash}',${tokenGenTs},null,null,:created)";
    $paramNames = ['userName', 'email', 'password', 'created'];

    $this->dbInterfaceMock->expects($this->once())
      ->method('changeFromModel')
      ->with($query, $paramNames, [$user], false)
      ->willReturn([1]);
    
    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($email, $token)
      ->willReturn(true);
    
    $actualValue = $user->register();
    
    $this->assertEquals($hashedPw, $user->getPassword());

    $this->assertEquals(true, $actualValue);
  }

  public function testRegisterIfDbinterfaceSelectThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $userName = 'test username';
    $email = 'test@test.com';

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception));
    
    $user->register();
  }

  public function testRegisterIfTheUsernameIsTakenItShouldThrowAnExceptionWithASpecificText() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("1062 Duplicate entry 'test username' for key 'userName'");

    $userName = 'test username';
    $email = 'test@test.com';

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
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

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
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

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
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
    $hashedPw = 'hashedtestpassword';

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
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

  public function testRegisterIfCreatingTheActivationTokenThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('generateToken test exception msg');

    $userName = 'test username';
    $email = 'test@test.com';
    $plainPw = 'test plain password';
    $hashedPw = 'hashedtestpassword';

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);
    
    $token = 'test token';
    $tokenGenTs = time();
    $tokenHash = 'hashofthetesttoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->will($this->throwException(new \Exception('generateToken test exception msg')));
    
    $user->register();
  }

  public function testRegisterIfTheActivationTokenHashIsntCreatedItShouldThrowAnException() {
    $this->expectException(\Exception::class);

    $userName = 'test username';
    $email = 'test@test.com';
    $plainPw = 'test plain password';
    $hashedPw = 'hashedtestpassword';

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);
    
    $token = 'test token';
    $tokenGenTs = time();
    $tokenHash = 'hashofthetesttoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn([
        'token' => $token,
        'ts' => $tokenGenTs
      ]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($token)
      ->willReturn('');
    
    $user->register();
  }

  public function testRegisterIfCreatingTheActivationTokenHashThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $userName = 'test username';
    $email = 'test@test.com';
    $plainPw = 'test plain password';
    $hashedPw = 'hashedtestpassword';

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);
    
    $token = 'test token';
    $tokenGenTs = time();
    $tokenHash = 'hashofthetesttoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn([
        'token' => $token,
        'ts' => $tokenGenTs
      ]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($token)
      ->will($this->throwException(new \Exception));
    
    $user->register();
  }

  public function testRegisterIfTheInsertQueryThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $userName = 'test username';
    $email = 'test@test.com';
    $plainPw = 'test plain password';
    $hashedPw = 'hashedtestpassword';
    $created = time();

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->setCreated($created);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);
    
    $token = 'test token';
    $tokenGenTs = time();
    $tokenHash = 'hashofthetesttoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn([
        'token' => $token,
        'ts' => $tokenGenTs
      ]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($token)
      ->willReturn($tokenHash);
    
    $query = 'INSERT INTO users '.
      "VALUES(null,:userName,:email,:password,0,1,'${tokenHash}',${tokenGenTs},null,null,:created)";
    $paramNames = ['userName', 'email', 'password', 'created'];

    $this->dbInterfaceMock->expects($this->once())
      ->method('changeFromModel')
      ->with($query, $paramNames, [$user], false)
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

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->setCreated($created);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);
    
    $token = 'test token';
    $tokenGenTs = time();
    $tokenHash = 'hashofthetesttoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn([
        'token' => $token,
        'ts' => $tokenGenTs
      ]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($token)
      ->willReturn($tokenHash);
    
    $query = 'INSERT INTO users '.
      "VALUES(null,:userName,:email,:password,0,1,'${tokenHash}',${tokenGenTs},null,null,:created)";
    $paramNames = ['userName', 'email', 'password', 'created'];

    $this->dbInterfaceMock->expects($this->once())
      ->method('changeFromModel')
      ->with($query, $paramNames, [$user], false)
      ->willReturn([null]);
    
    $user->register();
  }

  public function testRegisterIfTheActivationEmailFailsToBeSentItShouldReturnFalse() {
    $userName = 'test username';
    $email = 'test@test.com';
    $plainPw = 'test plain password';
    $hashedPw = 'hashedtestpassword';
    $created = time();

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->setCreated($created);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);
    
    $token = 'test token';
    $tokenGenTs = time();
    $tokenHash = 'hashofthetesttoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn([
        'token' => $token,
        'ts' => $tokenGenTs
      ]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($token)
      ->willReturn($tokenHash);
    
    $query = 'INSERT INTO users '.
      "VALUES(null,:userName,:email,:password,0,1,'${tokenHash}',${tokenGenTs},null,null,:created)";
    $paramNames = ['userName', 'email', 'password', 'created'];

    $this->dbInterfaceMock->expects($this->once())
      ->method('changeFromModel')
      ->with($query, $paramNames, [$user], false)
      ->willReturn([1]);
    
    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($email, $token)
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

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setUserName($userName);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->setCreated($created);

    $query = '(select count(id) as count from users where userName=:userName)'.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$userName, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->WillReturn([[
        ['count' => 0],
        ['count' => 0],
      ]]);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($hashedPw);
    
    $token = 'test token';
    $tokenGenTs = time();
    $tokenHash = 'hashofthetesttoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn([
        'token' => $token,
        'ts' => $tokenGenTs
      ]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($token)
      ->willReturn($tokenHash);
    
    $query = 'INSERT INTO users '.
      "VALUES(null,:userName,:email,:password,0,1,'${tokenHash}',${tokenGenTs},null,null,:created)";
    $paramNames = ['userName', 'email', 'password', 'created'];

    $this->dbInterfaceMock->expects($this->once())
      ->method('changeFromModel')
      ->with($query, $paramNames, [$user], false)
      ->willReturn([1]);
    
    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($email, $token)
      ->will($this->throwException(new \Exception));
    
    $user->register();
  }

  public function testActivateItShouldQueryTheDbForAnInactiveUserWithTheProvidedEmailThenCheckIfTheProvidedTokenIsActiveAndIsValidThenActivateTheUsersAccountInTheDbAndReturnVoid() {
    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $activationHash = 'hashedtoken';
    $activationHashGenTs = time() - 24*3600;
    $tokenDuration = 72;

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'activationHash' => $activationHash,
        'activationHashGenTs' => $activationHashGenTs,
      ]]]);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn($tokenDuration);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($expectedToken, $activationHash)
      ->willReturn(true);

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
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $actualValue = $user->activate($expectedToken, $this->routerInterfaceMock);

    $this->assertEquals(null, $actualValue);
  }

  public function testActivateIfNoInactiveUserIsFoundItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("There is no inactive user with email 'test@test.com'.");

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $activationHash = 'hashedtoken';
    $activationHashGenTs = time() - 24*3600;
    $tokenDuration = 72;

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[]]);
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate($expectedToken, $this->routerInterfaceMock);
  }

  public function testActivateIfDbinterfaceSelectThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('select test exception msg');

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception('select test exception msg')));
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate($expectedToken, $this->routerInterfaceMock);
  }

  public function testActivateIfTheTokenIsNotValidItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("The activation token provided for the email 'test@test.com' is not correct.");

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $activationHash = 'hashedtoken';
    $activationHashGenTs = time() - 24*3600;
    $tokenDuration = 72;

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'activationHash' => $activationHash,
        'activationHashGenTs' => $activationHashGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($expectedToken, $activationHash)
      ->willReturn(false);
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate($expectedToken, $this->routerInterfaceMock);
  }

  public function testActivateIfCheckingIfTheTokenIsValidThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('isHashValid test exception msg');

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $activationHash = 'hashedtoken';
    $activationHashGenTs = time() - 24*3600;
    $tokenDuration = 72;

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'activationHash' => $activationHash,
        'activationHashGenTs' => $activationHashGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($expectedToken, $activationHash)
      ->will($this->throwException(new \Exception('isHashValid test exception msg')));
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate($expectedToken, $this->routerInterfaceMock);
  }

  public function testActivateIfContainerGetparameterThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('getParameter test exception msg');

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $activationHash = 'hashedtoken';
    $activationHashGenTs = time() - 90*3600;
    $tokenDuration = 72;

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'activationHash' => $activationHash,
        'activationHashGenTs' => $activationHashGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($expectedToken, $activationHash)
      ->willReturn(true);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->will($this->throwException(new \Exception('getParameter test exception msg')));
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate($expectedToken, $this->routerInterfaceMock);
  }

  public function testActivateIfTheQueryToActivateTheUserFailsItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Failed to UPDATE the user with email 'test@test.com' with the active account values.");

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $activationHash = 'hashedtoken';
    $activationHashGenTs = time() - 24*3600;
    $tokenDuration = 72;

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'activationHash' => $activationHash,
        'activationHashGenTs' => $activationHashGenTs,
      ]]]);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn($tokenDuration);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($expectedToken, $activationHash)
      ->willReturn(true);

    $query = 'UPDATE users SET isActive = 1, activationHash = null, activationHashGenTs = null WHERE email = :email';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([0]);
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate($expectedToken, $this->routerInterfaceMock);
  }

  public function testActivateIfDbinterfaceChangeThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $activationHash = 'hashedtoken';
    $activationHashGenTs = time() - 24*3600;
    $tokenDuration = 72;

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'activationHash' => $activationHash,
        'activationHashGenTs' => $activationHashGenTs,
      ]]]);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn($tokenDuration);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($expectedToken, $activationHash)
      ->willReturn(true);

    $query = 'UPDATE users SET isActive = 1, activationHash = null, activationHashGenTs = null WHERE email = :email';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];
    
    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception));
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate($expectedToken, $this->routerInterfaceMock);
  }

  public function testActivateIfTheTokenIsExpiredItShouldGenerateAndProcessANewTokenAndThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The activation link used is expired. A new activation link was sent to this account\'s email address.');

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $activationHash = 'hashedtoken';
    $activationHashGenTs = time() - 90*3600;
    $tokenDuration = 72;

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'activationHash' => $activationHash,
        'activationHashGenTs' => $activationHashGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($expectedToken, $activationHash)
      ->willReturn(true);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn($tokenDuration);
    
    $newToken = 'newsecrettoken';
    $newTokenTs = time();
    $newTokenHash = 'newhashedtoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn(['token' => $newToken, 'ts' => $newTokenTs]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($newToken)
      ->willReturn($newTokenHash);
    
    $query = 'UPDATE users SET isActive = 0, activationHash = :hash, activationHashGenTs = :hashTs WHERE email = :email';
    $paramData = [
      [
        'hash' => [$newTokenHash, \PDO::PARAM_STR],
        'hashTs' => [$newTokenTs, \PDO::PARAM_INT],
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($expectedEmail, $newToken)
      ->willReturn(true);
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate($expectedToken, $this->routerInterfaceMock);
  }

  public function testActivateIfTheTokenIsExpiredAndTheHashOfTheNewTokenFailsToBeCreatedItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(
      'The activation link used is expired. An attempt was made to send a new activation link to '.
      'this account\'s email address, but the email could not be sent. Please <a href="resendLink">click here</a> to resend the activation email.'
    );

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $activationHash = 'hashedtoken';
    $activationHashGenTs = time() - 90*3600;
    $tokenDuration = 72;

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'activationHash' => $activationHash,
        'activationHashGenTs' => $activationHashGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($expectedToken, $activationHash)
      ->willReturn(true);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn($tokenDuration);
    
    $newToken = 'newsecrettoken';
    $newTokenTs = time();
    $newTokenHash = 'newhashedtoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn(['token' => $newToken, 'ts' => $newTokenTs]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($newToken)
      ->will($this->throwException(new \Exception('The hash of the new token couldn\'t be generated.')));
    
    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('resendActivation', ['e' => $expectedEmail])
      ->willReturn('resendLink');
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate($expectedToken, $this->routerInterfaceMock);
  }

  public function testActivateIfTheTokenIsExpiredAndTheDbFailsToBeUpdatedItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(
      'The activation link used is expired. An attempt was made to send a new activation link to '.
      'this account\'s email address, but the email could not be sent. Please <a href="resendLink">click here</a> to resend the activation email.'
    );

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $activationHash = 'hashedtoken';
    $activationHashGenTs = time() - 90*3600;
    $tokenDuration = 72;

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'activationHash' => $activationHash,
        'activationHashGenTs' => $activationHashGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($expectedToken, $activationHash)
      ->willReturn(true);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn($tokenDuration);
    
    $newToken = 'newsecrettoken';
    $newTokenTs = time();
    $newTokenHash = 'newhashedtoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn(['token' => $newToken, 'ts' => $newTokenTs]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($newToken)
      ->willReturn($newTokenHash);
    
    $query = 'UPDATE users SET isActive = 0, activationHash = :hash, activationHashGenTs = :hashTs WHERE email = :email';
    $paramData = [
      [
        'hash' => [$newTokenHash, \PDO::PARAM_STR],
        'hashTs' => [$newTokenTs, \PDO::PARAM_INT],
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception));

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('resendActivation', ['e' => $expectedEmail])
      ->willReturn('resendLink');
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate($expectedToken, $this->routerInterfaceMock);
  }

  public function testActivateIfTheTokenIsExpiredAndTheEmailFailsToSendItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(
      'The activation link used is expired. An attempt was made to send a new activation link to '.
      'this account\'s email address, but the email could not be sent. Please <a href="resendLink">click here</a> to resend the activation email.'
    );

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $activationHash = 'hashedtoken';
    $activationHashGenTs = time() - 90*3600;
    $tokenDuration = 72;

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'activationHash' => $activationHash,
        'activationHashGenTs' => $activationHashGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($expectedToken, $activationHash)
      ->willReturn(true);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn($tokenDuration);
    
    $newToken = 'newsecrettoken';
    $newTokenTs = time();
    $newTokenHash = 'newhashedtoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn(['token' => $newToken, 'ts' => $newTokenTs]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($newToken)
      ->willReturn($newTokenHash);
    
    $query = 'UPDATE users SET isActive = 0, activationHash = :hash, activationHashGenTs = :hashTs WHERE email = :email';
    $paramData = [
      [
        'hash' => [$newTokenHash, \PDO::PARAM_STR],
        'hashTs' => [$newTokenTs, \PDO::PARAM_INT],
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($expectedEmail, $newToken)
      ->willReturn(false);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('resendActivation', ['e' => $expectedEmail])
      ->willReturn('resendLink');
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate($expectedToken, $this->routerInterfaceMock);
  }

  public function testActivateIfIfTheTokenIsExpiredAndTheProcessingOfANewTokenFailsAndTheRouterThrowsAnExceptionToSendItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('router exception message bubbled up');

    $expectedEmail = 'test@test.com';
    $expectedToken = 'supersecrettoken';

    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $activationHash = 'hashedtoken';
    $activationHashGenTs = time() - 90*3600;
    $tokenDuration = 72;

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'activationHash' => $activationHash,
        'activationHashGenTs' => $activationHashGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($expectedToken, $activationHash)
      ->willReturn(true);
    
    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('activationTokenDuration')
      ->willReturn($tokenDuration);
    
    $newToken = 'newsecrettoken';
    $newTokenTs = time();
    $newTokenHash = 'newhashedtoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn(['token' => $newToken, 'ts' => $newTokenTs]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($newToken)
      ->willReturn($newTokenHash);
    
    $query = 'UPDATE users SET isActive = 0, activationHash = :hash, activationHashGenTs = :hashTs WHERE email = :email';
    $paramData = [
      [
        'hash' => [$newTokenHash, \PDO::PARAM_STR],
        'hashTs' => [$newTokenTs, \PDO::PARAM_INT],
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($expectedEmail, $newToken)
      ->willReturn(false);

    $this->routerInterfaceMock->expects($this->once())
      ->method('generate')
      ->with('resendActivation', ['e' => $expectedEmail])
      ->will($this->throwException(new \Exception('router exception message bubbled up')));
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->activate($expectedToken, $this->routerInterfaceMock);
  }

  public function testRegenactivationtokenItShouldCheckThatTheUserAccountIsInactiveThenProcessANewActivationTokenAndReturnVoid() {
    $expectedEmail = 'test@test.com';

    $query = 'SELECT count(id) as count FROM users WHERE email = :email AND activationHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with()
      ->willReturn([[['count' => 1]]]);

    $newToken = 'newsecrettoken';
    $newTokenTs = time();
    $newTokenHash = 'newhashedtoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn(['token' => $newToken, 'ts' => $newTokenTs]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($newToken)
      ->willReturn($newTokenHash);
    
    $query = 'UPDATE users SET isActive = 0, activationHash = :hash, activationHashGenTs = :hashTs WHERE email = :email';
    $paramData = [
      [
        'hash' => [$newTokenHash, \PDO::PARAM_STR],
        'hashTs' => [$newTokenTs, \PDO::PARAM_INT],
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($expectedEmail, $newToken)
      ->willReturn(true);

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
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

    $query = 'SELECT count(id) as count FROM users WHERE email = :email AND activationHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with()
      ->willReturn([[['count' => 0]]]);

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->regenActivationToken();
  }

  public function testRegenactivationtokenIfTheProcessingOfTheNewTokenThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $expectedEmail = 'test@test.com';

    $query = 'SELECT count(id) as count FROM users WHERE email = :email AND activationHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with()
      ->willReturn([[['count' => 1]]]);

    $newToken = 'newsecrettoken';
    $newTokenTs = time();
    $newTokenHash = 'newhashedtoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn(['token' => $newToken, 'ts' => $newTokenTs]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($newToken)
      ->willReturn($newTokenHash);
    
    $query = 'UPDATE users SET isActive = 0, activationHash = :hash, activationHashGenTs = :hashTs WHERE email = :email';
    $paramData = [
      [
        'hash' => [$newTokenHash, \PDO::PARAM_STR],
        'hashTs' => [$newTokenTs, \PDO::PARAM_INT],
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('activationEmail')
      ->with($expectedEmail, $newToken)
      ->willReturn(false);

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->regenActivationToken();
  }

  public function testInitpwresetprocessItShouldCallGentokensendemailAndReturnVoid() {
    $expectedEmail = 'test@test.com';

    $query = 'SELECT id FROM users WHERE email = :email AND isActive = 1';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[['id' => '1']]]);

    $newToken = 'newsecrettoken';
    $newTokenTs = time();
    $newTokenHash = 'newhashedtoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn(['token' => $newToken, 'ts' => $newTokenTs]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($newToken)
      ->willReturn($newTokenHash);

    $query = 'UPDATE users SET pwResetHash = :hash, pwResetHashGenTs = :hashTs WHERE email = :email';
    $paramData = [
      [
        'hash' => [$newTokenHash, \PDO::PARAM_STR],
        'hashTs' => [$newTokenTs, \PDO::PARAM_INT],
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('pwResetEmail')
      ->with($expectedEmail, $newToken)
      ->willReturn(true);

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $actualValue = $user->initPwResetProcess();

    $this->assertEquals(null, $actualValue);
  }

  public function testInitpwresetprocessIfTheProcessingOfTheNewTokenThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $expectedEmail = 'test@test.com';
    
    $query = 'SELECT id FROM users WHERE email = :email AND isActive = 1';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[['id' => '1']]]);

    $newToken = 'newsecrettoken';
    $newTokenTs = time();
    $newTokenHash = 'newhashedtoken';

    $this->utilsMock->expects($this->once())
      ->method('generateToken')
      ->with()
      ->willReturn(['token' => $newToken, 'ts' => $newTokenTs]);

    $this->utilsMock->expects($this->once())
      ->method('createHash')
      ->with($newToken)
      ->willReturn($newTokenHash);
    
    $query = 'UPDATE users SET pwResetHash = :hash, pwResetHashGenTs = :hashTs WHERE email = :email';
    $paramData = [
      [
        'hash' => [$newTokenHash, \PDO::PARAM_STR],
        'hashTs' => [$newTokenTs, \PDO::PARAM_INT],
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([1]);

    $this->emailInterfaceMock->expects($this->once())
      ->method('pwResetEmail')
      ->with($expectedEmail, $newToken)
      ->willReturn(false);

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->initPwResetProcess();
  }

  public function testInitpwresetprocessIfSelectReturnsEmptyItShouldThrowAnException() {
    $expectedEmail = 'test@test.com';

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("No user was found in the DB with email '${expectedEmail}' with an active account.");

    $query = 'SELECT id FROM users WHERE email = :email AND isActive = 1';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[]]);

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->initPwResetProcess();
  }

  public function testInitpwresetprocessIfSelectThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('select test exception msg');

    $expectedEmail = 'test@test.com';

    $query = 'SELECT id FROM users WHERE email = :email AND isActive = 1';
    $paramData = [
      [
        'email' => [$expectedEmail, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception('select test exception msg')));

    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($expectedEmail);
    $user->initPwResetProcess();
  }

  public function testResetpwItShouldQueryTheDbForThePwresethashAndItsTimestampThenCheckIfTheProvidedTokenMatchesTheDbHashThenCheckIfTheLinkIsNotExpiredThenEncodeTheNewPasswordThenUpdateTheDbWithItAndReturnVoid() {
    $email = 'test@test.com';
    $token = 'supersecrettoken';
    $tokenHash = 'passwordresttokenhash';
    $tokenGenTs = time() - 0.5*3600;
    $tokenDuration = 2;

    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'pwResetHash' => $tokenHash,
        'pwResetHashGenTs' => $tokenGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($token, $tokenHash)
      ->willReturn(true);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn($tokenDuration);

    $plainPw = 'newpassword';
    $pwHash = 'newpasswordhashedversion';
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($pwHash);
    
    $query = 'UPDATE users SET password = :pw, pwResetHash = null, pwResetHashGenTs = null WHERE email = :email';
    $paramData = [
      [
        'pw' => [$pwHash, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([1]);

    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $actualValue = $user->resetPw($token);

    $this->assertEquals(null, $actualValue);
  }

  public function testResetpwIfDbinterfaceSelectThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('select test exception msg');

    $email = 'test@test.com';
    $token = 'supersecrettoken';

    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception('select test exception msg')));

    $plainPw = 'newpassword';
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw($token);
  }

  public function testResetpwIfThereIsNoUserWithTheProvidedEmailOrItHasNotInitiatedThePwResetProcessItShouldThrowAnExceptionWithACustomMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("No user was found in the DB with email 'test@test.com' and with a password reset token.");

    $email = 'test@test.com';
    $token = 'supersecrettoken';

    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[]]);

    $plainPw = 'newpassword';
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw($token);
  }

  public function testResetpwIfTheProvidedTokenDoesntMatchTheDbHashItShouldThrowAnExceptionWithACustomMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("The password reset token provided for the email 'test@test.com' is not correct.");

    $email = 'test@test.com';
    $token = 'supersecrettoken';
    $tokenHash = 'passwordresttokenhash';
    $tokenGenTs = time() - 0.5*3600;

    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'pwResetHash' => $tokenHash,
        'pwResetHashGenTs' => $tokenGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($token, $tokenHash)
      ->willReturn(false);

    $plainPw = 'newpassword';
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw($token);
  }

  public function testResetpwIfCheckingTheTokenAgainstTheHashThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('isHashValid test exception msg');

    $email = 'test@test.com';
    $token = 'supersecrettoken';
    $tokenHash = 'passwordresttokenhash';
    $tokenGenTs = time() - 0.5*3600;

    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'pwResetHash' => $tokenHash,
        'pwResetHashGenTs' => $tokenGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($token, $tokenHash)
      ->will($this->throwException(new \Exception('isHashValid test exception msg')));

    $plainPw = 'newpassword';
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw($token);
  }

  public function testResetpwIfTheParameterbagThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('getParameter test exception msg');

    $email = 'test@test.com';
    $token = 'supersecrettoken';
    $tokenHash = 'passwordresttokenhash';
    $tokenGenTs = time() - 4*3600;
    $tokenDuration = 2;

    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'pwResetHash' => $tokenHash,
        'pwResetHashGenTs' => $tokenGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($token, $tokenHash)
      ->willReturn(true);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->will($this->throwException(new \Exception('getParameter test exception msg')));
    
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    $user->setEmail($email);
    $user->resetPw($token);
  }

  public function testResetpwIfTheTokenIsExpiredItShouldUpdateTheDbToRemoveTheTokenAndItsTimestampAndThrowATokenExpiredExceptionWithACustomMsg() {
    $this->expectException(\AppBundle\Exceptions\TokenExpiredException::class);
    $this->expectExceptionMessage("The password reset token provided for the email 'test@test.com' is expired.");

    $email = 'test@test.com';
    $token = 'supersecrettoken';
    $tokenHash = 'passwordresttokenhash';
    $tokenGenTs = time() - 4*3600;
    $tokenDuration = 2;

    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'pwResetHash' => $tokenHash,
        'pwResetHashGenTs' => $tokenGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($token, $tokenHash)
      ->willReturn(true);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn($tokenDuration);
    
    $query = 'UPDATE users SET pwResetHash = null, pwResetHashGenTs = null WHERE email = :email';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([1]);

    $plainPw = 'newpassword';
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw($token);
  }

  public function testResetpwIfTheTokenIsExpiredAndTheQueryToRemoveTheTokenThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('change test exception msg');

    $email = 'test@test.com';
    $token = 'supersecrettoken';
    $tokenHash = 'passwordresttokenhash';
    $tokenGenTs = time() - 4*3600;
    $tokenDuration = 2;

    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'pwResetHash' => $tokenHash,
        'pwResetHashGenTs' => $tokenGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($token, $tokenHash)
      ->willReturn(true);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn($tokenDuration);
    
    $query = 'UPDATE users SET pwResetHash = null, pwResetHashGenTs = null WHERE email = :email';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception('change test exception msg')));

    $plainPw = 'newpassword';
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw($token);
  }

  public function testResetpwIfTheTokenIsExpiredAndTheQueryToRemoveTheTokenFailsItShouldIgnoreThatFailAndThrowATokenExpiredExceptionWithACustomMsg() {
    $this->expectException(\AppBundle\Exceptions\TokenExpiredException::class);
    $this->expectExceptionMessage("The password reset token provided for the email 'test@test.com' is expired.");

    $email = 'test@test.com';
    $token = 'supersecrettoken';
    $tokenHash = 'passwordresttokenhash';
    $tokenGenTs = time() - 4*3600;
    $tokenDuration = 2;

    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'pwResetHash' => $tokenHash,
        'pwResetHashGenTs' => $tokenGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($token, $tokenHash)
      ->willReturn(true);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn($tokenDuration);
    
    $query = 'UPDATE users SET pwResetHash = null, pwResetHashGenTs = null WHERE email = :email';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([0]);

    $plainPw = 'newpassword';
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw($token);
  }

  public function testResetpwIfTheEncoderThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('encodePassword test exception msg');

    $email = 'test@test.com';
    $token = 'supersecrettoken';
    $tokenHash = 'passwordresttokenhash';
    $tokenGenTs = time() - 0.5*3600;
    $tokenDuration = 2;

    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'pwResetHash' => $tokenHash,
        'pwResetHashGenTs' => $tokenGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($token, $tokenHash)
      ->willReturn(true);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn($tokenDuration);

    $plainPw = 'newpassword';
    $pwHash = 'newpasswordhashedversion';
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->will($this->throwException(new \Exception('encodePassword test exception msg')));

    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw($token);
  }

  public function testResetpwIfTheQueryToUpdateWithTheNewPasswordThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $email = 'test@test.com';
    $token = 'supersecrettoken';
    $tokenHash = 'passwordresttokenhash';
    $tokenGenTs = time() - 0.5*3600;
    $tokenDuration = 2;

    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'pwResetHash' => $tokenHash,
        'pwResetHashGenTs' => $tokenGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($token, $tokenHash)
      ->willReturn(true);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn($tokenDuration);

    $plainPw = 'newpassword';
    $pwHash = 'newpasswordhashedversion';
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($pwHash);
    
    $query = 'UPDATE users SET password = :pw, pwResetHash = null, pwResetHashGenTs = null WHERE email = :email';
    $paramData = [
      [
        'pw' => [$pwHash, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception));

    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw($token);
  }

  public function testResetpwIfTheQueryToUpdateWithTheNewPasswordReturnsZeroAffectedRowsItShouldThrowAnExceptionWithACustomMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("The new password for the email 'test@test.com' failed to be stored in the DB.");

    $email = 'test@test.com';
    $token = 'supersecrettoken';
    $tokenHash = 'passwordresttokenhash';
    $tokenGenTs = time() - 0.5*3600;
    $tokenDuration = 2;

    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn([[[
        'pwResetHash' => $tokenHash,
        'pwResetHashGenTs' => $tokenGenTs,
      ]]]);
    
    $this->utilsMock->expects($this->once())
      ->method('isHashValid')
      ->with($token, $tokenHash)
      ->willReturn(true);

    $this->containerMock->expects($this->once())
      ->method('getParameter')
      ->with('resetPwTokenDuration')
      ->willReturn($tokenDuration);

    $plainPw = 'newpassword';
    $pwHash = 'newpasswordhashedversion';
    $user = new UserModel($this->dbInterfaceMock, $this->utilsMock, $this->emailInterfaceMock, $this->encoderMock, $this->containerMock);
    
    $this->encoderMock->expects($this->once())
      ->method('encodePassword')
      ->with($user, $plainPw)
      ->willReturn($pwHash);
    
    $query = 'UPDATE users SET password = :pw, pwResetHash = null, pwResetHashGenTs = null WHERE email = :email';
    $paramData = [
      [
        'pw' => [$pwHash, \PDO::PARAM_STR],
        'email' => [$email, \PDO::PARAM_STR],
      ],
    ];

    $this->dbInterfaceMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn([0]);

    $user->setEmail($email);
    $user->setPlainPassword($plainPw);
    $user->resetPw($token);
  }
}

Class CustomUserModelContainer implements \Psr\Container\ContainerInterface {
  public function getParameter(string $name) {}
  public function get($id) {}
  public function has($id) {}
}