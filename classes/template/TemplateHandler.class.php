<?php

/**
 * @class   TemplateHandler
 * @author  NAVER (developers@xpressengine.com)
 * @Adaptor DAOL Project (developer@daolcms.org)
 * template compiler
 * @version 0.1
 * @remarks It compiles template file by using regular expression into php
 *          code, and XE caches compiled code for further uses
 **/
class TemplateHandler {

	private $compiled_path = './files/cache/template_compiled/'; ///< path of compiled caches files
	private $path = NULL; ///< target directory
	private $filename = NULL; ///< target filename
	private $file = NULL; ///< target file (fullpath)
	private $xe_path = NULL;  ///< XpressEngine base path
	private $web_path = NULL; ///< tpl file web path
	private $compiled_file = NULL; ///< tpl file web path
	private $config = NULL;
	private $skipTags = NULL;
	private $handler_mtime = 0;
	private $autoescape = false;

	/**
	 * constructor
	 * @return void
	 **/
	public function __construct(){
		$this->xe_path = rtrim(preg_replace('/([^\.^\/]+)\.php$/i', '', $_SERVER['SCRIPT_NAME']), '/');
		$this->compiled_path = _DAOL_PATH_ . $this->compiled_path;
		$this->config = new stdClass();

		$this->ignoreEscape = array(
			'functions' => function($m){
				$list = array(
					'htmlspecialchars',
					'nl2br',
				);
				return preg_match('/^(' . implode('|', $list) . ')\(/', $m[1]);
			},
			'lang' => function($m){
				// 다국어
				return preg_match('/^\$lang\-\>/', trim($m[1]));
			}
		);

		$this->dbinfo = Context::getDBInfo();
	}

	/**
	 * returns TemplateHandler's singleton object
	 * @return TemplateHandler instance
	 **/
	function &getInstance() {
		static $oTemplate = null;

		if(__DEBUG__ == 3) {
			if(!isset($GLOBALS['__TemplateHandlerCalled__'])) $GLOBALS['__TemplateHandlerCalled__'] = 1;
			else $GLOBALS['__TemplateHandlerCalled__']++;
		}

		if(!$oTemplate) $oTemplate = new TemplateHandler();

		return $oTemplate;
	}

	/**
	 * set variables for template compile
	 * @param string $tpl_path
	 * @param string $tpl_filename
	 * @param string $tpl_file
	 * @return void
	 **/
	function init($tpl_path, $tpl_filename, $tpl_file = '') {
		// verify arguments
		$tpl_path = trim(preg_replace('@^' . preg_quote(_DAOL_PATH_, '@') . '|\./@', '', str_replace('\\', '/', $tpl_path)), '/') . '/';
		$tpl_path = preg_replace('/[\{\}\(\)\[\]<>\$\'"]/', '', $tpl_path);
		
		if($tpl_path === '/' || !is_dir($tpl_path)) {
			return;
		}
		if(!file_exists($tpl_path . $tpl_filename) && file_exists($tpl_path . $tpl_filename . '.html')) {
			$tpl_filename .= '.html';
		}

		// create tpl_file variable
		if(!$tpl_file) $tpl_file = $tpl_path . $tpl_filename;

		// set template file infos.
		$this->path = $tpl_path;
		$this->filename = $tpl_filename;
		$this->file = $tpl_file;

		$this->web_path = $this->xe_path . '/' . ltrim(preg_replace('@^' . preg_quote(_DAOL_PATH_, '@') . '|\./@', '', $this->path), '/');

		// get compiled file name
		$hash = md5($this->file . __DAOL_VERSION__);
		$this->compiled_file = "{$this->compiled_path}{$hash}.compiled.php";

		$this->autoescape = $this->isAutoescape();

		// compare various file's modified time for check changed
		$this->handler_mtime = filemtime(__FILE__);

		$skip = array('');
	}

