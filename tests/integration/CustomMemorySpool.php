<?php

namespace tests\integration;

final class CustomMemorySpool extends \Swift_MemorySpool {
  public function getMessages(): array {
    return($this->messages);
  }
}