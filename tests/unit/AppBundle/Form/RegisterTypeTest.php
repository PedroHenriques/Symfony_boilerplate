<?php

namespace tests\unit\AppBundle\Form;

use AppBundle\Form\RegisterType;
use AppBundle\Model\UserModel;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{TextType, PasswordType, EmailType, RepeatedType};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Exception\AccessException;

class RegisterTypeTest extends TestCase {
  public function testBuildformItShouldCallFormbuilderToAddTheFormFieldsAndReturnNull() {
    $builderMock = $this->createMock(FormBuilderInterface::class);
    
    $builderMock->expects($this->exactly(3))
      ->method('add')
      ->withConsecutive(
        ['userName', TextType::class, ['required' => true,'label' => 'Username','trim' => true]],
        ['email', EmailType::class, ['required' => true,'label' => 'Email','trim' => true]],
        ['plainPassword', RepeatedType::class, [
          'type' => PasswordType::class,
          'invalid_message' => 'The password fields must match.',
          'options' => ['attr' => ['class' => 'password-field']],
          'required' => true,
          'first_options'  => ['label' => 'Password'],
          'second_options' => ['label' => 'Repeat Password']
        ]]
      )
      ->will($this->returnSelf());
    
    $registerType = new RegisterType();

    $this->assertNull($registerType->buildForm($builderMock, []));
  }

  public function testConfigureoptionsItShouldSetTheFormDataclassToTheUserModelAndReturnNull() {
    $optionsResolverMock = $this->createMock(OptionsResolver::class);

    $optionsResolverMock->expects($this->once())
      ->method('setDefaults')
      ->with(['data_class' => UserModel::class])
      ->will($this->returnSelf());
    
    $registerType = new RegisterType();

    $this->assertNull($registerType->configureOptions($optionsResolverMock));
  }

  public function testConfigureoptionsIfSetdefaultsThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(AccessException::class);

    $optionsResolverMock = $this->createMock(OptionsResolver::class);

    $optionsResolverMock->expects($this->once())
      ->method('setDefaults')
      ->with(['data_class' => UserModel::class])
      ->will($this->throwException(new AccessException));
    
    $registerType = new RegisterType();

    $registerType->configureOptions($optionsResolverMock);
  }
}