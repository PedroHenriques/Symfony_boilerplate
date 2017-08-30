<?php

namespace tests\unit\AppBundle\Form;

use AppBundle\Form\LoginType;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{TextType, PasswordType};

class LoginTypeTest extends TestCase {
  public function testBuildformItShouldCallFormbuilderToAddTheFormFieldsAndReturnNull() {
    $builderMock = $this->createMock(FormBuilderInterface::class);
    
    $builderMock->expects($this->exactly(2))
      ->method('add')
      ->withConsecutive(
        ['uniqueId', TextType::class, ['required' => true,'label' => 'Username or Email','trim' => true]],
        ['password', PasswordType::class, ['required' => true,'label' => 'Password','trim' => true]]
      )
      ->will($this->returnSelf());
    
    $loginType = new LoginType();

    $this->assertNull($loginType->buildForm($builderMock, []));
  }
}