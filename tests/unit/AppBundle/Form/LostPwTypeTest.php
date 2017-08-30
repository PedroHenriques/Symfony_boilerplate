<?php

namespace tests\unit\AppBundle\Form;

use AppBundle\Form\LostPwType;
use AppBundle\Model\UserModel;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{EmailType, HiddenType};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Exception\AccessException;

class LostPwTypeTest extends TestCase {
  public function testBuildformItShouldCallFormbuilderToAddTheFormFieldsAndReturnNull() {
    $builderMock = $this->createMock(FormBuilderInterface::class);
    
    $builderMock->expects($this->exactly(2))
      ->method('add')
      ->withConsecutive(
        ['email', EmailType::class, ['required' => true,'label' => 'Email','trim' => true]],
        ['userName', HiddenType::class, ['required' => false,'data' => 'placeHolder','trim' => true]]
      )
      ->will($this->returnSelf());
    
    $lostPwType = new LostPwType();

    $this->assertNull($lostPwType->buildForm($builderMock, []));
  }

  public function testConfigureoptionsItShouldSetTheFormDataclassToTheUserModelAndReturnNull() {
    $optionsResolverMock = $this->createMock(OptionsResolver::class);

    $optionsResolverMock->expects($this->once())
      ->method('setDefaults')
      ->with(['data_class' => UserModel::class])
      ->will($this->returnSelf());
    
    $lostPwType = new LostPwType();

    $this->assertNull($lostPwType->configureOptions($optionsResolverMock));
  }

  public function testConfigureoptionsIfSetdefaultsThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(AccessException::class);

    $optionsResolverMock = $this->createMock(OptionsResolver::class);

    $optionsResolverMock->expects($this->once())
      ->method('setDefaults')
      ->with(['data_class' => UserModel::class])
      ->will($this->throwException(new AccessException));
    
    $lostPwType = new LostPwType();

    $lostPwType->configureOptions($optionsResolverMock);
  }
}