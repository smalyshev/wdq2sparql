<?php
namespace Sparql;

/**
 * SPARQL syntax representation
 */
interface Syntax {
    const TYPE_DIRECT = "direct";
    const TYPE_STATEMENT = "statement";
    const TYPE_STATEMENT_SIMPLE = "simplevalue";
    const TYPE_QUALIFIER = "qualifier";

	/**
	 * Get SPARQL prefixes for this syntax
	 */
	function getPrefixes();
	/**
	 * Expressing property name expression that states "X has property Y"
	 * @param string $id
	 */
	function propertyName( $id, $type = self::TYPE_DIRECT );
	/**
	 * Expressing item name, e.g. Q123
	 * @param string $id
	 */
	function entityName( $id ) ;
	/**
	 * Expressing the fact that $var is unknown
	 * @param string $var
	 */
	function isUnknown( $var );

	/**
	 * Var is an item (any item)
	 * @param string $var
	 */
	function isItem( $var );
}

