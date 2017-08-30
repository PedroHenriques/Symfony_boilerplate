<?php

namespace AppBundle\Form;

use AppBundle\Model\UserModel;

use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\Form\Extension\Core\Type\{TextType, PasswordType, EmailType, RepeatedType};
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegisterType extends AbstractType {
  public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder
      ->add('userName', TextType::class, array(
        'required' => true,
        'label' => 'Username',
        'trim' => true,
      ))
      ->add('email', EmailType::class, array(
        'required' => true,
        'label' => 'Email',
        'trim' => true,
      ))
      ->add('plainPassword', RepeatedType::class, array(
        'type' => PasswordType::class,
        'invalid_message' => 'The password fields must match.',
        'options' => array('attr' => array('class' => 'password-field')),
        'required' => true,
        'first_options'  => array('label' => 'Password'),
        'second_options' => array('label' => 'Repeat Password'),
      ));
  }

  public function configureOptions(OptionsResolver $resolver) {
    $resolver->setDefaults(array(
      'data_class' => UserModel::class,
    ));
  }
}