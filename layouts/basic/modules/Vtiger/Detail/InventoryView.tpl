{*<!-- {[The file is published on the basis of YetiForce Public License 5.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	<!-- tpl-Base-Detail-InventoryView -->
	{assign var="INVENTORY_MODEL" value=Vtiger_Inventory_Model::getInstance($MODULE_NAME)}
	{assign var="FIELDS" value=$INVENTORY_MODEL->getFieldsForView($VIEW)}
	{assign var="INVENTORY_ROWS" value=$RECORD->getInventoryData()}
	{if $FIELDS && $INVENTORY_MODEL->isField('name') && $INVENTORY_ROWS}
		{assign var=INVENTORY_ROW value=current($INVENTORY_ROWS)}
		{assign var="BASE_CURRENCY" value=Vtiger_Util_Helper::getBaseCurrency()}
		{assign var="REFERENCE_MODULE_DEFAULT" value=''}
		{if isset($FIELDS[0])}
			{if isset($INVENTORY_ROW['currency'])}
				{assign var="CURRENCY" value=$INVENTORY_ROW['currency']}
			{else}
				{assign var="CURRENCY" value=$BASE_CURRENCY['id']}
			{/if}
			{assign var="CURRENCY_SYMBOLAND" value=\App\Fields\Currency::getById($CURRENCY)}
			<table class="table table-bordered blockContainer">
				<thead>
					<tr>
						<th style="width: 40%;"></th>
						{foreach item=FIELD from=$FIELDS[0]}
							<th>
								<span class="inventoryLineItemHeader">{\App\Language::translate($FIELD->get('label'), $MODULE_NAME)}
									:</span>&nbsp;
								{assign var="FIELD_TPL_NAME" value="inventoryfields/"|cat:$FIELD->getTemplateName('DetailView',$MODULE_NAME)}
								{include file=\App\Layout::getTemplatePath($FIELD_TPL_NAME, $MODULE_NAME) ITEM_VALUE=$INVENTORY_ROW[$FIELD->getColumnName()] MODULE=$MODULE_NAME}
							</th>
						{/foreach}
					</tr>
				</thead>
			</table>
		{/if}
		{assign var="FIELDS_TEXT_ALIGN_RIGHT" value=['TotalPrice','Tax','MarginP','Margin','Purchase','Discount','NetPrice','GrossPrice','UnitPrice','Quantity','Unit','TaxPercent','ItemNumber']}
		<div class="table-responsive">
			<table class="table table-bordered inventoryItems mb-0">
				<thead>
					<tr>
						{foreach item=FIELD from=$FIELDS[1]}
							<th class="textAlignCenter u-table-column__before-block u-table-column__before-block--inventory{if $FIELD->get('colspan') neq 0 } u-table-column__vw-{$FIELD->get('colspan')}{/if}">
								{\App\Language::translate($FIELD->get('label'), $MODULE_NAME)}
							</th>
						{/foreach}
					</tr>
				</thead>
				<tbody class="js-inventory-items-body" data-js="container">
					{assign var="ROW_NO" value=0}
					{assign var=GROUP_FIELD value=$INVENTORY_MODEL->getField('grouplabel')}
					{foreach key=KEY item=INVENTORY_ROW from=$INVENTORY_MODEL->transformData($INVENTORY_ROWS)}
						{assign var=ROW_NO value=$ROW_NO+1}
						{if !empty($INVENTORY_ROW['add_header']) && $GROUP_FIELD && !empty($INVENTORY_ROW[$GROUP_FIELD->getColumnName()])}
							<tr class="inventoryRowGroup">
								{foreach item=FIELD from=$FIELDS[1]}
									{if $FIELD->getColumnName() eq 'name' && $FIELD->isVisible()}
										<td class="p-1 u-font-weight-700">
											{assign var="FIELD_TPL_NAME" value="inventoryfields/"|cat:$GROUP_FIELD->getTemplateName('DetailView',$MODULE_NAME)}
											{include file=\App\Layout::getTemplatePath($FIELD_TPL_NAME, $MODULE_NAME) FIELD=$GROUP_FIELD ITEM_VALUE=$INVENTORY_ROW[$GROUP_FIELD->getColumnName()]}
										</td>
									{else}
										<td class="text-right u-font-weight-600 text-nowrap">
											{assign var="FIELD_TPL_NAME" value="inventoryfields/"|cat:$FIELD->getTemplateName('GroupHeaders/DetailView',$MODULE_NAME)}
											{include file=\App\Layout::getTemplatePath($FIELD_TPL_NAME, $MODULE_NAME) FIELD=$FIELD}
										</td>
									{/if}
								{/foreach}
							</tr>
						{/if}
						{assign var="ROW_MODULE" value=\App\Record::getType($INVENTORY_ROW['name'])}
						<tr class="js-inventory-row inventoryRow" data-product-id="{$INVENTORY_ROW['name']}" data-js="data-product-id">
							{foreach item=FIELD from=$FIELDS[1]}
								<td {if in_array($FIELD->getType(), $FIELDS_TEXT_ALIGN_RIGHT)}class="textAlignRight text-nowrap" {/if}>
									{assign var="FIELD_TPL_NAME" value="inventoryfields/"|cat:$FIELD->getTemplateName('DetailView',$MODULE_NAME)}
									{include file=\App\Layout::getTemplatePath($FIELD_TPL_NAME, $MODULE_NAME) ITEM_VALUE=$INVENTORY_ROW[$FIELD->getColumnName()]}
								</td>
							{/foreach}
						</tr>
					{/foreach}
				</tbody>
				{if $INVENTORY_MODEL->getSummaryFields()}
					<tfoot>
						<tr>
							{foreach item=FIELD from=$FIELDS[1]}
								<th class="col{$FIELD->getType()} textAlignCenter {if !$FIELD->isSummaryEnabled()}hideTd{/if}">
									{if $FIELD->isSummaryEnabled()}
										{\App\Language::translate($FIELD->get('label'), $MODULE_NAME)}
									{/if}
								</th>
							{/foreach}
						</tr>
						<tr>
							{foreach item=FIELD from=$FIELDS[1]}
								<td class="col{$FIELD->getType()} textAlignRight text-nowrap {if !$FIELD->isSummaryEnabled()}hideTd{else}wisableTd{/if}"
									data-sumfield="{lcfirst($FIELD->getType())}">
									{if $FIELD->isSummaryEnabled()}
										{assign var="SUM" value=$FIELD->getSummaryValuesFromData($INVENTORY_ROWS)}
										{$FIELD->getDisplayValue($SUM, $INVENTORY_ROW)}
									{/if}
								</td>
							{/foreach}
						</tr>
					</tfoot>
				{/if}
			</table>
		</div>
		{include file=\App\Layout::getTemplatePath('Detail/InventoryGroupSummary.tpl', $MODULE_NAME)}
		{include file=\App\Layout::getTemplatePath('Detail/InventorySummary.tpl', $MODULE_NAME)}
	{/if}
	<!-- /tpl-Base-Detail-InventoryView -->
{/strip}
