<?php

namespace tests\unit\AppBundle\Services;

use AppBundle\Services\{DbHandler};
use AppBundle\Model\{UserModel, Model, UserModelFactory};

use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Driver\{Connection, Statement};

class DbHandlerTest extends TestCase {
  protected function setUp() {
    parent::setUp();

    $this->connMock = $this->createMock(Connection::class);
    $this->statementMock = $this->createMock(Statement::class);
    $this->userFactoryMock = $this->createMock(UserModelFactory::class);
  }

  public function testCommitItShouldCommitATransactionOnTheDbConnectionThenDecreaseByOneTheCountOfActiveTransactionsAndReturnTrue() {
    $this->connMock->expects($this->once())
      ->method('commit')
      ->with()
      ->willReturn(true);

    $dbHandler = new DbHandler($this->connMock);
    $actualValue = $dbHandler->commit();

    $this->assertEquals(true, $actualValue);
  }

  public function testCommitIfCommittingTheTransactionFailsItShouldNotDecreaseByOneTheCountOfActiveTransactionsAndReturnFalse() {
    $this->connMock->expects($this->once())
      ->method('commit')
      ->with()
      ->willReturn(false);

    $dbHandler = new DbHandler($this->connMock);
    $actualValue = $dbHandler->commit();

    $this->assertEquals(false, $actualValue);
  }

  public function testCommitIfCommittingTheTransactionThrowsAnExceptionItShouldCatchItThenNotDecreaseByOneTheCountOfActiveTransactionsAndReturnFalse() {
    $this->connMock->expects($this->once())
      ->method('commit')
      ->with()
      ->will($this->throwException(new \Exception));

    $dbHandler = new DbHandler($this->connMock);
    $actualValue = $dbHandler->commit();

    $this->assertEquals(false, $actualValue);
  }

  public function testRollbackItShouldRollbackATransactionOnTheDbConnectionThenDecreaseByOneTheCountOfActiveTransactionsAndReturnTrue() {
    $this->connMock->expects($this->once())
      ->method('rollBack')
      ->with()
      ->willReturn(true);

    $dbHandler = new DbHandler($this->connMock);
    $actualValue = $dbHandler->rollBack();

    $this->assertEquals(true, $actualValue);
  }

  public function testRollbackIfRollingbackTheTransactionFailsItShouldNotDecreaseByOneTheCountOfActiveTransactionsAndReturnFalse() {
    $this->connMock->expects($this->once())
      ->method('rollBack')
      ->with()
      ->willReturn(false);

    $dbHandler = new DbHandler($this->connMock);
    $actualValue = $dbHandler->rollBack();

    $this->assertEquals(false, $actualValue);
  }

  public function testRollbackIfRollingbackTheTransactionThrowsAnExceptionItShouldCatchItThenNotDecreaseByOneTheCountOfActiveTransactionsAndReturnFalse() {
    $this->connMock->expects($this->once())
      ->method('rollBack')
      ->with()
      ->will($this->throwException(new \Exception));

    $dbHandler = new DbHandler($this->connMock);
    $actualValue = $dbHandler->rollBack();

    $this->assertEquals(false, $actualValue);
  }

  public function testQueryItShouldCallDbalQueryWithTheProvidedQueryAndReturnTheArrayObtainedFromCallingFetchOnTheQueryExecutionResult() {
    $query = 'SELECT id FROM users';

    $this->connMock->expects($this->once())
      ->method('query')
      ->with($query)
      ->willReturn($this->statementMock);

    $resultSet = [
      ['id' => 1],
      ['id' => 3],
    ];

    $this->statementMock->expects($this->once())
      ->method('fetchAll')
      ->with(\PDO::FETCH_ASSOC)
      ->willReturn($resultSet);

    $dbHandler = new DbHandler($this->connMock);
    $actualValue = $dbHandler->query($query);

    $this->assertEquals($resultSet, $actualValue);
  }

  public function testQueryIfDbalQueryThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('DBAL query() exception msg');

    $query = 'SELECT id FROM users';

