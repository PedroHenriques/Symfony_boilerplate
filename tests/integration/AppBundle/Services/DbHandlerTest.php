<?php

namespace tests\integration\AppBundle\Services;

require_once(dirname(dirname(__DIR__)).'/BaseIntegrationCase.php');

use tests\integration\BaseIntegrationCase;
use AppBundle\Model\{UserModel, UserModelFactory};

class DbHandlerTest extends BaseIntegrationCase {
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
        [
          'id' => 2, 'userName' => 'another activated username', 'email' => 'activated@test.com',
          'password' => '$2y$15$/.9tL5uq.t.BjAxsHGn5feQG4gxe7fh8BZVgjQEN19W4sKg4RHywW',
          'isActive' => 1, 'roleId' => 1, 'activationHash' => null,
          'activationHashGenTs' => null, 'pwResetHash' => null, 'pwResetHashGenTs' => null, 'created' => time()-86400
        ],
        [
          'id' => 3, 'userName' => 'another username', 'email' => 'another@email.com',
          'password' => '$2y$15$/.9tL5uq.t.BjAxsHGn5feQG4gxe7fh8BZVgjQEN19W4sKg4RHy.F',
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
    $this->container = $client->getContainer();
    $this->dbHandler = $this->container->get('service_container')->get('AppBundle\Services\DbInterface');
  }

  public function testQueryIsCallingDoctrineDbalCorrectly() {
    $query = 'SELECT * FROM users WHERE id = '.$this->fixtures['users'][0]['id'];
    $actualValue = $this->dbHandler->query($query);

    $this->assertEquals($this->fixtures['users'][0], $actualValue[0]);
  }

  public function testSelectIsCallingDoctrineDbalCorrectly() {
    $query = 'SELECT u.id, u.userName, u.email, u.password, u.isActive, u.created, ur.role
    FROM users as u
    JOIN users_roles as ur ON ur.id = u.roleId
    WHERE u.id = :id';

    $paramData = [
      ['id' => [1, \PDO::PARAM_INT]],
      ['id' => [2, \PDO::PARAM_INT]],
    ];

    $actualValue = $this->dbHandler->select($query, $paramData);

    $this->assertEquals(2, count($actualValue));

    $this->assertEquals('1', $actualValue[0][0]['id']);
    $this->assertEquals('activated test username', $actualValue[0][0]['userName']);
    $this->assertEquals('test@test.com', $actualValue[0][0]['email']);
    $this->assertEquals('$2y$15$/.9tL5uq.t.BjAxsHGn5feQG4gxe7fh8BZVgj.EN1AW4sKg4RHywW', $actualValue[0][0]['password']);
    $this->assertEquals('1', $actualValue[0][0]['isActive']);
    $this->assertEquals('ROLE_USER', $actualValue[0][0]['role']);
    
    $this->assertEquals('2', $actualValue[1][0]['id']);
    $this->assertEquals('another activated username', $actualValue[1][0]['userName']);
    $this->assertEquals('activated@test.com', $actualValue[1][0]['email']);
    $this->assertEquals('$2y$15$/.9tL5uq.t.BjAxsHGn5feQG4gxe7fh8BZVgjQEN19W4sKg4RHywW', $actualValue[1][0]['password']);
    $this->assertEquals('1', $actualValue[1][0]['isActive']);
    $this->assertEquals('ROLE_USER', $actualValue[1][0]['role']);
  }

  public function testSelectIsCallingDoctrineDbalCorrectlyWhenSpecificArgsToFetchallAreProvided() {
    $query = 'SELECT id, userName, email, password, isActive, created FROM users WHERE id = :id';

    $paramData = [
      ['id' => [1, \PDO::PARAM_INT]],
      ['id' => [2, \PDO::PARAM_INT]],
    ];

    $actualValue = $this->dbHandler->select($query, $paramData, [\PDO::FETCH_COLUMN, 2]);

    $expectedValue = [
      [$this->fixtures['users'][0]['email']],
      [$this->fixtures['users'][1]['email']],
    ];

    $this->assertEquals(2, count($actualValue));
    $this->assertEquals($expectedValue, $actualValue);
  }

