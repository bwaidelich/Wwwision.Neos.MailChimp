<?php
namespace Wwwision\Neos\MailChimp\Domain\Dto;

/*                                                                          *
 * This script belongs to the TYPO3 Flow package "Wwwision.Neos.MailChimp". *
 *                                                                          *
 *                                                                          */

use TYPO3\Flow\Persistence\QueryResultInterface;

/**
 * A QueryResult for the CallbackQuery
 */
class CallbackQueryResult implements QueryResultInterface {

	/**
	 * @var CallbackQuery
	 */
	protected $query;

	/**
	 * @var array
	 */
	protected $results;

	/**
	 * @param CallbackQuery $query
	 */
	public function __construct(CallbackQuery $query) {
		$this->query = $query;
	}

	/**
	 * Loads the objects this QueryResult is supposed to hold
	 *
	 * @return void
	 */
	protected function initialize() {
		if ($this->results === NULL) {
			$this->results = $this->query->getResult();
			if($this->query->getLimit()) {
				$this->results = array_slice(
					$this->results,
					(int) $this->query->getOffset(),
					$this->query->getLimit()
				);
			}
		}
	}

	/**
	 * Returns a clone of the query object
	 *
	 * @return CallbackQuery
	 */
	public function getQuery() {
		return clone $this->query;
	}

	/**
	 * Returns the first object in the result set
	 *
	 * @return object
	 */
	public function getFirst() {
		if (is_array($this->results)) {
			$results = &$this->results;
		} else {
			$query = clone $this->query;
			$query->setLimit(1);
			$results = $query->getResult();
		}

		return (isset($results[0])) ? $results[0] : NULL;
	}

	/**
	 * Returns the number of objects in the result
	 *
	 * @return integer The number of matching objects
	 */
	public function count() {
		return $this->query->count();
	}

	/**
	 * Returns an array with the objects in the result set
	 *
	 * @return array
	 */
	public function toArray() {
		$this->initialize();
		return $this->results;
	}

	/**
	 * This method is needed to implement the \ArrayAccess interface,
	 * but it isn't very useful as the offset has to be an integer
	 *
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		$this->initialize();
		return isset($this->results[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		$this->initialize();
		return isset($this->results[$offset]) ? $this->results[$offset] : NULL;
	}

	/**
	 * This method has no effect on the persisted objects but only on the result set
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->initialize();
		$this->results[$offset] = $value;
	}

	/**
	 * This method has no effect on the persisted objects but only on the result set
	 *
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		$this->initialize();
		unset($this->results[$offset]);
	}

	/**
	 * @return mixed
	 */
	public function current() {
		$this->initialize();
		return current($this->results);
	}

	/**
	 * @return mixed
	 */
	public function key() {
		$this->initialize();
		return key($this->results);
	}

	/**
	 * @return void
	 */
	public function next() {
		$this->initialize();
		next($this->results);
	}

	/**
	 * @return void
	 */
	public function rewind() {
		$this->initialize();
		reset($this->results);
	}

	/**
	 * @return boolean
	 */
	public function valid() {
		$this->initialize();
		return current($this->results) !== FALSE;
	}
}
