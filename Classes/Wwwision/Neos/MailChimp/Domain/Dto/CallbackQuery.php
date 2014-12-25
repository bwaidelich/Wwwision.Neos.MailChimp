<?php
namespace Wwwision\Neos\MailChimp\Domain\Dto;

/*                                                                          *
 * This script belongs to the TYPO3 Flow package "Wwwision.Neos.MailChimp". *
 *                                                                          *
 *                                                                          */

use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Persistence\QueryResultInterface;

/**
 * A generic Query that can be used to produce proper QueryResults from computed results (via callbacks)
 * This is useful to provide simple pagination for example
 */
class CallbackQuery implements QueryInterface {

	/**
	 * @var \Closure
	 */
	protected $resultCallback;

	/**
	 * @var \Closure
	 */
	protected $countCallback;

	/**
	 * @var array in the format array('foo' => QueryInterface::ORDER_ASCENDING, 'bar' => QueryInterface::ORDER_DESCENDING)
	 */
	protected $orderings;

	/**
	 * @var integer
	 */
	protected $limit;

	/**
	 * @var integer
	 */
	protected $offset;

	/**
	 * @param \Closure $resultCallback
	 * @param \Closure $countCallback
	 */
	function __construct(\Closure $resultCallback, \Closure $countCallback = NULL) {
		$this->resultCallback = $resultCallback;
		$this->countCallback = $countCallback;
	}

	/**
	 * Returns the type this query cares for.
	 *
	 * @return string
	 */
	public function getType() {
		return NULL;
	}

	/**
	 * Executes the query and returns the result.
	 *
	 * @param boolean $cacheResult If the result cache should be used
	 * @return QueryResultInterface The query result
	 */
	public function execute($cacheResult = FALSE) {
		return new CallbackQueryResult($this);
	}

	/**
	 * @return array
	 */
	public function getResult() {
		return call_user_func_array($this->resultCallback, array($this));
	}

	/**
	 * Returns the query result count.
	 *
	 * @return integer The query result count
	 */
	public function count() {
		if ($this->countCallback !== NULL) {
			return call_user_func_array($this->countCallback, array($this));
		}
		return count($this->getResult());
	}

	/**
	 * Sets the property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => QueryInterface::ORDER_ASCENDING,
	 *  'bar' => QueryInterface::ORDER_DESCENDING
	 * )
	 *
	* @param array $orderings The property names to order by
	 * @return QueryInterface
	 */
	public function setOrderings(array $orderings) {
		$this->orderings = $orderings;
	}

	/**
	 * Gets the property names to order the result by, like this:
	 * array(
	 *  'foo' => QueryInterface::ORDER_ASCENDING,
	 *  'bar' => QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @return array
	 */
	public function getOrderings() {
		return $this->orderings;
	}

	/**
	 * Sets the maximum size of the result set to limit. Returns $this to allow
	 * for chaining (fluid interface).
	 *
	 * @param integer $limit
	 * @return QueryInterface
	 */
	public function setLimit($limit) {
		$this->limit = $limit;
	}

	/**
	 * Returns the maximum size of the result set to limit.
	 *
	 * @return integer
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Sets the start offset of the result set to offset. Returns $this to
	 * allow for chaining (fluid interface).
	 *
	 * @param integer $offset
	 * @return QueryInterface
	 */
	public function setOffset($offset) {
		$this->offset = $offset;
	}

	/**
	 * Returns the start offset of the result set.
	 *
	 * @return integer
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * @param object $constraint Some constraint, depending on the backend
	 * @return QueryInterface
	 */
	public function matching($constraint) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @return mixed the constraint, or null if none
	 */
	public function getConstraint() {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @return object
	 */
	public function logicalAnd($constraint1) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @return object
	 */
	public function logicalOr($constraint1) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @param object $constraint Constraint to negate
	 * @return object
	 */
	public function logicalNot($constraint) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param boolean $caseSensitive Whether the equality test should be done case-sensitive for strings
	 * @return object
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @param string $propertyName The name of the property to compare against
	 * @param string $operand The value to compare with
	 * @param boolean $caseSensitive Whether the matching should be done case-sensitive
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a non-string property
	 */
	public function like($propertyName, $operand, $caseSensitive = TRUE) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @param string $propertyName The name of the multivalued property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 */
	public function contains($propertyName, $operand) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @param string $propertyName The name of the multivalued property to compare against
	 * @return boolean
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 */
	public function isEmpty($propertyName) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with, multivalued
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property
	 */
	public function in($propertyName, $operand) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 */
	public function lessThan($propertyName, $operand) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 */
	public function lessThanOrEqual($propertyName, $operand) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 */
	public function greaterThan($propertyName, $operand) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

	/**
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\Flow\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 */
	public function greaterThanOrEqual($propertyName, $operand) {
		throw new \BadMethodCallException('This method is not implemented in this query implementation.');
	}

}