<?php

namespace App;
use PHPMailer\PHPMailer\PHPMailer;

/**
* Mail
*
* PHP version 7.0
*/

class Mail
{
  /**
  * Send a message
  *
  * @param string $to Recipient
  * @param string $subject Subject
  * @param string $text Text-only content of the message
  * @param string $html HTML content of the message
  *
  * @return mixed
  */
  public static function send($to, $subject, $text, $html)
  {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'host.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'username';
    $mail->Password = 'password';
    $mail->SMTPSecure = 'ssl';
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->Port = 1234;

    $mail->setFrom('no-reply@myname');
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->send();
    $mail->ClearAddresses();
  }
}
