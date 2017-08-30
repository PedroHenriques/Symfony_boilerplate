<?php

namespace AppBundle\Model;

use AppBundle\Model\ModelInterface;

abstract class Model implements ModelInterface {
  /** {@inheritDoc} */
  abstract public function dbData(): array;

  /** {@inheritDoc} */
  abstract public function populateFromDb(array $bindData): void;

  /**
  * {@inheritDoc}
  *
  * Calls the setters for the relevant properties.
  * Ex: ['userName' => 'Pedro'] will result in setUserName('Pedro') being called.
  *
  * @throws \Exception if a setter doesn't exist
  */
  final public function populateFromArray(array $data): void {
    foreach ($data as $propName => $propValue) {
      $setterName = 'set'.ucfirst($propName);

      if (!method_exists($this, $setterName)) {
        throw new \Exception(
          'The '.get_class($this)." class doesn't have the method ${setterName}"
        );
      }

      $this->$setterName($propValue);
    }
  }
}