  public function testChangeIsCallingDoctrineDbalCorrectlyForANonInsertQuery() {
    $query = 'UPDATE users SET email = :email WHERE id = :id';

    $paramData = [
      ['id' => [1, \PDO::PARAM_INT], 'email' => ['1@1.com', \PDO::PARAM_STR]],
      ['id' => [3, \PDO::PARAM_INT], 'email' => ['3@3.com', \PDO::PARAM_STR]],
    ];

    $actualValue = $this->dbHandler->change($query, $paramData);
    $this->assertEquals([2], $actualValue);

    $pdo = parent::getPdo();
    $query = $pdo->query('SELECT email FROM users WHERE id IN (1,3)');
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(2, count($resultSet));
    $this->assertEquals('1@1.com', $resultSet[0]['email']);
    $this->assertEquals('3@3.com', $resultSet[1]['email']);
  }
  
  public function testChangeIsCallingDoctrineDbalCorrectlyForAnInsertQuery() {
    $query = 'INSERT INTO users VALUES(null,:userName,:email,:password,0,1,null,null,null,null,:created)';

    $createdOne = time();
    $createdTwo = time() - 86400;

    $paramData = [
      [
        'userName' => ['a new username', \PDO::PARAM_STR], 'email' => ['1@1.com', \PDO::PARAM_STR],
        'password' => ['hashedPasswordOne', \PDO::PARAM_STR], 'created' => [$createdOne, \PDO::PARAM_INT]
      ],
      [
        'userName' => ['another new username', \PDO::PARAM_STR], 'email' => ['2@2.com', \PDO::PARAM_STR],
        'password' => ['hashedPasswordTwo', \PDO::PARAM_STR], 'created' => [$createdTwo, \PDO::PARAM_INT]
      ],
    ];

    $actualValue = $this->dbHandler->change($query, $paramData);
    $this->assertEquals([4, 5], $actualValue);

    $pdo = parent::getPdo();
    $query = $pdo->query('SELECT * FROM users WHERE id IN (4,5)');
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(2, count($resultSet));
    $this->assertEquals($resultSet[0]['id'], $actualValue[0]);
    $this->assertEquals($resultSet[0]['userName'], $paramData[0]['userName'][0]);
    $this->assertEquals($resultSet[0]['email'], $paramData[0]['email'][0]);
    $this->assertEquals($resultSet[0]['password'], $paramData[0]['password'][0]);
    $this->assertEquals($resultSet[0]['isActive'], 0);
    $this->assertEquals($resultSet[0]['roleId'], 1);
    $this->assertEquals($resultSet[0]['activationHash'], null);
    $this->assertEquals($resultSet[0]['activationHashGenTs'], null);
    $this->assertEquals($resultSet[0]['pwResetHash'], null);
    $this->assertEquals($resultSet[0]['pwResetHashGenTs'], null);
    $this->assertEquals($resultSet[0]['created'], $paramData[0]['created'][0]);
    
    $this->assertEquals($resultSet[1]['id'], $actualValue[1]);
    $this->assertEquals($resultSet[1]['userName'], $paramData[1]['userName'][0]);
    $this->assertEquals($resultSet[1]['email'], $paramData[1]['email'][0]);
    $this->assertEquals($resultSet[1]['password'], $paramData[1]['password'][0]);
    $this->assertEquals($resultSet[1]['isActive'], 0);
    $this->assertEquals($resultSet[1]['roleId'], 1);
    $this->assertEquals($resultSet[1]['activationHash'], null);
    $this->assertEquals($resultSet[1]['activationHashGenTs'], null);
    $this->assertEquals($resultSet[1]['pwResetHash'], null);
    $this->assertEquals($resultSet[1]['pwResetHashGenTs'], null);
    $this->assertEquals($resultSet[1]['created'], $paramData[1]['created'][0]);
  }

