<?php
/**
 * Mail server query condition field file.
 *
 * @package UIType
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

namespace App\Conditions\QueryFields;

/**
 * Mail server query condition field class.
 */
class MailServerField extends BaseField
{
	/** {@inheritdoc} */
	public function getValue()
	{
		return \is_array($this->value) ? $this->value : explode('##', $this->value);
	}
}
