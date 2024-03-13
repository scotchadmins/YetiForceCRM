<?php
/**
 * Web service init file.
 *
 * @package API
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
chdir(__DIR__);
require_once __DIR__ . '/include/main/WebUI.php';
require_once __DIR__ . '/include/RequirementsValidation.php';

\App\Process::$requestMode = 'API';
\App\Log::beginProfile(\App\Request::getRequestMethod() . '::' . $_SERVER['REQUEST_URI'], 'WebServiceAPI');
try {
	if (!\in_array('webservice', \App\Config::api('enabledServices'))) {
		throw new \App\Exceptions\NoPermittedToApi('Webservice - Service is not active', 503);
	}
	$controller = Api\Controller::getInstance();
	try {
		$process = $controller->preProcess();
		if ($process) {
			$controller->process();
		}
		$controller->postProcess();
	} catch (\Throwable $e) {
		\App\Log::error($e->getMessage() . PHP_EOL . $e->__toString());
		$controller->handleError($e);
	}
} catch (\Throwable $e) {
	\App\Log::error($e->getMessage() . PHP_EOL . $e->__toString());
	if (!headers_sent()) {
		$ex = new \Api\Core\Exception($e->getMessage(), $e->getCode(), $e);
		$ex->showError();
	}
}
\App\Log::endProfile(\App\Request::getRequestMethod() . '::' . $_SERVER['REQUEST_URI'], 'WebServiceAPI');
