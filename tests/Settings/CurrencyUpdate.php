<?php
/**
 * CurrencyUpdate test class.
 *
 * @package   Tests
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Sławomir Kłos <s.klos@yetiforce.com>
 */

namespace Tests\Settings;

class CurrencyUpdate extends \Tests\Base
{
	/**
	 * Testing module model methods.
	 */
	public function testModuleModel()
	{
		$moduleModel = \Settings_CurrencyUpdate_Module_Model::getCleanInstance();
		$this->assertNotEmpty(\Settings_CurrencyUpdate_Module_Model::getCRMCurrencyName('PLN'), 'Expected currency name');
		$this->assertIsInt($moduleModel->getCurrencyNum(), 'Expected currency number as integer');
		try {
			$this->assertNull($moduleModel->refreshBanks(), 'Method should return nothing');
			$moduleModel->setActiveBankById((new \App\Db\Query())->select('id')->from('yetiforce_currencyupdate_banks')->where(['bank_name' => 'NBP'])->scalar());
			$this->assertIsBool($moduleModel->fetchCurrencyRates(date('Y-m-d')), 'Expected boolean result.');
			$this->assertIsArray($moduleModel->getSupportedCurrencies(), 'getSupportedCurrencies should always return array');
			$this->assertIsArray($moduleModel->getUnSupportedCurrencies(), 'getUnSupportedCurrencies should always return array');
			$this->assertIsNumeric($moduleModel->getCRMConversionRate(\App\Fields\Currency::getIdByCode('PLN'), \App\Fields\Currency::getIdByCode('USD'), date('Y-m-d')), 'getCRMConversionRate should always return number');
			// @codeCoverageIgnoreStart
		} catch (\Exception $e) {
			$this->markTestSkipped('Possibly connection error from integration:' . $e->getTraceAsString() . 'File: ' . $e->getFile());
		}
		// @codeCoverageIgnoreEnd
		$this->assertIsInt($moduleModel->getActiveBankId(), 'Expected active bank id as integer');
		$this->assertTrue($moduleModel->setActiveBankById(random_int(1, 4)), 'setActiveBankById should return true');
		$this->assertNotEmpty($moduleModel->getActiveBankName(), 'Active bank name should be not empty');
	}

	/**
	 * Testing external bank interfaces.
	 */
	public function testBanks()
	{
		$dataReader = (new \App\Db\Query())->select(['id', 'currency_code'])
			->from('vtiger_currency_info')
			->where(['currency_status' => 'Active', 'deleted' => 0])
			->andWhere(['<>', 'defaultid', -11])->createCommand()->query();
		$currencyList = [];
		while ($row = $dataReader->read()) {
			$currencyList[$row['currency_code']] = $row['id'];
		}
		try {
			foreach (['CBR', 'ECB', 'NBR', 'NBP'] as $bankCode) {
				if (\in_array($bankCode, ['CBR'])) {
					echo "$bankCode - Disabled due to data source instability\n";
					continue;
				}
				$bankClass = '\Settings_CurrencyUpdate_' . $bankCode . '_BankModel';
				$bank = new $bankClass();
				$this->assertNotEmpty($bank->getName(), 'Bank name should be not empty');
				$this->assertNotEmpty($bank->getSource(), 'Bank source should be not empty');
				$this->assertIsArray($bank->getSupportedCurrencies(), 'Expected array of currencies');
				$this->assertNotEmpty($bank->getMainCurrencyCode(), 'Main bank currency should be not empty');
				$this->assertNull($bank->getRates($currencyList, date('Y-m-d'), true), ' Expected nothing/null');
			}
			// @codeCoverageIgnoreStart
		} catch (\Exception $e) {
			$this->markTestSkipped('Possibly connection error from integration:' . $e->getMessage());
		}
		// @codeCoverageIgnoreEnd
	}
}
