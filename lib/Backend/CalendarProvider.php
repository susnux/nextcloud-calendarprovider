<?php

namespace OCA\CalendarProviderDemo\Backend;

use OCA\CalendarProviderDemo\Db\EventMapper;
use OCP\IL10N;
use OCP\Activity\IManager;
use OCP\Calendar\ICalendarProvider;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class CalendarProvider implements ICalendarProvider {
	protected IL10N $l10n;
	protected IManager $manager;
	protected IAppConfig $appConfig;
	protected LoggerInterface $logger;
	protected EventMapper $mapper;
	protected IUserManager $userManager;

	public function __construct(IL10N $l10n, IManager $manager, IAppConfig $appConfig, LoggerInterface $logger, EventMapper $mapper, IUserManager $userManager) {
		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->manager = $manager;
		$this->appConfig = $appConfig;
		$this->mapper = $mapper;
		$this->userManager = $userManager;
	}

	public function getCalendars(string $principalUri, array $calendarUris = []): array {
		$user = explode('/', $principalUri, 3);
		if (count($user) < 3 || $user[0] !== 'principals' || $user[1] !== 'users') {
			$this->logger->warning('Invalid principal uri given', ['uri' => $principalUri]);
			return [];
		}

		$user = $this->userManager->get($user[2]);
		if ($user === null) {
			$this->logger->warning('Unknown user given', ['uri' => $principalUri]);
			return [];
		}

		if ($this->manager->getCurrentUserId() !== $user->getUID()) {
			$this->logger->error('Forbidden access on personal calendar');
			return [];
		}

		return [new Calendar($user, $this->appConfig, $this->logger, $this->mapper, $this->l10n)];
	}
}
