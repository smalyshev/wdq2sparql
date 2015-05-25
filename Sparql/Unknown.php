<?php
namespace Sparql;

/**
 * Item expressing "unknown"
 */
class Unknown extends ItemExpression {

	public function emit(Syntax $syntax, $indent = "") {
		return "{$indent}FILTER ({$syntax->isUnknown($this->var)})\n";
	}

	public function getVarName(Syntax $syntax) {
		return $this->var;
	}
}