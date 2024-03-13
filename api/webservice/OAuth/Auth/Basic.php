<?php
/**
 * Basic OAuth authorization file.
 *
 * @package API
 *
 * @copyright YetiForce S.A.
 * @license YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

namespace Api\OAuth\Auth;

/**
 * Basic authorization class.
 */
class Basic extends \Api\Core\Auth\Basic
{
	/** {@inheritdoc} */
	public function setServer(): self
	{
		$this->api->app = [];
		$type = $this->api->request->getByType('_container', \App\Purifier::STANDARD);
		$key = $this->api->request->getByType('action', \App\Purifier::ALNUM);
		$dbKey = \App\Encryption::getInstance()->encrypt($key);

		$query = (new \App\Db\Query())->from('w_#__servers')->where(['type' => $type,  'status' => 1, 'api_key' => $dbKey]);
		if ($row = $query->one()) {
			$row['id'] = (int) $row['id'];
			$this->api->app = $row;
		}

		return $this;
	}

	/** {@inheritdoc}  */
	public function authenticate(string $realm): bool
	{
		if (!$this->api->app) {
			$this->api->response->addHeader('WWW-Authenticate', 'Basic realm="' . $realm . '"');
			throw new \Api\Core\Exception('Web service - Applications: Unauthorized', 401);
		}
		return true;
	}
}
