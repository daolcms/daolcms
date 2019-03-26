<?php

/**
 * A class to handle extra variables used in posts, member and others
 *
 * @author  NAVER (developers@xpressengine.com)
 * @Adaptor DAOL Project (developer@daolcms.org)
 **/
class ExtraVar {

	/**
	 * sequence of module
	 * @var int
	 */
	var $module_srl = null;

	/**
	 * Current module's Set of ExtraItem
	 * @var ExtraItem[]
	 */
	var $keys = null;

	/**
	 * Get instance of ExtraVar (singleton)
	 *
	 * @param int $module_srl Sequence of module
	 * @return ExtraVar
	 **/
	function &getInstance($module_srl) {
		return new ExtraVar($module_srl);
	}

	/**
	 * Constructor
	 *
	 * @param int $module_srl Sequence of module
	 * @return void
	 **/
	function __construct($module_srl) {
		$this->module_srl = $module_srl;
	}

	/**
	 * Register a key of extra variable
	 *
	 * @param object[] $extra_keys Array of extra variable. A value of array is object that contains module_srl, idx,
	 *                             name, default, desc, is_required, search, value, eid.
	 * @return void
	 **/
	function setExtraVarKeys($extra_keys) {
		if(!is_array($extra_keys) || !count($extra_keys)) return;
		foreach($extra_keys as $key => $val) {
			$obj = null;
			$obj = new ExtraItem($val->module_srl, $val->idx, $val->name, $val->type, $val->default, $val->desc, $val->is_required, $val->search, $val->value, $val->eid);
			$this->keys[$val->idx] = $obj;
		}
	}

	/**
	 * Returns an array of ExtraItem
	 *
	 * @return ExtraItem[]
	 **/
	function getExtraVars() {
		return $this->keys;
	}
}

/**
 * Each value of the extra vars
 *
 * @author NAVER (developers@xpressengine.com)
 **/
class ExtraItem {
	/**
	 * Sequence of module
	 * @var int
	 */
	var $module_srl = 0;

	/**
	 * Index of extra variable
	 * @var int
	 */
	var $idx = 0;

	/**
	 * Name of extra variable
	 * @var string
	 */
	var $name = 0;

	/**
	 * Type of extra variable
	 * @var string text, homepage, email_address, tel, textarea, checkbox, date, select, radio, kr_zip
	 */
	var $type = 'text';

	/**
	 * Default values
	 * @var string[]
	 */
	var $default = null;

	/**
	 * Description
	 * @var string
	 */
	var $desc = '';

	/**
	 * Whether required or not requred this extra variable
	 * @var string Y, N
	 */
	var $is_required = 'N';

	/**
	 * Whether can or can not search this extra variable
	 * @var string Y, N
	 */
	var $search = 'N';

	/**
	 * Value
	 * @var string
	 */
	var $value = null;

	/**
	 * Unique id of extra variable in module
	 * @var string
	 */
	var $eid = '';

	/**
	 * Constructor
	 *
	 * @param int      $module_srl  Sequence of module
	 * @param int      $idx         Index of extra variable
	 * @param string   $type        Type of extra variable. text, homepage, email_address, tel, textarea, checkbox,
	 *                              date, sleect, radio, kr_zip
	 * @param string[] $default     Default values
	 * @param string   $desc        Description
	 * @param string   $is_required Whether required or not requred this extra variable. Y, N
	 * @param string   $search      Whether can or can not search this extra variable
	 * @param string   $value       Value
	 * @param string   $eid         Unique id of extra variable in module
	 * @return void
	 **/
	function __construct($module_srl, $idx, $name, $type = 'text', $default = null, $desc = '', $is_required = 'N', $search = 'N', $value = null, $eid = '') {
		if(!$idx) return;
		$this->module_srl = $module_srl;
		$this->idx = $idx;
		$this->name = $name;
		$this->type = $type;
		$this->default = $default;
		$this->desc = $desc;
		$this->is_required = $is_required;
		$this->search = $search;
		$this->value = $value;
		$this->eid = $eid;
	}

	/**
	 * Sets Value
	 *
	 * @param string $value The value to set
	 * @return void
	 **/
	function setValue($value) {
		$this->value = $value;
	}

