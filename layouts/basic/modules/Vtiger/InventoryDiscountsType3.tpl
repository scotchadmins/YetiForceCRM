{*<!-- {[The file is published on the basis of YetiForce Public License 5.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	<!-- tpl-Base-InventoryDiscountsType3 -->
	{assign var="CHECKED" value=in_array('additional', $SELECTED_AGGR)}
	<div class="card js-panel mb-2{if $CHECKED} js-active{/if}" data-js="class: js-active">
		<div class="card-header py-1">
			<span class="fas fa-plus-minus mr-2"></span>
			<strong>{\App\Language::translate('LBL_ADDITIONAL_DISCOUNT', $MODULE)}</strong>
			<div class="float-right">
				<input type="{$AGGREGATION_INPUT_TYPE}" name="aggregationType" value="additional" class="activeCheckbox"{if $CHECKED} checked="checked"{/if}>
			</div>
		</div>
		<div class=" card-body js-panel__body{if !$CHECKED} d-none{/if}" data-js="class: d-none">
			<div class="container-fluid">
				<div class="row">
					<div class="input-group additionalDiscountContainer">
						<input type="text" name="additionalDiscount" class="form-control additionalDiscountValue" value="{\App\Fields\Double::formatToDisplay($DISCOUNT_VALUE, false)|escape}" data-validation-engine="validate[required]">
						<div class="input-group-append">
							<span class="input-group-text">%</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- /tpl-Base-InventoryDiscountsType3 -->
{/strip}