	/**
	 * compiles specified tpl file and execution result in Context into resultant content
	 * @param string $tpl_path     path of the directory containing target template file
	 * @param string $tpl_filename target template file's name
	 * @param string $tpl_file     if specified use it as template file's full path
	 * @return string Returns compiled result in case of success, NULL otherwise
	 */
	function compile($tpl_path, $tpl_filename, $tpl_file = '') {
		global $__templatehandler_root_tpl;

		$buff = '';

		// store the starting time for debug information
		if(__DEBUG__ == 3) $start = getMicroTime();

		// initiation
		$this->init($tpl_path, $tpl_filename, $tpl_file);

		// if target file does not exist exit
		if(!$this->file || !file_exists($this->file)) return "Err : '{$this->file}' template file does not exists.";

		// for backward compatibility
		if(is_null($__templatehandler_root_tpl)) {
			$__templatehandler_root_tpl = $this->file;
		}

		$source_template_mtime = filemtime($this->file);
		$latest_mtime = $source_template_mtime > $this->handler_mtime ? $source_template_mtime : $this->handler_mtime;

		// cache control
		$oCacheHandler = &CacheHandler::getInstance('template');

		// get cached buff
		if($oCacheHandler->isSupport()) {
			$cache_key = 'template:' . $this->file;
			$buff = $oCacheHandler->get($cache_key, $latest_mtime);
		} else {
			if(is_readable($this->compiled_file) && filemtime($this->compiled_file) > $latest_mtime && filesize($this->compiled_file)) {
				$buff = 'file://' . $this->compiled_file;
			}
		}

		if(!$buff) {
			$buff = $this->parse();
			if($oCacheHandler->isSupport()) $oCacheHandler->put($cache_key, $buff);
			else FileHandler::writeFile($this->compiled_file, $buff);
		}

		$output = $this->_fetch($buff);

		if($__templatehandler_root_tpl == $this->file) {
			$__templatehandler_root_tpl = null;
		}

		// store the ending time for debug information
		if(__DEBUG__ == 3) $GLOBALS['__template_elapsed__'] += getMicroTime() - $start;

		return $output;
	}

	/**
	 * compile specified file and immediately return
	 * @param string $tpl_path     path of the directory containing target template file
	 * @param string $tpl_filename target template file's name
	 * @return string Returns compiled content in case of success or NULL in case of failure
	 **/
	function compileDirect($tpl_path, $tpl_filename) {
		$this->init($tpl_path, $tpl_filename, null);

		// if target file does not exist exit
		if(!$this->file || !file_exists($this->file)) {
			Context::close();
			exit("Cannot find the template file: '{$this->file}'");
		}

		return $this->parse();
	}

	/**
	 * parse syntax.
	 * @param string $buff template file
	 * @return string compiled result in case of success or NULL in case of error
	 **/
	function parse($buff = null) {
		if(is_null($buff)) {
			if(!is_readable($this->file)) return;

			// read tpl file
			$buff = FileHandler::readFile($this->file);
		}

		// HTML tags to skip
		if(is_null($this->skipTags)) {
			$this->skipTags = array('marquee');
		}

		// reset config for this buffer (this step is necessary because we use a singleton for every template)
		$previous_config = clone $this->config;
		$this->config = new stdClass();
		$this->config->autoescape = null;

		if(preg_match('/\<config( [^\>\/]+)/', $buff, $config_match)){
			if(preg_match_all('@ (?<name>\w+)="(?<value>[^"]+)"@', $config_match[1], $config_matches, PREG_SET_ORDER)){
				foreach($config_matches as $config_match){
					if($config_match['name'] === 'autoescape'){
						$this->config->autoescape = $config_match['value'];
					}
				}
			}
		}

		if($this->config->autoescape === 'on') $this->autoescape = true;

		// replace comments
		$buff = preg_replace('@<!--//.*?-->@s', '', $buff);

		// replace value of src in img/input/script tag
		$buff = preg_replace_callback('/<(?:img|input|script)(?:[^<>]*?)(?(?=cond=")(?:cond="[^"]+"[^<>]*)+|)[^<>]* src="(?!(?:https?|file):\/\/|[\/\{])([^"]+)"/is', array($this, '_replacePath'), $buff);

		// replace loop and cond template syntax
		$buff = $this->_parseInline($buff);

		// include, unload/load, import
		$buff = preg_replace_callback('/{(@[\s\S]+?|(?=\$\w+|_{1,2}[A-Z]+|[!\(+-]|\w+(?:\(|::)|\d+|[\'"].*?[\'"]).+?)}|<(!--[#%])?(include|import|(un)?load(?(4)|(?:_js_plugin)?)|config)(?(2)\(["\']([^"\']+)["\'])(.*?)(?(2)\)--|\/)>|<!--(@[a-z@]*)([\s\S]*?)-->(\s*)/', array($this, '_parseResource'), $buff);

		// remove block which is a virtual tag
		$buff = preg_replace('@</?block\s*>@is', '', $buff);

		// form auto generation
		$temp = preg_replace_callback('/(<form(?:<\?php.+?\?>|[^<>]+)*?>)(.*?)(<\/form>)/is', array($this, '_compileFormAuthGeneration'), $buff);
		if($temp) {
			$buff = $temp;
		}

		// prevent from calling directly before writing into file
		$buff = '<?php if(!defined("__XE__"))exit;?>' . $buff;

		// remove php script reopening
		$buff = preg_replace(array('/(\n|\r\n)+/', '/(;)?( )*\?\>\<\?php([\n\t ]+)?/'), array("\n", ";\n"), $buff);

		// restore config to previous value
		$this->config = $previous_config;

		return $buff;
	}

