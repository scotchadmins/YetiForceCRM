{*<!-- {[The file is published on the basis of YetiForce Public License 5.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	<script type="text/javascript">
		YetiForce_Bar_Widget_Js('YetiForce_ClosedTicketsByUser_Widget_Js', {}, {
			getBasicOptions: function getBasicOptions() {
				let options = this._super();
				options.tooltip = {
					appendToBody: true,
					formatter: function(params, ticket, callback) {
						let name = params.value[2].fullName || '';
						let value = Number.isInteger(params.value[1]) ? App.Fields.Integer.formatToDisplay(params.value[1]) : App.Fields.Double.formatToDisplay(params.value[1]);
						return params.marker + (name ? (name + ': ') : '') + "<strong>" + value + '</strong>';
					}
				}
				return options;
			}
		});
	</script>
	<div class="dashboardWidgetHeader">
		<div class="d-flex flex-row flex-nowrap no-gutters justify-content-between">
			{include file=\App\Layout::getTemplatePath('dashboards/WidgetHeaderTitle.tpl', $MODULE_NAME)}
			{include file=\App\Layout::getTemplatePath('dashboards/WidgetHeaderButtons.tpl', $MODULE_NAME)}
		</div>
		<hr class="widgetHr" />
		<div class="row no-gutters">
			<div class="col-ceq-xsm-6">
				<div class="input-group input-group-sm">
					<div class=" input-group-prepend">
						<span class="input-group-text u-cursor-pointer js-date__btn" data-js="click">
							<span class="fas fa-calendar-alt"></span>
						</span>
					</div>
					<input type="text" name="time" title="{\App\Language::translate('Created Time', $MODULE_NAME)}" class="dateRangeField form-control widgetFilter text-center" value="{implode(',',$DTIME)}" aria-label="Small" aria-describedby="inputGroup-sizing-sm" />
				</div>
			</div>
		</div>
	</div>
	<div class="dashboardWidgetContent">
		{include file=\App\Layout::getTemplatePath('dashboards/DashBoardWidgetContents.tpl', $MODULE_NAME)}
	</div>
{/strip}
