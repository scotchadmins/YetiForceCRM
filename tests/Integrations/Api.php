<?php
/**
 * Api integrations test class.
 *
 * @see https://github.com/Maks3w/SwaggerAssertions/
 *
 * @package   Tests
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

namespace Tests\Integrations;

use FR3D\SwaggerAssertions\PhpUnit\AssertsTrait;
use FR3D\SwaggerAssertions\SchemaManager;

class Api extends \Tests\Base
{
	use AssertsTrait;
	/**
	 * Server address.
	 *
	 * @var string
	 */
	private static $url;

	/**
	 * Api server id.
	 *
	 * @var int
	 */
	private static $serverId;

	/**
	 * Api user id.
	 *
	 * @var int
	 */
	private static $apiUserId;

	/**
	 * Request options.
	 *
	 * @var array
	 */
	private static $requestOptions = [
		'auth' => ['portal', 'portal'],
		'Content-Type' => 'application/json',
		'Accept' => 'application/json',
		'timeout' => 60,
		'connect_timeout' => 60,
	];

	/**
	 * Details about logged in user.
	 *
	 * @var array
	 */
	private static $authUserParams;
	private static $recordId;

	/**
	 * @var SchemaManager
	 */
	protected static $schemaManager;

	/**
	 * @var \GuzzleHttp\Client
	 */
	protected $httpClient;

	public static function setUpBeforeClass(): void
	{
		self::$schemaManager = new SchemaManager(json_decode(file_get_contents(ROOT_DIRECTORY . '/public_html/api/Portal.json')));
	}

	protected function setUp(): void
	{
		self::$url = \App\Config::main('site_URL') . 'webservice/';
		$this->httpClient = new \GuzzleHttp\Client(\App\RequestHttp::getOptions());
	}

	/**
	 * Testing add configuration.
	 */
	public function testAddConfiguration(): void
	{
		$webserviceApps = \Settings_WebserviceApps_Record_Model::getCleanInstance();
		$webserviceApps->set('type', 'Portal');
		$webserviceApps->set('status', 1);
		$webserviceApps->set('name', 'portal');
		$webserviceApps->set('acceptable_url', '');
		$webserviceApps->set('pass', 'portal');
		$webserviceApps->save();
		self::$serverId = (int) $webserviceApps->getId();

		$row = (new \App\Db\Query())->from('w_#__servers')->where(['id' => self::$serverId])->one();
		$this->assertNotFalse($row, 'No record id: ' . self::$serverId);
		$this->assertSame($row['type'], 'Portal');
		$this->assertSame($row['status'], 1);
		$this->assertSame($row['name'], 'portal');
		$this->assertSame($row['pass'], 'portal');
		self::$requestOptions['headers']['x-api-key'] = $row['api_key'];

		$webserviceUsers = \Settings_WebserviceUsers_Record_Model::getCleanInstance('Portal');
		$webserviceUsers->setData([
			'server_id' => self::$serverId,
			'status' => '1',
			'user_name' => 'demo@yetiforce.com',
			'password_t' => 'demo',
			'type' => '1',
			'language' => 'pl-PL',
			'popupReferenceModule' => 'Contacts',
			'crmid' => 0,
			'crmid_display' => '',
			'user_id' => \App\User::getActiveAdminId(),
		]);
		$webserviceUsers->save();
		self::$apiUserId = $webserviceUsers->getId();
		$row = (new \App\Db\Query())->from('w_#__portal_user')->where(['id' => self::$apiUserId])->one();
		$this->assertNotFalse($row, 'No record id: ' . self::$apiUserId);
		$this->assertSame((int) $row['server_id'], self::$serverId);
		$this->assertSame($row['user_name'], 'demo@yetiforce.com');
		$this->assertSame($row['password_t'], 'demo');
		$this->assertSame($row['language'], 'pl-PL');

		$blockInstance = \vtlib\Block::getInstance('LBL_ACCOUNT_INFORMATION', 'Accounts');
		$fieldInstance = new \Vtiger_Field_Model();
		$fieldInstance->table = 'vtiger_account';
		$fieldInstance->label = 'FL_IN_PORTAL';
		$fieldInstance->name = 'in_portal';
		$fieldInstance->column = 'in_portal';
		$fieldInstance->columntype = 'tinyint(1)';
		$fieldInstance->uitype = 318;
		$fieldInstance->typeofdata = 'C~O';
		$fieldInstance->fieldparams = self::$serverId;
		$blockInstance->addField($fieldInstance);
	}

	/**
	 * Testing login.
	 */
	public function testLogIn(): void
	{
		$request = (new \GuzzleHttp\Client(
			\App\RequestHttp::getOptions()))->post(self::$url . 'Users/Login', array_merge(
				[
					'json' => [
						'userName' => 'demo@yetiforce.com',
						'password' => 'demo',
					]
				], self::$requestOptions)
		);
		$this->logs = $body = $request->getBody()->getContents();
		$response = \App\Json::decode($body);
		$this->assertSame($response['status'], 1, 'Users/Login API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		self::$authUserParams = $response['result'];
		self::$requestOptions['headers']['x-token'] = self::$authUserParams['token'];

		self::assertResponseBodyMatch($response, self::$schemaManager, '/webservice/Users/Login', 'post', 200);
	}

	/**
	 * Testing add record.
	 */
	public function testAddRecord(): void
	{
		$recordData = [
			'accountname' => 'Api YetiForce Sp. z o.o.',
			'addresslevel5a' => 'Warszawa',
			'addresslevel8a' => 'Marszałkowska',
			'buildingnumbera' => 111,
			'legal_form' => 'PLL_GENERAL_PARTNERSHIP',
			'in_portal' => 1
		];
		$request = (new \GuzzleHttp\Client(\App\RequestHttp::getOptions()))->post(self::$url . 'Accounts/Record/', array_merge(
				[
					'json' => $recordData
				], self::$requestOptions)
		);
		$this->logs = $body = $request->getBody()->getContents();
		$response = \App\Json::decode($body);
		$this->assertSame($response['status'], 1, 'Accounts/Record/ API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		self::$recordId = $response['result']['id'];
		self::assertResponseBodyMatch($response, self::$schemaManager, '/webservice/{moduleName}/Record', 'post', 200);
	}

	/**
	 * Testing edit record.
	 */
	public function testEditRecord(): void
	{
		$recordData = [
			'accountname' => 'Api YetiForce Sp. z o.o. New name',
			'buildingnumbera' => 222,
		];
		$request = (new \GuzzleHttp\Client(
			\App\RequestHttp::getOptions()))->put(self::$url . 'Accounts/Record/' . self::$recordId, array_merge(
				[
					'json' => $recordData
				], self::$requestOptions)
		);
		$this->logs = $body = $request->getBody()->getContents();
		$response = \App\Json::decode($body);
		$this->assertSame($response['status'], 1, 'Accounts/Record/{ID} API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		self::assertResponseBodyMatch($response, self::$schemaManager, '/webservice/{moduleName}/Record/{recordId}', 'put', 200);
	}

	/**
	 * Testing record list.
	 */
	public function testRecordList(): void
	{
		$request = (new \GuzzleHttp\Client(\App\RequestHttp::getOptions()))->get(self::$url . 'Accounts/RecordsList', self::$requestOptions);
		$this->logs = $body = $request->getBody()->getContents();
		$response = \App\Json::decode($body);
		$this->assertSame($response['status'], 1, 'Accounts/RecordsList/ API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		self::assertResponseBodyMatch($response, self::$schemaManager, '/webservice/{moduleName}/RecordsList', 'get', 200);
	}

	/**
	 * Testing get fields.
	 */
	public function testGetFields(): void
	{
		$request = (new \GuzzleHttp\Client(\App\RequestHttp::getOptions()))->get(self::$url . 'Accounts/Fields/', self::$requestOptions);
		$this->logs = $body = $request->getBody()->getContents();
		$response = \App\Json::decode($body);
		$this->assertSame($response['status'], 1, 'Accounts/Fields/ API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		$this->assertTrue(!empty($response['result']['fields']), 'Accounts/Fields/ API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		$this->assertTrue(!empty($response['result']['blocks']), 'Accounts/Fields/ API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		self::assertResponseBodyMatch($response, self::$schemaManager, '/webservice/{moduleName}/Fields', 'get', 200);
	}

	/**
	 * Testing get privileges.
	 */
	public function testGetPrivileges(): void
	{
		$request = (new \GuzzleHttp\Client(\App\RequestHttp::getOptions()))->get(self::$url . 'Accounts/Privileges/', self::$requestOptions);
		$this->logs = $body = $request->getBody()->getContents();
		$response = \App\Json::decode($body);
		$this->assertSame($response['status'], 1, 'Accounts/Privileges/ API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		$this->assertTrue(!empty($response['result']), 'Accounts/Privileges/ API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
	}

	/**
	 * Testing get modules.
	 */
	public function testGetModules(): void
	{
		$request = (new \GuzzleHttp\Client(\App\RequestHttp::getOptions()))->get(self::$url . 'Modules', self::$requestOptions);
		$this->logs = $body = $request->getBody()->getContents();
		$response = \App\Json::decode($body);
		$this->assertSame($response['status'], 1, 'Modules API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		$this->assertTrue(!empty($response['result']['Accounts']), 'Modules API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		self::assertResponseBodyMatch($response, self::$schemaManager, '/webservice/Modules', 'get', 200);
	}

	/**
	 * Testing get api methods.
	 */
	public function testGetMethods(): void
	{
		$request = (new \GuzzleHttp\Client(\App\RequestHttp::getOptions()))->get(self::$url . 'Methods', self::$requestOptions);
		$this->logs = $body = $request->getBody()->getContents();
		$response = \App\Json::decode($body);
		$this->assertSame($response['status'], 1, 'Methods API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		$this->assertTrue(!empty($response['result']['BaseAction']), 'Methods API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		$this->assertTrue(!empty($response['result']['BaseModule']), 'Methods API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		$this->assertTrue(!empty($response['result']['Users']), 'Methods API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
	}

	/**
	 * Testing get api menu.
	 */
	public function testGetMenu(): void
	{
		$request = (new \GuzzleHttp\Client(\App\RequestHttp::getOptions()))->get(self::$url . 'Menu', self::$requestOptions);
		$this->logs = $body = $request->getBody()->getContents();
		$response = \App\Json::decode($body);
		$this->assertSame($response['status'], 1, 'Methods API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		self::assertResponseBodyMatch($response, self::$schemaManager, '/webservice/Menu', 'get', 200);
	}

	/**
	 * Testing Logout.
	 */
	public function testLogout(): void
	{
		$request = (new \GuzzleHttp\Client(
			\App\RequestHttp::getOptions()))->put(self::$url . 'Users/Logout', self::$requestOptions);
		$this->logs = $body = $request->getBody()->getContents();
		$response = \App\Json::decode($body);
		$this->assertSame($response['status'], 1, 'Users/Logout API error: ' . PHP_EOL . $request->getReasonPhrase() . '|' . $body);
		self::assertResponseBodyMatch($response, self::$schemaManager, '/webservice/Users/Logout', 'put', 200);
	}

	/**
	 * Testing delete configuration.
	 */
	public function testDeleteConfiguration(): void
	{
		\Settings_WebserviceUsers_Record_Model::getInstanceById(self::$apiUserId, 'Portal')->delete();
		\Settings_WebserviceApps_Record_Model::getInstanceById(self::$serverId)->delete();

		$this->assertFalse((new \App\Db\Query())->from('w_#__servers')->where(['id' => self::$serverId])->exists(), 'Record in the database should not exist');
		$this->assertFalse((new \App\Db\Query())->from('w_#__portal_user')->where(['id' => self::$apiUserId])->exists(), 'Record in the database should not exist');
	}
}
