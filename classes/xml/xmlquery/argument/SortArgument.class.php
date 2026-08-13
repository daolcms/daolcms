<?php

/**
 * SortArgument class
 * @author  NAVER (developers@xpressengine.com)
 * @package /classes/xml/xmlquery/argument
 * @version 0.1
 */
class SortArgument extends Argument {

	function getValue() {
		return $this->getUnescapedValue();
	}

	function ensureValidColumnName($default_value = null) {
		if(!isset($this->value) || $this->value === '') {
			return;
		}

		if($this->isValidColumnName($this->value)) {
			return;
		}

		if(func_num_args() > 0) {
			$this->value = $default_value;
			$this->uses_default_value = true;
			$this->isValid = true;
			$this->errorMessage = null;
			return;
		}

		global $lang;
		$key = $this->name;
		$this->isValid = false;
		$this->errorMessage = new BaseObject(-1, sprintf($lang->filter->invalid, $lang->{$key} ? $lang->{$key} : $key));
	}

	function isValidColumnName($column_name) {
		if(!is_string($column_name)) {
			return false;
		}

		return preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)?$/', $column_name) === 1;
	}

}

?>
