[![Build Status](https://travis-ci.org/PedroHenriques/Symfony_boilerplate.svg?branch=master)](https://travis-ci.org/PedroHenriques/Symfony_boilerplate)

# Symfony 3.3.* Boilerplate (Startup Kit)

## Setup

1. Download this repository as a ZIP file and unzip its contents into an empty directory (your project's root directory).
2. Rename the file `.gitignore.src` to `.gitignore`
3. `cd` into the project's directory
4. Run `composer install`
5. Do all the Symfony specific configuration (ex: secret tokens, database credentials, etc.)
6. Run the database migrations on your development database using `php bin/console doctrine:migrations:migrate --env=dev`
7. Adjust the values in `services.yml` file under

```yml
parameters:
  activationTokenDuration: 72
  resetPwTokenDuration: 2

  EmailHandler:
    from: 'your@email.com'
    websiteName: 'YOUR WEBSITE NAME'
```

8. Adjust the route where users will be redirected to after they login in `security.yml` file under

```yml
firewalls:
  ...
  main:
    ...
    form_login:
      ...
      default_target_path: homepage
      ...
```

9. Change the `indexAction` content and the views to your application's needs

### Test Environment Setup

1. Update the project's test database and webserver information in the `phpunit.xml.dist` file under

```xml
<php>
    <var name="DB_DSN" value="mysql:host=localhost;dbname=phpunit_test" />
    <var name="DB_USER" value="root" />
    <var name="DB_PASSWD" value="" />
    <var name="DB_DBNAME" value="phpunit_test" />

    <var name="DOMAIN_NAME" value="localhost" />
    <var name="DOMAIN_PORT" value="8000" />
    ...
</php>
```

2. Update the project's test database information in the `config_test.yml` file under

```yml
doctrine:
  dbal:
    driver: pdo_mysql
    host: 127.0.0.1
    port: null
    dbname: phpunit_test
    user: root
    password: null
    charset: utf8
    default_table_options:
      charset: utf8
      collate: utf8_unicode_ci
      engine: InnoDB
```

3. Create the project's test database, matching the name inserted in step 1

4. Run the database migrations on your test database using `php bin/console doctrine:migrations:migrate --env=test`

## Summary of what this boilerplate contains

- **User register and login system:**

  The registration system uses an activation email to validate the user's email address.
  A user can login using their email address or username.
  The user can request a password reset, which will send an email to the user with a link that will allow a new password to be inserted.
  The activation and password reset tokens expire after a certain amount of time, customizable in `security.yml`.

- **Models:**

  Following Symfony's way, you can use the approach to Models that best fits your needs.
  This boilerplate comes with a `ModelInterface` interface and an implementation of it in `Model` that can be extended by the models and provides base functionality (ex: populating its properties using an array).
  The models can be instantiated through factories and this boilerplate comes with a `ModelFactoryInterface` interface and an implementation in `ModelFactory` that allows instances of models to be created and its properties populated from data provided in an array or queried from the database.

- **Doctrine DBAL and Migrations:**

  The database schema is handled using migrations.
  The class `DbHandler` is used to handle all database interactions, which serves as a bridge between your application and Doctrine DBAL, and implements the `DbInterface` interface.
  This class allows for the execution of SQL queries directly as well as allowing some functionality for using Models in queries.
  If a different database abstraction is preferred simply change the connection object used by this class. No changes are needed in the rest of the application.

- **Emails:**

  Emails are sent using Swift Mailer.
  The class `EmailHandler` is used to handle all email interactions, which serves as a bridge between your application and Swift Mailer, and implements the `EmailInterface` interface.
  If a different mailer is preferred simply change the mailer object used by this class. No changes are needed in the rest of the application.

## Using this boilerplate

### Models and Model Factory
---

#### Models

This boilerplate expects models to implement `ModelInterface` (AppBundle/Model/ModelInterface.php), which requires the implementation of these methods

```php
public function populateFromDb(array $bindData): void;
public function populateFromArray(array $data): void;
public function dbData(): array;
```

And comes with an implementation of this interface in the class `Model` (AppBundle/Model/Model.php), which can be extended by the actual models.
This class has an implementation for `populateFromArray` which calls the setter functions for the properties provided in the argument.

#### Model Factory

Models can be created using factory classes and to facilitate this process, this boilerplate comes with an interface `ModelFactoryInterface` (AppBundle/Model/ModelFactoryInterface.php) which requires the following methods

```php
public function create(): ModelInterface;
public function createFromDb(array $bindData): ModelInterface;
public function createFromArray(array $data): ModelInterface;
```

which standardizes the way models are created, either empty or with their properties populated from an array or from a query to the database.

The class `ModelFactory` (AppBundle/Model/ModelFactory.php) is the boilerplate's implementation of the interface, which can be extended by each model's specific factory, that handles `createFromDb()` and `createFromArray()`.

### Database interaction
---

The code expects a class handling database interaction to implement `DbInterface` (AppBundle/Services/DbInterface.php) which requires the implementation of these methods

```php
public function inTransaction(): bool;
public function beginTransaction(): bool;
public function commit(): bool;
public function rollBack(): bool;
public function query(string $query): array;
public function select(string $query, array $paramData): array;
public function change(string $query, array $paramData): array;
public function changeFromModel(string $query, array $paramNames, array $models, bool $withTransaction): array;
public function selectIntoModel(string $query, array $paramData, ModelFactoryInterface $modelFactory, callable $callBack = null, string $indexCol = ''): array;
```

which provides the basic database interface and provides some functionality to interlace Models with database queries.

The class `DbHandler` (AppBundle/Services/DbHandler.php) is one implementation of the interface, which provides a bridge between your application and **Doctrine's DBAL**.
Besides implementing the interface's required methods, it also contains an extra method

```php
public function changeInBulk(string $query, array $paramData): array;
```

which wraps a transaction around a call to `change()`.

It also adds an optional argument to `select()` with the syntax

```php
public function select(string $query, array $paramData, array $fetchAllArgs = [\PDO::FETCH_ASSOC]): array;
```

which allows controll over the arguments that will be passed to `fetchAll()`, using array unpacking.

**NOTE:** If you create an alternative implementation of `DbInterface` update the autowire configuration in `services.yml` under

```yml
AppBundle\Services\DbInterface:
  alias: AppBundle\Services\DbHandler
  public: true
```

### Email interaction
---

The code expects any class handling email interaction to implement `EmailInterface` (AppBundle/Services/EmailInterface.php) which requires the implementation of

```php
public function sendEmail(string $type, string $subject, string $from, array $to, $content, string $contentType): bool;
public function activationEmail(string $userEmail, string $token): bool;
public function pwResetEmail(string $userEmail, string $token): bool;
```

which provides the general ability to send emails and handling of the specific cases of the activation and password reset emails.

The class `EmailHandler` (AppBundle/Services/EmailHandler.php) is an implementation of this interface, which provides a bridge between your application and **SwiftMailer**.

**NOTE:** If you create an alternative implementation of `EmailInterface` update the autowire configuration in `services.yml` under

```yml
AppBundle\Services\EmailInterface:
  alias: AppBundle\Services\EmailHandler
  public: true
```

### Utils class
---

The `Utils` class (AppBundle/Services/Utils.php) provides utility methods that can be used when needed, including

```php
public static function generateToken(): array;
public static function createHash(string $rawString): string;
public static function isHashValid(string $rawString, string $hash): bool;
```

- **`generateToken()`**:

  Generates a token using `bin2hex(openssl_random_pseudo_bytes(16))` which can be used when a random string is needed

  Returns an array with two key:value pairs - `token` contains the random string and `ts` contains the value of `time()` when the token was generated

- **`createHash()`**:

  Creates a hash from the provided string using `password_hash($rawString, PASSWORD_DEFAULT, ['cost' => 15])`
  Returns the hash or an empty string if the hash creation fails

- **`isHashValid()`**:

  Checks if a plain string matches a hash using PHP's `password_verify()`

  Returns `true` if they match or `false` otherwise

### Testing this boilerplate
---

This boilerplate comes with **unit**, **integration** and **acceptance** tests, located in the `tests` directory.
There is a script in composer.json named `test` that facilitates the execution of the tests.

#### Unit tests

To execute these tests run `composer test -- tests/unit` from the project's root directory.

#### Integration tests

- **Requirements**: a running database server

The test classes can extend `tests/integration/BaseIntegrationCase.php` which provides integration with dbunit, allowing the fixtures to be inserted into the test database before each individual test.
This class extends `Symfony\Bundle\FrameworkBundle\Test\WebTestCase.php` giving access to symfony's client and container if needed.

It provides the following methods

```php
protected function createCustomMailer(): \Swift_Mailer;
protected function getEmails(): array;
```

- `createCustomMailer()`: returns an instance of SwiftMailer with a custom memory spool that allows access to the messages stored in SwiftMailer's queue.

- `getEmails()`: returns an array with the emails stored in SwiftMailer's queue.

Each test class will need to implement dbunit's required `getDataSet()`, used to prepare the test database before each test.

To execute these tests run `composer test -- tests/integration` from the project's root directory.

#### Acceptance tests

- **Requirements**: a running database server + a running webserver + [a running mailcatcher server](https://mailcatcher.me/ "Mailcatcher's Homepage")

The test classes can extend `tests/acceptance/MinkTestCase.php` which provides integration with dbunit and Mink, allowing the fixtures to be inserted into the test database before each individual test and the use of web browsers to interact with the front-end of the website.

It provides the following methods

```php
protected function getSession(string $name = null): Session;
protected static function createGoutteSession(): Session;
protected function getUrlFromUri(string $uri): string;
protected function visitUri(Session $session, string $uri): void;
protected function authenticateUser(Session $session, string $uniqueId, string $password): void;
protected function getEmails(): array;
protected function getEmailBody(int $id): string;
protected static function oneLineHtml(string $html): string;
```

- `getSession()`: returns the Mink Session object registered to the provided name.

- `createGoutteSession()`: creates and returns a Mink Session with a Goutte driver and client.

- `getUrlFromUri()`: returns a URL based on the provided URI and the value of `$baseUrl`.

- `visitUri()`: converts the provided URI into a URL and calls `visit()` on the provided Mink Session.

- `authenticateUser()`: will make the provided Mink Session visit the login route and submit the login form with the provided credentials.

- `getEmails()`: returns an array with the emails caught by mailcatcher.

- `getEmailBody()`: returns the HTML content of the body for the email with the provided id.

- `oneLineHtml()`: returns a copy of the provided html string with all line breaks and excess whitespaces removed.

Each test class will need to implement dbunit's required `getDataSet()`, used to prepare the test database before each test.

To execute these tests run `composer test -- tests/acceptance` from the project's root directory.

These tests will use the file `web/app_test.php` and will run in the `test` environment.
It is recomended to clear and warmup the cache for the test environment using `composer build-test` from the project's root directory.
