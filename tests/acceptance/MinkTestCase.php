<?php

namespace tests\acceptance;

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
use Behat\Mink\{Mink, Session};
use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Driver\Goutte\Client as GoutteClient;
 
abstract class MinkTestCase extends TestCase {
  use TestCaseTrait {
    setUp as public setUpTestCase;
  }

  private static $mink;
  private static $baseUrl;
  private static $mailCatcher;
  private static $pdo;
  private $connection;

  protected function getMink() {
    return(self::$mink);
  }

  protected static function getPdo() {
    return(self::$pdo);
  }

  protected static function getBaseUrl() {
    return(self::$baseUrl);
  }

  /** {@inheritDoc} */
  abstract protected function getDataSet();

  /** {@inheritDoc} */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();

    self::$baseUrl = "http://{$GLOBALS['DOMAIN_NAME']}:{$GLOBALS['DOMAIN_PORT']}/app_test.php";

    if (self::$mink === null) {
      self::$mink = new Mink([
        'goutte' => self::createGoutteSession(),
      ]);
      self::$mink->setDefaultSessionName('goutte');
    }

    if (self::$mailCatcher === null) {
      self::$mailCatcher = new \GuzzleHttp\Client(['base_uri' => 'http://127.0.0.1:1080']);
    }
  }

  /** {@inheritDoc} */
  protected function setUp() {
    parent::setUp();
    $this->setUpTestCase();

    $this->getMink()->resetSessions();
    self::clearEmails();
  }

  /** {@inheritDoc} */
  final public function getConnection() {
    if ($this->connection === null) {
      if (self::$pdo === null) {
        try {
          self::$pdo = new \PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
        } catch (\PDOException $e) {
          echo("\n[ERROR] Unable to connect to the DB server.\n");
          throw $e;
        }
      }

      $this->connection = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
    }

    return($this->connection);
  }

  /**
  * Retrieves a Session from the Mink object.
  *
  * @param string $name The name associated with the Session in the Mink object.
  *
  * @return Session The Session with the requested name or the default one if no
  *                 name was provided.
  */
  protected function getSession(string $name = null): Session {
    return($this->getMink()->getSession($name));
  }

  /**
  * Creates a new Session with a GoutteDriver and GoutteClient instances.
  *
  * @return Session
  */
  protected static function createGoutteSession(): Session {
    return(new Session(new GoutteDriver(new GoutteClient())));
  }

  /**
  * Builds an absolute URL from a URI, based on the base url.
  *
  * @param string $uri The URI.
  *
  * @return string The absolute URL.
  */
  protected function getUrlFromUri(string $uri): string {
    if (strpos($uri, '/') !== 0) {
      $uri = "/${uri}";
    }

    return(self::$baseUrl.$uri);
  }

  /**
  * Calls a Session's visit() on a URL built from the provided URI.
  *
  * @param Session $session The Session instance to call visit() on.
  * @param string $uri The URI to visit.
  */
  protected function visitUri(Session $session, string $uri): void {
    $session->visit($this->getUrlFromUri($uri));
  }

  /**
  * Authenticates a user in a Session via the login form.
  *
  * @param Session $session The session where the user will be logged in.
  * @param string $uniqueId The unique identifier used in the login form.
  * @param string $password The password used in the login form.
  */
  protected function authenticateUser(Session $session, string $uniqueId,
  string $password): void {
    $this->visitUri($session, '/login');
    $page = $session->getPage();

    $form = $page->find('named', ['id_or_name', 'login']);
    $fieldUniqueId = $form->findField('login_uniqueId');
    $fieldPassword = $form->findField('login_password');

    $fieldUniqueId->setValue($uniqueId);
    $fieldPassword->setValue($password);
    $form->submit();
  }

  /**
  * Returns the meta data for all the emails caught be mailcatcher.
  *
  * @return array The decoded json string with the meta data for each email.
  */
  protected function getEmails(): array {
    $jsonResponse = self::$mailCatcher->get('/messages');
    return(json_decode($jsonResponse->getBody(), true));
  }

  /**
  * Retrieves the HTML body of the email with the provided ID.
  *
  * @param int $id The desired email's ID in mailcatcher.
  *
  * @return string
  */
  protected function getEmailBody(int $id): string {
    return(self::$mailCatcher->get("/messages/${id}.html")->getBody());
  }

  /**
  * Clears all the emails stored by mailcatcher.
  */
  private static function clearEmails(): void {
    self::$mailCatcher->delete('/messages');
  }

  private static $htmlCleanerPatterns = [
    '/>(?:[\n\t\r]| {2,})+</' => '><',
    '/^(?:[\n\t\r]| {2,})+</' => '<',
    '/>(?:[\n\t\r]| {2,})+$/' => '>',
  ];

  /**
  * Strips line breaks and excess whitespaces from an html string.
  *
  * @param string $html The html string to condense into one line.
  *
  * @return string
  */
  protected static function oneLineHtml(string $html): string {
    foreach (self::$htmlCleanerPatterns as $pattern => $replacement) {
      $html = preg_replace($pattern, $replacement, $html);
    }

    return($html);
  }
}