<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce S.A.
 * ********************************************************************************** */

/**
 * VTiger workflow VTTask class.
 */
abstract class VTTask
{
	/** @var int */
	public const RECORD_EVENT_ACTIVE = 0;
	/** @var int */
	public const RECORD_EVENT_INACTIVE = 1;
	/** @var int */
	public const RECORD_EVENT_DOUBLE_MODE = 2;

	/**
	 * Task contents.
	 *
	 * @var Vtiger_Record_Model
	 */
	public $contents;

	/** @var bool The record event. */
	public $recordEventState = self::RECORD_EVENT_ACTIVE;

	/** @var int Task ID */
	public $id;
	/** @var array The array of task data. */
	private array $data = [];

	/**
	 * Restore the model after serialization.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function __unserialize(array $data)
	{
		$this->data = $data;
	}

	/**
	 * Prepare the instance values for serialization.
	 *
	 * @return array
	 */
	public function __serialize()
	{
		$values = [];
		foreach ($this->data as $name => $value) {
			if (\is_object($value) || 'data' === $name) {
				continue;
			}
			$values[$name] = $value;
		}

		return $values;
	}

	public function __isset($name)
	{
		return isset($this->data[$name]);
	}

	/**
	 * Set a piece of data on the task.
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public function __set($name, $value)
	{
		if (!property_exists($this, $name)) {
			$this->data[$name] = $value;
		} else {
			$this->{$name} = $value;
		}
	}

	/**
	 * Get a piece of data from the tesk.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function &__get($name)
	{
		return $this->data[$name];
	}

	/**
	 * Do task.
	 *
	 * @param Vtiger_Record_Model
	 * @param mixed $recordModel
	 */
	abstract public function doTask($recordModel);

	/**
	 * Return field names.
	 */
	abstract public function getFieldNames();

	/**
	 * Return time field list.
	 *
	 * @return array
	 */
	public function getTimeFieldList()
	{
		return [];
	}

	/**
	 * Return content.
	 *
	 * @param Vtiger_Record_Model $recordModel
	 *
	 * @return Vtiger_Record_Model
	 */
	public function getContents($recordModel)
	{
		return $this->contents;
	}

	/**
	 * Set contents.
	 *
	 * @param Vtiger_Record_Model $recordModel
	 */
	public function setContents($recordModel)
	{
		$this->contents = $recordModel;
	}

	/**
	 * Check if has contents.
	 *
	 * @param Vtiger_Record_Model $recordModel
	 *
	 * @return bool
	 */
	public function hasContents($recordModel)
	{
		if ($this->getContents($recordModel)) {
			return true;
		}
		return false;
	}

	/**
	 * Return formatted time for timepicker.
	 *
	 * @param string $time
	 *
	 * @return string
	 */
	public function formatTimeForTimePicker($time)
	{
		[$h, $m] = explode(':', $time);
		$mn = str_pad($m - $m % 15, 2, 0, STR_PAD_LEFT);
		$AM_PM = ['am', 'pm'];

		return str_pad($h % 12, 2, 0, STR_PAD_LEFT) . ':' . $mn . $AM_PM[($h / 12) % 2];
	}
}