  public function testChangeinbulkIsCallingDoctrineDbalCorrectlyAndCommittingWhenSuccessfull() {
    $query = 'INSERT INTO users VALUES(null,:userName,:email,:password,0,1,null,null,null,null,:created)';

    $createdOne = time();
    $createdTwo = time() - 86400;

    $paramData = [
      [
        'userName' => ['a new username', \PDO::PARAM_STR], 'email' => ['1@1.com', \PDO::PARAM_STR],
        'password' => ['hashedPasswordOne', \PDO::PARAM_STR], 'created' => [$createdOne, \PDO::PARAM_INT]
      ],
      [
        'userName' => ['another new username', \PDO::PARAM_STR], 'email' => ['2@2.com', \PDO::PARAM_STR],
        'password' => ['hashedPasswordTwo', \PDO::PARAM_STR], 'created' => [$createdTwo, \PDO::PARAM_INT]
      ],
    ];

    $actualValue = $this->dbHandler->changeInBulk($query, $paramData);
    $this->assertEquals([4, 5], $actualValue);
    
    $pdo = parent::getPdo();
    $query = $pdo->query('SELECT * FROM users WHERE id IN (4,5)');
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(2, count($resultSet));
    $this->assertEquals($resultSet[0]['id'], $actualValue[0]);
    $this->assertEquals($resultSet[0]['userName'], $paramData[0]['userName'][0]);
    $this->assertEquals($resultSet[0]['email'], $paramData[0]['email'][0]);
    $this->assertEquals($resultSet[0]['password'], $paramData[0]['password'][0]);
    $this->assertEquals($resultSet[0]['isActive'], 0);
    $this->assertEquals($resultSet[0]['roleId'], 1);
    $this->assertEquals($resultSet[0]['activationHash'], null);
    $this->assertEquals($resultSet[0]['activationHashGenTs'], null);
    $this->assertEquals($resultSet[0]['pwResetHash'], null);
    $this->assertEquals($resultSet[0]['pwResetHashGenTs'], null);
    $this->assertEquals($resultSet[0]['created'], $paramData[0]['created'][0]);
    
    $this->assertEquals($resultSet[1]['id'], $actualValue[1]);
    $this->assertEquals($resultSet[1]['userName'], $paramData[1]['userName'][0]);
    $this->assertEquals($resultSet[1]['email'], $paramData[1]['email'][0]);
    $this->assertEquals($resultSet[1]['password'], $paramData[1]['password'][0]);
    $this->assertEquals($resultSet[1]['isActive'], 0);
    $this->assertEquals($resultSet[1]['roleId'], 1);
    $this->assertEquals($resultSet[1]['activationHash'], null);
    $this->assertEquals($resultSet[1]['activationHashGenTs'], null);
    $this->assertEquals($resultSet[1]['pwResetHash'], null);
    $this->assertEquals($resultSet[1]['pwResetHashGenTs'], null);
    $this->assertEquals($resultSet[1]['created'], $paramData[1]['created'][0]);
  }

  public function testChangeinbulkIsCallingDoctrineDbalCorrectlyAndRollsBackWhenNotSuccessfull() {
    $query = 'INSERT INTO users VALUES(null,:userName,:email,:password,0,1,null,null,null,null,:created)';

    $createdOne = time();
    $createdTwo = time() - 86400;

    $paramData = [
      [
        'userName' => ['a new username', \PDO::PARAM_STR], 'email' => ['1@1.com', \PDO::PARAM_STR],
        'password' => ['hashedPasswordOne', \PDO::PARAM_STR], 'created' => [$createdOne, \PDO::PARAM_INT]
      ],
      [
        'userName' => ['another new username', \PDO::PARAM_STR], 'email' => ['2@2.com', \PDO::PARAM_STR],
        'password' => ['hashedPasswordTwo', \PDO::PARAM_STR], 'created' => [$createdTwo, \PDO::PARAM_INT],
        'nonExistingParam' => [10, \PDO::PARAM_INT]
      ],
    ];

    $exceptionThrown = false;
    $exceptionMsg = '';
    try {
      $this->dbHandler->changeInBulk($query, $paramData);
    } catch (\Exception $e) {
      $exceptionThrown = true;
      $exceptionMsg = $e->getMessage();
    }
    $this->assertEquals(true, $exceptionThrown);
    $this->assertEquals('Not all queries were successfully executed. As such the transaction was rolled back.', $exceptionMsg);
    
    $pdo = parent::getPdo();
    $query = $pdo->query('SELECT * FROM users WHERE id IN (4,5)');
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(0, count($resultSet));
  }

