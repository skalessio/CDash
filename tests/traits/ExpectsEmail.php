<?php
namespace CDash\Test\Traits;

use Illuminate\Support\Facades\Mail;
use Swift_Events_SendEvent;

trait ExpectsEmail
{
  protected $emailsRecieved = [];

  public function listenForEmail()
  {
    Mail::getSwiftMailer()->registerPlugin(new EmailSendListener($this));
  }

  public function addEmail(\Swift_Mime_Message $email)
  {
    $this->emailsRecieved[] = $email;
  }

  /**
   * @return $this
   */
  public function assertEmailSent()
  {
    $this->assertNotEmpty($this->emailsRecieved, 'No emails sent');
    return $this;
  }

  /**
   * @param $recipient
   * @return ExpectsEmailWrapper
   */
  public function to($recipient)
  {
    $message = null;
    /** @var \Swift_Mime_Message $email */
    foreach ($this->emailsRecieved as $email) {
      if (array_key_exists($recipient, $email->getTo())) {
        $message = new ExpectsEmailWrapper($this, $email);
        break;
      }
    }
    $this->assertNotNull($message, "No email sent to {$recipient}");
    return $message;
  }

  public function expectsMailToHaveInBody($text, $message = null)
  {
    $message = $message ? $message : end($this->emailsRecieved);
    $this->assertContains($message->body, $text);
  }

  public function assertEmailCount($expected)
  {
    $actual = count($this->emailsRecieved);
    $message = "Expected {$expected} email(s), {$actual} email(s) present";
    $this->assertEquals($expected, $actual, $message);
  }
}

class ExpectsEmailWrapper
{
  /** @var \Swift_Mime_Message */
  private $message;

  /** @var \TestCase $test */
  private $test;

  public function __construct($test, $message)
  {
    $this->test = $test;
    $this->message = $message;
  }

  /**
   * @param $subject
   * @return $this
   */
  public function withSubject($subject)
  {
    $this->test->assertEquals($this->message->getSubject(), $subject);
    return $this;
  }

  /**
   * @param $contains
   * @return $this
   */
  public function contains($contains)
  {
    if (!is_array($contains)) {
      $contains = [$contains];
    }
    $body = $this->message->getBody();
    foreach ($contains as $expected) {
      $this->test->assertContains($expected, $body);
    }

    return $this;
  }
}

class EmailSendListener implements \Swift_Events_SendListener
{
  /** @var \CDash\EmailProjectTest $test */
  private $test;

  public function __construct(\TestCase $test)
  {
    $this->test = $test;
  }

  /**
   * Invoked immediately before the Message is sent.
   *
   * @param Swift_Events_SendEvent $evt
   */
  public function beforeSendPerformed(Swift_Events_SendEvent $evt)
  {
    $this->test->addEmail($evt->getMessage());
  }

  /**
   * Invoked immediately after the Message is sent.
   *
   * @param Swift_Events_SendEvent $evt
   */
  public function sendPerformed(Swift_Events_SendEvent $evt)
  {
  }
}
