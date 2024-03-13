<?php

namespace App\TextParser;

/**
 * Products table new class.
 *
 * @package TextParser
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author    Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class ProductsTableNew extends Base
{
	/** @var string Class name */
	public $name = 'LBL_PRODUCTS_TABLE_NEW';

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
		$inventory = \Vtiger_Inventory_Model::getInstance($this->textParser->moduleName);
		$inventoryRows = $this->textParser->recordModel->getInventoryData();

		$currencyId = current($inventoryRows)['currency'] ?? null;
		if (!$currencyId) {
			$currencyId = \App\Fields\Currency::getDefault()['id'];
			foreach ($inventoryRows as &$row) {
				$row['currency'] = $currencyId;
			}
		}
		$currencySymbol = \App\Fields\Currency::getById($currencyId)['currency_symbol'];

		$headerStyle = 'font-size:9px;padding:0px 4px;text-align:center;';
		$bodyStyle = 'font-size:8px;border:1px solid #ddd;padding:0px 4px;text-align:center;';
		$html .= '<table class="products-table-new" style="width:100%;border-collapse:collapse;"><thead><tr>';
		$groupModels = [];
		foreach (['ItemNumber', 'Name', 'Quantity', 'Value', 'UnitPrice', 'TotalPrice', 'NetPrice', 'Tax', 'GrossPrice'] as $fieldType) {
			foreach ($inventory->getFieldsByType($fieldType) as $fieldModel) {
				if (!$fieldModel->isVisible()) {
					continue;
				}
				$html .= "<th class=\"col-type-{$fieldModel->getType()}\" style=\"{$headerStyle}\">" . \App\Language::translate($fieldModel->get('label'), $this->textParser->moduleName) . '</th>';
				$groupModels[$fieldModel->getColumnName()] = $fieldModel;
			}
		}
		$html .= '</tr></thead>';
		if (!empty($groupModels)) {
			$groupField = $inventory->getField('grouplabel');
			$count = \count($groupModels);
			$html .= '<tbody>';
			$number = 0;
			$counter = 0;
			foreach ($inventory->transformData($inventoryRows) as $inventoryRow) {
				if (!empty($inventoryRow['add_header']) && $groupField && $groupField->isVisible() && !empty($blockLabel = $inventoryRow['grouplabel'])) {
					++$number;
					$html .= "<tr class=\"row-{$number}\"><td colspan=\"{$count}\" style=\"font-size:8px;border:1px solid #ddd;padding:2px 6px;font-weight:bold;\">" . \App\Purifier::encodeHtml($groupField->getDisplayValue($blockLabel, $inventoryRow, true)) . '</td></tr>';
				}
				++$number;
				++$counter;
				$html .= '<tr class="row-' . $number . '">';
				foreach ($groupModels as $fieldModel) {
					$columnName = $fieldModel->getColumnName();
					$typeName = $fieldModel->getType();
					$fieldStyle = $bodyStyle;

					if ('ItemNumber' === $typeName) {
						$html .= "<td class=\"col-type-ItemNumber\" style=\"{$fieldStyle}\">{$counter}</td>";
					} elseif ('ean' === $columnName) {
						$code = $inventoryRow[$columnName];
						$html .= "<td class=\"col-type-barcode\" style=\"{$fieldStyle}font-weight:bold;\"><div data-barcode=\"EAN13\" data-code=\"{$code}\" data-size=\"1\" data-height=\"16\">{$code}</div></td>";
					} else {
						$itemValue = $inventoryRow[$columnName];
						if ('Name' === $typeName) {
							$fieldStyle = $bodyStyle . 'text-align:left;';
							$fieldValue = '<strong>' . \App\Purifier::encodeHtml($fieldModel->getDisplayValue($itemValue, $inventoryRow, true)) . '</strong>';
							foreach ($inventory->getFieldsByType('Comment') as $commentField) {
								if ($commentField->isVisible() && ($value = $inventoryRow[$commentField->getColumnName()]) && $comment = $commentField->getDisplayValue($value, $inventoryRow, true)) {
									$fieldValue .= '<br />' . $comment;
								}
							}
						} elseif (\in_array($typeName, ['TotalPrice', 'Tax', 'MarginP', 'Margin', 'Purchase', 'Discount', 'NetPrice', 'GrossPrice', 'UnitPrice'])) {
							$fieldValue = $fieldModel->getDisplayValue($itemValue, $inventoryRow);
							$fieldStyle = $bodyStyle . 'text-align:right;white-space: nowrap;';
						} else {
							$fieldValue = \App\Purifier::encodeHtml($fieldModel->getDisplayValue($itemValue, $inventoryRow, true));
						}
						$html .= "<td class=\"col-type-{$typeName}\" style=\"{$fieldStyle}\">{$fieldValue}</td>";
					}
				}
				$html .= '</tr>';
			}
			$html .= '</tbody><tfoot><tr>';
			foreach ($groupModels as $fieldModel) {
				$headerStyle = 'font-size:7px;padding:0px 4px;text-align:center;';
				$html .= "<th class=\"col-type-{$fieldModel->getType()}\" style=\"{$headerStyle}text-align:right;\">";
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
		}

		return $html;
	}
}