	/**
	 * Returns a given value converted based on its type
	 *
	 * @param string $type  Type of variable
	 * @param string $value Value
	 * @return string Returns a converted value
	 **/
	function _getTypeValue($type, $value) {
		$value = trim($value);
		if(!isset($value)) return;
		switch($type) {
			case 'homepage' :
				if($value && !preg_match('/^([a-z]+):\/\//i', $value)) {
					$value = 'http://' . $value;
				}

				return escape($value, false);
			case 'tel' :
				if(is_array($value)) {
					$values = $value;
				} elseif(strpos($value, '|@|') !== false) {
					$values = explode('|@|', $value);
				} elseif(strpos($value, ',') !== false) {
					$values = explode(',', $value);
				}

				$values = array_values($values);
				for($i = 0, $c = count($values); $i < $c; $i++) {
					$values[$i] = trim(escape($values[$i], false));
				}

				return $values;
			case 'checkbox' :
			case 'radio' :
			case 'select' :
				if(is_array($value)) {
					$values = $value;
				} elseif(strpos($value, '|@|') !== false) {
					$values = explode('|@|', $value);
				} elseif(strpos($value, ',') !== false) {
					$values = explode(',', $value);
				} else {
					$values = array($value);
				}

				for($i = 0; $i < count($values); $i++) {
					$values[$i] = trim(escape($values[$i], false));
				}

				return $values;
			case 'kr_zip' :
				if(is_array($value)) {
					$values = $value;
				} elseif(strpos($value, '|@|') !== false) {
					$values = explode('|@|', $value);
				} else {
					$values = array($value);
				}

				$values = array_values($values);
				for($i = 0, $c = count($values); $i < $c; $i++) {
					$values[$i] = trim(escape($values[$i], false));
				}

				return $values;
			//case 'date' :
			//case 'email_address' :
			//case 'text' :
			//case 'textarea' :
			default:
				return escape($value, false);
				break;
		}
	}

	/**
	 * Returns a value for HTML
	 *
	 * @return string Returns a value expressed in HTML.
	 **/
	function getValue() {
		return $this->_getTypeValue($this->type, $this->value);
	}

	/**
	 * Returns a value for HTML
	 *
	 * @return string Returns a value expressed in HTML.
	 **/
	function getValueHTML() {
		$value = $this->_getTypeValue($this->type, $this->value);
		switch($this->type) {
			case 'homepage' :
				return ($value) ? (sprintf('<a href="%s" target="_blank">%s</a>', escape($value, false), strlen($value) > 60 ? substr($value, 0, 40) . '...' . substr($value, -10) : $value)) : "";
			case 'email_address' :
				return ($value) ? sprintf('<a href="mailto:%s">%s</a>', escape($value, false), $value) : "";
				break;
			case 'tel' :
				return sprintf('%s-%s-%s', $value[0], $value[1], $value[2]);
				break;
			case 'textarea' :
				return nl2br($value);
				break;
			case 'checkbox' :
				if(is_array($value)) return implode(', ', $value);
				else return $value;
				break;
			case 'date' :
				return zdate($value, "Y-m-d");
				break;
			case 'select' :
			case 'radio' :
				if(is_array($value)) return implode(', ', $value);
				else return $value;
				break;
			case 'kr_zip' :
				if(is_array($value)) return implode(' ', $value);
				else return $value;
				break;
			// case 'text' :
			default :
				return $value;
		}
	}

