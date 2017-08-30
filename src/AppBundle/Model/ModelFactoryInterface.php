<?php

namespace AppBundle\Model;

use AppBundle\Model\ModelInterface;

interface ModelFactoryInterface {
  /**
  * Creates an empty instance of a ModelInterface object.
  *
  * @return ModelInterface The empty instance
  */
  public function create(): ModelInterface;

  /**
  * Creates an instance of a ModelInterface object with its properties populated
  * from data queried from the DB.
  *
  * @param array $bindData The column names and their values to be used in the
  *                        WHERE clause of the DB query
  *
  * @return ModelInterface The populated ModelInterface instance.
  */
  public function createFromDb(array $bindData): ModelInterface;

  /**
  * Creates an instance of a ModelInterface with its properties populated from
  * the provided data by calling the setters for the relevant properties.
  *
  * @param array $data The properties (keys) and their values (values)
  *
  * @return ModelInterface The populated ModelInterface instance.
  */
  public function createFromArray(array $data): ModelInterface;
}