  public function testChangefrommodelIsCallingDoctrineDbalCorrectlyWhenATransactionIsNotRequested() {
    $emailHandler = $this->container->get('service_container')->get('AppBundle\Services\EmailInterface');
    $encoder = $this->container->get('security.password_encoder');

    $userOne = new UserModel($this->dbHandler, $emailHandler, $encoder, $this->container);
    $userTwo = new UserModel($this->dbHandler, $emailHandler, $encoder, $this->container);

    $userOneData = $this->fixtures['users'][0];
    $userOneData['email'] = 'new.user@one.email';
    $userOneData['roles'] = ['ROLE_USER'];
    unset($userOneData['roleId']);
    unset($userOneData['activationHash']);
    unset($userOneData['activationHashGenTs']);
    unset($userOneData['pwResetHash']);
    unset($userOneData['pwResetHashGenTs']);

    $userOne->populateFromArray($userOneData);
    
    $userTwoData = $this->fixtures['users'][1];
    $userTwoData['email'] = 'new.user@two.email';
    $userTwoData['roles'] = ['ROLE_USER'];
    unset($userTwoData['roleId']);
    unset($userTwoData['activationHash']);
    unset($userTwoData['activationHashGenTs']);
    unset($userTwoData['pwResetHash']);
    unset($userTwoData['pwResetHashGenTs']);

    $userTwo->populateFromArray($userTwoData);

    $query = 'UPDATE users SET email = :email WHERE id = :id';
    $paramNames = ['email', 'id'];

    $actualValue = $this->dbHandler->changeFromModel($query, $paramNames, [$userOne, $userTwo], false);
    $this->assertEquals(2, $actualValue[0]);

    $pdo = parent::getPdo();
    $query = $pdo->query('SELECT email FROM users WHERE id IN (1,2)');
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(2, count($resultSet));
    $this->assertEquals('new.user@one.email', $resultSet[0]['email']);
    $this->assertEquals('new.user@two.email', $resultSet[1]['email']);
  }
  
  public function testChangefrommodelIsCallingDoctrineDbalCorrectlyWhenATransactionIsRequestedAndEverythingSucceeds() {
    $emailHandler = $this->container->get('service_container')->get('AppBundle\Services\EmailInterface');
    $encoder = $this->container->get('security.password_encoder');

    $userOne = new UserModel($this->dbHandler, $emailHandler, $encoder, $this->container);
    $userTwo = new UserModel($this->dbHandler, $emailHandler, $encoder, $this->container);

    $userOneData = $this->fixtures['users'][0];
    $userOneData['email'] = 'new.user@one.email';
    $userOneData['roles'] = ['ROLE_USER'];
    unset($userOneData['roleId']);
    unset($userOneData['activationHash']);
    unset($userOneData['activationHashGenTs']);
    unset($userOneData['pwResetHash']);
    unset($userOneData['pwResetHashGenTs']);

    $userOne->populateFromArray($userOneData);
    
    $userTwoData = $this->fixtures['users'][1];
    $userTwoData['email'] = 'new.user@two.email';
    $userTwoData['roles'] = ['ROLE_USER'];
    unset($userTwoData['roleId']);
    unset($userTwoData['activationHash']);
    unset($userTwoData['activationHashGenTs']);
    unset($userTwoData['pwResetHash']);
    unset($userTwoData['pwResetHashGenTs']);

    $userTwo->populateFromArray($userTwoData);

    $query = 'UPDATE users SET email = :email WHERE id = :id';
    $paramNames = ['email', 'id'];

    $actualValue = $this->dbHandler->changeFromModel($query, $paramNames, [$userOne, $userTwo], true);
    $this->assertEquals(2, $actualValue[0]);

    $pdo = parent::getPdo();
    $query = $pdo->query('SELECT email FROM users WHERE id IN (1,2)');
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(2, count($resultSet));
    $this->assertEquals('new.user@one.email', $resultSet[0]['email']);
    $this->assertEquals('new.user@two.email', $resultSet[1]['email']);
  }
  
