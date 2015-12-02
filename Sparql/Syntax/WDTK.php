<?php
namespace Sparql\Syntax;

use Sparql\Syntax;

class WDTK implements Syntax {

	public function getPrefixes() {
		return array("" => "http://www.wikidata.org/entity/" );
	}

	public function propertyName( $id ) {
		return ":P{$id}s/:P{$id}v";
	}

	public function entityName( $id ) {
		return ":Q{$id}";
	}

	public function isUnknown( $var ) {
		return "{$var} = {$this->entityName('4294967294')}";
	}

	public function isItem( $var ) {
		return "{$var} a <http://www.wikidata.org/ontology#Item> .";
	}
}
