<?php

namespace tests\unit\AppBundle\Form;

use AppBundle\Form\PwResetType;
use AppBundle\Model\UserModel;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{PasswordType, RepeatedType, HiddenType};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Exception\AccessException;

class PwResetTypeTest extends TestCase {
  public function testBuildformItShouldCallFormbuilderToAddTheFormFieldsAndReturnNull() {
    $builderMock = $this->createMock(FormBuilderInterface::class);
    
    $builderMock->expects($this->exactly(3))
      ->method('add')
      ->withConsecutive(
        ['plainPassword', RepeatedType::class, [
          'type' => PasswordType::class,
          'invalid_message' => 'The password fields must match.',
          'options' => ['attr' => ['class' => 'password-field']],
          'required' => true,
          'first_options'  => ['label' => 'New Password'],
          'second_options' => ['label' => 'Repeat New Password']
        ]],
        ['userName', HiddenType::class, ['required' => false,'data' => 'placeHolder','trim' => true]],
        ['email', HiddenType::class, ['required' => false,'data' => 'placeHolder@email.com','trim' => true]]
      )
      ->will($this->returnSelf());
    
    $pwResetType = new PwResetType();

    $this->assertNull($pwResetType->buildForm($builderMock, []));
  }

  public function testConfigureoptionsItShouldSetTheFormDataclassToTheUserModelAndReturnNull() {
    $optionsResolverMock = $this->createMock(OptionsResolver::class);

    $optionsResolverMock->expects($this->once())
      ->method('setDefaults')
      ->with(['data_class' => UserModel::class])
      ->will($this->returnSelf());
    
    $pwResetType = new PwResetType();

    $this->assertNull($pwResetType->configureOptions($optionsResolverMock));
  }

  public function testConfigureoptionsIfSetdefaultsThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(AccessException::class);

    $optionsResolverMock = $this->createMock(OptionsResolver::class);

    $optionsResolverMock->expects($this->once())
      ->method('setDefaults')
      ->with(['data_class' => UserModel::class])
      ->will($this->throwException(new AccessException));
    
    $pwResetType = new PwResetType();

    $pwResetType->configureOptions($optionsResolverMock);
  }
}