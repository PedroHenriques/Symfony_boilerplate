<?php

namespace AppBundle\Model;

use AppBundle\Model\Model;
use AppBundle\Services\{DbInterface, Utils, EmailInterface};
use AppBundle\Exceptions\TokenExpiredException;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\{UserInterface, AdvancedUserInterface, EquatableInterface};
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Routing\RouterInterface;
use Psr\Container\ContainerInterface;

class UserModel extends Model implements AdvancedUserInterface, EquatableInterface, \Serializable {
  /**
  * @Assert\Type(type="integer")
  */
  private $id;

  /**
  * @Assert\NotBlank()
  * @Assert\Type(type="string")
  * @Assert\Length(
  *   max=255,
  *   maxMessage="This value is too long."
  * )
  */
  private $userName;

  /**
  * @Assert\NotBlank()
  * @Assert\Email()
  * @Assert\Length(
  *   max=100,
  *   maxMessage="This value is too long."
  * )
  */
  private $email;

  /**
  * @Assert\Type(type="string")
  * @Assert\Length(
  *   min=6,
  *   minMessage="This value is too short."
  * )
  */
  private $plainPassword;

  /**
  * @Assert\Type(type="string")
  * @Assert\Length(
  *   min=60,
  *   max=60
  * )
  */
  private $password;

  /**
  * @Assert\Type(type="bool")
  */
  private $isActive;
  
  /**
  * @Assert\Type(type="integer")
  */
  private $created;

  /**
  * @Assert\Type(type="array")
  */
  private $roles;

