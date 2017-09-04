<?php

namespace AppBundle\Services;

use AppBundle\Services\DbInterface;
use AppBundle\Model\{ModelInterface, ModelFactoryInterface};

use Doctrine\DBAL\Driver\{Connection, Statement};

class DbHandler implements DbInterface {
  private $connection;

  /**
  * @param Connection $conn An instance of Doctrine DBAL Connection object.
  */
  public function __construct(Connection $conn) {
    $this->connection = $conn;
  }

  /** {@inheritDoc} */
  public function inTransaction(): bool {
    return($this->connection->isTransactionActive());
  }

  /** {@inheritDoc} */
  public function beginTransaction(): bool {
    try {
      return(!($this->connection->beginTransaction() === false));
    } catch (\Exception $e) {
      return(false);
    }
  }

  /** {@inheritDoc} */
  public function commit(): bool {
    try {
      return(!($this->connection->commit() === false));
    } catch (\Exception $e) {
      return(false);
    }
  }
  
  /** {@inheritDoc} */
  public function rollBack(): bool {
    try {
      return(!($this->connection->rollBack() === false));
    } catch (\Exception $e) {
      return(false);
    }
  }

  /**
  * {@inheritDoc}
  * The return array has the value of fetchAll(\PDO::FETCH_ASSOC).
  */
  public function query(string $query): array {
    return($this->connection->query($query)->fetchAll(\PDO::FETCH_ASSOC));
  }

  /**
  * {@inheritDoc}
  *
  * @param array $fetchAllArgs The arguments that will be passed to fetchAll()
  *                            using array unpacking.
  *                            Defaults to [\PDO::FETCH_ASSOC].
  *
  * The return array has the values of fetchAll() for each
  * execution of the prepared statement in separate indexes.
  *
  * @throws \Exception with an error message if any step fails.
  */
  public function select(string $query, array $paramData,
  array $fetchAllArgs = [\PDO::FETCH_ASSOC]): array {
    if (preg_match('/^\(?SELECT /i', $query) !== 1) {
      throw new \Exception("The query '${query}' is not a SELECT statement.");
    }

    if (empty($paramData)) {
      throw new \Exception(
        "No parameter data was provided for the query '${query}'. As such ".
        'select() is not the most efficient option. Consider using query() instead.'
      );
    }

    $resultSet = [];

    $prepStatement = $this->connection->prepare($query);

    for ($i = 0; $i < count($paramData); $i++) {
      foreach ($paramData[$i] as $paramName => $data) {
        if ($prepStatement->bindValue($paramName, $data[0], $data[1]) === false) {
          throw new \Exception(
            "The parameter named '${paramName}' for the execution #".($i+1).
            ' failed to be bound.'
          );
        }
      }

      if ($prepStatement->execute() === false) {
        throw new \Exception(
          'The query\'s execution failed for the execution #'.($i+1)
        );
      } else {
        $selectedData = $prepStatement->fetchAll(...$fetchAllArgs);

        if ($selectedData === false) {
          throw new \Exception(
            'The call to fetchAll() failed for the execution #'.($i+1)
          );
        }

        $resultSet[] = $selectedData;
      }
    }

    return($resultSet);
  }

