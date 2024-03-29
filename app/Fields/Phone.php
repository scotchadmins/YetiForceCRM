<?php
/**
 * Tools for phone class.
 *
 * @package App
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

namespace App\Fields;

/**
 * Phone class.
 */
class Phone
{
	/**
	 * Get phone details.
	 *
	 * @param string      $phoneNumber
	 * @param string|null $phoneCountry
	 * @param int         $numberFormat the PhoneNumberFormat the phone number should be formatted into
	 *
	 * @return array|bool
	 */
	public static function getDetails(string $phoneNumber, ?string $phoneCountry = null, int $numberFormat = \libphonenumber\PhoneNumberFormat::NATIONAL)
	{
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
			$swissNumberProto = $phoneUtil->parse($phoneNumber, $phoneCountry);
			if ($phoneUtil->isValidNumber($swissNumberProto)) {
				return [
					'number' => $phoneUtil->format($swissNumberProto, $numberFormat),
					'geocoding' => \libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance()->getDescriptionForNumber($swissNumberProto, \App\Language::getLanguage()),
					'carrier' => \libphonenumber\PhoneNumberToCarrierMapper::getInstance()->getNameForValidNumber($swissNumberProto, \App\Language::getShortLanguageName()),
					'country' => $phoneUtil->getRegionCodeForNumber($swissNumberProto),
				];
			}
			return [
				'country' => $phoneUtil->getRegionCodeForNumber($swissNumberProto),
			];
		} catch (\libphonenumber\NumberParseException $e) {
			\App\Log::info($e->getMessage(), __CLASS__);
		}
		return false;
	}

	/**
	 * Verify phone number.
	 *
	 * @param string      $phoneNumber
	 * @param string|null $phoneCountry
	 *
	 * @throws \App\Exceptions\FieldException
	 *
	 * @return bool
	 */
	public static function verifyNumber(string $phoneNumber, ?string $phoneCountry = null)
	{
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		if ($phoneCountry && !\in_array($phoneCountry, $phoneUtil->getSupportedRegions())) {
			throw new \App\Exceptions\FieldException('LBL_INVALID_COUNTRY_CODE');
		}
		try {
			$swissNumberProto = $phoneUtil->parse($phoneNumber, $phoneCountry);
			if ($phoneUtil->isValidNumber($swissNumberProto)) {
				$phoneNumber = $phoneUtil->format($swissNumberProto, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);

				return [
					'isValidNumber' => true,
					'number' => $phoneNumber,
					'geocoding' => \libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance()->getDescriptionForNumber($swissNumberProto, \App\Language::getLanguage()),
					'carrier' => \libphonenumber\PhoneNumberToCarrierMapper::getInstance()->getNameForValidNumber($swissNumberProto, \App\Language::getShortLanguageName()),
					'country' => $phoneUtil->getRegionCodeForNumber($swissNumberProto),
				];
			}
		} catch (\libphonenumber\NumberParseException $e) {
			\App\Log::info($e->getMessage(), __CLASS__);
		}
		throw new \App\Exceptions\FieldException('LBL_INVALID_PHONE_NUMBER');
	}

	/**
	 * Parse phone number.
	 *
	 * @param string      $fieldName
	 * @param array       $parsedData
	 * @param string|null $phoneCountry
	 *
	 * @return array
	 */
	public static function parsePhone(string $fieldName, array $parsedData, ?string $phoneCountry = null): array
	{
		if (\App\Config::component('Phone', 'advancedVerification', false)) {
			$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
			$phone = trim($parsedData[$fieldName]);
			try {
				$swissNumberProto = $phoneUtil->parse($phone, $phoneCountry);
				$international = $phoneUtil->format($swissNumberProto, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
			} catch (\libphonenumber\NumberParseException $e) {
				$international = false;
				foreach ($phoneUtil->findNumbers($phone, $phoneCountry) as $phoneNumberMatch) {
					$international = $phoneUtil->format($phoneNumberMatch->number(), \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
					$parsedData[$fieldName . '_extra'] = trim(str_replace($phoneNumberMatch->rawString(), '', $phone));
					break;
				}
			}
			if ($international) {
				$parsedData[$fieldName] = $international;
			} else {
				$parsedData[$fieldName . '_extra'] = $phone;
				unset($parsedData[$fieldName]);
			}
		}
		return $parsedData;
	}
}
