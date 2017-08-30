<?php

namespace tests\unit\AppBundle\Model;

use AppBundle\Model\{ModelInterface, ModelFactory};

use PHPUnit\Framework\TestCase;

class ModelFactoryTest extends TestCase {
  private $modelFactoryMock;
  private $modelInterfaceMock;

  protected function setUp() {
    parent::setUp();

    $this->modelFactoryMock = $this->createMock(ModelFactoryTestClass::class);
    $this->modelInterfaceMock = $this->createMock(ModelInterface::class);
  }

  public function testCreatefromdbItShouldCallCreateThenTheCallTheModelPopulatefromdbAndReturnTheModelInstance() {
    $this->modelFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->modelInterfaceMock);

    $bindData = ['email' => 'test@test.com'];

    $this->modelInterfaceMock->expects($this->once())
      ->method('populateFromDb')
      ->with($bindData)
      ->willReturn(null);
    
    $actualValue = $this->modelFactoryMock->createFromDb($bindData);

    $this->assertEquals(get_class($this->modelInterfaceMock), get_class($actualValue));
  }

  public function testCreatefromdbIfCreateThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('ModelFactory create() exception message');

    $this->modelFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->will($this->throwException(new \Exception(
        'ModelFactory create() exception message'
      )));

    $bindData = ['email' => 'test@test.com'];
    
    $this->modelFactoryMock->createFromDb($bindData);
  }

  public function testCreatefromdbIfPopulatefromdbThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('ModelInterface populateFromDb() exception message');

    $this->modelFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->modelInterfaceMock);

    $bindData = ['email' => 'test@test.com'];

    $this->modelInterfaceMock->expects($this->once())
      ->method('populateFromDb')
      ->with($bindData)
      ->will($this->throwException(new \Exception('ModelInterface populateFromDb() exception message')));
    
    $this->modelFactoryMock->createFromDb($bindData);
  }

  public function testCreatefromarrayItShouldCallCreateThenTheCallTheModelPopulatefromarrayAndReturnTheModelInstance() {
    $this->modelFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->modelInterfaceMock);

    $data = ['email' => 'test@test.com', 'userName' => 'test username'];

    $this->modelInterfaceMock->expects($this->once())
      ->method('populateFromArray')
      ->with($data)
      ->willReturn(null);
    
    $actualValue = $this->modelFactoryMock->createFromArray($data);

    $this->assertEquals(get_class($this->modelInterfaceMock), get_class($actualValue));
  }

  public function testCreatefromarrayIfCreateThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('ModelFactory create() exception message');

    $this->modelFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->will($this->throwException(new \Exception('ModelFactory create() exception message')));

    $data = ['email' => 'test@test.com', 'userName' => 'test username'];
    
    $this->modelFactoryMock->createFromArray($data);
  }

  public function testCreatefromarrayIfPopulatefromdbThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('ModelInterface populateFromArray() exception message');

    $this->modelFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->modelInterfaceMock);

    $data = ['email' => 'test@test.com', 'userName' => 'test username'];

    $this->modelInterfaceMock->expects($this->once())
      ->method('populateFromArray')
      ->with($data)
      ->will($this->throwException(new \Exception('ModelInterface populateFromArray() exception message')));
    
    $this->modelFactoryMock->createFromArray($data);
  }
}

class ModelFactoryTestClass extends ModelFactory {
  public function create(): ModelInterface {}
}