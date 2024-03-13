<?php

/**
 * Basic inventory action file.
 *
 * @package Action
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author    Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

/**
 * Basic inventory action class.
 */
class Vtiger_Inventory_Action extends \App\Controller\Action
{
	use \App\Controller\ExposeMethod;

	/** {@inheritdoc} */
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('checkLimits');
		$this->exposeMethod('getDetails');
		$this->exposeMethod('getTableData');
		$this->exposeMethod('getCurrencyData');
	}

	/** {@inheritdoc} */
	public function checkPermission(App\Request $request)
	{
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$userPrivilegesModel->hasModulePermission($request->getModule())) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED', 406);
		}
	}

	/**
	 * Function verifies whether the Account's credit limit has been reached.
	 *
	 * @param \App\Request $request
	 */
	public function checkLimits(App\Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->getInteger('record');
		if (!\App\Privilege::isPermitted($moduleName, 'EditView', $record)) {
			throw new \App\Exceptions\NoPermittedToRecord('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
		}
		$currency = $request->getInteger('currency');
		$price = $request->getByType('price', 'Double');
		$limitFieldName = 'creditlimit';
		$balanceFieldName = 'inventorybalance';
		$response = new Vtiger_Response();

		$moduleInstance = Vtiger_Module_Model::getInstance('Accounts');
		$limitField = Vtiger_Field_Model::getInstance($limitFieldName, $moduleInstance);
		$balanceField = Vtiger_Field_Model::getInstance($balanceFieldName, $moduleInstance);
		if (!$limitField->isActiveField() || !$balanceField->isActiveField()) {
			$response->setResult(['status' => true]);
			$response->emit();

			return;
		}
		$recordModel = Vtiger_Record_Model::getInstanceById($record, 'Accounts');
		$limitID = $recordModel->get($limitFieldName);
		$balance = $recordModel->get($balanceFieldName);
		if (!empty($limitID)) {
			$limit = Vtiger_InventoryLimit_UIType::getValues($limitID)['value'];
		} else {
			$response->setResult(['status' => true]);
			$response->emit();

			return;
		}

		$baseCurrency = Vtiger_Util_Helper::getBaseCurrency();
		$symbol = $baseCurrency['currency_symbol'];
		if ($baseCurrency['id'] != $currency) {
			$selectedCurrency = \App\Fields\Currency::getById($currency);
			$price = (float) $price * $selectedCurrency['conversion_rate'];
			$symbol = $selectedCurrency['currency_symbol'];
		}
		$totalPrice = $price + $balance;

		$status = $totalPrice > $limit ? false : true;
		if (!$status) {
			$viewer = new Vtiger_Viewer();
			$viewer->assign('PRICE', $price);
			$viewer->assign('BALANCE', $balance);
			$viewer->assign('SYMBOL', $symbol);
			$viewer->assign('LIMIT', $limit);
			$viewer->assign('TOTALS', $totalPrice);
			$html = $viewer->view('InventoryLimitAlert.tpl', $moduleName, true);
		}
		$response->setResult([
			'status' => $status,
			'html' => $html,
		]);
		$response->emit();
	}

	/**
	 * Get record details.
	 *
	 * @param App\Request $request
	 */
	public function getDetails(App\Request $request)
	{
		$currencyId = $request->getInteger('currency_id');
		$fieldName = $request->getByType('fieldname');
		$moduleName = $request->getModule();
		if ($request->isEmpty('idlist')) {
			$info = static::getRecordDetail($request->getInteger('record'), $currencyId, $moduleName, $fieldName, $request->getArray('currencyParams', \App\Purifier::TEXT));
		} else {
			foreach ($request->getArray('idlist', 'Integer') as $id) {
				$info[] = static::getRecordDetail($id, $currencyId, $moduleName, $fieldName);
			}
		}
		$response = new Vtiger_Response();
		$response->setResult($info);
		$response->emit();
	}

	/**
	 * Get record detail for inventory table.
	 *
	 * @param int      $recordId
	 * @param int|null $currencyId
	 * @param string   $moduleName
	 * @param string   $fieldName
	 * @param array    $currencyParams
	 *
	 * @throws \App\Exceptions\NoPermittedToRecord
	 *
	 * @return array
	 */
	public static function getRecordDetail(int $recordId, ?int $currencyId, string $moduleName, string $fieldName, array $currencyParams = []): array
	{
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId);
		if (!$recordModel->isViewable()) {
			throw new \App\Exceptions\NoPermittedToRecord('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
		}
		$recordModuleName = $recordModel->getModuleName();
		$info = [
			'id' => $recordId,
			'name' => App\Purifier::decodeHtml($recordModel->getName()),
			'description' => $recordModel->get('description'),
		];
		$autoFields = [];
		$inventory = Vtiger_Inventory_Model::getInstance($moduleName);
		if ($autoCompleteField = ($inventory->getAutoCompleteFields()[$recordModuleName] ?? [])) {
			foreach ($autoCompleteField as $field) {
				$fieldModel = $recordModel->getField($field['field']);
				if ($fieldModel && ($fieldValue = $recordModel->get($field['field']))) {
					$autoFields[$field['tofield']] = $fieldModel->isReferenceField() ? $fieldValue : $fieldModel->getEditViewDisplayValue($fieldValue, $recordModel);
					$autoFields[$field['tofield'] . 'Text'] = $fieldModel->getDisplayValue($fieldValue, $recordId, $recordModel, true);
				}
			}
		}
		$info['autoFields'] = $autoFields;
		if (!$recordModel->isEmpty('taxes')) {
			if (false === strpos($recordModel->get('taxes'), ',')) {
				$taxModel = Settings_Inventory_Record_Model::getInstanceById($recordModel->get('taxes'), 'Taxes');
			} else {
				$productTaxes = explode(',', $recordModel->get('taxes'));
				$taxModel = Settings_Inventory_Record_Model::getInstanceById(reset($productTaxes), 'Taxes');
			}
			$info['taxes'] = [
				'type' => 'group',
				'value' => $taxModel->get('value'),
			];
		}
		$eventHandler = new App\EventHandler();
		$eventHandler->setRecordModel($recordModel);
		$eventHandler->setModuleName($recordModuleName);
		$eventHandler->setParams([
			'currencyId' => $currencyId,
			'currencyParams' => $currencyParams,
			'moduleName' => $moduleName,
			'fieldName' => $fieldName,
			'info' => $info,
		]);
		$eventHandler->trigger('InventoryRecordDetails');
		$info = $eventHandler->getParam('info');
		return [$recordId => array_merge($info, $inventory->getCustomAutoComplete($fieldName, $recordModel))];
	}

	/**
	 * Get products and services from source invoice to display in correcting invoice before block.
	 *
	 * @param \App\Request $request
	 *
	 * @throws \App\Exceptions\IllegalValue
	 * @throws \App\Exceptions\NoPermittedToRecord
	 */
	public function getTableData(App\Request $request)
	{
		if ($request->isEmpty('src_record', true) || $request->isEmpty('src_module', true)) {
			throw new \App\Exceptions\NoPermittedToRecord('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
		}
		$srcModule = $request->getByType('src_module', \App\Purifier::ALNUM);
		$srcRecord = $request->getInteger('src_record');
		if (!\App\Privilege::isPermitted($srcModule, 'DetailView', $srcRecord)) {
			throw new \App\Exceptions\NoPermittedToRecord('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
		}
		$recordModel = Vtiger_Record_Model::getInstanceById($srcRecord, $srcModule);
		$inventoryModel = Vtiger_Inventory_Model::getInstance($srcModule);
		$data = $inventoryModel->transformData($recordModel->getInventoryData());

		foreach ($data as &$item) {
			$currencyParams = empty($item['currencyparam']) ? [] : \App\Json::decode($item['currencyparam']);
			$item['info'] = static::getRecordDetail($item['name'], $item['currency'] ?? 0, $request->getModule(), 'name', $currencyParams)[$item['name']];
			$item['moduleName'] = \App\Record::getType($item['info']['id']);
			$item['basetableid'] = Vtiger_Module_Model::getInstance($item['moduleName'])->get('basetableid');
		}

		$response = new Vtiger_Response();
		$response->setResult(array_values($data));
		$response->emit();
	}

	/**
	 * Get cuurenct currency data with conversion rate and date.
	 *
	 * @param App\Request $request
	 */
	public function getCurrencyData(App\Request $request)
	{
		$currencies = [];
		$moduleName = $request->getModule();
		$fieldModel = \Vtiger_Inventory_Model::getInstance($moduleName)->getField('currency');

		if ($fieldModel && $fieldModel->getParamConfig('reset_currency')) {
			$currencies = $fieldModel->getCurrencyParam(\App\Fields\Currency::getAll(true));
		}

		$response = new Vtiger_Response();
		$response->setResult($currencies);
		$response->emit();
	}
}