    $this->connMock->expects($this->once())
      ->method('query')
      ->with($query)
      ->will($this->throwException(new \Exception('DBAL query() exception msg')));

    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->query($query);
  }

  public function testQueryIfFetchallThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('DBAL query() exception msg');

    $query = 'SELECT id FROM users';

    $this->connMock->expects($this->once())
      ->method('query')
      ->with($query)
      ->willReturn($this->statementMock);

    $resultSet = [
      ['id' => 1],
      ['id' => 3],
    ];

    $this->statementMock->expects($this->once())
      ->method('fetchAll')
      ->with(\PDO::FETCH_ASSOC)
      ->will($this->throwException(new \Exception('DBAL query() exception msg')));

    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->query($query);
  }

  public function testSelectItShouldCallDbalPrepareThenForEachExecutionDataBindTheQueryParamsThenExecuteTheQueryAndReturnTheResultsOfFetchall() {
    $query = 'SELECT * FROM users WHERE id=:id AND email=:email';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
        'email' => ['test@test.com', \PDO::PARAM_STR],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
        'email' => ['p@p.com', \PDO::PARAM_STR],
      ],
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(4))
      ->method('bindValue')
      ->withConsecutive(
        ['id', 10, \PDO::PARAM_INT],
        ['email', 'test@test.com', \PDO::PARAM_STR],
        ['id', 4, \PDO::PARAM_INT],
        ['email', 'p@p.com', \PDO::PARAM_STR]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);
    
    $expectedValue = [
      [
        [
          'id' => 10,
          'email' => 'test@test.com',
          'isActive' => 1,
        ],
      ],
      [
        [
          'id' => 4,
          'email' => 'p@p.com',
          'isActive' => 0,
        ]
      ],
    ];

    $this->statementMock->expects($this->exactly(2))
      ->method('fetchAll')
      ->with(\PDO::FETCH_ASSOC)
      ->will($this->onConsecutiveCalls(
        [
          [
            'id' => 10,
            'email' => 'test@test.com',
            'isActive' => 1,
          ],
        ],
        [
          [
            'id' => 4,
            'email' => 'p@p.com',
            'isActive' => 0,
          ]
        ]
      ));
    
    $dbHandler = new DbHandler($this->connMock);
    $actualValue = $dbHandler->select($query, $paramData);

    $this->assertEquals($expectedValue, $actualValue);
  }

  public function testSelectIfASpecificSetOfArgsForFetchallIsProvidedItShouldReturnTheResultsOfFetchallUsingThoseArgs() {
    $query = 'SELECT id, userName FROM users WHERE id=:id AND email=:email';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
        'email' => ['test@test.com', \PDO::PARAM_STR],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
        'email' => ['p@p.com', \PDO::PARAM_STR],
      ],
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(4))
      ->method('bindValue')
      ->withConsecutive(
        ['id', 10, \PDO::PARAM_INT],
        ['email', 'test@test.com', \PDO::PARAM_STR],
        ['id', 4, \PDO::PARAM_INT],
        ['email', 'p@p.com', \PDO::PARAM_STR]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);
    
    $expectedValue = ['Pedro', 'João'];

    $this->statementMock->expects($this->exactly(2))
      ->method('fetchAll')
      ->with(\PDO::FETCH_COLUMN, 1)
      ->will($this->onConsecutiveCalls('Pedro', 'João'));
    
    $dbHandler = new DbHandler($this->connMock);
    $actualValue = $dbHandler->select($query, $paramData, [\PDO::FETCH_COLUMN, 1]);

    $this->assertEquals($expectedValue, $actualValue);
  }

  public function testSelectIfTheQueryIsNotASelectItShouldThrowAnExceptionWithAnErrorMsg() {
    $query = "UPDATE users SET email = :email WHERE id = :id";

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("The query '${query}' is not a SELECT statement.");

    $paramData = [
      [
        'id' => [3, \PDO::PARAM_INT],
        'email' => ['new.email.com', \PDO::PARAM_STR],
      ],
    ];

    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->select($query, $paramData);
  }

  public function testSelectIfTheProvidedParamDataIsAnEmptyArrayItShouldThrowAnExceptionWithAnErrorMsg() {
    $query = "SELECT * FROM users WHERE id = 1";
    
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(
      "No parameter data was provided for the query '${query}'. As such select() is not the most efficient option. Consider using query() instead."
    );

    $paramData = [];

    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->select($query, $paramData);
  }

  public function testSelectIfPreparingTheQueryThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('prepare test exception msg');

    $query = 'SELECT * FROM users WHERE id=:id AND email=:email';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
        'email' => ['test@test.com', \PDO::PARAM_STR],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
        'email' => ['p@p.com', \PDO::PARAM_STR],
      ],
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->will($this->throwException(new \Exception('prepare test exception msg')));
    
    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->select($query, $paramData);
  }

  public function testSelectIfAParamFailsToBindItShouldThrowAnException() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("The parameter named 'id' for the execution #2 failed to be bound.");

    $query = 'SELECT * FROM users WHERE id=:id AND email=:email';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
        'email' => ['test@test.com', \PDO::PARAM_STR],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
        'email' => ['p@p.com', \PDO::PARAM_STR],
      ],
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(3))
      ->method('bindValue')
      ->withConsecutive(
        ['id', 10, \PDO::PARAM_INT],
        ['email', 'test@test.com', \PDO::PARAM_STR],
        ['id', 4, \PDO::PARAM_INT]
      )
      ->will($this->onConsecutiveCalls(true, true, false));
    
    $this->statementMock->expects($this->once())
      ->method('execute')
      ->with()
      ->willReturn(true);
    
    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->select($query, $paramData);
  }

  public function testSelectIfBindvalueThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $query = 'SELECT * FROM users WHERE id=:id AND email=:email';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
        'email' => ['test@test.com', \PDO::PARAM_STR],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
        'email' => ['p@p.com', \PDO::PARAM_STR],
      ],
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(3))
      ->method('bindValue')
      ->withConsecutive(
        ['id', 10, \PDO::PARAM_INT],
        ['email', 'test@test.com', \PDO::PARAM_STR],
        ['id', 4, \PDO::PARAM_INT]
      )
      ->will($this->onConsecutiveCalls(true, true, $this->throwException(new \Exception)));
    
    $this->statementMock->expects($this->once())
      ->method('execute')
      ->willReturn(true);
    
    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->select($query, $paramData);
  }

  public function testSelectIfExecuteFailsItShouldThrowAnException() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The query\'s execution failed for the execution #1');

    $query = 'SELECT * FROM users WHERE id=:id AND email=:email';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
        'email' => ['test@test.com', \PDO::PARAM_STR],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
        'email' => ['p@p.com', \PDO::PARAM_STR],
      ],
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('bindValue')
      ->withConsecutive(
        ['id', 10, \PDO::PARAM_INT],
        ['email', 'test@test.com', \PDO::PARAM_STR]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->once())
      ->method('execute')
      ->with()
      ->willReturn(false);

    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->select($query, $paramData);
  }

  public function testSelectIfExecuteThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('execute test exception msg');

    $query = 'SELECT * FROM users WHERE id=:id AND email=:email';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
        'email' => ['test@test.com', \PDO::PARAM_STR],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
        'email' => ['p@p.com', \PDO::PARAM_STR],
      ],
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('bindValue')
      ->withConsecutive(
        ['id', 10, \PDO::PARAM_INT],
        ['email', 'test@test.com', \PDO::PARAM_STR]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->once())
      ->method('execute')
      ->with()
      ->will($this->throwException(new \Exception('execute test exception msg')));

    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->select($query, $paramData);
  }

  public function testSelectIfFetchallThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $query = 'SELECT * FROM users WHERE id=:id AND email=:email';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
        'email' => ['test@test.com', \PDO::PARAM_STR],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
        'email' => ['p@p.com', \PDO::PARAM_STR],
      ],
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(4))
      ->method('bindValue')
      ->withConsecutive(
        ['id', 10, \PDO::PARAM_INT],
        ['email', 'test@test.com', \PDO::PARAM_STR],
        ['id', 4, \PDO::PARAM_INT],
        ['email', 'p@p.com', \PDO::PARAM_STR]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('fetchAll')
      ->with(\PDO::FETCH_ASSOC)
      ->will($this->onConsecutiveCalls(
        [
          'id' => [10, \PDO::PARAM_INT],
          'email' => ['test@test.com', \PDO::PARAM_STR],
        ],
        $this->throwException(new \Exception)
      ));

    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->select($query, $paramData);
  }

  public function testSelectIfFetchallFailsItShouldThrowAnException() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The call to fetchAll() failed for the execution #2');

    $query = 'SELECT * FROM users WHERE id=:id AND email=:email';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
        'email' => ['test@test.com', \PDO::PARAM_STR],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
        'email' => ['p@p.com', \PDO::PARAM_STR],
      ],
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(4))
      ->method('bindValue')
      ->withConsecutive(
        ['id', 10, \PDO::PARAM_INT],
        ['email', 'test@test.com', \PDO::PARAM_STR],
        ['id', 4, \PDO::PARAM_INT],
        ['email', 'p@p.com', \PDO::PARAM_STR]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('fetchAll')
      ->with(\PDO::FETCH_ASSOC)
      ->will($this->onConsecutiveCalls(
        [
          'id' => [10, \PDO::PARAM_INT],
          'email' => ['test@test.com', \PDO::PARAM_STR],
        ],
        false
      ));

    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->select($query, $paramData);
  }

  public function testChangeIfHandlingAnInsertQueryItShouldPrepareTheQueryThenForEachSetBindEachParamValueThenExecuteTheQueryAndReturnAnArrayWithTheValuesOfLastinsertidOfEachSet() {
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction'])
      ->getMock();

    $query = 'INSERT INTO tableName VALUES(null,:param1,:param2)';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR]
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(4))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR],
        ['param1', 'value 3', \PDO::PARAM_STR],
        ['param2', 'value 4', \PDO::PARAM_STR]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);

    $this->connMock->expects($this->exactly(2))
      ->method('lastInsertId')
      ->with()
      ->will($this->onConsecutiveCalls(5, 9));

    $expectedValue = [5, 9];

    $actualValue = $dbHandlerMock->change($query, $paramData);

    $this->assertEquals($expectedValue, $actualValue);
  }
  
  public function testChangeIfHandlingAnInsertQueryAndASetFailsToBindItShouldSkipThatSetAndReturnAnArrayWithANullForThatSetsCorrespondingIndex() {
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction'])
      ->getMock();

    $query = 'INSERT INTO tableName VALUES(null,:param1,:param2)';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR]
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR]
      ],
      [
        'param1' => ['value 5', \PDO::PARAM_STR],
        'param2' => ['value 6', \PDO::PARAM_STR]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(5))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR],
        ['param1', 'value 3', \PDO::PARAM_STR],
        ['param1', 'value 5', \PDO::PARAM_STR],
        ['param2', 'value 6', \PDO::PARAM_STR]
      )
      ->will($this->onConsecutiveCalls(true, true, false, true, true));
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);

    $this->connMock->expects($this->exactly(2))
      ->method('lastInsertId')
      ->with()
      ->will($this->onConsecutiveCalls(5, 9));

    $dbHandlerMock->expects($this->once())
      ->method('inTransaction')
      ->with()
      ->willReturn(false);

    $expectedValue = [5, null, 9];

    $actualValue = $dbHandlerMock->change($query, $paramData);

    $this->assertEquals($expectedValue, $actualValue);
  }

  public function testChangeIfHandlingAnInsertQueryAndExecuteFailsItShouldSkipThatSetAndReturnAnArrayWithANullForThatSetsCorrespondingIndex() {
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction'])
      ->getMock();

    $query = 'INSERT INTO tableName VALUES(null,:param1,:param2)';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR]
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR]
      ],
      [
        'param1' => ['value 5', \PDO::PARAM_STR],
        'param2' => ['value 6', \PDO::PARAM_STR]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(6))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR],
        ['param1', 'value 3', \PDO::PARAM_STR],
        ['param2', 'value 4', \PDO::PARAM_STR],
        ['param1', 'value 5', \PDO::PARAM_STR],
        ['param2', 'value 6', \PDO::PARAM_STR]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(3))
      ->method('execute')
      ->with()
      ->will($this->onConsecutiveCalls(true, false, true));

    $this->connMock->expects($this->exactly(2))
      ->method('lastInsertId')
      ->with()
      ->will($this->onConsecutiveCalls(5, 9));

    $dbHandlerMock->expects($this->once())
      ->method('inTransaction')
      ->with()
      ->willReturn(false);

    $expectedValue = [5, null, 9];

    $actualValue = $dbHandlerMock->change($query, $paramData);

    $this->assertEquals($expectedValue, $actualValue);
  }

  public function testChangeIfHandlingAnInsertQueryAndLastinsertidThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('lastInsertId test exception msg');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction'])
      ->getMock();

    $query = 'INSERT INTO tableName VALUES(null,:param1,:param2)';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR]
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(4))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR],
        ['param1', 'value 3', \PDO::PARAM_STR],
        ['param2', 'value 4', \PDO::PARAM_STR]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);

    $this->connMock->expects($this->exactly(2))
      ->method('lastInsertId')
      ->with()
      ->will($this->onConsecutiveCalls(5, $this->throwException(new \Exception('lastInsertId test exception msg'))));

    $dbHandlerMock->change($query, $paramData);
  }
  
  public function testChangeIfHandlingAnUpdateQueryItShouldPrepareTheQueryThenForEachSetBindEachParamValueThenExecuteTheQueryAndReturnAnArrayWithTheNumberOfAffectedRows() {
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction'])
      ->getMock();

    $query = 'UPDATE tableName SET col1=:param1, col2=:param2 WHERE id=:id';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR],
        'id' => [1, \PDO::PARAM_INT]
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR],
        'id' => [2, \PDO::PARAM_INT]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(6))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR],
        ['id', 1, \PDO::PARAM_INT],
        ['param1', 'value 3', \PDO::PARAM_STR],
        ['param2', 'value 4', \PDO::PARAM_STR],
        ['id', 2, \PDO::PARAM_INT]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);

    $this->statementMock->expects($this->exactly(2))
      ->method('rowCount')
      ->with()
      ->will($this->onConsecutiveCalls(1, 1));
    
    $expectedValue = [2];

    $actualValue = $dbHandlerMock->change($query, $paramData);

    $this->assertEquals($expectedValue, $actualValue);
  }

  public function testChangeIfHandlingAnUpdateQueryAndASetFailsToBindItShouldSkipThatSetAndReturnAnArrayWithTheNumberOfAffectedRows() {
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction'])
      ->getMock();

    $query = 'UPDATE tableName SET col1=:param1, col2=:param2 WHERE id=:id';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR],
        'id' => [1, \PDO::PARAM_INT]
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR],
        'id' => [2, \PDO::PARAM_INT]
      ],
      [
        'param1' => ['value 5', \PDO::PARAM_STR],
        'param2' => ['value 6', \PDO::PARAM_STR],
        'id' => [3, \PDO::PARAM_INT]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(7))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR],
        ['id', 1, \PDO::PARAM_INT],
        ['param1', 'value 3', \PDO::PARAM_STR],
        ['param1', 'value 5', \PDO::PARAM_STR],
        ['param2', 'value 6', \PDO::PARAM_STR],
        ['id', 3, \PDO::PARAM_INT]
      )
      ->will($this->onConsecutiveCalls(true, true, true, false, true, true, true));
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);

    $this->statementMock->expects($this->exactly(2))
      ->method('rowCount')
      ->with()
      ->will($this->onConsecutiveCalls(1, 1));

    $dbHandlerMock->expects($this->once())
      ->method('inTransaction')
      ->with()
      ->willReturn(false);
    
    $expectedValue = [2];

    $actualValue = $dbHandlerMock->change($query, $paramData);

    $this->assertEquals($expectedValue, $actualValue);
  }

  public function testChangeIfHandlingAnUpdateQueryAndExecuteFailsItShouldSkipThatSetAndReturnAnArrayWithTheNumberOfAffectedRows() {
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction'])
      ->getMock();

    $query = 'UPDATE tableName SET col1=:param1, col2=:param2 WHERE id=:id';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR],
        'id' => [1, \PDO::PARAM_INT]
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR],
        'id' => [2, \PDO::PARAM_INT]
      ],
      [
        'param1' => ['value 5', \PDO::PARAM_STR],
        'param2' => ['value 6', \PDO::PARAM_STR],
        'id' => [3, \PDO::PARAM_INT]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(9))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR],
        ['id', 1, \PDO::PARAM_INT],
        ['param1', 'value 3', \PDO::PARAM_STR],
        ['param2', 'value 4', \PDO::PARAM_STR],
        ['id', 2, \PDO::PARAM_INT],
        ['param1', 'value 5', \PDO::PARAM_STR],
        ['param2', 'value 6', \PDO::PARAM_STR],
        ['id', 3, \PDO::PARAM_INT]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(3))
      ->method('execute')
      ->with()
      ->will($this->onConsecutiveCalls(true, false, true));

    $this->statementMock->expects($this->exactly(2))
      ->method('rowCount')
      ->with()
      ->will($this->onConsecutiveCalls(1, 1));

    $dbHandlerMock->expects($this->once())
      ->method('inTransaction')
      ->with()
      ->willReturn(false);
    
    $expectedValue = [2];

    $actualValue = $dbHandlerMock->change($query, $paramData);

    $this->assertEquals($expectedValue, $actualValue);
  }
  
  public function testChangeIfHandlingAnUpdateQueryAndRowcountThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('rowCount test exception msg');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction'])
      ->getMock();

    $query = 'UPDATE tableName SET col1=:param1, col2=:param2 WHERE id=:id';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR],
        'id' => [1, \PDO::PARAM_INT]
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR],
        'id' => [2, \PDO::PARAM_INT]
      ],
      [
        'param1' => ['value 5', \PDO::PARAM_STR],
        'param2' => ['value 6', \PDO::PARAM_STR],
        'id' => [3, \PDO::PARAM_INT]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(6))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR],
        ['id', 1, \PDO::PARAM_INT],
        ['param1', 'value 3', \PDO::PARAM_STR],
        ['param2', 'value 4', \PDO::PARAM_STR],
        ['id', 2, \PDO::PARAM_INT]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);

    $this->statementMock->expects($this->exactly(2))
      ->method('rowCount')
      ->with()
      ->will($this->onConsecutiveCalls(1, $this->throwException(new \Exception('rowCount test exception msg'))));
    
    $dbHandlerMock->change($query, $paramData);
  }
  
  public function testChangeIfHandlingADeleteQueryItShouldPrepareTheQueryThenForEachSetBindEachParamValueThenExecuteTheQueryAndReturnAnArrayWithTheNumberOfAffectedRows() {
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction'])
      ->getMock();

    $query = 'DELETE FROM tableName WHERE id=:id';
    $paramData = [
      [
        'id' => [1, \PDO::PARAM_INT]
      ],
      [
        'id' => [2, \PDO::PARAM_INT]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('bindValue')
      ->withConsecutive(
        ['id', 1, \PDO::PARAM_INT],
        ['id', 2, \PDO::PARAM_INT]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);

    $this->statementMock->expects($this->exactly(2))
      ->method('rowCount')
      ->with()
      ->will($this->onConsecutiveCalls(1, 1));
    
    $expectedValue = [2];

    $actualValue = $dbHandlerMock->change($query, $paramData);

    $this->assertEquals($expectedValue, $actualValue);
  }

  public function testChangeIfHandlingADeleteQueryAndASetFailsToBindItShouldSkipThatSetAndReturnAnArrayWithTheNumberOfAffectedRows() {
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction'])
      ->getMock();

    $query = 'DELETE FROM tableName WHERE id=:id';
    $paramData = [
      [
        'id' => [1, \PDO::PARAM_INT]
      ],
      [
        'id' => [2, \PDO::PARAM_INT]
      ],
      [
        'id' => [3, \PDO::PARAM_INT]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(3))
      ->method('bindValue')
      ->withConsecutive(
        ['id', 1, \PDO::PARAM_INT],
        ['id', 2, \PDO::PARAM_INT],
        ['id', 3, \PDO::PARAM_INT]
      )
      ->will($this->onConsecutiveCalls(true, false, true));
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);

    $this->statementMock->expects($this->exactly(2))
      ->method('rowCount')
      ->with()
      ->will($this->onConsecutiveCalls(1, 1));

    $dbHandlerMock->expects($this->once())
      ->method('inTransaction')
      ->with()
      ->willReturn(false);
    
    $expectedValue = [2];

    $actualValue = $dbHandlerMock->change($query, $paramData);

    $this->assertEquals($expectedValue, $actualValue);
  }

  public function testChangeIfHandlingADeleteQueryAndExecuteFailsItShouldSkipThatSetAndReturnAnArrayWithTheNumberOfAffectedRows() {
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction'])
      ->getMock();

    $query = 'DELETE FROM tableName WHERE id=:id';
    $paramData = [
      [
        'id' => [1, \PDO::PARAM_INT]
      ],
      [
        'id' => [2, \PDO::PARAM_INT]
      ],
      [
        'id' => [3, \PDO::PARAM_INT]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(3))
      ->method('bindValue')
      ->withConsecutive(
        ['id', 1, \PDO::PARAM_INT],
        ['id', 2, \PDO::PARAM_INT],
        ['id', 3, \PDO::PARAM_INT]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(3))
      ->method('execute')
      ->with()
      ->will($this->onConsecutiveCalls(true, false, true));

    $this->statementMock->expects($this->exactly(2))
      ->method('rowCount')
      ->with()
      ->will($this->onConsecutiveCalls(1, 1));

    $dbHandlerMock->expects($this->once())
      ->method('inTransaction')
      ->with()
      ->willReturn(false);
    
    $expectedValue = [2];

    $actualValue = $dbHandlerMock->change($query, $paramData);

    $this->assertEquals($expectedValue, $actualValue);
  }
  
  public function testChangeIfHandlingADeleteQueryAndRowcountThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('rowCount test exception msg');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction'])
      ->getMock();

    $query = 'DELETE FROM tableName WHERE id=:id';
    $paramData = [
      [
        'id' => [1, \PDO::PARAM_INT]
      ],
      [
        'id' => [2, \PDO::PARAM_INT]
      ],
      [
        'id' => [3, \PDO::PARAM_INT]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(3))
      ->method('bindValue')
      ->withConsecutive(
        ['id', 1, \PDO::PARAM_INT],
        ['id', 2, \PDO::PARAM_INT],
        ['id', 3, \PDO::PARAM_INT]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(3))
      ->method('execute')
      ->with()
      ->willReturn(true);

    $this->statementMock->expects($this->exactly(3))
      ->method('rowCount')
      ->with()
      ->will($this->onConsecutiveCalls(1, 1, $this->throwException(new \Exception('rowCount test exception msg'))));
    
    $dbHandlerMock->change($query, $paramData);
  }

  public function testChangeIfTheQueryIsASelectItShouldThrowAnExceptionWithAnErrorMsg() {
    $query = "SELECT * FROM users WHERE id = :id";
    
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("The query '${query}' is not an INSERT, UPDATE or DELETE statement.");

    $paramData = [
      [
        'id' => [3, \PDO::PARAM_INT],
      ],
    ];

    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->change($query, $paramData);
  }

  public function testChangeIfTheProvidedParamDataIsAnEmptyArrayItShouldThrowAnExceptionWithAnErrorMsg() {
    $query = 'DELETE FROM tableName WHERE id = 1';
    
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(
      "No parameter data was provided for the query '${query}'. As such change() is not the most efficient option. Consider using query() instead."
    );

    $paramData = [];
    
    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->change($query, $paramData);
  }

  public function testChangeIfASetFailsToBindAndThereAreActiveTransactionsItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The query on index number 1 failed to be executed.');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction', 'beginTransaction'])
      ->getMock();

    $query = 'INSERT INTO tableName VALUES(null,:param1,:param2)';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR],
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR],
      ],
      [
        'param1' => ['value 5', \PDO::PARAM_STR],
        'param2' => ['value 6', \PDO::PARAM_STR],
      ],
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(3))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR],
        ['param1', 'value 3', \PDO::PARAM_STR]
      )
      ->will($this->onConsecutiveCalls(true, true, false));
    
    $this->statementMock->expects($this->once())
      ->method('execute')
      ->with()
      ->willReturn(true);

    $this->connMock->expects($this->once())
      ->method('lastInsertId')
      ->with()
      ->willReturn(5);

    $dbHandlerMock->expects($this->once())
      ->method('inTransaction')
      ->with()
      ->willReturn(true);
    
    $dbHandlerMock->expects($this->once())
      ->method('beginTransaction')
      ->with()
      ->willReturn(true);

    $dbHandlerMock->beginTransaction();
    $dbHandlerMock->change($query, $paramData);
  }

  public function testChangeIfExecuteFailsAndThereAreActiveTransactionsItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The query on index number 1 failed to be executed.');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction', 'beginTransaction'])
      ->getMock();

    $query = 'INSERT INTO tableName VALUES(null,:param1,:param2)';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR],
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR],
      ],
      [
        'param1' => ['value 5', \PDO::PARAM_STR],
        'param2' => ['value 6', \PDO::PARAM_STR],
      ],
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(4))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR],
        ['param1', 'value 3', \PDO::PARAM_STR],
        ['param2', 'value 4', \PDO::PARAM_STR]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->will($this->onConsecutiveCalls(true, false));

    $this->connMock->expects($this->exactly(1))
      ->method('lastInsertId')
      ->with()
      ->will($this->onConsecutiveCalls(5));

    $dbHandlerMock->expects($this->once())
      ->method('inTransaction')
      ->with()
      ->willReturn(true);
    
    $dbHandlerMock->expects($this->once())
      ->method('beginTransaction')
      ->with()
      ->willReturn(true);
      
    $dbHandlerMock->beginTransaction();
    $dbHandlerMock->change($query, $paramData);
  }

  public function testChangeIfHandlingANonInsertQueryAndAnExecuteAffectsZeroRowsAndThereAreActiveTransactionsItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The query on index number 1 failed to be executed.');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['inTransaction', 'beginTransaction'])
      ->getMock();

    $query = 'UPDATE tableName SET col1=:param1, col2=:param2 WHERE id=:id';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR],
        'id' => [1, \PDO::PARAM_INT]
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR],
        'id' => [2, \PDO::PARAM_INT]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(6))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR],
        ['id', 1, \PDO::PARAM_INT],
        ['param1', 'value 3', \PDO::PARAM_STR],
        ['param2', 'value 4', \PDO::PARAM_STR],
        ['id', 2, \PDO::PARAM_INT]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->willReturn(true);

    $this->statementMock->expects($this->exactly(2))
      ->method('rowCount')
      ->with()
      ->will($this->onConsecutiveCalls(1, 0));

    $dbHandlerMock->expects($this->once())
      ->method('inTransaction')
      ->with()
      ->willReturn(true);
    
    $dbHandlerMock->expects($this->once())
      ->method('beginTransaction')
      ->with()
      ->willReturn(true);
      
    $dbHandlerMock->beginTransaction();
    $dbHandlerMock->change($query, $paramData);
  }

  public function testChangeIfPrepareThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('prepare test exception msg');

    $query = 'INSERT INTO tableName VALUES(null,:param1,:param2)';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR]
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->will($this->throwException(new \Exception('prepare test exception msg')));
    
    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->change($query, $paramData);
  }

  public function testChangeIfBindvalueThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('bindValue test exception msg');

    $query = 'INSERT INTO tableName VALUES(null,:param1,:param2)';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR]
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR]
      )
      ->will($this->onConsecutiveCalls(true, $this->throwException(new \Exception('bindValue test exception msg'))));

    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->change($query, $paramData);
  }

  public function testChangeIfExecuteThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $query = 'INSERT INTO tableName VALUES(null,:param1,:param2)';
    $paramData = [
      [
        'param1' => ['value 1', \PDO::PARAM_STR],
        'param2' => ['value 2', \PDO::PARAM_STR]
      ],
      [
        'param1' => ['value 3', \PDO::PARAM_STR],
        'param2' => ['value 4', \PDO::PARAM_STR]
      ]
    ];

    $this->connMock->expects($this->once())
      ->method('prepare')
      ->with($query)
      ->willReturn($this->statementMock);
    
    $this->statementMock->expects($this->exactly(4))
      ->method('bindValue')
      ->withConsecutive(
        ['param1', 'value 1', \PDO::PARAM_STR],
        ['param2', 'value 2', \PDO::PARAM_STR],
        ['param1', 'value 3', \PDO::PARAM_STR],
        ['param2', 'value 4', \PDO::PARAM_STR]
      )
      ->willReturn(true);
    
    $this->statementMock->expects($this->exactly(2))
      ->method('execute')
      ->with()
      ->will($this->onConsecutiveCalls(true, $this->throwException(new \Exception)));

    $this->connMock->expects($this->once())
      ->method('lastInsertId')
      ->with()
      ->willReturn(5);

    $dbHandler = new DbHandler($this->connMock);
    $dbHandler->change($query, $paramData);
  }

  public function testChangeinbulkItShouldStartATransactionThenCallDbhandlerChangeThenCommitTheTransactionAndReturnWhatChangeReturned() {
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['change'])
      ->getMock();
    
    $this->connMock->expects($this->once())
      ->method('beginTransaction')
      ->with()
      ->WillReturn(true);
    
    $query = 'this is a test query';
    $paramData = [
      [
        'param1' => 'value1',
        'param2' => 'value2',
      ],
    ];
    $changeReturnValue = [[2]];

    $dbHandlerMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn($changeReturnValue);
    
    $this->connMock->expects($this->once())
      ->method('commit')
      ->with()
      ->willReturn(true);
    
    $actualValue = $dbHandlerMock->changeInBulk($query, $paramData);

    $this->assertEquals($changeReturnValue, $actualValue);
  }

  public function testChangeinbulkIfTheTransactionFailsToStartItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('A transaction failed to be initiated.');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['change'])
      ->getMock();

    $this->connMock->expects($this->once())
      ->method('beginTransaction')
      ->with()
      ->WillReturn(false);
    
    $query = 'this is a test query';
    $paramData = [
      [
        'param1' => 'value1',
        'param2' => 'value2',
      ],
    ];
    
    $dbHandlerMock->changeInBulk($query, $paramData);
  }

  public function testChangeinbulkIfStartingTheTransactionThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('A transaction failed to be initiated.');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['change'])
      ->getMock();

    $this->connMock->expects($this->once())
      ->method('beginTransaction')
      ->with()
      ->will($this->throwException(new \Exception('beginTransaction test exception msg')));
    
    $query = 'this is a test query';
    $paramData = [
      [
        'param1' => 'value1',
        'param2' => 'value2',
      ],
    ];
    
    $dbHandlerMock->changeInBulk($query, $paramData);
  }

  public function testChangeinbulkIfTheTransactionFailsToBeCommittedItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('A transaction failed to be committed.');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['change'])
      ->getMock();

    $this->connMock->expects($this->once())
      ->method('beginTransaction')
      ->with()
      ->WillReturn(true);
    
    $query = 'this is a test query';
    $paramData = [
      [
        'param1' => 'value1',
        'param2' => 'value2',
      ],
    ];
    $changeReturnValue = [[2]];

    $dbHandlerMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn($changeReturnValue);
    
    $this->connMock->expects($this->once())
      ->method('commit')
      ->with()
      ->willReturn(false);
    
    $dbHandlerMock->changeInBulk($query, $paramData);
  }

  public function testChangeinbulkIfCommittingTheTransactionThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['change'])
      ->getMock();

    $this->connMock->expects($this->once())
      ->method('beginTransaction')
      ->with()
      ->WillReturn(true);
    
    $query = 'this is a test query';
    $paramData = [
      [
        'param1' => 'value1',
        'param2' => 'value2',
      ],
    ];
    $changeReturnValue = [[2]];

    $dbHandlerMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->willReturn($changeReturnValue);
    
    $this->connMock->expects($this->once())
      ->method('commit')
      ->with()
      ->will($this->throwException(new \Exception));
    
    $dbHandlerMock->changeInBulk($query, $paramData);
  }

  public function testChangeinbulkIfDbhandlerChangeThrowsAnExceptionItShouldCatchItThenRollbackTheTransactionAndThrowANewExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Not all queries were successfully executed. As such the transaction was rolled back.');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['change'])
      ->getMock();

    $this->connMock->expects($this->once())
      ->method('beginTransaction')
      ->with()
      ->WillReturn(true);
    
    $query = 'this is a test query';
    $paramData = [
      [
        'param1' => 'value1',
        'param2' => 'value2',
      ],
    ];

    $dbHandlerMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception));
    
    $this->connMock->expects($this->once())
      ->method('rollBack')
      ->with()
      ->willReturn(true);
    
    $dbHandlerMock->changeInBulk($query, $paramData);
  }

  public function testChangeinbulkIfDbhandlerChangeThrowsAnExceptionAndTheTransactionFailsToBeRolledBackItShouldThrowANewExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Not all queries were successfully executed. As such the transaction was rolled back. NOTE: The transaction failed to be rolled back.');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['change'])
      ->getMock();

    $this->connMock->expects($this->once())
      ->method('beginTransaction')
      ->with()
      ->WillReturn(true);
    
    $query = 'this is a test query';
    $paramData = [
      [
        'param1' => 'value1',
        'param2' => 'value2',
      ],
    ];

    $dbHandlerMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception));
    
    $this->connMock->expects($this->once())
      ->method('rollBack')
      ->with()
      ->willReturn(false);
    
    $dbHandlerMock->changeInBulk($query, $paramData);
  }

  public function testChangeinbulkIfDbhandlerChangeThrowsAnExceptionAndRollingbackTheTransactionThrowsAnExceptionItShouldThrowANewExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Not all queries were successfully executed. As such the transaction was rolled back. NOTE: The transaction failed to be rolled back.');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['change'])
      ->getMock();

    $this->connMock->expects($this->once())
      ->method('beginTransaction')
      ->with()
      ->WillReturn(true);
    
    $query = 'this is a test query';
    $paramData = [
      [
        'param1' => 'value1',
        'param2' => 'value2',
      ],
    ];

    $dbHandlerMock->expects($this->once())
      ->method('change')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception));
    
    $this->connMock->expects($this->once())
      ->method('rollBack')
      ->with()
      ->will($this->throwException(new \Exception('test exception message for the final exception thrown')));
    
    $dbHandlerMock->changeInBulk($query, $paramData);
  }

  public function testChangefrommodelIfATransactionIsNotRequestedItShouldLoopThroughEachModelAndBuildTheArrayWithTheBindValueDataThenCallChangeAndReturnItsReturnedValue() {
    $modelMock = $this->createMock(UserModel::class);
    $otherModelMock = $this->createMock(UserModel::class);
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['change'])
      ->getMock();

    $query = 'INSERT INTO users VALUES(null,:userName,:passWord,:email,:isActive)';
    $paramNames = ['userName', 'passWord', 'email', 'isActive'];
    
    $modelUserName = 'Pedro Henriques';
    $modelPassWord = 'password';
    $modelEmail = 'pjh@gmail.com';
    $modelIsActive = false;
    $modelId = 1;
    $modelDbData = [
      'userName' => [$modelUserName, \PDO::PARAM_STR],
      'passWord' => [$modelPassWord, \PDO::PARAM_STR],
      'email' => [$modelEmail, \PDO::PARAM_STR],
      'isActive' => [$modelIsActive, \PDO::PARAM_BOOL],
    ];
    
    $otherModelUserName = 'João Silva';
    $otherModelPassWord = 'other password';
    $otherModelEmail = 'js@gmail.com';
    $otherModelIsActive = false;
    $otherModelId = 2;
    $otherModelDbData = [
      'userName' => [$otherModelUserName, \PDO::PARAM_STR],
      'passWord' => [$otherModelPassWord, \PDO::PARAM_STR],
      'email' => [$otherModelEmail, \PDO::PARAM_STR],
      'isActive' => [$otherModelIsActive, \PDO::PARAM_BOOL],
    ];

    $modelMock->expects($this->once())
      ->method('dbData')
      ->with()
      ->willReturn($modelDbData);

    $otherModelMock->expects($this->once())
      ->method('dbData')
      ->with()
      ->willReturn($otherModelDbData);
    
    $dbHandlerMock->expects($this->once())
      ->method('change')
      ->with($query, [$modelDbData, $otherModelDbData])
      ->willReturn([$modelId, $otherModelId]);
    
    $actualValue = $dbHandlerMock->changeFromModel($query, $paramNames, [$modelMock, $otherModelMock], false);

    $this->assertEquals([1, 2], $actualValue);
  }

  public function testChangefrommodelIfATransactionIsRequestedItShouldLoopThroughEachModelAndBuildTheArrayWithTheBindValueDataThenCallChangeinbulkAndReturnItsReturnedValue() {
    $modelMock = $this->createMock(UserModel::class);
    $otherModelMock = $this->createMock(UserModel::class);
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['changeInBulk'])
      ->getMock();

    $query = 'INSERT INTO users VALUES(null,:userName,:passWord,:email,:isActive)';
    $paramNames = ['userName', 'passWord', 'email', 'isActive'];
    
    $modelUserName = 'Pedro Henriques';
    $modelPassWord = 'password';
    $modelEmail = 'pjh@gmail.com';
    $modelIsActive = false;
    $modelId = 1;
    $modelDbData = [
      'userName' => [$modelUserName, \PDO::PARAM_STR],
      'passWord' => [$modelPassWord, \PDO::PARAM_STR],
      'email' => [$modelEmail, \PDO::PARAM_STR],
      'isActive' => [$modelIsActive, \PDO::PARAM_BOOL],
    ];
    
    $otherModelUserName = 'João Silva';
    $otherModelPassWord = 'other password';
    $otherModelEmail = 'js@gmail.com';
    $otherModelIsActive = false;
    $otherModelId = 2;
    $otherModelDbData = [
      'userName' => [$otherModelUserName, \PDO::PARAM_STR],
      'passWord' => [$otherModelPassWord, \PDO::PARAM_STR],
      'email' => [$otherModelEmail, \PDO::PARAM_STR],
      'isActive' => [$otherModelIsActive, \PDO::PARAM_BOOL],
    ];

    $modelMock->expects($this->once())
      ->method('dbData')
      ->with()
      ->willReturn($modelDbData);

    $otherModelMock->expects($this->once())
      ->method('dbData')
      ->with()
      ->willReturn($otherModelDbData);
    
    $dbHandlerMock->expects($this->once())
      ->method('changeInBulk')
      ->with($query, [$modelDbData, $otherModelDbData])
      ->willReturn([$modelId, $otherModelId]);
    
    $actualValue = $dbHandlerMock->changeFromModel($query, $paramNames, [$modelMock, $otherModelMock], true);

    $this->assertEquals([1, 2], $actualValue);
  }

  public function testChangefrommodelIfAnyOfTheProvidedModelsDoesNotImplementModelinterfaceItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The object at index #0 provided to the "$models" parameter of '.
      'changeFromModel() doesn\'t implement ModelInterface.');
    
    $modelMock = $this->createMock(\Exception::class);
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['change'])
      ->getMock();

    $query = 'INSERT INTO users VALUES(null,:userName,:passWord,:email,:isActive)';
    $paramNames = ['userName', 'passWord', 'email', 'isActive'];

    $dbHandlerMock->changeFromModel($query, $paramNames, [$modelMock], false);
  }

  public function testChangefrommodelIfUserModelDbdataThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('dbData test exception msg');
    
    $modelMock = $this->createMock(UserModel::class);
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['change'])
      ->getMock();

    $query = 'INSERT INTO users VALUES(null,:userName,:passWord,:email,:isActive)';
    $paramNames = ['userName', 'passWord', 'email', 'isActive'];
    
    $modelMock->expects($this->once())
      ->method('dbData')
      ->with()
      ->will($this->throwException(new \Exception('dbData test exception msg')));

    $dbHandlerMock->changeFromModel($query, $paramNames, [$modelMock], false);
  }

  public function testChangefrommodelIfChangeThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    
    $modelMock = $this->createMock(UserModel::class);
    $otherModelMock = $this->createMock(UserModel::class);
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['change'])
      ->getMock();

    $query = 'INSERT INTO users VALUES(null,:userName,:passWord,:email,:isActive)';
    $paramNames = ['userName', 'passWord', 'email', 'isActive'];
    
    $modelUserName = 'Pedro Henriques';
    $modelPassWord = 'password';
    $modelEmail = 'pjh@gmail.com';
    $modelIsActive = false;
    $modelId = 1;
    $modelDbData = [
      'userName' => [$modelUserName, \PDO::PARAM_STR],
      'passWord' => [$modelPassWord, \PDO::PARAM_STR],
      'email' => [$modelEmail, \PDO::PARAM_STR],
      'isActive' => [$modelIsActive, \PDO::PARAM_BOOL],
    ];
    
    $otherModelUserName = 'João Silva';
    $otherModelPassWord = 'other password';
    $otherModelEmail = 'js@gmail.com';
    $otherModelIsActive = false;
    $otherModelId = 2;
    $otherModelDbData = [
      'userName' => [$otherModelUserName, \PDO::PARAM_STR],
      'passWord' => [$otherModelPassWord, \PDO::PARAM_STR],
      'email' => [$otherModelEmail, \PDO::PARAM_STR],
      'isActive' => [$otherModelIsActive, \PDO::PARAM_BOOL],
    ];

    $modelMock->expects($this->once())
      ->method('dbData')
      ->with()
      ->willReturn($modelDbData);

    $otherModelMock->expects($this->once())
      ->method('dbData')
      ->with()
      ->willReturn($otherModelDbData);
    
    $dbHandlerMock->expects($this->once())
      ->method('change')
      ->with($query, [$modelDbData, $otherModelDbData])
      ->will($this->throwException(new \Exception));
    
    $dbHandlerMock->changeFromModel($query, $paramNames, [$modelMock, $otherModelMock], false);
  }

  public function testChangefrommodelIfChangeinbulkThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    
    $modelMock = $this->createMock(UserModel::class);
    $otherModelMock = $this->createMock(UserModel::class);
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['changeInBulk'])
      ->getMock();

    $query = 'INSERT INTO users VALUES(null,:userName,:passWord,:email,:isActive)';
    $paramNames = ['userName', 'passWord', 'email', 'isActive'];
    
    $modelUserName = 'Pedro Henriques';
    $modelPassWord = 'password';
    $modelEmail = 'pjh@gmail.com';
    $modelIsActive = false;
    $modelId = 1;
    $modelDbData = [
      'userName' => [$modelUserName, \PDO::PARAM_STR],
      'passWord' => [$modelPassWord, \PDO::PARAM_STR],
      'email' => [$modelEmail, \PDO::PARAM_STR],
      'isActive' => [$modelIsActive, \PDO::PARAM_BOOL],
    ];
    
    $otherModelUserName = 'João Silva';
    $otherModelPassWord = 'other password';
    $otherModelEmail = 'js@gmail.com';
    $otherModelIsActive = false;
    $otherModelId = 2;
    $otherModelDbData = [
      'userName' => [$otherModelUserName, \PDO::PARAM_STR],
      'passWord' => [$otherModelPassWord, \PDO::PARAM_STR],
      'email' => [$otherModelEmail, \PDO::PARAM_STR],
      'isActive' => [$otherModelIsActive, \PDO::PARAM_BOOL],
    ];

    $modelMock->expects($this->once())
      ->method('dbData')
      ->with()
      ->willReturn($modelDbData);

    $otherModelMock->expects($this->once())
      ->method('dbData')
      ->with()
      ->willReturn($otherModelDbData);
    
    $dbHandlerMock->expects($this->once())
      ->method('changeInBulk')
      ->with($query, [$modelDbData, $otherModelDbData])
      ->will($this->throwException(new \Exception));
    
    $dbHandlerMock->changeFromModel($query, $paramNames, [$modelMock, $otherModelMock], true);
  }

  public function testSelectIntoModelItShouldCallSelectWithTheProvidedQueryAndBindDataThenRunTheCallbackAndCreateAnInstanceOfAModelForEachResultSetRowUsingTheProvidedModelFactoryAndReturnAnArrayWithTheModelInstancesIndexedByTheSpecifiedColumn() {
    $userMockOne = $this->getMockBuilder(UserModel::class)
      ->setMethods(['setEmail'])
      ->disableOriginalConstructor()
      ->getMock();
    $userMockTwo = $this->getMockBuilder(UserModel::class)
      ->setMethods(['setEmail'])
      ->disableOriginalConstructor()
      ->getMock();
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['select'])
      ->getMock();

    $query = 'SELECT id, email FROM users WHERE id=:id';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
      ],
    ];

    $expectedCallBackReceivedData = [
      ['id' => '10', 'email' => 'test@email.com'],
      ['id' => '4', 'email' => 'other@testemail.com'],
    ];
    $callBackReceivedData = [];

    $callBack = function($data) use (&$callBackReceivedData) {
      $callBackReceivedData[] = $data;

      return($data);
    };

    $resultSet = [
      [
        ['id' => '10', 'email' => 'test@email.com'],
      ],
      [
        ['id' => '4', 'email' => 'other@testemail.com'],
      ],
    ];

    $dbHandlerMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn($resultSet);
    
    $this->userFactoryMock->expects($this->exactly(2))
      ->method('create')
      ->with()
      ->will($this->onConsecutiveCalls($userMockOne, $userMockTwo));

    $userMockOne->expects($this->once())
      ->method('setEmail')
      ->with('test@email.com')
      ->willReturn(null);
    
    $userMockTwo->expects($this->once())
      ->method('setEmail')
      ->with('other@testemail.com')
      ->willReturn(null);

    $actualValue = $dbHandlerMock->selectIntoModel($query, $paramData, $this->userFactoryMock, $callBack, 'id');

    $expectedValue = ['10' => $userMockOne, '4' => $userMockTwo];

    $this->assertEquals($expectedValue, $actualValue);
    $this->assertEquals($expectedCallBackReceivedData, $callBackReceivedData);
    $this->assertEquals(10, $userMockOne->getId());
    $this->assertEquals(4, $userMockTwo->getId());
  }

  public function testSelectIntoModelIfNoIndexColumnIsSpecifiedItShouldReturnAnArrayWithTheModelInstancesIndexedByZeroBasedInteger() {
    $userMockOne = $this->getMockBuilder(UserModel::class)
      ->setMethods(['setEmail'])
      ->disableOriginalConstructor()
      ->getMock();
    $userMockTwo = $this->getMockBuilder(UserModel::class)
      ->setMethods(['setEmail'])
      ->disableOriginalConstructor()
      ->getMock();
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['select'])
      ->getMock();

    $query = 'SELECT id, email FROM users WHERE id=:id';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
      ],
    ];

    $expectedCallBackReceivedData = [
      ['id' => '10', 'email' => 'test@email.com'],
      ['id' => '4', 'email' => 'other@testemail.com'],
    ];
    $callBackReceivedData = [];

    $callBack = function($data) use (&$callBackReceivedData) {
      $callBackReceivedData[] = $data;

      return($data);
    };

    $resultSet = [
      [
        ['id' => '10', 'email' => 'test@email.com'],
      ],
      [
        ['id' => '4', 'email' => 'other@testemail.com'],
      ],
    ];

    $dbHandlerMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn($resultSet);
    
    $this->userFactoryMock->expects($this->exactly(2))
      ->method('create')
      ->with()
      ->will($this->onConsecutiveCalls($userMockOne, $userMockTwo));

    $userMockOne->expects($this->once())
      ->method('setEmail')
      ->with('test@email.com')
      ->willReturn(null);
    
    $userMockTwo->expects($this->once())
      ->method('setEmail')
      ->with('other@testemail.com')
      ->willReturn(null);

    $actualValue = $dbHandlerMock->selectIntoModel($query, $paramData, $this->userFactoryMock, $callBack);

    $expectedValue = [0 => $userMockOne, 1 => $userMockTwo];

    $this->assertEquals($expectedValue, $actualValue);
    $this->assertEquals($expectedCallBackReceivedData, $callBackReceivedData);
    $this->assertEquals(10, $userMockOne->getId());
    $this->assertEquals(4, $userMockTwo->getId());
  }

  public function testSelectIntoModelIfNoCallbackIsProvidedItShouldCallDirectlyTheModelFactoryWithTheRespectiveResultSetRow() {
    $userMockOne = $this->getMockBuilder(UserModel::class)
      ->setMethods(['setEmail'])
      ->disableOriginalConstructor()
      ->getMock();
    $userMockTwo = $this->getMockBuilder(UserModel::class)
      ->setMethods(['setEmail'])
      ->disableOriginalConstructor()
      ->getMock();
    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['select'])
      ->getMock();
    
    $query = 'SELECT id, email FROM users WHERE id=:id';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
      ],
    ];

    $resultSet = [
      [
        ['id' => '10', 'email' => 'test@email.com'],
      ],
      [
        ['id' => '4', 'email' => 'other@testemail.com'],
      ],
    ];

    $dbHandlerMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn($resultSet);
    
    $this->userFactoryMock->expects($this->exactly(2))
      ->method('create')
      ->with()
      ->will($this->onConsecutiveCalls($userMockOne, $userMockTwo));

    $userMockOne->expects($this->once())
      ->method('setEmail')
      ->with('test@email.com')
      ->willReturn(null);
    
    $userMockTwo->expects($this->once())
      ->method('setEmail')
      ->with('other@testemail.com')
      ->willReturn(null);

    $actualValue = $dbHandlerMock->selectIntoModel($query, $paramData, $this->userFactoryMock, null, 'id');

    $expectedValue = ['10' => $userMockOne, '4' => $userMockTwo];

    $this->assertEquals($expectedValue, $actualValue);
    $this->assertEquals(10, $userMockOne->getId());
    $this->assertEquals(4, $userMockTwo->getId());
  }

  public function testSelectIntoModelIfSelectThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('select test exception msg');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['select'])
      ->getMock();

    $query = 'SELECT id, email FROM users WHERE id=:id';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
      ],
    ];

    $resultSet = [
      [
        ['id' => '10', 'email' => 'test@email.com'],
      ],
      [
        ['id' => '4', 'email' => 'other@testemail.com'],
      ],
    ];

    $dbHandlerMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->will($this->throwException(new \Exception('select test exception msg')));

    $dbHandlerMock->selectIntoModel($query, $paramData, $this->userFactoryMock, null, 'id');
  }

  public function testSelectIntoModelIfTheCallbackThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('callBack test exception msg');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['select'])
      ->getMock();

    $query = 'SELECT id, email FROM users WHERE id=:id';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
      ],
    ];

    $callBack = function($data) {
      throw new \Exception('callBack test exception msg');
    };

    $resultSet = [
      [
        ['id' => '10', 'email' => 'test@email.com'],
      ],
      [
        ['id' => '4', 'email' => 'other@testemail.com'],
      ],
    ];

    $dbHandlerMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn($resultSet);

    $dbHandlerMock->selectIntoModel($query, $paramData, $this->userFactoryMock, $callBack, 'id');
  }

  public function testSelectIntoModelIfTheSpecifiedIndexingColumnIsntOneOfTheResultSetColumnsItShouldThrowAnExceptionWithAnErrorMsg() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The indexing column \'userName\' isn\'t part of the result set.');

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['select'])
      ->getMock();

    $query = 'SELECT id, email FROM users WHERE id=:id';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
      ],
    ];

    $resultSet = [
      [
        ['id' => '10', 'email' => 'test@email.com'],
      ],
      [
        ['id' => '4', 'email' => 'other@testemail.com'],
      ],
    ];

    $dbHandlerMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn($resultSet);
    
    $dbHandlerMock->selectIntoModel($query, $paramData, $this->userFactoryMock, null, 'userName');
  }

  public function testSelectIntoModelIfTheModelFactoryThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);

    $dbHandlerMock = $this->getMockBuilder(DbHandler::class)
      ->setConstructorArgs([$this->connMock])
      ->setMethods(['select'])
      ->getMock();

    $query = 'SELECT id, email FROM users WHERE id=:id';
    $paramData = [
      [
        'id' => [10, \PDO::PARAM_INT],
      ],
      [
        'id' => [4, \PDO::PARAM_INT],
      ],
    ];

    $resultSet = [
      [
        ['id' => '10', 'email' => 'test@email.com'],
      ],
      [
        ['id' => '4', 'email' => 'other@testemail.com'],
      ],
    ];

    $dbHandlerMock->expects($this->once())
      ->method('select')
      ->with($query, $paramData)
      ->willReturn($resultSet);
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->will($this->throwException(new \Exception));

    $dbHandlerMock->selectIntoModel($query, $paramData, $this->userFactoryMock, null, 'id');
  }
}