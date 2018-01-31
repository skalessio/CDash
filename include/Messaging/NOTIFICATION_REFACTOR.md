# Messaging Refactor

This document is incomplete and is subject to change.

## Definitions

### Topic

A user subscribes to topics. Examples of topics are test failures, label subscriptions, build fixes, builds from a particular build group, etc. Any criteria that triggers a message shall be known as a topic. The topic class accepts a model as an argument, which is the the subject of the topic, giving the topic class the ability to query the model for criteria. For example the TestFailureTopic accepts a Build model as its subject giving it the ability to query if the build has any test failures. Topics whose subject queries are successful are added to subscriptions. 
 
### Subscription

The subscription is a collection of topics, the subscriber to those topics (the user), and the notification for the subscriber.

### Notification

The notification the message containing the information provided by the topic. This is usually an email, but we are abstracting this concept to support other messaging systems such as SMS and Slack.

### Preferences

The topics a subscriber is subscribed to.

### Message Decorator

A decorator decorates the message with text as described by the topic.
  

## High Level Overview

When a build is submitted the XML handler responsible for creating the build submission is passed to a subscription builder. The subscription builder determines the build's project users and the topics that they are subscribed, then proceeds to check the build submission for criteria matching that of each of the subscribers topics. Once the subscriptions have been created and notification builder, in our first case, an email builder is created, it is passed the subscriptions and an email message for each subscriber is constructed. Those messages are then passed to a transport for delivery.

## Interfaces

A good place to start for making sense of the planned CDash messaging system refactor is with its interfaces. There 
are a number of interfaces in the CDash/Collection and CDash/Messaging packages. Below is a list of some of them and 
their purpose.

Interface...    | Purpose...         | Example of class implementation
----------------|--------------------|--------------------------------------
FactoryInterface | Classes implementing the FactoryInterface add a layer of abstraction to the creation of class instances allowing, for instance, the uncoupling of object creation from the calling method. | SubscriptionFactory, NotificationFactory
NotificationInterface | Insure that implementing classes provide methods for setting and getting address information, message subject and message body. Allows us to abstract notification behavior from the type of notification  | EmailMessage, SlackNotification, SMSMessage
PreferencesInterface | Insure that implementing classes provide the ability read, write, and make runtime changes to project, group, or user preferences (settings). | BitmaskNotificationPreferences, JSONNotificationPreference
TopicInterface | Insure that implementing classes provide the ability to determine if a user has subscribed to any of the details in an incoming CDash submission. | BuildTypeTopic, ExpectedSiteSubmitTopic, LabelTopic
DecoratorInterface | Implements a version of the decorator pattern for textual decoration of messages based on topics. | BuildTypeDecorator, ExpectedSiteSubmitDecorator, LabelTopicDecorator

## Code Examples

Steps to build a collection of notifications:

```php
$project = new Project();  
  
// $actionableBuild is a AbstractHanlder implementing ActionableBuildInterface
// (i.e., a CDash submission)
$project->Id = $actionableBuild->getProjectId(); 
$project->Fill();  
  
// Create the subscriptions
$subscriptionBuilder = new SubscriptionBuilder($actionableBuild, $project);  
  
/** @var SubscriptionCollection $subscriptions */
$subscriptions = $subscriptionBuilder->build();  
  
// Create the notifications
$director = new NotificationDirector();
$emailBuilder = new EmailBuilder(new EmailNotificationFactory(), new NotificationCollection());
$emailBuilder
  ->setSubscriptions($subscriptions)
  ->setProject($project);
  
/** @var NotificationCollection $emails */
$emails = $director->build($emailBuilder);
```

