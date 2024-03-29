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

class Leads_LeadsBySource_Dashboard extends Vtiger_IndexAjax_View
{
	public function getSearchParams($value, $assignedto, $dates)
	{
		$listSearchParams = [];
		$conditions = [['leadsource', 'e', $value]];
		if ('' != $assignedto) {
			array_push($conditions, ['assigned_user_id', 'e', $assignedto]);
		}
		if (!empty($dates)) {
			array_push($conditions, ['createdtime', 'bw', \App\Fields\DateTime::formatToDisplay($dates[0] . ' 00:00:00') . ',' . \App\Fields\DateTime::formatToDisplay($dates[1] . ' 23:59:59')]);
		}
		$listSearchParams[] = $conditions;
		return '&search_params=' . json_encode($listSearchParams);
	}

	/**
	 * Function returns Leads grouped by Source.
	 *
	 * @param int   $owner
	 * @param array $dateFilter
	 *
	 * @return array
	 */
	public function getLeadsBySource($owner, $dateFilter)
	{
		$query = new \App\Db\Query();
		$query->select([
			'leadsourceid' => 'vtiger_leadsource.leadsourceid',
			'count' => new \yii\db\Expression('COUNT(*)'),
			'leadsourcevalue' => new \yii\db\Expression("CASE WHEN vtiger_leaddetails.leadsource IS NULL OR vtiger_leaddetails.leadsource = '' THEN '' ELSE vtiger_leaddetails.leadsource END"), ])
			->from('vtiger_leaddetails')
			->innerJoin('vtiger_crmentity', 'vtiger_leaddetails.leadid = vtiger_crmentity.crmid')
			->innerJoin('vtiger_leadsource', 'vtiger_leaddetails.leadsource = vtiger_leadsource.leadsource')
			->where(['deleted' => 0, 'converted' => 0]);
		if (!empty($owner)) {
			$query->andWhere(['smownerid' => $owner]);
		}
		if (!empty($dateFilter)) {
			$query->andWhere(['between', 'createdtime', $dateFilter[0] . ' 00:00:00', $dateFilter[1] . ' 23:59:59']);
		}
		\App\PrivilegeQuery::getConditions($query, 'Leads');
		$query->groupBy(['vtiger_leaddetails.leadsource', 'vtiger_leadsource.leadsourceid']);
		$dataReader = $query->createCommand()->query();

		$chartData = [
			'dataset' => [],
			'show_chart' => false,
		];
		$chartData['series'][0]['colorBy'] = 'data';
		$moduleName = 'Leads';
		$listViewUrl = Vtiger_Module_Model::getInstance($moduleName)->getListViewUrl();
		$colors = \App\Fields\Picklist::getColors('leadsource');
		while ($row = $dataReader->read()) {
			$link = $listViewUrl . '&viewname=All&entityState=Active' . $this->getSearchParams($row['leadsourcevalue'], $owner, $dateFilter);
			$chartData['dataset']['source'][] = [\App\Language::translate($row['leadsourcevalue'], $moduleName, null, false), (int) $row['count'], ['link' => $link]];
			$chartData['color'][] = $colors[$row['leadsourceid']] ?? null;
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
		$createdTime = $request->getDateRange('createdtime');
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
		if (empty($createdTime)) {
			$createdTime = Settings_WidgetsManagement_Module_Model::getDefaultDateRange($widget);
		}
		$data = (false === $owner) ? [] : $this->getLeadsBySource($owner, $createdTime);
		$createdTime = \App\Fields\Date::formatRangeToDisplay($createdTime);
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', $data);
		$viewer->assign('DTIME', $createdTime);
		$viewer->assign('ACCESSIBLE_USERS', \App\Fields\Owner::getInstance('Leads', $currentUserId)->getAccessibleUsersForModule());
		$viewer->assign('ACCESSIBLE_GROUPS', \App\Fields\Owner::getInstance('Leads', $currentUserId)->getAccessibleGroupForModule());
		$viewer->assign('OWNER', $ownerForwarded);
		if ($request->has('content')) {
			$viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/LeadsBySource.tpl', $moduleName);
		}
	}
}
