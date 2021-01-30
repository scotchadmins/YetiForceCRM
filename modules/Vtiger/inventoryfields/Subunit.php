<?php

/**
 * Inventory Subunit Field Class.
 *
 * @package   InventoryField
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Davide Alghi <davide@penguinable.it>
 */
class Vtiger_Subunit_InventoryField extends Vtiger_Basic_InventoryField
{
	protected $type = 'Subunit';
	protected $defaultLabel = 'FL_SUBUNIT';
	protected $columnName = 'subunit';
	protected $dbType = 'string';
	protected $onlyOne = true;
	protected $purifyType = \App\Purifier::TEXT;

	/**
	 * {@inheritdoc}
	 */
	public function getDisplayValue($value, array $rowData = [], bool $rawText = false)
	{
		if (($rel = $rowData['name'] ?? '') && (($type = \App\Record::getType($rel)) && $mapDetail = $this->getMapDetail($type))) {
			$value = $mapDetail->getDisplayValue($value, false, false, $rawText);
		}
		return $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getEditValue($value)
	{
		return \App\Purifier::encodeHtml($value);
	}
}