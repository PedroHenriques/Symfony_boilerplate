<?php

namespace AppBundle\Model;

use AppBundle\Model\{ModelFactory, UserModel, ModelInterface};
use AppBundle\Services\{DbInterface, EmailInterface};

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Psr\Container\ContainerInterface;

class UserModelFactory extends ModelFactory {
  private $db;
  private $email;
  private $encoder;
  private $container;

  /**
  * @param DbInterface $db An instance of a DbInterface implementing class
  * @param EmailInterface $email An instance of a EmailInterface implementing class
  * @param UserPasswordEncoderInterface $encoder An instance of a UserPasswordEncoderInterface implementing class
  * @param ContainerInterface $container An instance of a ContainerInterface implementing class
  */
  public function __construct(DbInterface $db, EmailInterface $email,
  UserPasswordEncoderInterface $encoder, ContainerInterface $container) {
    $this->db = $db;
    $this->email = $email;
    $this->encoder = $encoder;
    $this->container = $container;
  }
  
  /** {@inheritDoc} */
  public function create(): ModelInterface {
    return(new UserModel($this->db, $this->email, $this->encoder, $this->container));
  }
}