	/**
	 * preg_replace_callback handler
	 * 1. remove ruleset from form tag
	 * 2. add hidden tag with ruleset value
	 * 3. if empty default hidden tag, generate hidden tag (ex:mid, vid, act...)
	 * 4. generate return url, return url use in server side validator
	 * @param array $matches
	 * @return string
	 **/

	function _compileFormAuthGeneration($matches) {
		// form ruleset attribute move to hidden tag
		if($matches[1]) {
			preg_match('/ruleset="([^"]*?)"/is', $matches[1], $m);
			if($m[0]) {
				$matches[1] = preg_replace('/' . addcslashes($m[0], '?$') . '/i', '', $matches[1]);

				if(strpos($m[1], '@') !== false) {
					$path = str_replace('@', '', $m[1]);
					$path = './files/ruleset/' . $path . '.xml';
				} else if(strpos($m[1], '#') !== false) {
					$fileName = str_replace('#', '', $m[1]);
					$fileName = str_replace('<?php echo ', '', $fileName);
					$fileName = str_replace(' ?>', '', $fileName);
					$path = '#./files/ruleset/' . $fileName . '.xml';

					preg_match('@(?:^|\.?/)(modules/[\w-]+)@', $this->path, $mm);
					$module_path = $mm[1];
					list($rulsetFile) = explode('.', $fileName);
					$autoPath = $module_path . '/ruleset/' . $rulsetFile . '.xml';
					$m[1] = $rulsetFile;
				} else if(preg_match('@(?:^|\.?/)(modules/[\w-]+)@', $this->path, $mm)) {
					$module_path = $mm[1];
					$path = $module_path . '/ruleset/' . $m[1] . '.xml';
				}

				$matches[2] = '<input type="hidden" name="ruleset" value="' . $m[1] . '" />' . $matches[2];
				//assign to addJsFile method for js dynamic recache
				$matches[1] = '<?php Context::addJsFile("' . $path . '", false, "", 0, "head", true, "' . $autoPath . '") ?' . '>' . $matches[1];
			}
		}

		// if not exists default hidden tag, generate hidden tag
		preg_match_all('/<input[^>]* name="(act|mid|vid)"/is', $matches[2], $m2);
		$checkVar = array('act', 'mid', 'vid');
		$resultArray = array_diff($checkVar, $m2[1]);
		if(is_array($resultArray)) {
			$generatedHidden = '';
			foreach($resultArray AS $key => $value) {
				$generatedHidden .= '<input type="hidden" name="' . $value . '" value="<?php echo $__Context->' . $value . ' ?>" />';
			}
			$matches[2] = $generatedHidden . $matches[2];
		}

		// return url generate
		if(!preg_match('/no-error-return-url="true"/i', $matches[1])) {
			preg_match('/<input[^>]*name="error_return_url"[^>]*>/is', $matches[2], $m3);
			if(!$m3[0]) $matches[2] = '<input type="hidden" name="error_return_url" value="<?php echo htmlspecialchars(getRequestUriByServerEnviroment()) ?>" />' . $matches[2];
		} else {
			$matches[1] = preg_replace('/no-error-return-url="true"/i', '', $matches[1]);
		}

		$matches[0] = '';
		return implode($matches);
	}

