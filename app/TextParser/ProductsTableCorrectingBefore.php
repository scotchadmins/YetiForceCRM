<?php

namespace App\TextParser;

/**
 * Products table correcting before class.
 *
 * @package TextParser
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 * @author    Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class ProductsTableCorrectingBefore extends Base
{
	/** @var string Class name */
	public $name = 'LBL_PRODUCTS_TABLE_CORRECTING_BEFORE';

	/** @var mixed Parser type */
	public $type = 'pdf';

	/**
	 * Process.
	 *
	 * @return string
	 */
	public function process()
	{
		$html = '';
		if (!$this->textParser->recordModel->getModule()->isInventory()) {
			return $html;
		}
		$beforeRecordModel = \Vtiger_Record_Model::getInstanceById($this->textParser->recordModel->get('finvoiceid'));
		$inventory = \Vtiger_Inventory_Model::getInstance($beforeRecordModel->getModuleName());
		$inventoryRows = $beforeRecordModel->getInventoryData();

		$currencyId = current($inventoryRows)['currency'] ?? null;
		if (!$currencyId) {
			$currencyId = \App\Fields\Currency::getDefault()['id'];
			foreach ($inventoryRows as &$row) {
				$row['currency'] = $currencyId;
			}
		}
		$currencySymbol = \App\Fields\Currency::getById($currencyId)['currency_symbol'];
		$firstRow = current($inventoryRows) ?: [];

		$headerStyle = 'font-size:9px;padding:0px 4px;text-align:center;background-color:#ddd;';
		$bodyStyle = 'font-size:8px;border:1px solid #ddd;padding:0px 4px;';
		$html .= '<table class="products-table-correcting-before" style="border-collapse:collapse;width:100%"><thead><tr>';
		$groupModels = [];
		foreach (['Name', 'Quantity', 'UnitPrice', 'TotalPrice', 'Discount', 'NetPrice', 'Currency', 'DiscountMode', 'Tax', 'TaxMode', 'GrossPrice', 'Value'] as $fieldType) {
			foreach ($inventory->getFieldsByType($fieldType) as $fieldModel) {
				$columnName = $fieldModel->getColumnName();
				if (!$fieldModel->isVisible()) {
					continue;
				}
				if (\in_array($fieldModel->getType(), ['Currency', 'DiscountMode', 'TaxMode'])) {
					$html .= "<th style=\"{$headerStyle}\">" . \App\Language::translate($fieldModel->getLabel(), $this->textParser->moduleName) . ': ' . $fieldModel->getDisplayValue($firstRow[$columnName] ?? '', $firstRow) . '</th>';
				} else {
					$groupModels[$columnName] = $fieldModel;
				}
			}
		}
		$html .= '</tr></thead></table>';
		$html .= '<table class="products-table-header" style="border-collapse:collapse;width:100%;"><thead><tr>';
		foreach ($groupModels as $fieldModel) {
			$html .= "<th style=\"{$headerStyle}\">" . \App\Language::translate($fieldModel->getLabel(), $this->textParser->moduleName) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		$counter = 1;
		$groupField = $inventory->getField('grouplabel');
		$count = \count($groupModels);
		foreach ($inventory->transformData($inventoryRows) as $inventoryRow) {
			if (!empty($inventoryRow['add_header']) && $groupField && $groupField->isVisible() && !empty($blockLabel = $inventoryRow['grouplabel'])) {
				$html .= "<tr><td colspan=\"{$count}\" style=\"font-size:8px;border:1px solid #ddd;padding:2px 6px;font-weight:bold;\">" . \App\Purifier::encodeHtml($groupField->getDisplayValue($blockLabel, $inventoryRow, true)) . '</td></tr>';
			}
			$html .= '<tr>';
			foreach ($groupModels as $fieldModel) {
				$columnName = $fieldModel->getColumnName();
				$typeName = $fieldModel->getType();
				$styleField = $bodyStyle;
				if ('ItemNumber' === $typeName) {
					$html .= "<td style=\"{$bodyStyle}\">" . $counter++ . '</td>';
				} elseif ('ean' === $columnName) {
					$code = $inventoryRow[$columnName];
					$html .= "<td style=\"{$bodyStyle}\"><div data-barcode=\"EAN13\" data-code=\"{$code}\" data-size=\"1\" data-height=\"16\">{$code}</div></td>";
				} else {
					$itemValue = $inventoryRow[$columnName];
					if ('Name' === $typeName) {
						$fieldValue = '<strong>' . $fieldModel->getDisplayValue($itemValue, $inventoryRow, true) . '</strong>';
						foreach ($inventory->getFieldsByType('Comment') as $commentField) {
							$commentFieldName = $commentField->getColumnName();
							if ($inventory->isField($commentFieldName) && $commentField->isVisible() && ($value = $inventoryRow[$commentFieldName]) && $comment = $commentField->getDisplayValue($value, $inventoryRow, true)) {
								$fieldValue .= '<br />' . $comment;
							}
						}
					} elseif (\in_array($typeName, ['TotalPrice', 'Purchase', 'NetPrice', 'GrossPrice', 'UnitPrice', 'Discount', 'Margin', 'Tax'])) {
						$fieldValue = $fieldModel->getDisplayValue($itemValue, $inventoryRow);
						$styleField = $bodyStyle . ' text-align:right;white-space: nowrap;';
					} else {
						$fieldValue = $fieldModel->getDisplayValue($itemValue, $inventoryRow);
					}
					$html .= "<td class=\"col-type-{$typeName}\" style=\"{$styleField}\">{$fieldValue}</td>";
				}
			}
			$html .= '</tr>';
		}
		$html .= '</tbody><tfoot><tr>';
		foreach ($groupModels as $fieldModel) {
			$html .= '<th style="font-size:9px;padding:0px 4px;text-align:right;background-color:#ddd;white-space: nowrap;">';
			if ($fieldModel->isSummary()) {
				$sum = 0;
				foreach ($inventoryRows as $inventoryRow) {
					$sum += $inventoryRow[$fieldModel->getColumnName()];
				}
				$html .= \CurrencyField::appendCurrencySymbol(\CurrencyField::convertToUserFormat($sum, null, true), $currencySymbol);
			}
			$html .= '</th>';
		}
		$html .= '</tr></tfoot></table>';
		return $html;
	}
}
