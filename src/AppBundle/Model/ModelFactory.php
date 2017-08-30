<?php

namespace AppBundle\Model;

use AppBundle\Model\{ModelInterface, ModelFactoryInterface};

abstract class ModelFactory implements ModelFactoryInterface {
  /** {@inheritDoc} */
  abstract public function create(): ModelInterface;

  /** {@inheritDoc} */
  final public function createFromDb(array $bindData): ModelInterface {
    $model = $this->create();

    $model->populateFromDb($bindData);

    return($model);
  }

  /** {@inheritDoc} */
  final public function createFromArray(array $data): ModelInterface {
    $model = $this->create();

    $model->populateFromArray($data);

    return($model);
  }
}