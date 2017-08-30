<?php

namespace AppBundle\Services;

use AppBundle\Model\ModelFactoryInterface;

interface DbInterface {
  /**
  * Indicates whether the DB connection has an active transaction.
  *
  * @return bool True if there is an active transaction or False otherwise.
  */
  public function inTransaction(): bool;

  /**
  * Starts a transaction on the DB connection.
  *
  * @return bool True successful or False otherwise.
  */
  public function beginTransaction(): bool;

  /**
  * Commits the latest transaction on the DB connection.
  *
  * @return bool True successful or False otherwise.
  */
  public function commit(): bool;

  /**
  * Rolls back the latest transaction on the DB connection.
  *
  * @return bool True successful or False otherwise.
  */
  public function rollBack(): bool;

  /**
  * Executes a query without using prepared statements.
  *
  * @param string $query The query to be executed.
  *
  * @return array The query's result.
  */
  public function query(string $query): array;

  /**
  * Executes a SELECT query using prepared statements.
  *
  * @param string $query The SELECT query to be executed.
  * @param array $paramData The value and PDO data type for each query parameter.
  *
  * @return array The prepared statement execution results.
  */
  public function select(string $query, array $paramData): array;

  /**
  * Executes an INSERT, UPDATE or DELETE query using prepared statements.
  *
  * @param string $query The INSERT, UPDATE or DELETE query to be executed.
  * @param array $paramData The value and PDO data type for each query parameter.
  *
  * @return array The prepared statement execution results.
  */
  public function change(string $query, array $paramData): array;

  /**
  * Wraps a call to change() using Model objects as the source of the
  * data to bind to the query parameters.
  *
  * @param string $query The SELECT query to be executed.
  * @param array $paramNames The names of the query parameters.
  * @param ModelInterface[] $models The models to use as the data source.
  * @param bool $withTransaction If true the call to change() will be wraped in
  *             a transaction that automaticaly commits or roll backs.
  *
  * @return array The returned value of change().
  */
  public function changeFromModel(string $query, array $paramNames, array $models,
    bool $withTransaction): array;

  /**
  * Queries the DB and creates a Model object for each returned row.
  *
  * @param string $query The SELECT query to be executed.
  * @param array $paramData The value and PDO data type for each query parameter.
  * @param ModelFactoryInterface $modelFactory The ModelFactoryInterface object
  *                                            to be used to create the Model objects.
  * @param callable $callBack A function that will be called for each queried row
  *                           and should return the row's data with any
  *                           necessary modifications.
  * @param string $indexCol The row's column to be used to index the created
  *                         Model objects in the returned value. If not provided
  *                         a zero based integer index will be used.
  *
  * @return Model[] The created Model objects.
  */
  public function selectIntoModel(string $query, array $paramData,
    ModelFactoryInterface $modelFactory, callable $callBack = null,
    string $indexCol = ''): array;
}