<?php

/**
 * LimitTag class
 *
 * @author  Arnia Software
 * @package /classes/xml/xmlquery/tags/navigation
 * @version 0.1
 */
class LimitTag {
	/**
	 * Value is relate to limit query
	 * @var array
	 */
	var $arguments;
	/**
	 * QueryArgument object
	 * @var QueryArgument
	 */
	var $page;
	/**
	 * QueryArgument object
	 * @var QueryArgument
	 */
	var $page_count;
	/**
	 * QueryArgument object
	 * @var QueryArgument
	 */
	var $list_count;
	/**
	 * QueryArgument object
	 * @var QueryArgument
	 */
	var $offset;
	/**
	 * constructor
	 * @param object $index
	 * @return void
	 */
	function __construct($index) {
		if($index->page && $index->page->attrs && $index->page_count && $index->page_count->attrs) {
			$this->page = new QueryArgument($index->page);
			$this->page_count = new QueryArgument($index->page_count);
			$this->arguments[] = $this->page;
			$this->arguments[] = $this->page_count;
		}
		
		$this->list_count = new QueryArgument($index->list_count);
		$this->arguments[] = $this->list_count;
		
		if(isset($index->offset) && isset($index->offset->attrs)){
			$this->offset = new QueryArgument($index->offset);
			$this->arguments[] = $this->offset;
		}
	}
	
	function toString() {
		if($this->page){
			return sprintf('new Limit(${\'%s_argument\'}, ${\'%s_argument\'}, ${\'%s_argument\'})', $this->list_count->getArgumentName(), $this->page->getArgumentName(), $this->page_count->getArgumentName());
		}
		elseif($this->offset){
			return sprintf('new Limit(${\'%s_argument\'}, NULL, NULL, ${\'%s_argument\'})', $this->list_count->getArgumentName(), $this->offset->getArgumentName());
		}
		else{
			return sprintf('new Limit(${\'%s_argument\'})', $this->list_count->getArgumentName());
		}
	}
	
	function getArguments() {
		return $this->arguments;
	}
}

?>
