<?php

/**
 * Widget showing ticket which have closed. We can filter by date.
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Tomasz Kur <t.kur@yetiforce.com>
 * @author    Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class HelpDesk_ClosedTicketsByUser_Dashboard extends Vtiger_IndexAjax_View
{
	/**
	 * Return search params (use to in building address URL to listview).
	 *
	 * @param int   $owner numer id of user
	 * @param array $time  contain start date and end time
	 *
	 * @return string
	 */
	public function getSearchParams($owner, $time)
	{
		$listSearchParams = [];
		$conditions = [];
		if (!empty($time)) {
			$conditions[] = ['closing_datatime', 'bw', implode(',', $time)];
		}
		if (!empty($owner)) {
			$conditions[] = ['assigned_user_id', 'e', $owner];
		}
		$listSearchParams[] = $conditions;
		return '&entityState=Active&viewname=All&search_params=' . urlencode(json_encode($listSearchParams));
	}

	/**
	 * Function returns Tickets grouped by users.
	 *
	 * @param array $time contain start date and end time
	 *
	 * @return array data to display chart
	 */
	public function getTicketsByUser($time)
	{
		$moduleName = 'HelpDesk';
		$ticketStatus = Settings_SupportProcesses_Module_Model::getTicketStatusNotModify();
		$listViewUrl = Vtiger_Module_Model::getInstance($moduleName)->getListViewUrl();
		$query = (new App\Db\Query())->select([
			'count' => new \yii\db\Expression('COUNT(*)'),
			'vtiger_crmentity.smownerid',
		])->from('vtiger_troubletickets')
			->innerJoin('vtiger_crmentity', 'vtiger_troubletickets.ticketid = vtiger_crmentity.crmid')
			->innerJoin('vtiger_ticketstatus', 'vtiger_troubletickets.status = vtiger_ticketstatus.ticketstatus')
			->innerJoin('vtiger_ticketpriorities', 'vtiger_ticketpriorities.ticketpriorities = vtiger_troubletickets.priority')
			->where(['vtiger_crmentity.deleted' => 0]);
		if (!empty($ticketStatus)) {
			$query->andWhere(['vtiger_troubletickets.status' => $ticketStatus]);
		}
		if (!empty($time)) {
			$time[0] .= ' 00:00:00';
			$time[1] .= ' 23:59:59';
			$query->andWhere([
				'and',
				['>=', 'vtiger_troubletickets.closing_datatime', $time[0]],
				['<=', 'vtiger_troubletickets.closing_datatime', $time[1]],
			]);
		}
		\App\PrivilegeQuery::getConditions($query, $moduleName);
		$query->groupBy('vtiger_crmentity.smownerid');
		$dataReader = $query->createCommand()->query();
		$time = \App\Fields\DateTime::formatRangeToDisplay($time);

		$chartData = [
			'dataset' => [],
			'show_chart' => false,
		];
		$chartData['series'][0]['colorBy'] = 'data';
		$listViewUrl = Vtiger_Module_Model::getInstance($moduleName)->getListViewUrl();

		while ($row = $dataReader->read()) {
			$label = \App\Fields\Owner::getLabel($row['smownerid']);
			$link = $listViewUrl . '&viewname=All&entityState=Active' . $this->getSearchParams($row['smownerid'], $time);
			$chartData['dataset']['source'][] = [\App\Utils::getInitials($label), (int) $row['count'], ['link' => $link, 'fullName' => $label]];
			$chartData['color'][] = \App\Fields\Owner::getColor((int) $row['smownerid']);
			$chartData['show_chart'] = true;
		}
		$dataReader->close();

		return $chartData;
	}

	/**
	 * Main function.
	 *
	 * @param \App\Request $request
	 */
	public function process(App\Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$widget = Vtiger_Widget_Model::getInstance($request->getInteger('linkid'), \App\User::getCurrentUserId());
		$time = $request->getDateRange('time');
		if (empty($time)) {
			$time = Settings_WidgetsManagement_Module_Model::getDefaultDateRange($widget);
		}
		$data = $this->getTicketsByUser($time);
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', $data);
		$viewer->assign('DTIME', \App\Fields\Date::formatRangeToDisplay($time));
		if ($request->has('content')) {
			$viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/ClosedTicketsByUser.tpl', $moduleName);
		}
	}
}
