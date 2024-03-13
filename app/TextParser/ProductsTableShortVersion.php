<?php

namespace App\TextParser;

/**
 * Products table short version class.
 *
 * @package TextParser
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Arkadiusz Sołek <a.solek@yetiforce.com>
 * @author    Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class ProductsTableShortVersion extends Base
{
	/** @var string Class name */
	public $name = 'LBL_PRODUCTS_TABLE_SHORT_VERSION';

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

		$headerStyle = 'font-size:9px;padding:0px 4px;text-align:center;background-color:#ddd;';
		$bodyStyle = 'font-size:8px;border:1px solid #ddd;padding:0px 4px;';
		$html .= '<table class="products-table-long-version" style="width:100%;font-size:8px;border-collapse:collapse;">
				<thead>
					<tr>';
		$groupModels = [];
		foreach (['ItemNumber', 'Name',  'Quantity', 'UnitPrice', 'TotalPrice', 'GrossPrice']  as $fieldType) {
			foreach ($inventory->getFieldsByType($fieldType) as $fieldModel) {
				$columnName = $fieldModel->getColumnName();
				if (!$fieldModel->isVisible()) {
					continue;
				}
				$headerStyle2 = 'ItemNumber' === $fieldType ? $headerStyle . ';width:1%;' : $headerStyle;
				$html .= "<th style=\"{$headerStyle2}\">" . \App\Language::translate($fieldModel->getLabel(), $this->textParser->moduleName) . '</th>';
				$groupModels[$columnName] = $fieldModel;
			}
		}
		$html .= '</tr></thead>';
		if (!empty($groupModels)) {
			$groupField = $inventory->getField('grouplabel');
			$count = \count($groupModels);
			$html .= '<tbody>';
			$counter = 0;
			foreach ($inventory->transformData($inventoryRows) as $inventoryRow) {
				if (!empty($inventoryRow['add_header']) && $groupField && $groupField->isVisible() && !empty($blockLabel = $inventoryRow['grouplabel'])) {
					$html .= "<tr><td colspan=\"{$count}\" style=\"font-size:8px;border:1px solid #ddd;padding:2px 6px;font-weight:bold;\">" . \App\Purifier::encodeHtml($groupField->getDisplayValue($blockLabel, $inventoryRow, true)) . '</td></tr>';
				}
				++$counter;
				$html .= '<tr class="row-' . $counter . '">';
				foreach ($groupModels as $fieldModel) {
					$columnName = $fieldModel->getColumnName();
					$typeName = $fieldModel->getType();
					$fieldStyle = $bodyStyle;
					if ('ItemNumber' === $typeName) {
						$html .= "<td style=\"{$bodyStyle}text-align:center;width:1%;\">" . $counter . '</td>';
					} elseif ('ean' === $columnName) {
						$code = $inventoryRow[$columnName];
						$html .= "<td class=\"col-type-barcode\" style=\"{$fieldStyle}font-weight:bold;text-align:center;\"><div data-barcode=\"EAN13\" data-code=\"{$code}\" data-size=\"1\" data-height=\"16\">{$code}</div></td>";
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
						} elseif (\in_array($typeName, ['Quantity', 'GrossPrice', 'UnitPrice', 'TotalPrice']) && !empty($currencySymbol)) {
							$fieldValue = $fieldModel->getDisplayValue($itemValue, $inventoryRow);
							$fieldStyle = $bodyStyle . 'text-align:right;white-space: nowrap;';
						} else {
							$fieldValue = \App\Purifier::encodeHtml($fieldModel->getDisplayValue($itemValue, $inventoryRow, true));
						}
						$html .= "<td class=\"col-type-{$typeName}\" style=\"{$fieldStyle}\">" . $fieldValue . '</td>';
					}
				}
				$html .= '</tr>';
			}
			$html .= '</tbody><tfoot><tr>';
			foreach ($groupModels as $fieldModel) {
				$headerStyle = 'font-size:7px;padding:0px 4px;text-align:center;background-color:#ddd;';
				$html .= "<th class=\"col-type-{$typeName}\" style=\"{$headerStyle}white-space: nowrap;\">";
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