	/**
	 * fetch using ob_* function
	 * @param string $buff if buff is not null, eval it instead of including compiled template file
	 * @return string
	 **/
	function _fetch($buff) {
		if(!$buff) return;

		$__Context = &$GLOBALS['__Context__'];
		$__Context->tpl_path = $this->path;

		if($_SESSION['is_logged']) {
			$__Context->logged_info = Context::get('logged_info');
		}

		$level = ob_get_level();
		ob_start();
		if(substr($buff, 0, 7) == 'file://') {
			include(substr($buff, 7));
		} else {
			$eval_str = "?>" . $buff;
			eval($eval_str);
		}

		$contents = '';
		while(ob_get_level() - $level > 0) {
			$contents .= ob_get_contents();
			ob_end_clean();
		}
		return $contents;
	}

	/**
	 * preg_replace_callback hanlder
	 *
	 * replace image path
	 * @param array $match
	 *
	 * @return string changed result
	 **/
	function _replacePath($match) {
		//return origin conde when src value started '${'.
		if(preg_match('@^\${@', $match[1])) {
			return $match[0];
		}
		//return origin code when src value include variable.
		if(preg_match('@^[\'|"]\s*\.\s*\$@', $match[1])) {
			return $match[0];
		}
		$src = preg_replace('@^(\./)+@', '', trim($match[1]));
		$src = $this->web_path . $src;
		$src = str_replace('/./', '/', $src);
		// for backward compatibility
		$src = preg_replace('@/((?:[\w-]+/)+)\1@', '/\1', $src);
		while(($tmp = preg_replace('@[^/]+/\.\./@', '', $src, 1)) !== $src) {
			$src = $tmp;
		}
		return substr($match[0], 0, -strlen($match[1]) - 6) . "src=\"{$src}\"";
	}