	/**
	 * Returns a form based on its type
	 *
	 * @return string Returns a form html.
	 **/
	function getFormHTML() {
		static $id_num = 1000;

		$type = $this->type;
		$name = $this->name;
		$value = $this->_getTypeValue($this->type, $this->value);
		$default = $this->_getTypeValue($this->type, $this->default);
		$column_name = 'extra_vars' . $this->idx;
		$tmp_id = $column_name . '-' . $id_num++;

		$buff = '';
		switch($type) {
			// Homepage
			case 'homepage' :
				$buff .= '<input type="text" name="' . $column_name . '" value="' . $value . '" class="homepage" />';
				break;
			// Email Address
			case 'email_address' :
				$buff .= '<input type="text" name="' . $column_name . '" value="' . $value . '" class="email_address" />';
				break;
			// Phone Number
			case 'tel' :
				$buff .=
					'<input type="text" name="' . $column_name . '[]" value="' . $value[0] . '" size="4" maxlength="4" class="tel" />' .
					'<input type="text" name="' . $column_name . '[]" value="' . $value[1] . '" size="4" maxlength="4" class="tel" />' .
					'<input type="text" name="' . $column_name . '[]" value="' . $value[2] . '" size="4" maxlength="4" class="tel" />';
				break;

			// textarea
			case 'textarea' :
				$buff .= '<textarea name="' . $column_name . '" rows="8" cols="42">' . $value . '</textarea>';
				break;
			// multiple choice
			case 'checkbox' :
				$buff .= '<ul>';
				foreach($default as $v) {
					if($value && in_array(trim($v), $value)) $checked = ' checked="checked"';
					else $checked = '';

					// Temporary ID for labeling
					$tmp_id = $column_name . '-' . $id_num++;

					$buff .= '<li><input type="checkbox" name="' . $column_name . '[]" id="' . $tmp_id . '" value="' . htmlspecialchars($v) . '" ' . $checked . ' /><label for="' . $tmp_id . '">' . $v . '</label></li>';
				}
				$buff .= '</ul>';
				break;
			// single choice
			case 'select' :
				$buff .= '<select name="' . $column_name . '" class="select">';
				foreach($default as $v) {
					if($value && in_array($v, $value)) $selected = ' selected="selected"';
					else $selected = '';
					$buff .= '<option value="' . $v . '" ' . $selected . '>' . $v . '</option>';
				}
				$buff .= '</select>';
				break;

			// radio
			case 'radio' :
				$buff .= '<ul>';
				foreach($default as $v) {
					if($value && in_array($v, $value)) $checked = ' checked="checked"';
					else $checked = '';

					// Temporary ID for labeling
					$tmp_id = $column_name . '-' . $id_num++;

					$buff .= '<li><input type="radio" name="' . $column_name . '" id="' . $tmp_id . '" ' . $checked . ' value="' . $v . '"  class="radio" /><label for="' . $tmp_id . '">' . $v . '</label></li>';
				}
				$buff .= '</ul>';
				break;
			// date
			case 'date' :
				// datepicker javascript plugin load
				Context::loadJavascriptPlugin('ui.datepicker');

				$buff .=
					'<input type="hidden" name="' . $column_name . '" value="' . $value . '" />' .
					'<input type="text" id="date_' . $column_name . '" value="' . zdate($value, 'Y-m-d') . '" class="date" /> <input type="button" value="' . Context::getLang('cmd_delete') . '" id="dateRemover_' . $column_name . '" />' . "\n" .
					'<script type="text/javascript">' . "\n" .
					'(function($){' . "\n" .
					'    $(function(){' . "\n" .
					'        var option = { dateFormat: "yy-mm-dd", changeMonth:true, changeYear:true, gotoCurrent: false,yearRange:\'-100:+10\', onSelect:function(){' . "\n" .
					'            $(this).prev(\'input[type="hidden"]\').val(this.value.replace(/-/g,""))}' . "\n" .
					'        };' . "\n" .
					'        $.extend(option,$.datepicker.regional[\'' . Context::getLangType() . '\']);' . "\n" .
					'        $("#date_' . $column_name . '").datepicker(option);' . "\n" .
					'        $("#date_' . $column_name . '").datepicker("option", "dateFormat", "yy-mm-dd");' . "\n" .
					'		$("#dateRemover_' . $column_name . '").click(function(){' . "\n" .
					'			$(this).siblings("input").val("");' . "\n" .
					'			return false;' . "\n" .
					'		})' . "\n" .
					'    });' . "\n" .
					'})(jQuery);' . "\n" .
					'</script>';
				break;
			// address
			case "kr_zip" :
				$krzipModel = &getModel('krzip');
				$buff .= $krzipModel->getKrzipCodeSearchHtml($column_name, $value);
				break;
			// General text
			default :
				$buff .= ' <input type="text" name="' . $column_name . '" value="' . ($value ? $value : $default) . '" class="text" />';
				break;
		}
		if($this->desc) {
			$oModuleController = getController('module');
			$oModuleController->replaceDefinedLangCode($this->desc);
			$buff .= '<p>' . htmlspecialchars($this->desc, ENT_COMPAT | ENT_HTML401, 'UTF-8', false) . '</p>';
		}
		return $buff;
	}
}
