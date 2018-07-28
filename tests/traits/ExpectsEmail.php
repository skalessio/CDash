<?php
/**
 * =========================================================================
 *   Program:   CDash - Cross-Platform Dashboard System
 *   Module:    $Id$
 *   Language:  PHP
 *   Date:      $Date$
 *   Version:   $Revision$
 *
 *   Copyright (c) Kitware, Inc. All rights reserved.
 *   See LICENSE or http://www.cdash.org/licensing/ for details.
 *
 *   This software is distributed WITHOUT ANY WARRANTY; without even
 *   the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 *   PURPOSE. See the above copyright notices for more information.
 * =========================================================================
 */

namespace CDash\Test\Traits;

use Illuminate\Support\Facades\Mail;
use Swift_Events_SendEvent;

/**
 * A class that performs testing on email sent by an application
 *
 * Trait ExpectsEmail
 * @package CDash\Test\Traits
 */
trait ExpectsEmail
{
  /** @var array $emailsRecieved */
  protected $emailsRecieved = [];

  /** @var int $accounting The number of emails tested */
  protected $accounting = 0;

  /**
   * Registers an email listener with SwiftMailer
   */
  public function listenForEmail()
  {
    Mail::getSwiftMailer()->registerPlugin(new EmailSendListener($this));
  }

  /**
   * Adds email sent by SwiftMailer
   *
   * @param \Swift_Mime_Message $email
   */
  public function addEmail(\Swift_Mime_Message $email)
  {
    $this->emailsRecieved[] = $email;
  }

  /**
   * Asserts that the queue contains emails while advances the accounting measure
   *
   * @return $this
   */
  public function assertEmailSent()
  {
    $this->assertNotEmpty($this->emailsRecieved, 'No emails sent');
    $this->accounting++;
    return $this;
  }

  /**
   * Asserts that an email recipient exists given an email
   *
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

  /**
   * Asserts that the email queue contains the given number of emails
   *
   * @param $expected
   */
  public function assertEmailCount($expected)
  {
    $actual = count($this->emailsRecieved);
    $message = "Expected {$expected} email(s), {$actual} email(s) present";
    $this->assertEquals($expected, $actual, $message);
  }

  /**
   * Asserts that a test has been run for every email in the queue.
   */
  public function assertAllEmailsAccountedFor()
  {
    $this->assertCount($this->accounting, $this->emailsRecieved);
  }
}

/**
 * A wrapper (test decorator) for a group of emails sent to a recipient with test cases
 *
 * Class ExpectsEmailWrapper
 * @package CDash\Test\Traits
 */
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
   * Tests that a message with the given subject exists. If the message exists it will be used
   * as the subject under test for the remainder of the lifetime of this class.
   *
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
   * Tests that message body contains the given content.
   *
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

  /**
   * Adds a message to the list of messages sent to an individual recipient
   * @param $message
   */
  public function addEmail($message)
  {
    $this->messages[] = $message;
  }
}

/**
 * Class EmailSendListener
 * @package CDash\Test\Traits
 */
class EmailSendListener implements \Swift_Events_SendListener
{
  /** @var \CDash\Test\Traits\ExpectsEmail $test */
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
