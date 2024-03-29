{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
	<div class="form-group row">
		<label class="col-md-2 col-form-label">{\App\Language::translate('LBL_RECEPIENTS',$QUALIFIED_MODULE)}<span
				class="redColor">*</span></label>
		<div class="col-md-4">
			<input type="text" class="fields form-control" data-validation-engine='validate[required]'
				name="sms_recepient" value="{if isset($TASK_OBJECT->sms_recepient)}{$TASK_OBJECT->sms_recepient}{/if}" />
		</div>
		<div class="col-md-4">
			<select class="select2 task-fields form-control">
				<optgroup class="p-0">
					<option value="none">{\App\Language::translate('LBL_SELECT', $QUALIFIED_MODULE)}</option>
				</optgroup>
				{foreach item=FIELDS key=BLOCK_NAME from=$TEXT_PARSER->getRecordVariable('phone')}
					<optgroup label="{$BLOCK_NAME}">
						{foreach item=ITEM from=$FIELDS}
							<option value=",{$ITEM['var_value']}" data-label="{$ITEM['var_label']}">
								{$ITEM['label']}
							</option>
						{/foreach}
					</optgroup>
				{/foreach}
				{foreach item=FIELDS key=BLOCK_NAME from=$TEXT_PARSER->getRelatedVariable('phone')}
					{foreach item=RELATED_FIELDS key=BLOCK_NAME from=$FIELDS}
						<optgroup label="{$BLOCK_NAME}">
							{foreach item=ITEM from=$RELATED_FIELDS}
								<option value=",{$ITEM['var_value']}" data-label="{$ITEM['var_label']}">
									{$ITEM['label']}
								</option>
							{/foreach}
						</optgroup>
					{/foreach}
				{/foreach}
				{foreach item=RELATED_FIELDS key=BLOCK_NAME from=$TEXT_PARSER->getRelatedLevelVariable('phone')}
					<optgroup label="{$BLOCK_NAME}">
						{foreach item=ITEM from=$RELATED_FIELDS}
							<option value=",{$ITEM['var_value']}" data-label="{$ITEM['var_label']}">
								{$ITEM['label']}
							</option>
						{/foreach}
					</optgroup>
				{/foreach}
			</select>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-md-2 col-form-label">{\App\Language::translate('LBL_SMS_PROVIDER',$QUALIFIED_MODULE)}</label>
		<div class="col-md-4">
			{assign var=ACTIVE_PROVIDERS value=\App\Integrations\SMSProvider::getAll(\App\Integrations\SMSProvider::STATUS_ACTIVE)}
			<select class="select2 task-fields form-control" name="sms_provider_id">
				<option value="" selected="">{\App\Language::translate('LBL_SELECT')}</option>
				{foreach from=$ACTIVE_PROVIDERS item=SMS_PROVIDER}
					<option value="{$SMS_PROVIDER['id']}" {if $TASK_OBJECT->sms_provider_id eq $SMS_PROVIDER['id']} selected {/if}> {\App\Purifier::encodeHtml($SMS_PROVIDER['name'])} </option>
				{/foreach}
			</select>
		</div>
	</div>
	{if $TASK_OBJECT->sms_provider_id && !isset($ACTIVE_PROVIDERS[$TASK_OBJECT->sms_provider_id])}
		<div class="alert alert-warning" role="alert">
			{\App\Language::translate('LBL_SELECTED_PROVIDER_NOT_ACTIVE',$QUALIFIED_MODULE)}
		</div>
	{/if}
	<hr />
	<div class="row">
		{include file=\App\Layout::getTemplatePath('VariablePanel.tpl') SELECTED_MODULE=$SOURCE_MODULE PARSER_TYPE='mail' GRAY=true}
	</div>
	<hr />
	<div class="form-group row">
		<label class="col-md-2 col-form-label">{\App\Language::translate('LBL_SMS_TEXT',$QUALIFIED_MODULE)}</label>
		<div class="col-md-8">
			<textarea name="content" class="form-control fields">{if isset($TASK_OBJECT->content)}{$TASK_OBJECT->content}{/if}</textarea>
		</div>
	</div>
{/strip}
