<?php

namespace AppBundle\Services;

class Utils {
  /**
  * Randomly generates a token string.
  *
  * @return array The token and its generated timestamp in the format
  *               ['token'=>string, 'ts'=>int]
  */
  public static function generateToken(): array {
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
  * @return string The hashed string.
  *
  * @throws \Exception if the hash fails to be created.
  */
  public static function createHash(string $rawString): string {
    $hash = password_hash($rawString, PASSWORD_DEFAULT, ['cost' => 15]);

    if ($hash === false) {
      throw new \Exception('The hash failed to be created.');
    }

    return($hash);
  }

  /**
  * Checks if the provided string matches the provided hash.
  *
  * @param string $rawString The string to check.
  * @param string $hash The hashed string to check against.
  *
  * @return bool True if the string matches the hash or False otherwise.
  */
  public static function isHashValid(string $rawString, string $hash): bool {
    return(password_verify($rawString, $hash));
  }
}