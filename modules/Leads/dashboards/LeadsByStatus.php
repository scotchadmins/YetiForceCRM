<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce S.A.
 * ********************************************************************************** */

class Leads_LeadsByStatus_Dashboard extends Vtiger_IndexAjax_View
{
	private $conditions = false;

	public function getSearchParams($value, $assignedto, $dates)
	{
		$listSearchParams = [];
		$conditions = [['leadstatus', 'e', $value]];
		if ('' != $assignedto) {
			array_push($conditions, ['assigned_user_id', 'e', $assignedto]);
		}
		if (!empty($dates)) {
			array_push($conditions, ['createdtime', 'bw', \App\Fields\DateTime::formatToDisplay($dates[0] . ' 00:00:00') . ',' . \App\Fields\DateTime::formatToDisplay($dates[1] . ' 23:59:59')]);
		}
		$listSearchParams[] = $conditions;

		return '&search_params=' . urlencode(json_encode($listSearchParams));
	}

	/**
	 * Function returns Leads grouped by Status.
	 *
	 * @param int   $owner
	 * @param array $dateFilter
	 *
	 * @return array
	 */
	public function getLeadsByStatus($owner, $dateFilter)
	{
		$leadsClosed = Settings_MarketingProcesses_Module_Model::getConfig('lead');
		$query = new \App\Db\Query();
		$query->select([
			'leadstatusid' => 'vtiger_leadstatus.leadstatusid',
			'count' => new \yii\db\Expression('COUNT(*)'),
			'leadstatusvalue' => new \yii\db\Expression("CASE WHEN vtiger_leadstatus.leadstatus IS NULL OR vtiger_leadstatus.leadstatus = '' THEN '' ELSE vtiger_leadstatus.leadstatus END"), ])
			->from('vtiger_leaddetails')
			->innerJoin('vtiger_crmentity', 'vtiger_leaddetails.leadid = vtiger_crmentity.crmid')
			->innerJoin('vtiger_leadstatus', 'vtiger_leaddetails.leadstatus = vtiger_leadstatus.leadstatus')
			->where(['deleted' => 0, 'converted' => 0]);
		if (!empty($owner)) {
			$query->andWhere(['smownerid' => $owner]);
		}
		if (!empty($dateFilter)) {
			$query->andWhere(['between', 'createdtime', $dateFilter[0] . ' 00:00:00', $dateFilter[1] . ' 23:59:59']);
		}
		\App\PrivilegeQuery::getConditions($query, 'Leads');
		if (!empty($leadsClosed['status'])) {
			$query->andWhere(['not in', 'vtiger_leaddetails.leadstatus', $leadsClosed['status']]);
			$this->conditions = [
				'condition' => ['not in', 'vtiger_leaddetails.leadstatus', $leadsClosed['status']],
			];
		}
		$query->groupBy(['leadstatusvalue', 'vtiger_leadstatus.leadstatusid', 'vtiger_leadstatus.sortorderid'])->orderBy('vtiger_leadstatus.sortorderid');
		$dataReader = $query->createCommand()->query();

		$chartData = [
			'dataset' => [],
			'show_chart' => false,
		];
		$chartData['series'][0]['colorBy'] = 'data';
		$moduleName = 'Leads';
		$listViewUrl = Vtiger_Module_Model::getInstance($moduleName)->getListViewUrl();
		$colors = \App\Fields\Picklist::getColors('leadstatus');
		while ($row = $dataReader->read()) {
			$link = $listViewUrl . '&viewname=All&entityState=Active' . $this->getSearchParams($row['leadstatusvalue'], $owner, $dateFilter);
			$chartData['dataset']['source'][] = [\App\Language::translate($row['leadstatusvalue'], $moduleName, null, false), (int) $row['count'], ['link' => $link]];
			$chartData['color'][] = $colors[$row['leadstatusid']] ?? \App\Colors::getRandomColor($row['leadstatusvalue']);
			$chartData['show_chart'] = true;
		}
		$dataReader->close();

		return $chartData;
	}

	public function process(App\Request $request)
	{
		$currentUserId = \App\User::getCurrentUserId();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$widget = Vtiger_Widget_Model::getInstance($request->getInteger('linkid'), $currentUserId);
		if (!$request->has('owner')) {
			$owner = Settings_WidgetsManagement_Module_Model::getDefaultUserId($widget, 'Leads');
		} else {
			$owner = $request->getByType('owner', 2);
		}
		$ownerForwarded = $owner;
		if ('all' == $owner) {
			$owner = '';
		}
		$createdTime = $request->getDateRange('createdtime');
		if (empty($createdTime)) {
			$createdTime = Settings_WidgetsManagement_Module_Model::getDefaultDateRange($widget);
		}

		$data = (false === $owner) ? [] : $this->getLeadsByStatus($owner, $createdTime);
		$createdTime = \App\Fields\Date::formatRangeToDisplay($createdTime);

		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', $data);
		$viewer->assign('DTIME', $createdTime);
		$viewer->assign('ACCESSIBLE_USERS', \App\Fields\Owner::getInstance('Leads', $currentUserId)->getAccessibleUsersForModule());
		$viewer->assign('ACCESSIBLE_GROUPS', \App\Fields\Owner::getInstance('Leads', $currentUserId)->getAccessibleGroupForModule());
		$viewer->assign('OWNER', $ownerForwarded);
		$viewer->assign('USER_CONDITIONS', $this->conditions);
		if ($request->has('content')) {
			$viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/LeadsByStatus.tpl', $moduleName);
		}
	}
}
