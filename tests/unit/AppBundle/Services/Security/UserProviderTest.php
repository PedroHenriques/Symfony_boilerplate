<?php

namespace tests\unit\AppBundle\Services\Security;

use AppBundle\Services\Security\UserProvider;
use AppBundle\Model\{UserModelFactory, UserModel};

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\{UsernameNotFoundException, UnsupportedUserException};

class UserProviderTest extends TestCase {
  protected function setUp() {
    parent::setUp();

    $this->userFactoryMock = $this->createMock(UserModelFactory::class);
    $this->userMock = $this->createMock(UserModel::class);
  }

  public function testLoaduserbyusernameItShouldDetermineIfTheProvidedUniqueIdIsTheUsernameOrTheEmailAndReturnAUserInstancePopulatedFromTheDb() {
    $uniqueId = 'test@test.com';
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $this->userMock->expects($this->once())
      ->method('populateFromDb')
      ->with(['email' => [$uniqueId, \PDO::PARAM_STR]])
      ->willReturn(null);
    
    $userProvider = new UserProvider($this->userFactoryMock);
    $actualValue = $userProvider->loadUserByUsername($uniqueId);

    $this->assertEquals(get_class($this->userMock), get_class($actualValue));
  }

  public function testLoaduserbyusernameIfTheUniqueIdIsAUsernameItShouldReturnAUserInstancePopulatedFromTheDb() {
    $uniqueId = 'test user name';
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->willReturn($this->userMock);
    
    $this->userMock->expects($this->once())
      ->method('populateFromDb')
      ->with(['userName' => [$uniqueId, \PDO::PARAM_STR]])
      ->willReturn(null);
    
    $userProvider = new UserProvider($this->userFactoryMock);
    $actualValue = $userProvider->loadUserByUsername($uniqueId);

    $this->assertEquals(get_class($this->userMock), get_class($actualValue));
  }

  public function testLoaduserbyusernameIfUsermodelfactoryThrowsAnExceptionItShouldCatchItAndThrowAUsernamenotfoundexception() {
    $this->expectException(UsernameNotFoundException::class);

    $uniqueId = 'test user name';
    
    $this->userFactoryMock->expects($this->once())
      ->method('create')
      ->with()
      ->will($this->throwException(new \Exception));
    
    $userProvider = new UserProvider($this->userFactoryMock);
    $userProvider->loadUserByUsername($uniqueId);
  }

  public function testRefreshuserItShouldCheckTheProvidedUserInterfaceIsAUserModelInstanceThenCallLoaduserbyusernameAndReturnTheUserModelInstance() {
    $newUserMock = $this->createMock(UserModel::class);
    $userProviderMock = $this->getMockBuilder(UserProvider::class)
      ->setConstructorArgs([$this->userFactoryMock])
      ->setMethods(['loadUserByUsername'])
      ->getMock();

    $userEmail = 'test@test.com';

    $this->userMock->expects($this->once())
      ->method('getEmail')
      ->with()
      ->willReturn($userEmail);

    $userProviderMock->expects($this->once())
      ->method('loadUserByUsername')
      ->with($userEmail)
      ->willReturn($newUserMock);
    
    $actualValue = $userProviderMock->refreshUser($this->userMock);

    $this->assertEquals(get_class($newUserMock), get_class($actualValue));
  }

  public function testRefreshuserIfTheProvidedUserInterfaceIsntAUserModelItShouldThrowAnUnsupporteduserexceptionExceptionWithAnErrorMsg() {
    $userInterfaceMock = $this->createMock(UserInterface::class);

    $this->expectException(UnsupportedUserException::class);
    $this->expectExceptionMessage('Instances of '.get_class($userInterfaceMock).' are not supported.');

    $userProvider = new UserProvider($this->userFactoryMock);
    $userProvider->refreshUser($userInterfaceMock);
  }

  public function testRefreshuserIfLoaduserbyusernameThrowsAnExceptionItShouldLetItBubbleUp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('UserProvider loadUserByUsername() exception msg');

    $userProviderMock = $this->getMockBuilder(UserProvider::class)
      ->setConstructorArgs([$this->userFactoryMock])
      ->setMethods(['loadUserByUsername'])
      ->getMock();

    $userEmail = 'test@test.com';

    $this->userMock->expects($this->once())
      ->method('getEmail')
      ->with()
      ->willReturn($userEmail);

    $userProviderMock->expects($this->once())
      ->method('loadUserByUsername')
      ->with($userEmail)
      ->will($this->throwException(new \Exception('UserProvider loadUserByUsername() exception msg')));
    
    $userProviderMock->refreshUser($this->userMock);
  }

  public function testSupportsclassItShouldReturnTrueIfTheProvidedClassNameIsUserModel() {
    $userProvider = new UserProvider($this->userFactoryMock);
    $actualValue = $userProvider->supportsClass(UserModel::class);

    $this->assertEquals(true, $actualValue);
  }
  
  public function testSupportsclassItShouldReturnFalseIfTheProvidedClassNameIsNotUserModel() {
    $userProvider = new UserProvider($this->userFactoryMock);
    $actualValue = $userProvider->supportsClass(\Exception::class);

    $this->assertEquals(false, $actualValue);
  }
}

Class CustomUserProviderContainer implements \Psr\Container\ContainerInterface {
  public function getParameter(string $name) {}
  public function get($id) {}
  public function has($id) {}
}