<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce S.A.
 * *********************************************************************************** */

class Vtiger_Multipicklist_UIType extends Vtiger_Base_UIType
{
	/** @var string Value separator in the database */
	const SEPARATOR = ' |##| ';

	/** {@inheritdoc} */
	public function getDbConditionBuilderValue($value, string $operator)
	{
		$values = [];
		if (!\is_array($value)) {
			$value = $value ? explode('##', $value) : [];
		}
		foreach ($value as $val) {
			$values[] = parent::getDbConditionBuilderValue($val, $operator);
		}
		return implode('##', $values);
	}

	/** {@inheritdoc} */
	public function getDBValue($value, $recordModel = false)
	{
		if (\is_array($value)) {
			$value = implode(self::SEPARATOR, $value);
		}
		return \App\Purifier::decodeHtml($value);
	}

	/** {@inheritdoc} */
	public function validate($value, $isUserFormat = false)
	{
		$hashValue = '';
		if (\is_string($value)) {
			$hashValue = $value;
			$value = explode(self::SEPARATOR, $value);
		} elseif (\is_array($value)) {
			$hashValue = implode(self::SEPARATOR, $value);
		}

		if (empty($value) || isset($this->validate[$hashValue])) {
			return;
		}
		if (!\is_array($value)) {
			throw new \App\Exceptions\Security('ERR_ILLEGAL_FIELD_VALUE||' . $this->getFieldModel()->getName() . '||' . $this->getFieldModel()->getModuleName() . '||' . $value, 406);
		}
		foreach ($value as $item) {
			if (!\is_string($item)) {
				throw new \App\Exceptions\Security('ERR_ILLEGAL_FIELD_VALUE||' . $this->getFieldModel()->getName() . '||' . $this->getFieldModel()->getModuleName() . '||' . $value, 406);
			}
			if ($item != strip_tags($item)) {
				throw new \App\Exceptions\Security('ERR_ILLEGAL_FIELD_VALUE||' . $this->getFieldModel()->getName() . '||' . $this->getFieldModel()->getModuleName() . '||' . $value, 406);
			}
		}
		$maximumLength = $this->getFieldModel()->get('maximumlength');
		if ($hashValue && $maximumLength && App\TextUtils::getTextLength($hashValue) > $maximumLength) {
			throw new \App\Exceptions\Security('ERR_VALUE_IS_TOO_LONG||' . $this->getFieldModel()->getName() . '||' . $this->getFieldModel()->getModuleName() . '||' . $hashValue, 406);
		}

		$this->validate[$hashValue] = true;
	}

	/** {@inheritdoc} */
	public function getDisplayValue($value, $record = false, $recordModel = false, $rawText = false, $length = false)
	{
		if (empty($value)) {
			return null;
		}
		$valueRaw = $valueHtml = '';
		$values = explode(self::SEPARATOR, $value);
		$trValueRaw = $trValue = [];
		$moduleName = $this->getFieldModel()->getModuleName();
		$fieldName = App\Colors::sanitizeValue($this->getFieldModel()->getName());
		foreach ($values as $value) {
			$displayValue = App\Language::translate($value, $moduleName);
			if ($icon = \App\Fields\Picklist::getIcon($this->getFieldModel()->getName(), $value) ?: '') {
				['type' => $type, 'name' => $name] = $icon;
				$icon = '';
				if ('icon' === $type) {
					$icon = "<span class=\"{$name} mr-1\"></span>";
				} elseif ('image' === $type && ($src = \App\Layout\Media::getImageUrl($name))) {
					$icon = '<img class="icon-img--picklist mr-1" src="' . $src . '">';
				}
			}
			$value = App\Colors::sanitizeValue($value);
			$trValueRaw[] = $displayValue;
			$trValue[] = "<span class=\"picklistValue picklistLb_{$moduleName}_{$fieldName}_{$value}\">{$icon}{$displayValue}</span>";
		}
		if ($rawText) {
			$valueRaw = str_ireplace(self::SEPARATOR, ', ', implode(self::SEPARATOR, $trValueRaw));
			if (\is_int($length)) {
				$valueRaw = \App\TextUtils::textTruncate($valueRaw, $length);
			}
		} else {
			$valueHtml = str_ireplace(self::SEPARATOR, ' ', implode(self::SEPARATOR, $trValue));
			if (\is_int($length)) {
				$valueHtml = \App\TextUtils::htmlTruncateByWords($valueHtml, $length);
			}
		}
		return $rawText ? $valueRaw : $valueHtml;
	}

	/** {@inheritdoc} */
	public function getEditViewDisplayValue($value, $recordModel = false)
	{
		if (\is_array($value)) {
			return $value;
		}

		return $value ? explode(self::SEPARATOR, \App\Purifier::encodeHtml($value)) : [];
	}

	/** {@inheritdoc} */
	public function getValueFromImport($value, $defaultValue = null)
	{
		$trimmedValue = trim($value);
		if ('' === $trimmedValue) {
			return $defaultValue ?? '';
		}
		$explodedValue = explode(self::SEPARATOR, $trimmedValue);
		foreach ($explodedValue as $key => $value) {
			$explodedValue[$key] = trim($value);
		}
		return implode(self::SEPARATOR, $explodedValue);
	}

	/** {@inheritdoc} */
	public function getTemplateName()
	{
		return 'Edit/Field/MultiPicklist.tpl';
	}

	/** {@inheritdoc} */
	public function getListSearchTemplateName()
	{
		return 'List/Field/MultiPicklist.tpl';
	}

	/** {@inheritdoc} */
	public function getAllowedColumnTypes()
	{
		return ['text'];
	}

	/** {@inheritdoc} */
	public function getQueryOperators()
	{
		return ['e', 'n', 'c', 'k', 'y', 'ny', 'ef', 'nf'];
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
		return 'ConditionBuilder/Picklist.tpl';
	}
}
