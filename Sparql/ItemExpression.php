<?php
namespace Sparql;

/**
 * Expression that appears in Item place in WDQ.
 * The expression should produce some variable as getVarName() and
 * expression involving this variable on emit(). Used by claim, noclaim, etc.
 */
abstract class ItemExpression extends Expression {
	/**
	 * Variable name
	 * @var string
	 */
	protected $var;
	public function __construct( $name ) {
		$this->var = $name;
	}
	abstract function getVarName(Syntax $syntax);
}

