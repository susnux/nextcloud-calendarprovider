<?php

namespace OCA\CalendarProviderDemo\Db;

use OCP\AppFramework\Db\Entity;

class Event extends Entity {
	protected $filename;
	protected $content;
	protected $uuid;
	protected $userId;
}
