<?php

/**
 * OpenStreetMap map modal view file.
 *
 * @package   View
 *
 * @copyright YetiForce S.A.
 * @license YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
/**
 * OpenStreetMap map modal view class.
 */
class OpenStreetMap_MapModal_View extends \App\Controller\Modal
{
	/** {@inheritdoc} */
	public $modalSize = 'modal-fullscreen';

	/** {@inheritdoc} */
	public $modalIcon = 'fas fa-globe';

	/** {@inheritdoc} */
	public $headerClass = 'py-2';

	/** {@inheritdoc} */
	protected $pageTitle = 'LBL_MAP';

	/** {@inheritdoc} */
	public $showFooter = false;

	/** {@inheritdoc} */
	public function checkPermission(App\Request $request)
	{
		$currentUserPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$currentUserPrivilegesModel->hasModulePermission($request->getModule())) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED', 406);
		}
		if (!$request->isEmpty('srcModule', true) && !$currentUserPrivilegesModel->hasModulePermission($request->getByType('srcModule'))) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED', 406);
		}
	}

	/** {@inheritdoc} */
	public function preProcessAjax(App\Request $request)
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('ADDRESS_PROVIDERS', \App\Map\Address::getActiveProviders());
		parent::preProcessAjax($request);
	}

	/** {@inheritdoc} */
	protected function preProcessTplName(App\Request $request)
	{
		return 'MapModalHeader.tpl';
	}

	/** {@inheritdoc} */
	public function process(App\Request $request)
	{
		$moduleName = $request->getModule();
		$coordinatesModel = OpenStreetMap_Coordinate_Model::getInstance();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $moduleName);
		if ($request->getBoolean('point')) {
			$moduleModel = Vtiger_Module_Model::getInstance($request->getByType('srcModule', 'Alnum'));
			$fieldModel = $moduleModel->getFieldByName($request->getByType('srcField', 'Alnum'));
			$value = $fieldModel->getEditViewDisplayValue($request->getArray('value'));
			if (!empty($value['decimal']['lat'])) {
				$viewer->assign('POINT_DECIMAL', $value);
			}
			$viewer->assign('SHOW_PANEL', false);
		} else {
			if (!$request->isEmpty('srcModule', true)) {
				$moduleModel = Vtiger_Module_Model::getInstance($request->getByType('srcModule'));
				$fields = $moduleModel->getFields();
				$fieldsToGroup = [];
				foreach ($fields as $fieldModel) {
					if ('picklist' === $fieldModel->getFieldDataType()) {
						$fieldsToGroup[] = $fieldModel;
					}
				}
				$cacheRecords[$request->getByType('srcModule')] = 0; // default values
				$cacheRecords = array_merge($cacheRecords, $coordinatesModel->getCachedRecords());
				$viewer->assign('FIELDS_TO_GROUP', $fieldsToGroup);
			} else {
				$cacheRecords = $coordinatesModel->getCachedRecords();
			}
			$moduleModel = OpenStreetMap_Module_Model::getInstance($moduleName);
			$viewer->assign('SHOW_PANEL', true);
			$viewer->assign('SRC_MODULE', $request->getByType('srcModule'));
			$viewer->assign('CACHE_GROUP_RECORDS', $cacheRecords);
			$viewer->assign('ALLOWED_MODULES', $moduleModel->getAllowedModules());
		}
		$viewer->view('MapModal.tpl', $moduleName);
	}

	/** {@inheritdoc} */
	public function getModalScripts(App\Request $request)
	{
		return $this->checkAndConvertJsScripts([
			'~libraries/leaflet/dist/leaflet.js',
			'~libraries/leaflet.markercluster/dist/leaflet.markercluster.js',
			'~libraries/leaflet.awesome-markers/dist/leaflet.awesome-markers.js',
			'modules.OpenStreetMap.resources.Map',
		]);
	}

	/** {@inheritdoc} */
	public function getModalCss(App\Request $request)
	{
		return $this->checkAndConvertCssStyles([
			'~libraries/leaflet/dist/leaflet.css',
			'~libraries/leaflet.markercluster/dist/MarkerCluster.Default.css',
			'~libraries/leaflet.markercluster/dist/MarkerCluster.css',
			'~libraries/leaflet.awesome-markers/dist/leaflet.awesome-markers.css',
		]);
	}
}
