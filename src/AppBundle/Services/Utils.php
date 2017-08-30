<?php

namespace AppBundle\Services;

class Utils {
  /**
  * Randomly generates a token string.
  *
  * @return array The token and its generated timestamp in the format
  *               ['token'=>string, 'ts'=>int]
  */
  public function generateToken(): array {
    return([
      'token' => bin2hex(openssl_random_pseudo_bytes(16)),
      'ts' => time()
    ]);
  }

  /**
  * Creates a hash from the provided string, using password_hash().
  *
  * @param string $rawString The string to hash.
  *
  * @return string The hashed string or en empty string on failure.
  */
  public function createHash(string $rawString): string {
    $hash = password_hash($rawString, PASSWORD_DEFAULT, ['cost' => 15]);

    return(($hash === false ? '' : $hash));
  }

  /**
  * Checks if the provided string matches the provided hash.
  *
  * @param string $rawString The string to check.
  * @param string $hash The hashed string to check against.
  *
  * @return bool True if the string matches the hash or False otherwise.
  */
  public function isHashValid(string $rawString, string $hash): bool {
    return(password_verify($rawString, $hash));
  }
}