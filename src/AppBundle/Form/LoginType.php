<?php

namespace AppBundle\Form;

use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\Form\Extension\Core\Type\{TextType, PasswordType};
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginType extends AbstractType {
  public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder
      ->add('uniqueId', TextType::class, array(
        'required' => true,
        'label' => 'Username or Email',
        'trim' => true,
      ))
      ->add('password', PasswordType::class, array(
        'required' => true,
        'label' => 'Password',
        'trim' => true,
      ));
  }

  public function configureOptions(OptionsResolver $resolver) {
  }
}