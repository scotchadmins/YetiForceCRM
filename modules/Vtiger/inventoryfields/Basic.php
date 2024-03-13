<?php

/**
 * Inventory Basic Field Class.
 *
 * @package   InventoryField
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author    Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Vtiger_Basic_InventoryField extends \App\Base
{
	/**
	 * Field visible everywhere.
	 */
	private const FIELD_VISIBLE_EVERYWHERE = 0;
	/**
	 * Field visible in detail view.
	 */
	private const FIELD_VISIBLE_IN_DETAIL = 2;
	/**
	 * Field hidden.
	 */
	private const FIELD_HIDDEN = 5;
	/**
	 * Field read-only.
	 */
	private const FIELD_READONLY = 10;

	protected $columnName = '';
	protected $moduleName = '';

	protected $type;
	protected $colSpan = 10;
	protected $defaultValue = '';
	protected $params = [];
	protected $dbType = 'string';
	/** @var string Database value. */
	protected $dbValue;
	protected $customColumn = [];
	protected $summationValue = false;
	protected $onlyOne = true;
	protected $displayType = self::FIELD_VISIBLE_EVERYWHERE;
	protected $displayTypeBase = [
		self::FIELD_VISIBLE_EVERYWHERE => 'LBL_DISPLAYTYPE_ALL',
		self::FIELD_VISIBLE_IN_DETAIL => 'LBL_DISPLAYTYPE_ONLY_DETAIL',
		self::FIELD_HIDDEN => 'LBL_DISPLAYTYPE_HIDDEN',
		self::FIELD_READONLY => 'LBL_DISPLAYTYPE_READONLY'
	];
	protected $blocks = [1];
	protected $fieldDataType = 'inventory';
	protected $maximumLength = 255;
	protected $defaultLabel = '';
	protected $purifyType = '';
	protected $customPurifyType = [];
	protected $customMaximumLength = [];
	/** @var array Default values for custom fields */
	protected $customDefault = [];
	/** @var bool Field is synchronized */
	protected $sync = false;
	/** @var array List of changes */
	protected $changes = [];
	/** @var bool Search allowed */
	protected $searchable = false;
	/** @var array Operators for query {@see \App\Condition::STANDARD_OPERATORS} */
	protected $queryOperators = ['e', 'n', 'y', 'ny'];
	/** @var array Operators for record conditions {@see \App\Condition::STANDARD_OPERATORS} */
	protected $recordOperators = ['e', 'n', 'y', 'ny'];

	/**
	 * Gets inventory field instance.
	 *
	 * @param string      $moduleName
	 * @param string|null $type
	 *
	 * @throws \App\Exceptions\AppException
	 *
	 * @return self
	 */
	public static function getInstance(string $moduleName, ?string $type = 'Basic')
	{
		$cacheName = "{$moduleName}:{$type}";
		if (\App\Cache::has(__METHOD__, $cacheName)) {
			$instance = \App\Cache::get(__METHOD__, $cacheName);
		} else {
			$className = Vtiger_Loader::getComponentClassName('InventoryField', $type, $moduleName);
			$instance = new $className();
			$instance->setModuleName($moduleName);
			\App\Cache::save(__METHOD__, $cacheName, $instance);
		}
		return clone $instance;
	}

	/**
	 * Function returns module name.
	 *
	 * @return string
	 */
	public function getModuleName(): string
	{
		return $this->moduleName;
	}

	/**
	 * Function to get the Id.
	 *
	 * @return int Field ID
	 */
	public function getId(): int
	{
		return (int) $this->get('id');
	}

	/**
	 * Sets module name.
	 *
	 * @param string $moduleName
	 *
	 * @return \Vtiger_Basic_InventoryField
	 */
	public function setModuleName(string $moduleName): self
	{
		$this->moduleName = $moduleName;
		return $this;
	}

	/**
	 * Getting onlyOne field.
	 *
	 * @return bool
	 */
	public function isOnlyOne()
	{
		return $this->onlyOne;
	}

	public function getBlocks()
	{
		return $this->blocks;
	}

	/**
	 * Getting database-type of field.
	 *
	 * @return string|yii\db\ColumnSchemaBuilder dbType
	 */
	public function getDBType()
	{
		$columnCriteria = $this->dbType;
		if (\is_array($columnCriteria)) {
			[$type, $length, $default, $unsigned] = array_pad($columnCriteria, 4, null);
			$columnCriteria = \App\Db::getInstance()->getSchema()->createColumnSchemaBuilder($type, $length);
			if (null !== $default) {
				$columnCriteria->defaultValue($default);
			}
			if (null !== $unsigned) {
				$columnCriteria->unsigned();
			}
		}

		return $columnCriteria;
	}

	/**
	 * Gets value for save.
	 *
	 * @param array  $item
	 * @param bool   $userFormat
	 * @param string $column
	 *
	 * @return mixed
	 */
	public function getValueForSave(array $item, bool $userFormat = false, string $column = null)
	{
		if (null === $column) {
			$column = $this->getColumnName();
		}
		$value = $item[$column] ?? null;
		return $userFormat ? $this->getDBValue($value) : $value;
	}

	/**
	 * Getting all params field.
	 *
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * Get params value.
	 *
	 * @return array
	 */
	public function getParamsConfig()
	{
		return $this->get('params') ? \App\Json::decode($this->get('params')) : [];
	}

	/**
	 * Get the configuration parameter value for the specified key.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getParamConfig(string $key)
	{
		return $this->getParamsConfig()[$key] ?? null;
	}

	/**
	 * Getting all values display Type.
	 *
	 * @return array
	 */
	public function displayTypeBase()
	{
		return $this->displayTypeBase;
	}

	/**
	 * Gets display type.
	 *
	 * @return int
	 */
	public function getDisplayType(): int
	{
		return $this->has('displaytype') ? $this->get('displaytype') : $this->displayType;
	}

	public function getColSpan()
	{
		return $this->has('colspan') ? $this->get('colspan') : $this->colSpan;
	}

	public function getRangeValues()
	{
		return $this->maximumLength;
	}

	/**
	 * The function determines whether sorting on this field is allowed.
	 *
	 * @return bool
	 */
	public function isSearchable(): bool
	{
		return $this->searchable && \in_array(1, $this->getBlocks());
	}

	/**
	 * Gets full field name for conditions.
	 *
	 * @return string
	 */
	public function getSearchName(): string
	{
		return "{$this->getColumnName()}:{$this->getModuleName()}:INVENTORY";
	}

	/**
	 * Return allowed query operators for field.
	 *
	 * @return string[]
	 */
	public function getQueryOperators(): array
	{
		return $this->queryOperators;
	}

	/**
	 * Return allowed record operators for field.
	 *
	 * @return string[]
	 */
	public function getRecordOperators(): array
	{
		return $this->recordOperators;
	}

	/**
	 * Gets query operator labels.
	 *
	 * @return string[]
	 */
	public function getQueryOperatorLabels(): array
	{
		return \App\Condition::getOperatorLabels($this->getQueryOperators());
	}

	/**
	 * Gets record operator labels.
	 *
	 * @return string[]
	 */
	public function getRecordOperatorLabels(): array
	{
		return \App\Condition::getOperatorLabels($this->getRecordOperators());
	}

	/**
	 * Returns template for operator.
	 *
	 * @param string $operator
	 *
	 * @return string
	 */
	public function getOperatorTemplateName(string $operator = '')
	{
		return 'ConditionBuilder/Base.tpl';
	}

	/**
	 * Function to get the DB Insert Value, for the current field type with given User Value for condition builder.
	 *
	 * @param mixed  $value
	 * @param string $operator
	 *
	 * @return string
	 */
	public function getDbConditionBuilderValue($value, string $operator)
	{
		$this->validate($value, $this->getColumnName(), true);
		return $this->getDBValue($value);
	}

	/**
	 * Function to get the field model for condition builder.
	 *
	 * @param string $operator
	 *
	 * @return $this
	 */
	public function getConditionBuilderField(string $operator)
	{
		return $this;
	}

	/**
	 * Get template name for edit.
	 *
	 * @return string
	 */
	public function getEditTemplateName()
	{
		return 'inventoryTypes/Base.tpl';
	}

	/**
	 * Getting template name.
	 *
	 * @param string $view
	 * @param string $moduleName
	 *
	 * @return string templateName
	 */
	public function getTemplateName($view, $moduleName)
	{
		$tpl = $view . $this->type . '.tpl';
		$dirs = [
			$filename = \App\Layout::getActiveLayout() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduleName,
			$filename = \App\Layout::getActiveLayout() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'Vtiger',
			$filename = Vtiger_Viewer::getDefaultLayoutName() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduleName,
			$filename = Vtiger_Viewer::getDefaultLayoutName() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'Vtiger',
		];
		if (\App\Config::performance('LOAD_CUSTOM_FILES')) {
			$loaderDirs[] = 'custom/layouts/';
		}
		$loaderDirs[] = 'layouts' . DIRECTORY_SEPARATOR;
		foreach ($dirs as $dir) {
			foreach ($loaderDirs as $loaderDir) {
				$filename = $loaderDir . $dir . DIRECTORY_SEPARATOR . 'inventoryfields' . DIRECTORY_SEPARATOR . $tpl;
				if (is_file($filename)) {
					return $tpl;
				}
			}
		}

		return $view . 'Base' . '.tpl';
	}

	/**
	 * Getting default label.
	 *
	 * @return string defaultLabel
	 */
	public function getDefaultLabel()
	{
		return $this->defaultLabel;
	}

	/**
	 * Getting field type.
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Getting column name.
	 *
	 * @return string columnName
	 */
	public function getColumnName()
	{
		return $this->has('columnname') ? $this->get('columnname') : $this->columnName;
	}

	/**
	 * Getting field name.
	 *
	 * @return string field name
	 */
	public function getName(): string
	{
		return $this->getColumnName();
	}

	/**
	 * Get label.
	 *
	 * @return string
	 */
	public function getLabel(): string
	{
		return $this->get('label');
	}

	/**
	 * Getting column name.
	 *
	 * @return array customColumn
	 */
	public function getCustomColumn()
	{
		$columns = [];
		$schame = \App\Db::getInstance()->getSchema();
		foreach ($this->customColumn as $name => $columnCriteria) {
			if (\is_array($columnCriteria)) {
				[$type, $length, $default, $unsigned] = array_pad($columnCriteria, 4, null);
				$columnCriteria = $schame->createColumnSchemaBuilder($type, $length);
				if (null !== $default) {
					$columnCriteria->defaultValue($default);
				}
				if (null !== $unsigned) {
					$columnCriteria->unsigned();
				}
			}
			$columns[$name] = $columnCriteria;
		}

		return $columns;
	}

	/**
	 * Check if the field is summed up.
	 *
	 * @return bool
	 */
	public function isSummary(): bool
	{
		return $this->summationValue;
	}

	/**
	 * Check if summary enabled.
	 *
	 * @return bool
	 */
	public function isSummaryEnabled(): bool
	{
		return $this->isSummary() && 1 === (int) ($this->getParamConfig('summary_enabled') ?? 1);
	}

	/**
	 * Gets default value by field.
	 *
	 * @param string $columnName
	 *
	 * @return mixed
	 */
	public function getDefaultValue(string $columnName = '')
	{
		if (!$columnName || $columnName === $this->getColumnName()) {
			$value = $this->has('defaultvalue') ? $this->get('defaultvalue') : $this->defaultValue;
		} else {
			$value = $this->customDefault[$columnName] ?? '';
		}

		return $value;
	}

	/**
	 * Getting value to display.
	 *
	 * @param mixed $value
	 * @param array $rowData
	 * @param bool  $rawText
	 *
	 * @return string
	 */
	public function getDisplayValue($value, array $rowData = [], bool $rawText = false)
	{
		return $value ? \App\Purifier::encodeHtml($value) : '';
	}

	/**
	 * Function to get the list value in display view.
	 *
	 * @param mixed $value
	 * @param array $rowData
	 * @param bool  $rawText
	 *
	 * @return mixed
	 */
	public function getListViewDisplayValue($value, array $rowData = [], bool $rawText = false)
	{
		return $this->getDisplayValue($value, $rowData, $rawText);
	}

	/**
	 * Get value for Edit view.
	 *
	 * @param array  $itemData
	 * @param string $column
	 *
	 * @return string|int
	 */
	public function getEditValue(array $itemData, string $column = '')
	{
		if (!$column) {
			$column = $this->getColumnName();
		}
		$value = '';
		if (isset($itemData[$column])) {
			$value = $itemData[$column];
		} elseif (($default = \App\Config::module($this->getModuleName(), 'defaultInventoryData', [])[$column] ?? null) !== null) {
			$value = $default;
		} elseif ($column === $this->getColumnName()) {
			$value = $this->getDefaultValue();
		}

		return $value;
	}

	/**
	 * Function to check if the current field is mandatory or not.
	 *
	 * @return bool
	 */
	public function isMandatory()
	{
		return true;
	}

	/**
	 * Function to check whether the current field is visible.
	 *
	 * @return bool
	 */
	public function isVisible()
	{
		return self::FIELD_HIDDEN !== $this->get('displaytype');
	}

	/**
	 * Function to check if field is visible in detail view.
	 *
	 * @return bool
	 */
	public function isVisibleInDetail()
	{
		return \in_array($this->get('displaytype'), [self::FIELD_VISIBLE_EVERYWHERE, self::FIELD_READONLY, self::FIELD_VISIBLE_IN_DETAIL]);
	}

	/**
	 * Function to check whether the current field is editable.
	 *
	 * @return bool
	 */
	public function isEditable(): bool
	{
		return \in_array($this->get('displaytype'), [self::FIELD_VISIBLE_EVERYWHERE, self::FIELD_READONLY]);
	}

	/**
	 * Function checks if the field is read-only.
	 *
	 * @return bool
	 */
	public function isReadOnly()
	{
		return self::FIELD_READONLY === $this->get('displaytype');
	}

	/** {@inheritdoc} */
	public function set($key, $value, $register = false)
	{
		if ($this->getId() && !\in_array($key, ['id']) && (\array_key_exists($key, $this->value) && $this->value[$key] != $value)) {
			$this->changes[$key] = $this->get($key);
		}
		return parent::set($key, $value);
	}

	/**
	 * Sum the field value for each row.
	 *
	 * @param array    $data
	 * @param int|null $groupId
	 *
	 * @return int|float
	 */
	public function getSummaryValuesFromData($data, ?int $groupId = null)
	{
		$sum = 0;
		if (\is_array($data)) {
			foreach ($data as $row) {
				if (null !== $groupId && $groupId !== $row['groupid'] ?? -1) {
					continue;
				}
				$sum += $row[$this->getColumnName()];
			}
		}
		return $sum;
	}

	/**
	 * Gets relation field.
	 *
	 * @param string $related
	 *
	 * @throws \App\Exceptions\AppException
	 *
	 * @return bool|\Vtiger_Field_Model
	 */
	public function getMapDetail(string $related)
	{
		$inventory = Vtiger_Inventory_Model::getInstance($this->getModuleName());
		$fields = $inventory->getAutoCompleteFields();
		$field = false;
		if ($mapDetail = $fields[$related][$this->getColumnName()] ?? false) {
			$moduleModel = Vtiger_Module_Model::getInstance($related);
			$field = Vtiger_Field_Model::getInstance($mapDetail['field'], $moduleModel);
		}
		return $field;
	}

	public function getFieldDataType()
	{
		return $this->fieldDataType;
	}

	/**
	 * Gets database value.
	 *
	 * @param mixed       $value
	 * @param string|null $name
	 *
	 * @return mixed
	 */
	public function getDBValue($value, ?string $name = '')
	{
		return $value;
	}

	/**
	 * Verification of data.
	 *
	 * @param mixed  $value
	 * @param string $columnName
	 * @param bool   $isUserFormat
	 * @param mixed  $originalValue
	 *
	 * @throws \App\Exceptions\Security
	 */
	public function validate($value, string $columnName, bool $isUserFormat, $originalValue = null)
	{
		if (!is_numeric($value) && (\is_string($value) && $value !== strip_tags($value))) {
			throw new \App\Exceptions\Security('ERR_ILLEGAL_FIELD_VALUE||' . ($columnName ?? $this->getColumnName()) . '||' . $this->getModuleName() . '||' . $value, 406);
		}
		if (App\TextUtils::getTextLength($value) > $this->maximumLength) {
			throw new \App\Exceptions\Security('ERR_VALUE_IS_TOO_LONG||' . ($columnName ?? $this->getColumnName()) . '||' . $this->getModuleName() . '||' . $value, 406);
		}
	}

	/**
	 * Sets default data config.
	 *
	 * @return $this
	 */
	public function setDefaultDataConfig()
	{
		$this->set('columnname', $this->columnName)
			->set('label', $this->defaultLabel)
			->set('presence', 0)
			->set('defaultvalue', $this->defaultValue)
			->set('displaytype', $this->displayType)
			->set('invtype', $this->type)
			->set('block', current($this->blocks))
			->set('colspan', $this->colSpan);

		return $this;
	}

	/**
	 * Field required to make an entry.
	 *
	 * @return bool
	 */
	public function isRequired()
	{
		return false;
	}

	/**
	 * Sets value data.
	 *
	 * @param \Vtiger_Record_Model $recordModel
	 * @param array                $item
	 * @param bool                 $userFormat
	 *
	 * @throws \App\Exceptions\AppException
	 * @throws \App\Exceptions\Security
	 */
	public function setValueToRecord(Vtiger_Record_Model $recordModel, array $item, bool $userFormat)
	{
		$column = $this->getColumnName();
		$baseValue = $item[$column] ?? null;
		$value = $this->getValueForSave($item, $userFormat, $column);
		if ($userFormat && $baseValue) {
			$baseValue = $this->getDBValue($baseValue, $column);
		}
		$this->validate($value, $column, false, $baseValue);

		$itemId = $item['id'];
		$addToChanges = !$recordModel->isNew() && is_numeric($itemId) && !$this->compare($value, $recordModel->getInventoryData()[$itemId][$column] ?? '', $column);
		$recordModel->setInventoryItemPart($itemId, $column, $value, $addToChanges);
		if ($customColumn = $this->getCustomColumn()) {
			foreach (array_keys($customColumn) as $column) {
				$value = $this->getValueForSave($item, $userFormat, $column);
				$this->validate($value, $column, false);
				$addToChanges = !$recordModel->isNew() && is_numeric($itemId) && !$this->compare($value, $recordModel->getInventoryData()[$itemId][$column] ?? '', $column);
				$recordModel->setInventoryItemPart($itemId, $column, $value, $addToChanges);
			}
		}
	}

	/**
	 * Compare two values.
	 *
	 * @param mixed  $value
	 * @param mixed  $prevValue
	 * @param string $column
	 *
	 * @return bool
	 */
	public function compare($value, $prevValue, string $column): bool
	{
		return (string) $value === (string) $prevValue;
	}

	/**
	 * Gets purify type.
	 *
	 * @return array
	 */
	public function getPurifyType()
	{
		return [$this->getColumnName() => $this->purifyType] + $this->customPurifyType;
	}

	/**
	 * Get information about field.
	 *
	 * @return array
	 */
	public function getFieldInfo(): array
	{
		return [
			'maximumlength' => $this->maximumLength
		];
	}

	/**
	 * Get maximum length by column.
	 *
	 * @param string $columnName
	 *
	 * @return int|string
	 */
	public function getMaximumLengthByColumn(string $columnName)
	{
		if ($columnName === $this->getColumnName()) {
			$value = $this->maximumLength;
		} else {
			$value = $this->customMaximumLength[$columnName] ?? '';
		}

		return $value;
	}

	/**
	 * Get pervious value by field.
	 *
	 * @param string $fieldName
	 *
	 * @return mixed
	 */
	public function getPreviousValue(string $fieldName = '')
	{
		return $fieldName ? ($this->changes[$fieldName] ?? null) : $this->changes;
	}

	/**
	 * Check if the field is synchronized.
	 *
	 * @return bool
	 */
	public function isSync(): bool
	{
		return $this->sync;
	}

	/**
	 * Gets config fields data.
	 *
	 * @return array
	 */
	public function getConfigFieldsData(): array
	{
		$row = [
			'invtype' => [
				'name' => 'invtype',
				'column' => 'invtype',
				'uitype' => 1,
				'label' => 'LBL_NAME_FIELD',
				'maximumlength' => '30',
				'typeofdata' => 'V~M',
				'purifyType' => \App\Purifier::STANDARD,
				'isEditableReadOnly' => true,
			],
			'label' => [
				'name' => 'label',
				'label' => 'LBL_LABEL_NAME',
				'uitype' => 1,
				'maximumlength' => '50',
				'typeofdata' => 'V~M',
				'purifyType' => \App\Purifier::TEXT,
			],
			'displaytype' => [
				'name' => 'displaytype',
				'label' => 'LBL_DISPLAY_TYPE',
				'uitype' => 16,
				'maximumlength' => '127',
				'typeofdata' => 'V~M',
				'purifyType' => \App\Purifier::INTEGER,
				'picklistValues' => [],
			],
			'colspan' => [
				'name' => 'colspan',
				'label' => 'LBL_COLSPAN',
				'uitype' => 7,
				'maximumlength' => '0,100',
				'typeofdata' => 'N~M',
				'purifyType' => \App\Purifier::INTEGER,
				'tooltip' => 'LBL_MAX_WIDTH_COLUMN_INFO'
			]
		];

		$qualifiedModuleName = 'Settings:LayoutEditor';
		foreach ($this->displayTypeBase() as $key => $value) {
			$row['displaytype']['picklistValues'][$key] = \App\Language::translate($value, $qualifiedModuleName);
		}

		if ($this->isSummary()) {
			$row['summary_enabled'] = [
				'name' => 'summary_enabled',
				'label' => 'LBL_INV_SUMMARY_ENABLED',
				'uitype' => 56,
				'maximumlength' => '1',
				'typeofdata' => 'C~O',
				'purifyType' => \App\Purifier::INTEGER,
				'defaultvalue' => 1
			];
		}

		return $row;
	}

	/**
	 * Gets config fields.
	 *
	 * @return Vtiger_Field_Model[]
	 */
	public function getConfigFields(): array
	{
		$module = 'LayoutEditor';
		$fields = [];
		foreach ($this->getConfigFieldsData() as $name => $data) {
			$fieldModel = \Vtiger_Field_Model::init($module, $data, $name);
			if (null !== $this->get($name)) {
				$fieldModel->set('fieldvalue', $this->get($name));
			} elseif (isset($this->getParamsConfig()[$name])) {
				$fieldModel->set('fieldvalue', $this->getParamsConfig()[$name]);
			} elseif (property_exists($this, $name) && null !== $this->{$name} && '' !== $this->{$name}) {
				$fieldModel->set('fieldvalue', $this->{$name});
			} elseif (($default = $fieldModel->get('defaultvalue')) !== null) {
				$fieldModel->set('fieldvalue', $default);
			}
			$fields[$name] = $fieldModel;
		}

		return $fields;
	}

	/**
	 * Gets config field.
	 *
	 * @param string $key
	 *
	 * @return Vtiger_Field_Model|null
	 */
	public function getConfigField(string $key): ?Vtiger_Field_Model
	{
		return $this->getConfigFields()[$key] ?? null;
	}
}
