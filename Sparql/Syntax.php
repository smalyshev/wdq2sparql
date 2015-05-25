<?php
namespace Sparql;

/**
 * SPARQL syntax representation
 */
interface Syntax {
	/**
	 * Get SPARQL prefixes for this syntax
	 */
	function getPrefixes();
	/**
	 * Expressing property name expression that states "X has property Y"
	 * @param string $id
	 */
	function propertyName( $id );
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
}

