<?php

namespace tests\integration;

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'CustomMemorySpool.php');

use tests\integration\CustomMemorySpool;

use PHPUnit\DbUnit\TestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class BaseIntegrationCase extends WebTestCase {
  use TestCaseTrait;

  private static $pdo;
  private $connection;
  private $spool;

  protected static function getPdo() {
    return(self::$pdo);
  }
  
  protected function getSpool() {
    return($this->spool);
  }

  /** {@inheritDoc} */
  abstract protected function getDataSet();

  /** {@inheritDoc} */
  final public function getConnection() {
    if ($this->connection === null) {
      if (self::$pdo === null) {
        try {
          self::$pdo = new \PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
        } catch (\PDOException $e) {
          print_r("\n[ERROR] Unable to connect to the DB server.\n");
          throw $e;
        }
      }

      $this->connection = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
    }

    return($this->connection);
  }

  /**
  * Creates a new instance of \Swift_Mailer with a custom memory spool that
  * allows access to the message queue.
  *
  * @return \Swift_Mailer A new instance of \Swift_Mailer.
  */
  protected function createCustomMailer(): \Swift_Mailer {
    $this->spool = new CustomMemorySpool();

    $transport = new \Swift_Transport_SpoolTransport(
      new \Swift_Events_SimpleEventDispatcher(),
      $this->spool
    );
    
    return(new \Swift_Mailer($transport));
  }

  /**
  * Retrieves the messages in the queue of the Swift Mailer instance.
  *
  * @return array The messages in the queue to be sent.
  */
  protected function getEmails(): array {
    return($this->getSpool()->getMessages());
  }
}