	/**
	 * replace loop and cond template syntax
	 * @param string $buff
	 * @return string changed result
	 **/
	function _parseInline($buff) {
		if(!preg_match_all('/<([a-zA-Z]+\d?)(?:\s)/', $buff, $match)) return $buff;

		$tags = array_diff(array_unique($match[1]), $this->skipTags);

		if(!count($tags)) return $buff;

		$tags = '(?:' . implode('|', $tags) . ')';
		$split_regex = "@(<(?>/?{$tags})(?>[^<>\{\}\"']+|<!--.*?-->|{[^}]+}|\".*?\"|'.*?'|.)*?>)@s";

		$nodes = preg_split($split_regex, $buff, -1, PREG_SPLIT_DELIM_CAPTURE);

		// list of self closing tags
		$self_closing = array('area' => 1, 'base' => 1, 'basefont' => 1, 'br' => 1, 'hr' => 1, 'input' => 1, 'img' => 1, 'link' => 1, 'meta' => 1, 'param' => 1, 'frame' => 1, 'col' => 1);

		for($idx = 1, $node_len = count($nodes); $idx < $node_len; $idx += 2) {
			if(!($node = $nodes[$idx])) continue;

			if(preg_match_all('@\s(loop|cond)="([^"]+)"@', $node, $matches)) {
				// this tag
				$tag = substr($node, 1, strpos($node, ' ') - 1);

				// if the vale of $closing is 0, it means 'skipping'
				$closing = 0;

				// process opening tag
				foreach($matches[1] as $n => $stmt) {
					$expr = $matches[2][$n];
					$expr = $this->_replaceVar($expr);
					$closing++;

					switch($stmt) {
						case 'cond':
							$nodes[$idx - 1] .= "<?php if({$expr}){ ?>";
							break;
						case 'loop':
							if(!preg_match('@^(?:(.+?)=>(.+?)(?:,(.+?))?|(.*?;.*?;.*?)|(.+?)\s*=\s*(.+?))$@', $expr, $expr_m)) break;
							if($expr_m[1]) {
								$expr_m[1] = trim($expr_m[1]);
								$expr_m[2] = trim($expr_m[2]);
								if($expr_m[3]) $expr_m[2] .= '=>' . trim($expr_m[3]);
								$nodes[$idx - 1] .= "<?php if({$expr_m[1]}&&count({$expr_m[1]}))foreach({$expr_m[1]} as {$expr_m[2]}){ ?>";
							} elseif($expr_m[4]) {
								$nodes[$idx - 1] .= "<?php for({$expr_m[4]}){ ?>";
							} elseif($expr_m[5]) {
								$nodes[$idx - 1] .= "<?php while({$expr_m[5]}={$expr_m[6]}){ ?>";
							}
							break;
					}
				}
				$node = preg_replace('@\s(loop|cond)="([^"]+)"@', '', $node);

				// find closing tag
				$close_php = '<?php ' . str_repeat('}', $closing) . ' ?>';
				if($node{1} == '!' || substr($node, -2, 1) == '/' || isset($self_closing[$tag])) { //  self closing tag
					$nodes[$idx + 1] = $close_php . $nodes[$idx + 1];
				} else {
					$depth = 1;
					for($i = $idx + 2; $i < $node_len; $i += 2) {
						$nd = $nodes[$i];
						if(strpos($nd, $tag) === 1) {
							$depth++;
						} elseif(strpos($nd, '/' . $tag) === 1) {
							$depth--;
							if(!$depth) {
								$nodes[$i - 1] .= $nodes[$i] . $close_php;
								$nodes[$i] = '';
								break;
							}
						}
					}
				}
			}

			if(strpos($node, '|cond="') !== false) {
				$node = preg_replace('@(\s[-\w:]+="[^"]+?")\|cond="(.+?)"@s', '<?php if($2){ ?>$1<?php } ?>', $node);
				$node = $this->_replaceVar($node);
			}

			if($nodes[$idx] != $node) $nodes[$idx] = $node;
		}

		$buff = implode('', $nodes);

		return $buff;
	}

