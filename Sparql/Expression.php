<?php
namespace Sparql;
/**
 * Generic sparql expression class
 */
abstract class Expression {
    /**
	 * Produce output for this expression
	 * @param Syntax $syntax Syntax engine to use
	 * @return string
	 */
	abstract function emit(Syntax $syntax, $indent = "");

	protected static $counters = array();

	public function counterVar($name) {
		if(!isset(self::$counters[$name])) {
			self::$counters[$name] = $no = 0;
		} else {
			$no = ++self::$counters[$name];
		}
		return "?{$name}{$no}";
	}

	public static function resetCounters() {
	    self::$counters = [];
    }

	public function getLastVar($name) {
		$no = self::$counters[$name];
		return "?{$name}{$no}";
	}
}

