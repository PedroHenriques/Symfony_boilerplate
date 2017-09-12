<?php

namespace tests\unit\AppBundle\Services;

use AppBundle\Services\Utils;

use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase {
  public function testGeneratetokenItShouldReturnAnArrayWithAnAsciiTokenAndATimestamp() {
    $actualValue = Utils::generateToken();

    $this->assertEquals(['token', 'ts'], array_keys($actualValue));
    $this->assertEquals(1, preg_match('/^[[:ascii:]]+$/', $actualValue['token']));
    $this->assertEquals(1, preg_match('/^\d+$/', $actualValue['ts']));
  }

  public function testCreatehashItShouldReturnAHashForTheProvidedString() {
    $actualValue = Utils::createHash('secretString');

    $this->assertEquals(true, password_verify('secretString', $actualValue));
  }

  public function testIshashvalidReturnTrueIfTheRawStringMatchesTheHash() {
    $actualValue = Utils::isHashValid('secretString', '$2y$15$IN8D5FzjpszXcLAR0yzHEeJEKjekAszQqppiCKUVhiMC8NZpWTHWe');

    $this->assertEquals(true, $actualValue);
  }
  
  public function testIshashvalidReturnFalseIfTheRawStringDoesNotMatchTheHash() {
    $actualValue = Utils::isHashValid('othersecretString', '$2y$15$IN8D5FzjpszXcLAR0yzHEeJEKjekAszQqppiCKUVhiMC8NZpWTHWe');

    $this->assertEquals(false, $actualValue);
  }
}