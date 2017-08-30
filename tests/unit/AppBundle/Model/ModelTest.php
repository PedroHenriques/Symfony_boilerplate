<?php

namespace tests\unit\AppBundle\Model;

use AppBundle\Model\Model;

use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase {
  private $modelMock;

  protected function setUp() {
    parent::setUp();

    $this->modelMock = $this->createMock(ModelTestClass::class);
  }

  public function testPopulatefromarrayItShouldLoopThroughTheProvidedArrayAndForEachEntryBuildTheSetterNameThenCheckIfItExistsThenCallItAndReturnVoid() {
    $data = [
      'propOne' => 'value1',
      'propTwo' => 'value2',
    ];

    $this->modelMock->expects($this->once())
      ->method('setPropOne')
      ->with('value1')
      ->willReturn(null);
    
    $this->modelMock->expects($this->once())
      ->method('setPropTwo')
      ->with('value2')
      ->willReturn(null);
    
    $actualValue = $this->modelMock->populateFromArray($data);

    $this->assertEquals(null, $actualValue);
  }

  public function testPopulatefromarrayIfASetterDoesntExistItShouldThrowAnException() {
    $this->expectException(\Exception::class);

    $data = [
      'propOne' => 'value1',
      'propTwo' => 'value2',
      'propThree' => 'value3',
    ];

    $this->modelMock->expects($this->once())
      ->method('setPropOne')
      ->with('value1')
      ->willReturn(null);
    
    $this->modelMock->expects($this->once())
      ->method('setPropTwo')
      ->with('value2')
      ->willReturn(null);
    
    $this->modelMock->populateFromArray($data);
  }

  public function testPopulatefromarrayIfASetterThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('setPropOne test exception msg');

    $data = [
      'propOne' => 'value1',
      'propTwo' => 'value2',
    ];

    $this->modelMock->expects($this->once())
      ->method('setPropOne')
      ->with('value1')
      ->will($this->throwException(new \Exception('setPropOne test exception msg')));
    
    $this->modelMock->populateFromArray($data);
  }
}

class ModelTestClass extends Model {
  public function dbData(): array {}
  public function populateFromDb(array $bindData): void {}

  public function setPropOne($value) {}
  public function setPropTwo($value) {}
}