<?php

/**
 * Security helpers for validating redirect targets.
 */
class URLSecurity {

	/**
	 * Normalize a host for case-insensitive and IDNA-safe comparison.
	 *
	 * @param string $host
	 * @return string
	 */
	static function normalizeHost($host) {
		$host = strtolower(rtrim(trim(strval($host)), '.'));
		if($host === '') return '';

		// IPv6 literals are already ASCII and may be enclosed in brackets.
		if(strpos($host, ':') !== false) return $host;

		if(function_exists('idn_to_ascii')){
			if(defined('INTL_IDNA_VARIANT_UTS46')){
				$encoded = idn_to_ascii($host, 0, INTL_IDNA_VARIANT_UTS46);
			}
			else{
				$encoded = idn_to_ascii($host);
			}
		}
		else{
			require_once(_DAOL_PATH_ . 'libs/idna_convert/idna_convert.class.php');
			$idn = new idna_convert(array('idn_version' => 2008));
			$encoded = $idn->encode($host);
		}

		return $encoded ? strtolower(rtrim($encoded, '.')) : '';
	}

	/**
	 * Build an allowed origin from a configured domain or URL.
	 *
	 * @param string $domain
	 * @param int $http_port
	 * @param int $https_port
	 * @return array|null
	 */
	static function createOrigin($domain, $http_port = 0, $https_port = 0) {
		$domain = trim(strval($domain));
		if($domain === '') return null;

		$candidate = str_replace('\\', '/', $domain);
		if(!preg_match('/^[a-z][a-z0-9+.-]*:/i', $candidate)){
			$candidate = 'http://' . ltrim($candidate, '/');
		}

		$info = parse_url($candidate);
		if($info === false || empty($info['host'])) return null;

		$host = self::normalizeHost($info['host']);
		if($host === '') return null;

		$explicit_port = isset($info['port']) ? intval($info['port']) : 0;
		$scheme = isset($info['scheme']) ? strtolower($info['scheme']) : 'http';
		$http_port = intval($http_port);
		$https_port = intval($https_port);

		if(!$http_port && $scheme === 'http' && $explicit_port) $http_port = $explicit_port;
		if(!$https_port && $scheme === 'https' && $explicit_port) $https_port = $explicit_port;

		return array(
			'host' => $host,
			'http_port' => $http_port > 0 ? $http_port : 80,
			'https_port' => $https_port > 0 ? $https_port : 443,
		);
	}

	/**
	 * Check whether a redirect URL belongs to one of the configured origins.
	 *
	 * @param string $url
	 * @param array $allowed_origins
	 * @param string $default_scheme Scheme used by protocol-relative URLs
	 * @return bool
	 */
	static function isInternalURL($url, $allowed_origins, $default_scheme = 'http') {
		if(!is_string($url) || !is_array($allowed_origins)) return false;

		// Browsers trim C0 whitespace and treat backslashes as separators.
		$url = trim(urldecode($url), "\x00..\x20");
		$url = str_replace('\\', '/', $url);
		if($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) return false;

		$info = parse_url($url);
		if($info === false) return false;

		$has_scheme = isset($info['scheme']);
		$has_host = isset($info['host']) && $info['host'] !== '';

		// Only genuine relative paths may omit a host.
		if(!$has_host){
			if($has_scheme || substr($url, 0, 2) === '//') return false;
			return true;
		}

		$scheme = $has_scheme ? strtolower($info['scheme']) : strtolower($default_scheme);
		if($scheme !== 'http' && $scheme !== 'https') return false;

		$host = self::normalizeHost($info['host']);
		if($host === '') return false;

		$port = isset($info['port']) ? intval($info['port']) : ($scheme === 'https' ? 443 : 80);
		foreach($allowed_origins as $origin){
			if(!is_array($origin) || empty($origin['host'])) continue;
			if($host !== self::normalizeHost($origin['host'])) continue;

			$expected_port = isset($origin[$scheme . '_port']) ? intval($origin[$scheme . '_port']) : 0;
			if(!$expected_port) $expected_port = $scheme === 'https' ? 443 : 80;
			if($port === $expected_port) return true;
		}

		return false;
	}

	/**
	 * Check a URL against the default and current-site origins.
	 *
	 * @param string $url
	 * @return bool
	 */
	static function isInternalURLForCurrentSite($url) {
		$db_info = Context::getDBInfo();
		$site_module_info = Context::get('site_module_info');

		$http_port = isset($db_info->http_port) ? intval($db_info->http_port) : 0;
		$https_port = isset($db_info->https_port) ? intval($db_info->https_port) : 0;
		$origins = array(
			self::createOrigin(isset($db_info->default_url) ? $db_info->default_url : '', $http_port, $https_port),
		);
		if($site_module_info && isset($site_module_info->domain)){
			$origins[] = self::createOrigin($site_module_info->domain, $http_port, $https_port);
		}

		$default_scheme = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
		return self::isInternalURL($url, $origins, $default_scheme);
	}

	/**
	 * Return an internal URL or the configured default URL.
	 *
	 * @param string $url
	 * @return string
	 */
	static function sanitizeReturnURL($url) {
		if(self::isInternalURLForCurrentSite($url)){
			return $url;
		}
		$db_info = Context::getDBInfo();
		return isset($db_info->default_url) ? $db_info->default_url : '';
	}
}

/* End of file URLSecurity.class.php */
