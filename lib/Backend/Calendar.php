<?php

namespace OCA\CalendarProviderDemo\Backend;

use OCA\CalendarProviderDemo\Db\Event;
use OCA\CalendarProviderDemo\Db\EventMapper;
use OCP\Constants;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICreateFromString;
use OCP\IUser;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component;

class Calendar implements ICalendar, ICreateFromString {
	protected IAppConfig $appConfig;
	protected IUser $user;
	protected LoggerInterface $logger;
	protected EventMapper $mapper;

	public function __construct(
		IUser $user,
		IAppConfig $appConfig,
		LoggerInterface $logger,
		EventMapper $mapper
	) {
		$this->appConfig = $appConfig;
		$this->logger = $logger;
		$this->user = $user;
		$this->mapper = $mapper;
	}

	public function getDisplayColor(): ?string {
		return '#ffab00';
	}

	public function getDisplayName(): ?string {
		return 'Demo Calendar';
	}

	public function getKey(): string {
		return $this->user->getUID() . '-demo-calendar';
	}

	public function getPermissions(): int {
		return Constants::PERMISSION_ALL;
	}

	public function getUri(): string {
		return 'demo-calendar';
	}

	public function isDeleted(): bool {
		return false;
	}

	public function createFromString(string $name, string $calendarData): void {
		/** @var \Sabre\VObject\Component\VCalendar */
		$vCal = \Sabre\VObject\Reader::read($calendarData, \Sabre\VObject\Reader::OPTION_FORGIVING | \Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES);
		$event = null;
		try {
			$event = $this->mapper->findByName($name);
		} catch (DoesNotExistException) {
			$event = new Event();
			$event->setFilename($name);
			$event->setUserId($this->user->getUID());
			$event->setUuid((string)$vCal->getBaseComponent()->UID);
			$event = $this->mapper->insert($event);
		}

		$event->setContent($vCal->serialize());
		$this->mapper->update($event);

		$this->logger->debug("creating $name", ["uuid" => $event->getUuid(), "content" => $event->getContent()]);
	}

	/**
	 * @param string $pattern which should match within the $searchProperties
	 * @param array $searchProperties defines the properties within the query pattern should match
	 * @param array $options - optional parameters:
	 * 	['timerange' => ['start' => new DateTime(...), 'end' => new DateTime(...)]]
	 * @param int|null $limit - limit number of search results
	 * @param int|null $offset - offset for paging of search results
	 * @return array an array of events/journals/todos which are arrays of key-value-pairs
	 * @since 13.0.0
	 */
	public function search(string $pattern, array $searchProperties = [], array $options = [], ?int $limit = null, ?int $offset = null): array {
		$result = [];
		$this->logger->debug("Request calendar", ["pattern" => $pattern, "props" => $searchProperties]);
		try {
			if (empty($searchProperties) || $pattern === "") {
				$result = $this->mapper->findAll($this->user->getUID());
			} else {
				if (in_array('X-FILENAME', $searchProperties)) {
					$result[] = $this->mapper->findByName($pattern);
				}
				if (in_array('UID', $searchProperties)) {
					$result[] = $this->mapper->find($pattern);
				}
			}
		} catch (DoesNotExistException) {
			return [];
		}

		$allIDs = [];
		// flatten array
		$result = array_merge(
			// convert Event to VObject\Component
			...array_map(function (Event $event) {
				/** @var \Sabre\VObject\Component\VCalendar */
				$obj = \Sabre\VObject\Reader::read($event->getContent());
				// Drop VTIMEZONE components
				return array_filter($obj->getComponents(), function(Component $component) {
					return strtoupper($component->name) !== 'VTIMEZONE';
				});
			},
			// only one instance per Event
			array_filter($result, function(Event $event) use(&$allIDs) {
				$r = !in_array($event->getUuid(), $allIDs);
				$allIDs[] = $event->getUuid();
				return $r;
			}))
		);

		$this->logger->debug("Demo calendar, found " . count($result) . " components");
		return $result;
		/*

		return [
		[
			"VEVENT" => [
				"DTSTAMP" => new \DateTime(),
				"UID" => "1673649305660-24360@cloud.nextcloud.com",
				"DTSTART" => new \DateTime("2023-01-31 12:00:00", new \DateTimeZone("Europe/Berlin")),
				"DTEND" => new \DateTime("2023-01-31 19:00:00", new \DateTimeZone("Europe/Berlin")),
				"SUMMARY" => "Title",
				"DESCRIPTION" => "Description"
			]
		]
		];*/
	}
}
