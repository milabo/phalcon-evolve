<?php

namespace Phalcon\Evolve\View;

use Phalcon\Evolve\CustomGinq;
use Phalcon\Http\Client\Exception;
use Phalcon\Paginator\AdapterInterface;

class GinqPaginator implements AdapterInterface
{
	/** @type array */
	protected $config;

	/** @type int */
	protected $page;

	/** @type int */
	protected $limitRows;

	public function __construct($config)
	{
		$this->config = $config;
		if (isset($config['limit'])) {
			$this->limitRows = $config['limit'];
		}
		if (isset($config['page'])) {
			$this->page = $config['page'];
		}
	}

	public function setCurrentPage($page)
	{
		$this->page = $page;
		return $this;
	}

	public function getPaginate()
	{
		$limit = intval($this->limitRows);
		$config = $this->config;
		/** @var Ginq $items */
		$items = $config['data'];
		$pageNumber = intval($this->page);

		if (!($items instanceof CustomGinq
			or (class_exists("Ginq\\Ginq") and $items instanceof \Ginq\Ginq))) {
			throw new Exception("Invalid data for paginator");
		}

		if ($pageNumber <= 0) {
			$pageNumber = 1;
		}

		if ($limit <= 0) {
			throw new Exception("The limit number is zero or less");
		}

		$n = $items->count();
		$offset = $limit * ($pageNumber - 1);
		$totalPages = (int)ceil($n / $limit);

		if ($offset > 0) $items = $items->drop($offset);
		$items = $items->take($limit);

		$next = $pageNumber < $totalPages
			? $pageNumber + 1
			: $totalPages;

		$before = $pageNumber > 1
			? $pageNumber - 1
			: 1;

		return (object)[
			'items' => $items->toList(),
			'first' => 1,
			'before' => $before,
			'current' => $pageNumber,
			'last' => $totalPages,
			'next' => $next,
			'total_pages' => $totalPages,
			'total_items' => $n,
			'limit' => $limit,
		];
	}
}