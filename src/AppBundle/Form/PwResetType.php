<?php

namespace AppBundle\Form;

use AppBundle\Model\UserModel;

use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\Form\Extension\Core\Type\{PasswordType, RepeatedType, HiddenType};
use Symfony\Component\OptionsResolver\OptionsResolver;

class PwResetType extends AbstractType {
  public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder
      ->add('plainPassword', RepeatedType::class, array(
        'type' => PasswordType::class,
        'invalid_message' => 'The password fields must match.',
        'options' => array('attr' => array('class' => 'password-field')),
        'required' => true,
        'first_options'  => array('label' => 'New Password'),
        'second_options' => array('label' => 'Repeat New Password'),
      ))
      ->add('userName', HiddenType::class, array(
        'required' => false,
        'data' => 'placeHolder',
        'trim' => true,
      ))
      ->add('email', HiddenType::class, array(
        'required' => false,
        'data' => 'placeHolder@email.com',
        'trim' => true,
      ));
  }

  public function configureOptions(OptionsResolver $resolver) {
    $resolver->setDefaults(array(
      'data_class' => UserModel::class,
    ));
  }
}