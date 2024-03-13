<?php

/**
 * Inventory GroupLabel Field file.
 *
 * @package   InventoryField
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
/**
 * Inventory GroupLabel Field Class.
 */
class Vtiger_GroupLabel_InventoryField extends Vtiger_Basic_InventoryField
{
	/** {@inheritdoc} */
	protected $type = 'GroupLabel';
	/** {@inheritdoc} */
	protected $defaultLabel = 'LBL_INV_GROUP_LABEL';
	/** {@inheritdoc} */
	protected $columnName = 'grouplabel';
	/** {@inheritdoc} */
	protected $dbType = [\yii\db\Schema::TYPE_STRING, 255, ''];
	/** {@inheritdoc} */
	protected $onlyOne = true;
	/** {@inheritdoc} */
	protected $purifyType = \App\Purifier::TEXT;
	/** {@inheritdoc} */
	protected $blocks = [2];
	/** {@inheritdoc} */
	protected $maximumLength = '255';
	/** {@inheritdoc} */
	protected $sync = true;
	/** {@inheritdoc} */
	protected $params = ['isOpened', 'group_values'];
	/** {@inheritdoc} */
	protected $customColumn = [
		'groupid' => [\yii\db\Schema::TYPE_INTEGER, 10, 0, true]
	];
	/** {@inheritdoc} */
	protected $customMaximumLength = [
		'groupid' => '0,4294967295'
	];
	/** {@inheritdoc} */
	protected $customPurifyType = [
		'groupid' => \App\Purifier::INTEGER
	];
	/** {@inheritdoc} */
	protected $customDefault = [
		'groupid' => 0
	];

	/** {@inheritdoc} */
	public function getDBValue($value, ?string $name = '')
	{
		return \App\Purifier::decodeHtml($value);
	}

	/** {@inheritdoc} */
	public function getDisplayValue($value, array $rowData = [], bool $rawText = false)
	{
		return $rawText ? $value : \App\Purifier::encodeHtml($value);
	}

	/** {@inheritdoc} */
	public function validate($value, string $columnName, bool $isUserFormat, $originalValue = null)
	{
		if ($columnName === $this->getColumnName()) {
			parent::validate($value, $columnName, $isUserFormat, $originalValue);
		} else {
			if (!is_numeric($value)) {
				throw new \App\Exceptions\Security('ERR_ILLEGAL_FIELD_VALUE||' . $columnName . '||' . $this->getModuleName() . '||' . \App\Utils::varExport($value), 406);
			}
			$length = $this->getMaximumLengthByColumn($columnName);
			[$minimumLength, $maximumLength] = false !== strpos($length, ',') ? explode(',', $length) : [-$length, $length];
			if ($length && (int) $minimumLength > $value || (int) $maximumLength < $value) {
				throw new \App\Exceptions\Security('ERR_VALUE_IS_TOO_LONG||' . $columnName . '||' . $this->getModuleName() . '||' . $value, 406);
			}
		}
	}

	/**
	 * Check if the block should be expanded.
	 *
	 * @return bool
	 */
	public function isOpened(): bool
	{
		return 1 === (int) ($this->getParamsConfig()['isOpened'] ?? 1);
	}

	/** {@inheritdoc} */
	public function getConfigFieldsData(): array
	{
		$data = parent::getConfigFieldsData();
		unset($data['colspan']);

		$data['isOpened'] = [
			'name' => 'isOpened',
			'label' => 'LBL_INV_BLOCK_IS_OPENED',
			'uitype' => 56,
			'maximumlength' => '1',
			'typeofdata' => 'V~O',
			'tooltip' => 'LBL_INV_BLOCK_IS_OPENED_INFO',
			'purifyType' => \App\Purifier::INTEGER,
			'defaultvalue' => 1
		];

		$groups = $this->getParamConfig('group_values');
		$values = $groups ? explode(' |##| ', $groups) : [];
		$data['group_values'] = [
			'name' => 'group_values',
			'label' => 'LBL_INV_GROUP_VALUES',
			'uitype' => 33,
			'maximumlength' => '1',
			'typeofdata' => 'V~O',
			'createTags' => true,
			'tooltip' => 'LBL_INV_GROUP_VALUES_DESC',
			'purifyType' => \App\Purifier::TEXT,
			'picklistValues' => array_map(fn ($key) => \App\Language::translate($key, $this->getModuleName(), null, false), array_combine($values, $values)),
			'fieldvalue' => $groups,
		];

		return $data;
	}

	/** {@inheritdoc} */
	public function compare($value, $prevValue, string $column): bool
	{
		return $column === $this->getColumnName() ? parent::compare($value, $prevValue, $column) : (int) $value === (int) $prevValue;
	}
}
