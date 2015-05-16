<?php
interface SparqlSyntax {
	function getPrefixes();
	function propertyName( $id );
	function entityName( $id ) ;
}

class WDTKSyntax implements SparqlSyntax {
	public function getPrefixes() {
		return array("" => "http://www.wikidata.org/entity/" );
	}
	public function propertyName( $id ) {
		return ":P{$id}s/:P{$id}v";
	}
	public function entityName( $id ) {
		return ":Q{$id}";
	}
}

class WikidataSyntax implements SparqlSyntax {
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
}