	/**
	 * preg_replace_callback hanlder
	 * replace php code.
	 * @param array $m
	 * @return string changed result
	 **/
	private function _parseResource($m){
		$escape_option = 'noescape';

		if($this->autoescape){
			$escape_option = 'autoescape';
		}

		// 템플릿에서 명시적으로 off이면 'noescape' 적용
		if ($this->config->autoescape === 'off'){
			$escape_option = 'noescape';
		}

		// {@ ... } or {$var} or {func(...)}
		if($m[1]){
			if(preg_match('@^(\w+)\(@', $m[1], $mm) && !function_exists($mm[1])){
				return $m[0];
			}

			if($m[1]{0} == '@'){
				$m[1] = $this->_replaceVar(substr($m[1], 1));
				return "<?php {$m[1]} ?>";
			}
			else{
				// Get escape options.
				foreach ($this->ignoreEscape as $key => $value){
					if($this->ignoreEscape[$key]($m)){
						$escape_option = 'noescape';
						break;
					}
				}

				// Separate filters from variable.
				if (preg_match('@^(.+?)(?<![|\s])((?:\|[a-z]{2}[a-z0-9_]+(?::.+)?)+)$@', $m[1], $mm)){
					$m[1] = $mm[1];
					$filters = array_map('trim', explode_with_escape('|', substr($mm[2], 1)));
				}
				else{
					$filters = array();
				}

				// Process the variable.
				$var = self::_replaceVar($m[1]);

				// Apply filters.
				foreach ($filters as $filter){
					// Separate filter option from the filter name.
					if (preg_match('/^([a-z0-9_-]+):(.+)$/', $filter, $matches)){
						$filter = $matches[1];
						$filter_option = $matches[2];
						if (!self::_isVar($filter_option) && !preg_match("/^'.*'$/", $filter_option) && !preg_match('/^".*"$/', $filter_option)){
							$filter_option = "'" . escape_sqstr($filter_option) . "'";
						}
						else{
							$filter_option = self::_replaceVar($filter_option);
						}
					}
					else{
						$filter_option = null;
					}

					// Apply each filter.
					switch ($filter){
						case 'auto':
						case 'autoescape':
						case 'escape':
						case 'noescape':
							$escape_option = $filter;
							break;

						case 'escapejs':
							$var = "escape_js({$var})";
							$escape_option = 'noescape';
							break;

						case 'json':
							$var = "json_encode({$var})";
							$escape_option = 'noescape';
							break;

						case 'strip':
						case 'strip_tags':
							$var = $filter_option ? "strip_tags({$var}, {$filter_option})" : "strip_tags({$var})";
							break;

						case 'trim':
							$var = "trim({$var})";
							break;

						case 'urlencode':
							$var = "rawurlencode({$var})";
							$escape_option = 'noescape';
							break;

						case 'lower':
							$var = "strtolower({$var})";
							break;

						case 'upper':
							$var = "strtoupper({$var})";
							break;

						case 'nl2br':
							$var = $this->_applyEscapeOption($var, $escape_option);
							$var = "nl2br({$var})";
							$escape_option = 'noescape';
							break;

						case 'join':
							$var = $filter_option ? "implode({$filter_option}, {$var})" : "implode(', ', {$var})";
							break;

						//case 'date':
						//	$var = $filter_option ? "getDisplayDateTime(ztime({$var}), {$filter_option})" : "getDisplayDateTime(ztime({$var}), 'Y-m-d H:i:s')";
						//	break;

						case 'format':
						case 'number_format':
							$var = $filter_option ? "number_format({$var}, {$filter_option})" : "number_format({$var})";
							break;

						case 'link':
							$var = $this->_applyEscapeOption($var, 'autoescape');
							if ($filter_option)
							{
								$filter_option = $this->_applyEscapeOption($filter_option, 'autoescape');
								$var = "'<a href=\"' . {$filter_option} . '\">' . {$var} . '</a>'";
							}
							else
							{
								$var = "'<a href=\"' . {$var} . '\">' . {$var} . '</a>'";
							}
							$escape_option = 'noescape';
							break;

						default:
							$filter = escape_sqstr($filter);
							$var = "'INVALID FILTER ({$filter})'";
					}
				}

				// Apply the escape option and return.
				return '<?php echo ' . $this->_applyEscapeOption($var, $escape_option) . ' ?>';
			}
		}

		if($m[3]) {
			$attr = array();
			if($m[5]) {
				if(preg_match_all('@,(\w+)="([^"]+)"@', $m[6], $mm)) {
					foreach($mm[1] as $idx => $name) {
						$attr[$name] = $mm[2][$idx];
					}
				}
				$attr['target'] = $m[5];
			} else {
				if(!preg_match_all('@ (\w+)="([^"]+)"@', $m[6], $mm)) return $m[0];
				foreach($mm[1] as $idx => $name) {
					$attr[$name] = $mm[2][$idx];
				}
			}

			switch($m[3]) {
				// <!--#include--> or <include ..>
				case 'include':
					if(!$this->file || !$attr['target']) return '';

					$pathinfo = pathinfo($attr['target']);
					$fileDir = $this->_getRelativeDir($pathinfo['dirname']);

					if(!$fileDir) return '';

					return "<?php \$__tpl=TemplateHandler::getInstance();echo \$__tpl->compile('{$fileDir}','{$pathinfo['basename']}') ?>";

				// <!--%load_js_plugin-->
				case 'load_js_plugin':
					$plugin = $this->_replaceVar($m[5]);
					if(strpos($plugin, '$__Context') === false) $plugin = "'{$plugin}'";

					return "<?php Context::loadJavascriptPlugin({$plugin}); ?>";

				// <load ...> or <unload ...> or <!--%import ...--> or <!--%unload ...-->
				case 'import':
				case 'load':
				case 'unload':
					$metafile = '';
					$pathinfo = pathinfo($attr['target']);
					$doUnload = ($m[3] === 'unload');
					$isRemote = !!preg_match('@^(https?:)?//@i', $attr['target']);

					if(!$isRemote) {
						if(!preg_match('@^\.?/@', $attr['target'])) $attr['target'] = './' . $attr['target'];
						if(substr($attr['target'], -5) == '/lang') {
							$pathinfo['dirname'] .= '/lang';
							$pathinfo['basename'] = '';
							$pathinfo['extension'] = 'xml';
						}

						$relativeDir = $this->_getRelativeDir($pathinfo['dirname']);

						$attr['target'] = $relativeDir . '/' . $pathinfo['basename'];
					}

					switch($pathinfo['extension']) {
						case 'xml':
							if($isRemote || $doUnload) return '';
							// language file?
							if($pathinfo['basename'] == 'lang.xml' || substr($pathinfo['dirname'], -5) == '/lang') {
								$result = "Context::loadLang('{$relativeDir}');";
							} else {
								$result = "require_once('./classes/xml/XmlJsFilter.class.php');\$__xmlFilter=new XmlJsFilter('{$relativeDir}','{$pathinfo['basename']}');\$__xmlFilter->compile();";
							}
							break;
						case 'js':
							if($doUnload) {
								$result = "Context::unloadFile('{$attr['target']}','{$attr['targetie']}');";
							} else {
								$metafile = $attr['target'];
								$result = "\$__tmp=array('{$attr['target']}','{$attr['type']}','{$attr['targetie']}','{$attr['index']}');Context::loadFile(\$__tmp,'{$attr['usecdn']}','{$attr['cdnprefix']}','{$attr['cdnversion']}');unset(\$__tmp);";
							}
							break;
						case 'css':
							if($doUnload) {
								$result = "Context::unloadFile('{$attr['target']}','{$attr['targetie']}','{$attr['media']}');";
							} else {
								$metafile = $attr['target'];
								$result = "\$__tmp=array('{$attr['target']}','{$attr['media']}','{$attr['targetie']}','{$attr['index']}');Context::loadFile(\$__tmp,'{$attr['usecdn']}','{$attr['cdnprefix']}','{$attr['cdnversion']}');unset(\$__tmp);";
							}
							break;
					}

					$result = "<?php {$result} ?>";
					if($metafile) $result = "<!--#Meta:{$metafile}-->" . $result;

					return $result;
				// <config ...>
				case 'config':
					$result = '';
					if(preg_match_all('@ (\w+)="([^"]+)"@', $m[6], $config_matches, PREG_SET_ORDER))
					{
						foreach($config_matches as $config_match)
						{
							$result .= "\$this->config->{$config_match[1]} = '" . trim(strtolower($config_match[2])) . "';";
						}
					}
					return "<?php {$result} ?>";
			}
		}

		// <!--@..--> such as <!--@if($cond)-->, <!--@else-->, <!--@end-->
		if($m[7]) {
			$m[7] = substr($m[7], 1);
			if(!$m[7]) return '<?php ' . $this->_replaceVar($m[8]) . '{ ?>' . $m[9];
			if(!preg_match('/^(?:((?:end)?(?:if|switch|for(?:each)?|while)|end)|(else(?:if)?)|(break@)?(case|default)|(break))$/', $m[7], $mm)) return '';
			if($mm[1]) {
				if($mm[1]{0} == 'e') return '<?php } ?>' . $m[9];

				$precheck = '';
				if($mm[1] == 'switch') {
					$m[9] = '';
				} elseif($mm[1] == 'foreach') {
					$var = preg_replace('/^\s*\(\s*(.+?) .*$/', '$1', $m[8]);
					$precheck = "if({$var}&&count({$var}))";
				}
				return '<?php ' . $this->_replaceVar($precheck . $m[7] . $m[8]) . '{ ?>' . $m[9];
			}
			if($mm[2]) return "<?php }{$m[7]}" . $this->_replaceVar($m[8]) . "{ ?>" . $m[9];
			if($mm[4]) return "<?php " . ($mm[3] ? 'break;' : '') . "{$m[7]} " . trim($m[8], '()') . ": ?>" . $m[9];
			if($mm[5]) return "<?php break; ?>";
			return '';
		}

		return $m[0];
	}

