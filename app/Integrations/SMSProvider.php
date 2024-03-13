<?php
/**
 * SMS Provider file.
 *
 * @package App
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

namespace App\Integrations;

/**
 * SMS Provider class.
 */
class SMSProvider extends \App\Base
{
	/** @var string Basic table name */
	public const TABLE_NAME = 'a_#__smsnotifier_servers';

	/** @var int Status inactive */
	public const STATUS_INACTIVE = 0;
	/** @var int Status active */
	public const STATUS_ACTIVE = 1;
	/** @var int Status active */
	public const DEFAULT = 1;

	/**
	 * Get providers.
	 *
	 * @return array
	 */
	public static function getProviders(): array
	{
		$providers = [];
		$iterator = new \DirectoryIterator(__DIR__ . '/SMSProvider');
		foreach ($iterator as $item) {
			if ($item->isFile() && 'Provider.php' !== $item->getFilename() && 'php' === $item->getExtension() && $provider = self::getProviderByName($item->getBasename('.php'))) {
				$providers[$provider->getName()] = $provider;
			}
		}

		return $providers;
	}

	/**
	 * Get provider by name.
	 *
	 * @param string $name
	 *
	 * @return SMSProvider\Provider|null
	 */
	public static function getProviderByName(string $name): ?SMSProvider\Provider
	{
		$className = "\\App\\Integrations\\SMSProvider\\{$name}";
		return class_exists($className) ? new $className() : null;
	}

	/**
	 * Get provider by data.
	 *
	 * @param array $data
	 *
	 * @return SMSProvider\Provider|null
	 */
	public static function getProviderByData(array $data): ?SMSProvider\Provider
	{
		$provider = self::getProviderByName($data['providertype']);
		$parameters = \App\Json::isJson($data['parameters']) ? \App\Json::decode($data['parameters']) : [];
		foreach ($parameters as $name => $value) {
			$data[$name] = $value;
		}
		unset($data['parameters']);
		return $provider ? $provider->setData($data) : null;
	}

	/**
	 * Get default provider.
	 *
	 * @return SMSProvider\Provider|null
	 */
	public static function getDefaultProvider(): ?SMSProvider\Provider
	{
		$providers = (new \App\Db\Query())->from(self::TABLE_NAME)->where(['isactive' => self::STATUS_ACTIVE])->all();
		if (1 === \count($providers)) {
			return self::getProviderByData(reset($providers));
		}
		$defaultProvider = array_filter($providers, fn ($server) => self::DEFAULT === $server['default']);
		return $defaultProvider ? self::getProviderByData(reset($defaultProvider)) : null;
	}

	/**
	 * Check if there is an active provider.
	 *
	 * @return bool
	 */
	public static function isActiveProvider(): bool
	{
		return (new \App\Db\Query())->from(self::TABLE_NAME)->where(['isactive' => self::STATUS_ACTIVE])->exists();
	}

	/**
	 * Get provider by ID.
	 *
	 * @param int $id
	 *
	 * @return SMSProvider\Provider|null
	 */
	public static function getById(int $id): ?SMSProvider\Provider
	{
		$data = (new \App\Db\Query())->from(self::TABLE_NAME)->where(['id' => $id])->one();
		return $data ? self::getProviderByData($data) : null;
	}

	/**
	 * Get a list of all SMS servers.
	 *
	 * @param int|null $active self::STATUS_INACTIVE, self::STATUS_ACTIVE
	 *
	 * @return array
	 */
	public static function getAll(?int $active = null): array
	{
		if (\App\Cache::has('SMSServer', 'all')) {
			$providers = \App\Cache::get('SMSServer', 'all');
		} else {
			$providers = (new \App\Db\Query())->from(self::TABLE_NAME)->indexBy('id')->all(\App\Db::getInstance('admin'));
			\App\Cache::save('SMSServer', 'all', $providers, \App\Cache::LONG);
		}
		if (null !== $active) {
			$providers = array_filter($providers, fn ($server) => $server['isactive'] === $active);
		}

		return $providers;
	}
}
