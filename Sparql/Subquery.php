<?php
namespace Sparql;

/**
 * Subquery as item expression
 */
class Subquery extends ItemExpression {

	public function __construct( $var, Expression $sub = null ) {
		parent::__construct($var);
		$this->sub = $sub;
	}

	public function getVarName(Syntax $syntax) {
		return $this->var;
	}

	public function emit(Syntax $syntax, $indent = "") {
		return $this->sub ? $this->sub->emit($syntax, $indent) : "";
	}
}