	/**
 	 * Apply escape option to an expression.
 	 */
 	private function _applyEscapeOption($str, $escape_option = 'noescape'){
 		switch($escape_option){
 			case 'escape':
 				return "escape({$str}, true)";
 			case 'noescape':
 				return "{$str}";
 			case 'autoescape':
 				return "escape({$str}, false)";
 			case 'auto':
 			default:
 				return "(\$this->config->autoescape === 'on' ? escape({$str}, false) : ({$str}))";
 		}
 	}

	/**
	 * change relative path
	 * @param string $path
	 * @return string
	 **/
	function _getRelativeDir($path) {
		$_path = $path;

		$fileDir = strtr(realpath($this->path), '\\', '/');
		if($path{0} != '/') $path = strtr(realpath($fileDir . '/' . $path), '\\', '/');

		// for backward compatibility
		if(!$path) {
			$dirs = explode('/', $fileDir);
			$paths = explode('/', $_path);
			$idx = array_search($paths[0], $dirs);

			if($idx !== false) {
				while($dirs[$idx] && $dirs[$idx] === $paths[0]) {
					array_splice($dirs, $idx, 1);
					array_shift($paths);
				}
				$path = strtr(realpath($fileDir . '/' . implode('/', $paths)), '\\', '/');
			}
		}

		$path = preg_replace('/^' . preg_quote(_DAOL_PATH_, '/') . '/', '', $path);

		return $path;
	}

