<?php

namespace AppBundle\Services\Security;

use AppBundle\Model\{UserModelFactory, UserModel};

use Symfony\Component\Security\Core\User\{UserProviderInterface, UserInterface};
use Symfony\Component\Security\Core\Exception\{UsernameNotFoundException, UnsupportedUserException};

class UserProvider implements UserProviderInterface {
  private $userFactory;

  /**
  * @param UserModelFactory $userFactory An instance of UserModelFactory.
  */
  public function __construct(UserModelFactory $userFactory) {
    $this->userFactory = $userFactory;
  }

  /**
  * {@inheritDoc}
  *
  * @throws UsernameNotFoundException if the user can't be loaded.
  */
  public function loadUserByUsername($uniqueId) {
    try {
      if (preg_match('/^[^@]+@[^@]+\.[^@\.]+$/i', $uniqueId) === 1) {
        $uniqueCol = 'email';
      } else {
        $uniqueCol = 'userName';
      }
      
      return($this->userFactory->createFromDb(
        [$uniqueCol => [$uniqueId, \PDO::PARAM_STR]]
      ));
    } catch (\Exception $e) {
      throw new UsernameNotFoundException(
        "${uniqueCol} '${uniqueId}' does not exist."
      );
    }
  }

  /**
  * {@inheritDoc}
  *
  * @throws UnsupportedUserException if the provided UserInterface isn't an
  *                                  instance of UserModel
  */
  public function refreshUser(UserInterface $user) {
    if (!($user instanceof UserModel)) {
      throw new UnsupportedUserException(
        'Instances of '.get_class($user).' are not supported.'
      );
    }

    return($this->loadUserByUsername($user->getEmail()));
  }

  /** {@inheritDoc} */
  public function supportsClass($class) {
    return(UserModel::class === $class);
  }
}