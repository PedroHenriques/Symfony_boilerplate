<?php

namespace AppBundle\Model;

interface ModelInterface {
  /**
  * Queries the DB for the data used to populate a Model instance properties.
  *
  * @param array $bindData The column names and their values to be used in the
  *                        WHERE clause of the DB query
  */
  public function populateFromDb(array $bindData): void;

  /**
  * Populates a Model's instance properties with the provided data.
  *
  * @param array $data The properties (keys) and their values (values)
  */
  public function populateFromArray(array $data): void;

  /**
  * Returns the Model's data in a format ready to be used in a DB prepared
  * statement.
  *
  * @return array The parameter names (keys) and their values are arrays with
  *               the bind value and the PDO data type
  */
  public function dbData(): array;
}