  private $dbInterface;
  private $emailInterface;
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
    $this->dbInterface = $db;
    $this->emailInterface = $email;
    $this->encoder = $encoder;
    $this->container = $container;
  }

  /**
  * @throws \Exception if the provided ID couldn't be set
  */
  protected function setId($id): void {
    if ($this->id !== null) {
      throw new \Exception('UserModel already has an ID.');
    }

    $this->id = intval($id, 10);
  }

  public function getId() {
    return($this->id);
  }

  public function setUserName(string $userName): void {
    $this->userName = $userName;
  }

  public function getUserName() {
    return($this->userName);
  }

  public function setPlainPassword(string $plainPassword): void {
    $this->plainPassword = $plainPassword;
  }

  public function getPlainPassword() {
    return($this->plainPassword);
  }
  
  public function setPassword(string $password): void {
    $this->password = $password;
  }

  public function getPassword() {
    return($this->password);
  }

  public function setEmail(string $email): void {
    $this->email = $email;
  }

  public function getEmail() {
    return($this->email);
  }

  public function setIsActive($isActive): void {
    $this->isActive = boolval($isActive);
  }

  public function getIsActive() {
    return($this->isActive);
  }
  
  public function setCreated($created): void {
    $this->created = intval($created, 10);
  }

  public function getCreated() {
    return($this->created);
  }

  public function setRoles(array $roles): void {
    $this->roles = $roles;
  }
  
  public function getRoles() {
    return($this->roles);
  }

  public function eraseCredentials() {
    $this->plainPassword = null;
  }

  public function getSalt() {
    // the bcrypt encoding algorithm doesn't require an explicit salt
    return(null);
  }

  public function isAccountNonExpired() {
    return(true);
  }

  public function isAccountNonLocked() {
    return(true);
  }

  public function isCredentialsNonExpired() {
    return(true);
  }

  public function isEnabled() {
    return($this->isActive);
  }

  /** @see \Serializable::serialize() */
  public function serialize() {
    return(serialize(array(
      $this->id,
      $this->userName,
      $this->email,
      $this->password,
      $this->roles,
      $this->isActive,
      $this->created,
    )));
  }

  /** @see \Serializable::unserialize() */
  public function unserialize($serialized) {
    list (
      $this->id,
      $this->userName,
      $this->email,
      $this->password,
      $this->roles,
      $this->isActive,
      $this->created
    ) = unserialize($serialized);
  }

  /**
  * Called by symfony's security component, if the user is logged in,
  * at every request to determine if that user should be logged out.
  *
  * {@inheritDoc}
  */
  public function isEqualTo(UserInterface $user) {
    $keepLoggedIn = $this->serialize() === $user->serialize();

    // this step is done since the security component doesn't log out as intended
    // (https://github.com/symfony/symfony/issues/17023)
    if (!$keepLoggedIn) {
      $user->setIsActive(false);
    }

    return($keepLoggedIn);
  }

  /** {@inheritDoc} */
  public function dbData(): array {
    return(array(
      'id' => [$this->id, \PDO::PARAM_INT],
      'userName' => [$this->userName, \PDO::PARAM_STR],
      'email' => [$this->email, \PDO::PARAM_STR],
      'password' => [$this->password, \PDO::PARAM_STR],
      'isActive' => [$this->isActive, \PDO::PARAM_BOOL],
      'created' => [$this->created, \PDO::PARAM_INT],
    ));
  }

  /**
  * {@inheritDoc}
  * 
  * @throws \Exception with an error message if any step fails.
  */
  public function populateFromDb(array $bindData): void {
    if (count($bindData) !== 1) {
      throw new \Exception('The number of elements in $bindData isn\'t valid.');
    }

    $colName = array_keys($bindData)[0];

    $query = 'SELECT u.id, u.userName, u.email, u.password, u.isActive, '.
      'u.created, ur.role FROM users as u JOIN users_roles as ur ON '.
      "ur.id = u.roleId WHERE u.${colName} = :${colName}";
    $paramData = [
      [
        $colName => array_values($bindData)[0],
      ],
    ];
    
    $resultSet = $this->dbInterface->select($query, $paramData);

    if (count($resultSet[0]) === 1) {
      $resultSet[0][0]['roles'] = [$resultSet[0][0]['role']];
      unset($resultSet[0][0]['role']);

      $this->populateFromArray($resultSet[0][0]);
    } else {
      throw new \Exception(
        'Unable to select this user\'s data from the database.'
      );
    }
  }

  /**
  * Handles the registration process for a UserModel instance.
  *
  * @return bool True if the registration was successful or False if the user
  *              was inserted in the DB but the activation email failed to be sent.
  *
  * @throws \Exception with an error message if any major steps fail.
  */
  public function register(): bool {
    $query = '(select count(id) as count from users where userName=:userName) '.
      'union all (select count(id) as count from users where email=:email)';
    $paramData = [
      [
        'userName' => [$this->getUserName(), \PDO::PARAM_STR],
        'email' => [$this->getEmail(), \PDO::PARAM_STR],
      ],
    ];

    $resultSet = $this->dbInterface->select($query, $paramData);

    if ($resultSet[0][0]['count'] > 0) {
      throw new \Exception(
        "1062 Duplicate entry '{$this->getUserName()}' for key 'userName'"
      );
    } 
    
    if ($resultSet[0][1]['count'] > 0) {
      throw new \Exception(
        "1062 Duplicate entry '{$this->getEmail()}' for key 'email'"
      );
    }

    $this->setPassword($this->encoder->encodePassword($this,
      $this->getPlainPassword()));

    $this->setCreated(time());
    
    $activationTokenData = Utils::generateToken();
    $activationTokenHash = Utils::createHash($activationTokenData['token']);

    $query = 'INSERT INTO users '.
      "VALUES(null,:userName,:email,:password,0,1,'${activationTokenHash}',".
      "{$activationTokenData['ts']},null,null,:created)";
    $paramNames = ['userName', 'email', 'password', 'created'];
    
    $insertedIds = $this->dbInterface->changeFromModel($query, $paramNames,
      [$this], false);

    if (count($insertedIds) !== 1 || $insertedIds[0] === null) {
      throw new \Exception(
        'An error occurred while executing the register related queries.'
      );
    }

    return($this->emailInterface->activationEmail($this->getEmail(),
      $activationTokenData['token']));
  }

  /**
  * Handles activating the user's account associated with the UserModel instance.
  *
  * @param string $token The activation token
  * @param RouterInterface $router An instance of symfony's RouterInterface
  *
  * @throws \Exception with an error message if any step fails.
  */
  public function activate(string $token, RouterInterface $router): void {
    $query = 'SELECT activationHash, activationHashGenTs FROM users WHERE '.
      'email = :email AND isActive = 0';
    $paramData = [
      [
        'email' => [$this->getEmail(), \PDO::PARAM_STR],
      ],
    ];

    $resultSet = $this->dbInterface->select($query, $paramData);

    if (count($resultSet[0]) !== 1) {
      throw new \Exception(
        "There is no inactive user with email '{$this->getEmail()}'."
      );
    }

    if (!Utils::isHashValid($token, $resultSet[0][0]['activationHash'])) {
      throw new \Exception(
        "The activation token provided for the email '{$this->getEmail()}' is ".
        'not correct.'
      );
    }

    $tokenDurationHours = $this->container->getParameter('activationTokenDuration');

    if ($resultSet[0][0]['activationHashGenTs'] + $tokenDurationHours * 3600 < time()) {
      try {
        $errorMsg = '[error]The activation link used is expired.';

        $this->genTokenSendEmail('activation');

        $errorMsg .= ' A new activation link was sent to this account\'s email '.
          'address.';
      } catch (\Exception $e) {
        $errorMsg .= ' An attempt was made to send a new activation link to '.
          'this account\'s email address, but the email could not be sent. '.
          'Please <a href="'.
          $router->generate('resendActivation', ['e' => $this->getEmail()]).
          '">click here</a> to resend the activation email.';
      }

      throw new \Exception($errorMsg);
    }

    $query = 'UPDATE users SET isActive = 1, activationHash = null, '.
      'activationHashGenTs = null WHERE email = :email';
    $paramData = [
      [
        'email' => [$this->getEmail(), \PDO::PARAM_STR],
      ],
    ];

    $numAffectedRows = $this->dbInterface->change($query, $paramData);

    if ($numAffectedRows[0] !== 1) {
      throw new \Exception(
        "Failed to UPDATE the user with email '{$this->getEmail()}' with the ".
        'active account values.'
      );
    }
  }

  /**
  * Handles generating a token, hashing it, storing the hash in the DB and
  * sending an email to this UserModel instance's email address with a link
  * relevant to the token type generated (handled by EmailInterface).
  *
  * @param string $tokenType The type of token that will be processed.
  *                          Accepts 'activation' or 'pwReset'
  *
  * @throws \Exception with an error message if any step fails.
  */
  private function genTokenSendEmail(string $tokenType): void {
    if (!in_array($tokenType, ['activation', 'pwReset'])) {
      throw new \Exception("The token type '${tokenType}' is not valid.");
    }

    $tokenData = Utils::generateToken();
    $tokenHash = Utils::createHash($tokenData['token']);

    $query = 'UPDATE users SET '.($tokenType === 'activation' ? 'isActive = 0, ' : '').
      "${tokenType}Hash = :hash, ${tokenType}HashGenTs = :hashTs WHERE email = :email";
    $paramData = [
      [
        'hash' => [$tokenHash, \PDO::PARAM_STR],
        'hashTs' => [$tokenData['ts'], \PDO::PARAM_INT],
        'email' => [$this->getEmail(), \PDO::PARAM_STR],
      ],
    ];

    $updatedRows = $this->dbInterface->change($query, $paramData);

    if ($updatedRows[0] !== 1) {
      throw new \Exception(
        'The database failed to be updated with the new token.'
      );
    }

    if (!$this->emailInterface->{"${tokenType}Email"}($this->getEmail(),
    $tokenData['token'])) {
      throw new \Exception('The email with the new token failed to be sent.');
    }
  }

  /**
  * Handles resending a user's activation email.
  *
  * @throws \Exception with an error message if any step fails.
  */
  public function regenActivationToken(): void {
    $query = 'SELECT count(id) as count FROM users WHERE email = :email AND '.
      'activationHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$this->getEmail(), \PDO::PARAM_STR],
      ],
    ];

    $resultSet = $this->dbInterface->select($query, $paramData);

    if (intval($resultSet[0][0]['count'], 10) !== 1) {
      throw new \Exception(
        "No user was found in the DB with email '{$this->getEmail()}' and with ".
        'an activation token.'
      );
    }

    $this->genTokenSendEmail('activation');
  }

  /**
  * Handles initiating the password reset process.
  *
  * @throws \Exception with an error message if any step fails.
  */
  public function initPwResetProcess(): void {
    $query = 'SELECT id FROM users WHERE email = :email AND isActive = 1';
    $paramData = [
      [
        'email' => [$this->getEmail(), \PDO::PARAM_STR],
      ],
    ];

    $resultSet = $this->dbInterface->select($query, $paramData);

    if (count($resultSet[0]) === 0 || count($resultSet[0][0]) !== 1) {
      throw new \Exception(
        "No user was found in the DB with email '{$this->getEmail()}' with ".
        'an active account.'
      );
    }

    $this->genTokenSendEmail('pwReset');
  }

  /**
  * Handles resetting the user's password, based on the provided token.
  *
  * @param string $token The password reset token
  *
  * @throws \Exception with an error message if any step fails.
  * @throws \AppBundle\Exceptions\TokenExpiredException if the provided token is expired.
  */
  public function resetPw(string $token): void {
    $query = 'SELECT pwResetHash, pwResetHashGenTs FROM users WHERE '.
      'email = :email AND pwResetHash IS NOT NULL';
    $paramData = [
      [
        'email' => [$this->getEmail(), \PDO::PARAM_STR],
      ],
    ];

    $resultSet = $this->dbInterface->select($query, $paramData);

    if (count($resultSet[0]) !== 1) {
      throw new \Exception(
        "No user was found in the DB with email '{$this->getEmail()}' and with ".
        'a password reset token.'
      );
    }

    if (!Utils::isHashValid($token, $resultSet[0][0]['pwResetHash'])) {
      throw new \Exception(
        "The password reset token provided for the email '{$this->getEmail()}' ".
        'is not correct.'
      );
    }

    $tokenDurationHours = $this->container->getParameter('resetPwTokenDuration');

    if ($resultSet[0][0]['pwResetHashGenTs'] + $tokenDurationHours * 3600 < time()) {
      $query = 'UPDATE users SET pwResetHash = null, pwResetHashGenTs = null '.
        'WHERE email = :email';
      $paramData = [
        [
          'email' => [$this->getEmail(), \PDO::PARAM_STR],
        ],
      ];

      $this->dbInterface->change($query, $paramData);
      
      throw new TokenExpiredException(
        "The password reset token provided for the email '{$this->getEmail()}' ".
        'is expired.'
      );
    }

    $newPwHash = $this->encoder->encodePassword($this, $this->getPlainPassword());

    $query = 'UPDATE users SET password = :pw, pwResetHash = null, '.
      'pwResetHashGenTs = null WHERE email = :email';
    $paramData = [
      [
        'pw' => [$newPwHash, \PDO::PARAM_STR],
        'email' => [$this->getEmail(), \PDO::PARAM_STR],
      ],
    ];

    $updatedRows = $this->dbInterface->change($query, $paramData);

    if ($updatedRows[0] === 0) {
      throw new \Exception(
        "The new password for the email '{$this->getEmail()}' failed to be ".
        'stored in the DB.'
      );
    }
  }
}