<?php

namespace OCA\CalendarProviderDemo\Db;

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class EventMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'calendarproviderdemo', Event::class);
	}

	/**
	 * @param string $uuid
	 * @return Entity|Event
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 */
	public function find(string $uuid): Event {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('calendarproviderdemo')
			->where($qb->expr()->eq('uuid', $qb->createNamedParameter($uuid, IQueryBuilder::PARAM_STR)));
		return $this->findEntity($qb);
	}

	/**
	 * @param string $userId
	 * @return array
	 */
	public function findAll(string $userId): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('calendarproviderdemo')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	public function findByName(string $filname): Event {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('calendarproviderdemo')
			->where($qb->expr()->eq('filename', $qb->createNamedParameter($filname, IQueryBuilder::PARAM_STR)));
		return $this->findEntity($qb);
	}
}