	/**
 	 * Check if a string seems to contain a variable.
 	 *
 	 * @param string $str
 	 * @return bool
 	 */
 	private static function _isVar($str){
 		return preg_match('@(?<!::|\\\\|(?<!eval\()\')\$([a-z_][a-z0-9_]*)@i', $str) ? true : false;
 	}

	/**
	 * replace PHP variables of $ character
	 * @param string $php
	 * @return string $__Context->varname
	 **/
	function _replaceVar($php) {
		if(!strlen($php)) return '';
		return preg_replace('@(?<!::|\\\\|(?<!eval\()\')\$([a-z]|_[a-z0-9])@i', '\$__Context->$1', $php);
	}

	function isAutoescape(){

		$absPath = str_replace(_DAOL_PATH_, '', $this->path);
		$absPath = str_replace(_XE_PATH_, '', $this->path);
		$dirTpl = '(addon|admin|adminlogging|autoinstall|board|comment|communication|counter|document|editor|file|importer|install|integration_search|krzip|layout|member|menu|message|module|page|point|poll|rss|seo|session|spamfilter|syndication|tag|trash|widget)';
		$dirSkins = '(layouts\/daol_official|layouts\/user_layout|m\.layouts\/colorCode|m\.layouts\/default|m\.layouts\/simpleGray|modules\/board\/m\.skins\/default|modules\/board\/skins\/default|modules\/communication\/m\.skins\/default|modules\/communication\/skins\/default|modules\/editor\/skins\/ckeditor|modules\/editor\/skins\/xpresseditor|modules\/integration_search\/skins\/default|modules\/layout\/faceoff|modules\/member\/m\.skins\/default|modules\/member\/skins\/default|modules\/message\/m\.skins\/default|modules\/message\/skins\/default|modules\/page\/m\.skins\/default|modules\/page\/skins\/default|modules\/poll\/skins\/default|modules\/poll\/skins\/simple|widgets\/content\/skins\/admin_rss|widgets\/content\/skins\/default|widgets\/counter_status\/skins\/default|widgets\/language_select\/skins\/default|widgets\/login_info\/skins\/default|widgets\/mcontent\/skins\/default|widgetstyles\/simple)';

		// 'tpl'
		if(preg_match('/^(\.\/)?(modules\/' . $dirTpl . '|common)\/tpl\//', $absPath)){
			return true;
		}

		// skin, layout
		if(preg_match('/^(\.\/)?\(' . $dirSkin . '\//', $absPath)){
			return true;
		}

		return false;
	}

	public function setAutoescape($val = true){
		$this->autoescape = $val;
	}
}

/* End of File: TemplateHandler.class.php */
