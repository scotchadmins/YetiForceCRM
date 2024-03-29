<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce S.A.
 * *********************************************************************************** */

class Calendar_Delete_Action extends Vtiger_Delete_Action
{
	/** {@inheritdoc} */
	public function process(App\Request $request)
	{
		$typeRemove = Calendar_RecuringEvents_Model::UPDATE_THIS_EVENT;
		if (!$request->isEmpty('typeRemove')) {
			$typeRemove = $request->getInteger('typeRemove');
			$this->record->ext['repeatType'] = $typeRemove;
		}
		$result = $this->performDelete($request);
		if (!$result) {
			$recurringEvents = Calendar_RecuringEvents_Model::getInstance();
			$recurringEvents->typeSaving = $typeRemove;
			$recurringEvents->recordModel = $this->record;
			$recurringEvents->templateRecordId = $request->getInteger('record');
		}

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}
}
