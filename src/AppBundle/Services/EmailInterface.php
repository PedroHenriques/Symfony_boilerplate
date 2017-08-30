<?php

namespace AppBundle\Services;

interface EmailInterface {
  /**
  * Sends a message by email.
  *
  * @param string $type The type of message to send.
  * @param string $subject The subject of the message.
  * @param string $from The sender address.
  * @param array $to The destination addresses.
  * @param mixed $content The content of the message's body.
  * @param string $contentType The content type of message.
  *
  * @return bool True if the message was sent or False otherwise
  */
  public function sendEmail(string $type, string $subject, string $from,
    array $to, $content, string $contentType): bool;

  /**
  * Sends an email with a user's account activation link.
  *
  * @param string $userEmail The user's email address.
  * @param string $token The account activation token.
  *
  * @return bool True if the email was sent or False otherwise.
  */
  public function activationEmail(string $userEmail, string $token): bool;

  /**
  * Sends an email with a user's password reset link.
  *
  * @param string $userEmail The user's email address.
  * @param string $token The password reset token.
  *
  * @return bool True if the email was sent or False otherwise.
  */
  public function pwResetEmail(string $userEmail, string $token): bool;
}