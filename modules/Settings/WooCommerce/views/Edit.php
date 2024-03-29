<?php

/**
 * Edit view file for Settings WooCommerce module.
 *
 * @package   Settings.View
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
/**
 * Edit view class for Settings WooCommerce module.
 */
class Settings_WooCommerce_Edit_View extends \App\Controller\ModalSettings
{
	/** {@inheritdoc} */
	public $showFooter = false;

	/** {@inheritdoc} */
	public $modalSize = 'modal-xl';

	/** {@inheritdoc} */
	public $successBtn = 'LBL_SAVE_AND_VERIFY';

	/** {@inheritdoc} */
	public function process(App\Request $request)
	{
		$record = !$request->isEmpty('record') ? $request->getInteger('record') : '';
		if ($record) {
			$recordModel = Settings_WooCommerce_Record_Model::getInstanceById($record);
		} else {
			$recordModel = Settings_WooCommerce_Record_Model::getCleanInstance();
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $record);
		$viewer->assign('MODULE_NAME', $request->getModule());
		$viewer->assign('BTN_SUCCESS', $this->successBtn);
		$viewer->assign('BTN_SUCCESS_ICON', $this->successBtnIcon);
		$viewer->assign('BTN_DANGER', $this->dangerBtn);
		$viewer->view('Edit/Modal.tpl', $request->getModule(false));
	}
}
