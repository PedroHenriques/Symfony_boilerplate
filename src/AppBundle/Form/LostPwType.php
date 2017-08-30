<?php

namespace AppBundle\Form;

use AppBundle\Model\UserModel;

use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\Form\Extension\Core\Type\{EmailType, HiddenType};
use Symfony\Component\OptionsResolver\OptionsResolver;

class LostPwType extends AbstractType {
  public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder
      ->add('email', EmailType::class, array(
        'required' => true,
        'label' => 'Email',
        'trim' => true,
      ))
      ->add('userName', HiddenType::class, array(
        'required' => false,
        'data' => 'placeHolder',
        'trim' => true,
      ));
  }

  public function configureOptions(OptionsResolver $resolver) {
    $resolver->setDefaults(array(
      'data_class' => UserModel::class,
    ));
  }
}