## Subscription Creation
### SubscriptionBuilder
```php
/**
 * @return SubscriptionCollection
 */
public function build()
{
    ...
    foreach ($subscribers as $subscriber) {
        /** @var SubscriberInterface $subscriber */
        if ($subscriber->hasBuildTopics($this->build)) {
            $subscription = $factory->create();
            $subscription
                ->setSubscriber($subscriber)
                ->setTopicCollection($subscriber->getTopics())
                ->setProject($this->project);

            $subscriptions->add($subscription);
        }
    }
    return $subscriptions;
}
```
### Subscriber
```php
/**
 * @param ActionableBuildInterface $actionableBuild
 * @return bool
 */
public function hasBuildTopics(ActionableBuildInterface $actionableBuild)
{
    $hasBuildTopics = false;
    $topics = $this->getTopics();
    /** @var Build $build */
    foreach ($actionableBuild->getActionableBuilds() as $build) {
        /** @var \CDash\Messaging\Topic\Topic|CancelationInterface $topic */
        foreach (TopicFactory::createFrom($this->preferences) as $topic) {

            if (is_a($topic, CancelationInterface::class)) {
                if ($topic->subscribesToBuild($build) === false) {
                    return false;
                }
            } else {
                if ($topic->subscribesToBuild($build)) {
                    $hasBuildTopics = true;
                    $topic->setBuild($build);
                    $topics->add($topic);
                }
            }
        }
    }
    return $hasBuildTopics;
}
```
### TopicFactory
```php
/**
 * @param NotificationPreferences $preferences
 * @return TopicInterface[]
 */
public static function createFrom(NotificationPreferences $preferences)
{
    $topics = [];
    foreach ($preferences->getPropertyNames() as $topic) {
        if ($preferences->notifyOn($topic)) {
            $instance = self::create($topic);
            // Putting our subscription killers at the front of the queue
            // will prevent the unnecessary checking of other topics
            // should the killer not meet the specified criteria (e.g. not the author)
            if (is_a($instance, CancelationInterface::class)) {
                array_unshift($topics, $instance);
            } else {
                $topics[] = $instance;
            }
        }
    }
    return $topics;
```
### Topic (for instance, TestFailureTopic)
```php
/**
 * @param Build $build
 * @return bool
 */
public function subscribesToBuild(Build $build)
{
    $subscribe = $build->GetNumberOfFailedTests() > 0;
    if ($subscribe) {
        $data = $build->GetFailedTests(Subscription::getMaxDisplayItems());
        $base_url = Config::getInstance()->getBaseUrl();
        foreach ($data as &$row) {
            // TODO: url should not be hardcoded this way, consider Route class to obtain endpoints
            $row['url'] = "{$base_url}/testDetails.php?test={$row['id']}&buildid={$build->Id}";
        }
        $this->setTopicData($data);
    }
    return $subscribe;
}
 ```

## Notification Creation
### EmailBuilder
```php
/**
 * @return NotificationCollection
 */
public function createNotifications()
{
    ...
    foreach ($this->subscriptions as $subscription) {
       ...
       $notification = $this->notificationFactory()->create();
       $preamble = DecoratorFactory::create('Preamble');
       $preamble->decorate(['url' => $build->getSummaryUrl()]);
     
       $summary = DecoratorFactory::create('Summary');
       $summary
         ->setDecorator($preamble)
         ->decorateWith(...);
       $body = $summary;
       
       foreach ($subscription->getTopics() as $topic) {
          ...
          
          $body = DecoratorFactory::createFromTopic($topic);
          $body
            ->setDecorator($body)
            ->decorateWith($topic->getData());
       }
       $footer = DecorateFactory::create('Footer');
       $footer
         ->setDecorator($body)
         ->decorateWith($config->getServer());
       
       $notification
         ->setSender($subscription->getSender())
         ->setRecipient($subscription->getRecipient())
         ->setBody($footer)
         ->setSubject($subject);

       $this->notifications->add($notification);
    }
}
```
### Decorator

```php
/**
 * @param array $topic
 * @return Decorator
 */
public function decorateWith(array $topic)
{
    // This prevents users from having to create multi-dimensional arrays where none
    // are needed.
    if (!isset($topic[0])) {
        $topic = [$topic];
    }

    $template = $this->getTemplate();

    $rx = '/{{ (.*?) }}/';
    $body = '';

    foreach ($topic as $row) {
        if (preg_match_all($rx, $template, $match)) {
            $tmpl = $template;
            foreach ($match[1] as $property_name) {
                $property = isset($row[$property_name]) ? $row[$property_name] : '';
                $tmpl = str_replace("{{ {$property_name} }}", $property, $tmpl);
            }
            $body .= $tmpl;
        }
    }

    $this->rows_processed = count($topic);
    $this->body .= $body;
    return $this;
}

/**
 * @return string
 */
public function __toString()
{
    $string = $this->decorator ? "{$this->decorator}" : '';

    if (!empty($this->description)) {
        $string .= "*{$this->description}*\n";
    }

    if (!empty($this->body)) {
        $string .= "{$this->body}\n";
    }

    return $string;
}
```

### TestFailureDecorator

The initialization of a decorator and how its template is set can be determined any number of ways. For now we're simply
hard coding the template directly in the class

```php
class TestFailureDecorator extends Decorator
{
    /**
     * It would probably be faster to format this string using sprintf format, but for both
     * extensiblity and readability sake, keeping it readable for now. The tokens are the
     * same tokens used by the Twig templating system.
     *
     * @var string $template
     */
    private $template = "{{ name }} | {{ details }} | ({{ url }})\n";
    
    protected $description = 'Tests Failing';
    
    protected $subject = 'FAILED (t={{ count }}): {{ project_name }} - {{ build_name }}';
    ...
}
```