  public function testChangefrommodelIsCallingDoctrineDbalCorrectlyWhenATransactionIsRequestedAndNotEverythingSucceeds() {
    $emailHandler = $this->container->get('service_container')->get('AppBundle\Services\EmailInterface');
    $encoder = $this->container->get('security.password_encoder');

    $userOne = new UserModel($this->dbHandler, $emailHandler, $encoder, $this->container);
    $userTwo = new UserModel($this->dbHandler, $emailHandler, $encoder, $this->container);

    $userOneData = $this->fixtures['users'][0];
    $userOneData['email'] = 'new.user@one.email';
    $userOneData['roles'] = ['ROLE_USER'];
    unset($userOneData['roleId']);
    unset($userOneData['activationHash']);
    unset($userOneData['activationHashGenTs']);
    unset($userOneData['pwResetHash']);
    unset($userOneData['pwResetHashGenTs']);

    $userOne->populateFromArray($userOneData);
    
    $userTwoData = $this->fixtures['users'][1];
    $userTwoData['email'] = 'new.user@two.email';
    $userTwoData['roles'] = ['ROLE_USER'];
    unset($userTwoData['roleId']);
    unset($userTwoData['activationHash']);
    unset($userTwoData['activationHashGenTs']);
    unset($userTwoData['pwResetHash']);
    unset($userTwoData['pwResetHashGenTs']);

    $userTwo->populateFromArray($userTwoData);

    $query = 'UPDATE users SET email = :email WHERE id = :id';
    $paramNames = ['email', 'id', 'test'];

    $exceptionThrown = false;
    try {
      $this->dbHandler->changeFromModel($query, $paramNames, [$userOne, $userTwo], true);
    } catch (\Exception $e) {
      $exceptionThrown = true;
    }
    $this->assertEquals(true, $exceptionThrown);

    $pdo = parent::getPdo();
    $query = $pdo->query('SELECT id, email FROM users WHERE id IN (1,2) ORDER BY id ASC');
    $resultSet = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEquals(2, count($resultSet));
    $this->assertEquals($this->fixtures['users'][0]['email'], $resultSet[0]['email']);
    $this->assertEquals($this->fixtures['users'][1]['email'], $resultSet[1]['email']);
  }

  public function testSelectintomodelIsCommunicatingCorrectlyWithSelectAndIsCreatingTheModelsPopulatedWithTheQueriedData() {
    $query = 'SELECT u.id, u.userName, u.email, u.password, u.isActive, u.created, ur.role
    FROM users as u
    JOIN users_roles as ur ON ur.id = u.roleId
    WHERE u.id = :id';

    $paramData = [
      ['id' => [1, \PDO::PARAM_INT]],
      ['id' => [2, \PDO::PARAM_INT]],
    ];

    $callback = function($data) {
      $data['roles'] = [$data['role']];
      unset($data['role']);
      return($data);
    };

    $modelFactory = new UserModelFactory(
      $this->dbHandler,
      $this->container->get('service_container')->get('AppBundle\Services\EmailInterface'),
      $this->container->get('security.password_encoder'),
      $this->container
    );

    $actualValue = $this->dbHandler->selectIntoModel($query, $paramData, $modelFactory, $callback, 'id');

    $this->assertEquals(2, count($actualValue));

    $this->assertEquals('1', $actualValue[1]->getId());
    $this->assertEquals('activated test username', $actualValue[1]->getUserName());
    $this->assertEquals('test@test.com', $actualValue[1]->getEmail());
    $this->assertEquals('$2y$15$/.9tL5uq.t.BjAxsHGn5feQG4gxe7fh8BZVgj.EN1AW4sKg4RHywW', $actualValue[1]->getPassword());
    $this->assertEquals('1', $actualValue[1]->getIsActive());
    $this->assertEquals(['ROLE_USER'], $actualValue[1]->getRoles());
    
    $this->assertEquals('2', $actualValue[2]->getId());
    $this->assertEquals('another activated username', $actualValue[2]->getUserName());
    $this->assertEquals('activated@test.com', $actualValue[2]->getEmail());
    $this->assertEquals('$2y$15$/.9tL5uq.t.BjAxsHGn5feQG4gxe7fh8BZVgjQEN19W4sKg4RHywW', $actualValue[2]->getPassword());
    $this->assertEquals('1', $actualValue[2]->getIsActive());
    $this->assertEquals(['ROLE_USER'], $actualValue[2]->getRoles());
  }
}