<?php
namespace Sparql\Syntax;

use Sparql\Syntax;

class Wikidata implements Syntax {

	public function getPrefixes() {
		return array("wdt" => "http://www.wikidata.org/prop/direct/",
				"entity" =>  "http://www.wikidata.org/entity/");
	}

	public function propertyName( $id ) {
		return "wdt:P{$id}";
	}

	public function entityName( $id ) {
		return "entity:Q{$id}";
	}

	public function isUnknown( $var ) {
		return "isBlank({$var})";
	}

	public function isItem( $var ) {
		return "{$var} <http://schema.org/version> _:v .";
	}
}

