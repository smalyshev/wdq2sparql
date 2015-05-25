<?php
namespace Sparql;
/**
 * Generic sparql expression class
 */
abstract class Expression {
	/**
	 * Produce output for this expression
	 * @param SparqlSyntax $syntax Syntax engine to use
	 */
	abstract function emit(Syntax $syntax);
}

