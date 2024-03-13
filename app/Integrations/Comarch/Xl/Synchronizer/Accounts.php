<?php

/**
 * Comarch accounts synchronization file.
 *
 * The file is part of the paid functionality. Using the file is allowed only after purchasing a subscription.
 * File modification allowed only with the consent of the system producer.
 *
 * @package Integration
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

namespace App\Integrations\Comarch\Xl\Synchronizer;

/**
 * Comarch accounts synchronization class.
 */
class Accounts extends \App\Integrations\Comarch\Synchronizer
{
	/** @var string The name of the configuration parameter for rows limit */
	const LIMIT_NAME = 'accounts_limit';

	/** {@inheritdoc} */
	public function process(): void
	{
		$mapModel = $this->getMapModel();
		if (\App\Module::isModuleActive($mapModel->getModule())) {
			$direction = (int) $this->config->get('direction_accounts');
			if ($this->config->get('master')) {
				if (self::DIRECTION_TWO_WAY === $direction || self::DIRECTION_YF_TO_API === $direction) {
					$this->runQueue('export');
					$this->export();
				}
				if (self::DIRECTION_TWO_WAY === $direction || self::DIRECTION_API_TO_YF === $direction) {
					$this->runQueue('import');
					$this->import();
				}
			} else {
				if (self::DIRECTION_TWO_WAY === $direction || self::DIRECTION_API_TO_YF === $direction) {
					$this->runQueue('import');
					$this->import();
				}
				if (self::DIRECTION_TWO_WAY === $direction || self::DIRECTION_YF_TO_API === $direction) {
					$this->runQueue('export');
					$this->export();
				}
			}
		}
	}

	/**
	 * Import accounts from Comarch.
	 *
	 * @return void
	 */
	public function import(): void
	{
		$this->lastScan = $this->config->getLastScan('import' . $this->name);
		if (
			!$this->lastScan['start_date']
			|| (0 === $this->lastScan['id'] && $this->lastScan['start_date'] === $this->lastScan['end_date'])
		) {
			$this->config->setScan('import' . $this->name);
			$this->lastScan = $this->config->getLastScan('import' . $this->name);
		}
		if ($this->config->get('log_all')) {
			$this->controller->log('Start import ' . $this->name, [
				'lastScan' => $this->lastScan,
			]);
		}
		$i = 0;
		try {
			$page = $this->lastScan['page'] ?? 1;
			$load = true;
			$finish = false;
			$limit = $this->config->get(self::LIMIT_NAME);
			while ($load) {
				if ($rows = $this->getFromApi('Customer/GetAll?&page=' . $page . '&' . $this->getFromApiCond())) {
					foreach ($rows as $id => $row) {
						if ('JEDNORAZOWY' === $row['knt_Akronim']) {
							continue;
						}
						$this->importItem($row);
						$this->config->setScan('import' . $this->name, 'id', $id);
						++$i;
					}
					++$page;
					if (\is_callable($this->controller->bathCallback)) {
						$load = \call_user_func($this->controller->bathCallback, 'import' . $this->name);
					}
					if ($limit !== \count($rows)) {
						$finish = true;
					}
				} else {
					$finish = true;
				}
				if ($finish || !$load) {
					$load = false;
					if ($finish) {
						$this->config->setEndScan('import' . $this->name, $this->lastScan['start_date']);
					} else {
						$this->config->setScan('import' . $this->name, 'page', $page);
					}
				}
			}
		} catch (\Throwable $ex) {
			$this->logError('import ' . $this->name, null, $ex);
		}
		if ($this->config->get('log_all')) {
			$this->controller->log('End import ' . $this->name, ['imported' => $i]);
		}
	}

	/** {@inheritdoc} */
	public function importById(int $apiId): int
	{
		$id = 0;
		try {
			$row = $this->getFromApi('Customer/GetById/' . $apiId);
			if ($row) {
				$this->importItem($row);
				$mapModel = $this->getMapModel();
				$id = $this->imported[$row[$mapModel::API_NAME_ID]] ?? 0;
			} else {
				$this->controller->log("Import {$this->name} by id [Empty details]", ['apiId' => $apiId]);
				\App\Log::error("Import during export {$this->name}: Empty details", self::LOG_CATEGORY);
			}
		} catch (\Throwable $ex) {
			$this->logError("import {$this->name} by id", ['apiId' => $apiId, 'API' => $row ?? []], $ex);
		}
		return $id;
	}
}