  /**
  * {@inheritDoc}
  * The return array has the values of fetchAll(\PDO::FETCH_ASSOC) for each
  * execution of the prepared statement in separate indexes, if an UPDATE or
  * DELETE query, or the results of lastInsertId() if an INSERT query.
  *
  * @throws \Exception with an error message if any step fails.
  */
  public function change(string $query, array $paramData): array {
    if (preg_match('/^\(?SELECT /i', $query) === 1) {
      throw new \Exception(
        "The query '${query}' is not an INSERT, UPDATE or DELETE statement."
      );
    }
    
    if (empty($paramData)) {
      throw new \Exception(
        "No parameter data was provided for the query '${query}'. As such ".
        'change() is not the most efficient option. Consider using query() instead.'
      );
    }

    $result = [];

    if (preg_match('/^insert /i', $query) === 1) {
      $isInsert = true;
    } else {
      $isInsert = false;

      $result[0] = 0;
    }

    $prepStatement = $this->connection->prepare($query);

    for ($i = 0; $i < count($paramData); $i++) {
      $allOk = true;

      foreach ($paramData[$i] as $paramName => $data) {
        if ($prepStatement->bindValue($paramName, $data[0], $data[1]) === false) {
          $allOk = false;
          break;
        }
      }

      if ($allOk && $prepStatement->execute() === false) {
        $allOk = false;
      }

      if ($isInsert) {
        $result[] = ($allOk ? $this->connection->lastInsertId() : null);
      } elseif ($allOk) {
        $numAffectedRows = $prepStatement->rowCount();

        if ($numAffectedRows === 0) {
          $allOk = false;
        } else {
          $result[0] += $numAffectedRows;
        }
      }

      if ($allOk === false && $this->inTransaction()) {
        throw new \Exception(
          "The query on index number ${i} failed to be executed."
        );
      }
    }

    return($result);
  }

  /**
  * Wraps a call to change() in a transaction that will automaticaly commit
  * or reject.
  *
  * @param string $query The query to be executed.
  * @param array $paramData The values and data types of each query parameter.
  *
  * @return array The return value of change() on a successful execution.
  *
  * @throws \Exception with an error message if any step fails or if the transaction
  *          is rolled back.
  */
  public function changeInBulk(string $query, array $paramData): array {
    if ($this->beginTransaction() === false) {
      throw new \Exception('A transaction failed to be initiated.');
    }

    try {
      $returnValue = $this->change($query, $paramData);
    } catch (\Exception $e) {
      $errorMsg = 'Not all queries were successfully executed. As such the '.
        'transaction was rolled back.';

      if ($this->rollBack() === false) {
        $errorMsg .= ' NOTE: The transaction failed to be rolled back.';
      }

      throw new \Exception($errorMsg);
    }

    if ($this->commit() === false) {
      throw new \Exception('A transaction failed to be committed.');
    }

    return($returnValue);
  }

  /**
  * {@inheritDoc}
  *
  * @throws \Exception with an error message if any step fails.
  */
  public function changeFromModel(string $query, array $paramNames, array $models,
  bool $withTransaction): array {
    $paramData = [];

    for ($i = 0; $i < count($models); $i++) {
      if (($models[$i] instanceof ModelInterface) === false) {
        throw new \Exception(
          "The object at index #${i} provided to the \"\$models\" parameter of ".
          'changeFromModel() doesn\'t implement ModelInterface.'
        );
      }

      $modelData = $models[$i]->dbData();

      $rowParamData = [];
      foreach ($paramNames as $paramName) {
        $rowParamData[$paramName] = [
          $modelData[$paramName][0],
          $modelData[$paramName][1],
        ];
      }

      $paramData[] = $rowParamData;
    }
    
    if ($withTransaction) {
      return($this->changeInBulk($query, $paramData));
    } else {
      return($this->change($query, $paramData));
    }
  }

  /**
  * {@inheritDoc}
  *
  * @throws \Exception with an error message if any step fails.
  */
  public function selectIntoModel(string $query, array $paramData,
  ModelFactoryInterface $modelFactory, callable $callBack = null,
  string $indexCol = ''): array {
    $resultSet = $this->select($query, $paramData);

    $models = [];

    foreach ($resultSet as $rows) {
      foreach ($rows as $modelData) {
        if ($indexCol !== '' && !array_key_exists($indexCol, $modelData)) {
          throw new \Exception(
            "The indexing column '${indexCol}' isn't part of the result set."
          );
        }

        if ($callBack !== null) {
          $modelData = $callBack($modelData);
        }

        $modelIndex = ($indexCol==='' ? count($models) : $modelData[$indexCol]);
        $models[$modelIndex] = $modelFactory->createFromArray($modelData);
      }
    }

    return($models);
  }
}