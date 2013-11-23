<?php
/**
 * @file  index.php
 * @author NHN (developers@xpressengine.com)
 * @Adaptor DAOL Project (developer@daolcms.org)
 * @brief Start page
 *
 * Find and create module object by mif, act in Request Argument
 * Set module information
 *
 * @mainpage DAOL CMS
 * @mainpage XpressEngine
 * @section intro introduction
 * DAOL Core is the base frame of DAOL CMS. DAOL CMS is a web CMS program,
 * branched out from XpressEngine.
 * For more information, please see the link below.
 * - Website: http://www.daolcms.org
 * - Repository: https://github.com/daolcms
 *
 * "DAOL CMS" is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 **/

/**
 * @brief Declare constants for generic use and for checking to avoid a direct call from the Web
 **/
define('__DAOL__', TRUE);
define('__XE__',   TRUE);
define('__ZBXE__', TRUE); // deprecated : __ZBXE__ will be removed. Use __XE__ instead.

/**
 * @brief Include the necessary configuration files
 **/
require dirname(__FILE__) . '/config/config.inc.php';

/**
 * @brief Initialize by creating Context object
 * Set all Request Argument/Environment variables
 **/
$oContext = &Context::getInstance();
$oContext->init();

/**
 * @brief If default_url is set and it is different from the current url, attempt to redirect for SSO authentication and then process the module
 **/
if($oContext->checkSSO())
{
	$oModuleHandler = new ModuleHandler();

	if($oModuleHandler->init())
	{
		$oModule = &$oModuleHandler->procModule();
		$oModuleHandler->displayContent($oModule);
	}
}

$oContext->close();

/* End of file index.php */
/* Location: ./index.php */
