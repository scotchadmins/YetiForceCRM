<?php

/**
 * Companies test class.
 *
 * @package   Tests
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Arkadiusz Sołek <a.solek@yetiforce.com>
 */

namespace Tests\Settings;

class Companies extends \Tests\Base
{
	/**
	 * Companies id.
	 */
	private static $id;

	/**
	 * Testing add companies creation.
	 */
	public function testAddCompanies()
	{
		$recordModel = new \Settings_Companies_Record_Model();
		$recordModel->set('name', 'Name');
		$recordModel->set('type', 1);
		$recordModel->set('industry', 'Industry');
		$recordModel->set('city', 'City');
		$recordModel->set('country', 'Country');
		$recordModel->set('website', 'www.website.com');
		$recordModel->set('email', 'email@gmail.com');
		$recordModel->set('logo', 'logo_two.png');
		$recordModel->save();
		self::$id = $recordModel->getId();
		$this->assertNotNull(self::$id, 'Id is null');

		$row = (new \App\Db\Query())->from('s_#__companies')->where(['id' => self::$id])->one();
		$this->assertNotFalse($row, 'No record id: ' . self::$id);
		$this->assertSame($row['name'], 'Name');
		$this->assertSame($row['type'], 1);
		$this->assertSame($row['industry'], 'Industry');
		$this->assertSame($row['city'], 'City');
		$this->assertSame($row['country'], 'Country');
		$this->assertSame($row['website'], 'www.website.com');
		$this->assertSame($row['email'], 'email@gmail.com');
		$this->assertSame($row['logo'], 'logo_two.png');
	}

	/**
	 * Testing edit companies creation.
	 */
	public function testEditCompanies()
	{
		$recordModel = \Settings_Companies_Record_Model::getInstance(self::$id);
		$recordModel->set('name', 'Company');
		$recordModel->set('industry', 'Ingenuity');
		$recordModel->set('city', 'Town');
		$recordModel->set('country', 'Land');
		$recordModel->set('website', 'www.website-site.com');
		$recordModel->set('email', 'emailtwo@gmail.com');
		$recordModel->set('logo', 'logo_main.png');
		$recordModel->save();
		self::$id = $recordModel->getId();

		$row = (new \App\Db\Query())->from('s_#__companies')->where(['id' => self::$id])->one();
		$this->assertNotFalse($row, 'No record id: ' . self::$id);
		$this->assertSame($row['name'], 'Company');
		$this->assertSame($row['industry'], 'Ingenuity');
		$this->assertSame($row['city'], 'Town');
		$this->assertSame($row['country'], 'Land');
		$this->assertSame($row['website'], 'www.website-site.com');
		$this->assertSame($row['email'], 'emailtwo@gmail.com');
		$this->assertSame($row['logo'], 'logo_main.png');
	}

	/**
	 * Testing delete companies creation.
	 */
	public function testDeleteCompanies()
	{
		$recordModel = \Settings_Companies_Record_Model::getInstance(self::$id);
		$recordModel->delete();
		$this->assertFalse((new \App\Db\Query())->from('s_#__companies')->where(['id' => self::$id])->exists(), 'Company should not exists');
	}

	/**
	 * Testing module model methods.
	 */
	public function testModuleModelFunctions()
	{
		$columns = \Settings_Companies_Module_Model::getColumnNames();
		$this->assertNotFalse($columns, 'Columns should be not false');
		$this->assertNotEmpty($columns, 'Columns should be not empty');
		$this->assertNotEmpty(\Settings_Companies_Module_Model::getIndustryList(), 'Industry list should be not empty');
	}
}
