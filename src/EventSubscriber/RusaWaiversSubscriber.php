<?php

namespace Drupal\rusa_waivers\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class RusaWaiversSubscriber.
 */
class RusaWaiversSubscriber implements EventSubscriberInterface {

	protected $logger;

	/**
	 * Constructs a new RusaWaiversSubscriber object.
	 */
	public function __construct(LoggerChannelFactory $logger_factory) {
		$this->logger = $logger_factory->get('rusa_waivers');
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents() {
		$events['SmartwaiverEvent::NEW_WAIVER'] = ['new_waiver'];

		return $events;
	}

	/**
	 * This method is called when the SmartwaiverEvent::NEW_WAIVER is dispatched.
	 *
	 * @param \Symfony\Component\EventDispatcher\Event $event
	 *   The dispatched event.
	 */
	public function new_waiver(Event $event) {
		\Drupal::messenger()->addMessage('Event SmartwaiverEvent::NEW_WAIVER thrown by Subscriber in module rusa_waivers.', 'status', TRUE);
		$this->logger->info('We got here', []);
        dpm($event);
	}

}
