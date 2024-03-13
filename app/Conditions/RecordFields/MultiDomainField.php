<?php
/**
 * Multi domain condition record field file.
 *
 * @package UIType
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

namespace App\Conditions\RecordFields;

/**
 * Multi domain condition record field class.
 */
class MultiDomainField extends BaseField
{
	/** @var string Separator. */
	protected $separator = ',';

	/** {@inheritdoc} */
	public function getValue(): array
	{
		$value = [];
		if (!empty(parent::getValue()) && \in_array($this->operator, ['e', 'n'])) {
			$value = explode($this->separator, trim(parent::getValue(), ','));
		}
		return $value;
	}

	/** {@inheritdoc} */
	public function operatorE(): bool
	{
		return (bool) array_intersect($this->getValue(), explode($this->separator, $this->value));
	}

	/** {@inheritdoc} */
	public function operatorN(): bool
	{
		return (bool) !array_intersect($this->getValue(), explode($this->separator, $this->value));
	}

	/** {@inheritdoc} */
	public function operatorC(): bool
	{
		return $this->operatorE();
	}
}
