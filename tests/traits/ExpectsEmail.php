<?php
namespace CDash\Test\Traits;

use Illuminate\Support\Facades\Mail;
use Swift_Events_SendEvent;

trait ExpectsEmail
{
  protected $emailsRecieved = [];

  protected $accounting = 0;
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
    $this->accounting++;
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
        if (is_null($message)) {
          $message = new ExpectsEmailWrapper($this, $recipient);
        }
        $message->addEmail($email);
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

  public function assertAllEmailsAccountedFor()
  {
    $this->assertCount($this->accounting, $this->emailsRecieved);
  }
}

class ExpectsEmailWrapper
{
  /** @var \Swift_Mime_Message[]  $messages */
  private $messages = [];

  /** @var \Swift_Mime_Message $message  */
  private $message;

  /** @var \TestCase $test */
  private $test;

  /** @var string $recipient */
  private $recipient;

  public function __construct($test, $recipient)
  {
    $this->test = $test;
    $this->recipient = $recipient;
  }

  /**
   * @param $subject
   * @return $this
   */
  public function withSubject($subject)
  {
    $found = false;
    foreach ($this->messages as $msg) {
      if ($msg->getSubject() == $subject) {
        $this->message = $msg;
        $found = true;
        break;
      }
    }
    if (!$found) {
      $trimmed_subject = substr($subject, 0, 50);
      $ellipses = strlen($subject) > 50 ? '...' : '.';
      $this->test->fail("No message for {$this->recipient} with subject {$trimmed_subject}{$ellipses}");
    }
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

  public function addEmail($message)
  {
    $this->messages[] = $message;
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
