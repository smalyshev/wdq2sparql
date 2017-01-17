<?php
namespace Sparql\Syntax;

use Sparql\Syntax;

class Wikidata implements Syntax {

	public function getPrefixes() {
		return array();
//		return array("wdt" => "http://www.wikidata.org/prop/direct/",
//				"wd" =>  "http://www.wikidata.org/entity/");
	}

	public function propertyName( $id, $type = self::TYPE_DIRECT ) {
		switch($type) {
            case self::TYPE_DIRECT:
                return "wdt:P{$id}";
            case self::TYPE_STATEMENT:
                return "p:P{$id}";
            case self::TYPE_QUALIFIER:
                return "pq:P{$id}";
            case self::TYPE_STATEMENT_SIMPLE:
                return "ps:P{$id}";
        }
        return "";
	}

	public function entityName( $id ) {
		return "wd:Q{$id}";
	}

	public function isUnknown( $var ) {
		return "isBlank({$var})";
	}

	public function isItem( $var ) {
		return "{$var} <http://schema.org/version> _:v .";
	}

    /**
     * Expressing predicate linking item to statement
     * @param string $id
     * @return string
     */
    function statementName($id)
    {
        return "p:P{$id}";
    }
}

