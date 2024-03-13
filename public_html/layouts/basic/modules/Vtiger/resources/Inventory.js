/* {[The file is published on the basis of YetiForce Public License 5.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} */
'use strict';

$.Class(
	'Vtiger_Inventory_Js',
	{
		inventoryInstance: false,

		/**
		 * Get inventory instance
		 * @param {jQuery} container
		 * @returns {Vtiger_Inventory_Js}
		 */
		getInventoryInstance: function (container) {
			if (this.inventoryInstance === false) {
				let moduleClassName = container.find('[name="module"]').val() + '_Inventory_Js';
				if (typeof window[moduleClassName] === 'undefined') {
					moduleClassName = 'Vtiger_Inventory_Js';
				}
				if (typeof window[moduleClassName] !== 'undefined') {
					this.inventoryInstance = new window[moduleClassName]();
					this.inventoryInstance.registerEvents(container);
				}
			}
			return this.inventoryInstance;
		}
	},
	{
		form: false,
		discount: false,
		tax: false,
		container: false,
		inventoryContainer: false,
		inventoryHeadContainer: false,
		summaryTaxesContainer: false,
		summaryDiscountContainer: false,
		summaryCurrenciesContainer: false,
		rowClass: 'tr.inventoryRow',
		discountModalFields: [
			'aggregationType',
			'globalDiscount',
			'groupCheckbox',
			'groupDiscount',
			'individualDiscount',
			'individualDiscountType',
			'additionalDiscount'
		],
		taxModalFields: ['aggregationType', 'globalTax', 'groupCheckbox', 'groupTax', 'individualTax', 'regionalTax'],
		/**
		 * Get current form element
		 * @returns {jQuery}
		 */
		getForm() {
			return this.form;
		},
		/**
		 * Get current form element
		 * @returns {jQuery}
		 */
		loadConfig() {
			let discontConfig = this.form.find('.js-discount-config');
			let taxConfig = this.form.find('.js-tax-config');

			this.discount = discontConfig.length ? JSON.parse(discontConfig.val()) : {};
			this.tax = taxConfig.length ? JSON.parse(taxConfig.val()) : {};
		},
		/**
		 * Function that is used to get the line item container
		 * @return : jQuery object
		 */
		getContainer: function () {
			if (this.container === false) {
				this.container = $('.js-inv-container');
			}
			return this.container;
		},
		getInventoryItemsContainer: function () {
			return this.getContainer().find('.inventoryItems');
		},
		getInventoryHeadContainer: function () {
			if (this.inventoryHeadContainer === false) {
				this.inventoryHeadContainer = $('.inventoryHeader');
			}
			return this.inventoryHeadContainer;
		},
		getInventorySummaryDiscountContainer: function () {
			if (this.summaryDiscountContainer === false) {
				this.summaryDiscountContainer = $('.inventorySummaryDiscounts');
			}
			return this.summaryDiscountContainer;
		},
		getInventorySummaryTaxesContainer: function () {
			if (this.summaryTaxesContainer === false) {
				this.summaryTaxesContainer = $('.inventorySummaryTaxes');
			}
			return this.summaryTaxesContainer;
		},
		getInventorySummaryCurrenciesContainer: function () {
			if (this.summaryCurrenciesContainer === false) {
				this.summaryCurrenciesContainer = $('.inventorySummaryCurrencies');
			}
			return this.summaryCurrenciesContainer;
		},
		getNextLineItemRowNumber: function () {
			let $inventoryItemsNo = $('#inventoryItemsNo');
			let rowNumber = parseInt($inventoryItemsNo.val()) + 1;
			$inventoryItemsNo.val(rowNumber);
			return rowNumber;
		},
		getAccountId: function () {
			let accountReferenceField = $('#accountReferenceField').val();
			if (accountReferenceField != '') {
				return $('[name="' + accountReferenceField + '"]').val();
			}
			return '';
		},
		checkDeleteIcon: function () {
			if (this.getInventoryItemsContainer().find(this.rowClass).length > 1) {
				this.showLineItemsDeleteIcon();
			} else if (app.getMainParams('isRequiredInventory')) {
				this.hideLineItemsDeleteIcon();
			}
		},
		showLineItemsDeleteIcon: function () {
			this.getInventoryItemsContainer().find('.deleteRow').removeClass('d-none');
		},
		hideLineItemsDeleteIcon: function () {
			this.getInventoryItemsContainer().find('.deleteRow').addClass('d-none');
		},
		getClosestRow: function (element) {
			return element.closest(this.rowClass);
		},
		/**
		 * Function which will return the basic row which can be used to add new rows
		 * @return jQuery object which you can use to
		 */
		getBasicRow: function () {
			return this.getForm().find('.js-inventory-base-item').eq(0).clone(true, true);
		},
		isRecordSelected: function (element) {
			let parentRow = element.closest('tr');
			let productField = parentRow.find('.recordLabel');
			return productField.validationEngine('validate');
		},
		getTaxModeSelectElement: function (row) {
			let items = this.getInventoryHeadContainer();
			if (items.find('thead .js-taxmode').length > 0) {
				return $('.js-taxmode');
			}
			if (row) {
				return row.find('.js-taxmode');
			} else {
				return false;
			}
		},
		isIndividualTaxMode: function (row) {
			let selectedOption = this.getTaxModeSelectElement(row).find('option:selected');
			if (selectedOption.length !== 0) {
				return selectedOption.val() == 1;
			}
			return this.tax.default_mode == 1;
		},
		isGroupTaxMode: function () {
			let selectedOption = this.getTaxModeSelectElement();
			if (selectedOption && (selectedOption = selectedOption.find('option:selected')) && selectedOption.length !== 0) {
				return selectedOption.val() == 0;
			}
			return this.tax.default_mode == 0;
		},
		showIndividualTax: function () {
			let thisInstance = this;
			let groupTax = thisInstance.getInventoryItemsContainer().find('.js-inv-tax_global');
			let items = thisInstance.getInventoryItemsContainer().find('.js-inventory-items-body');
			let newRow = $('#blackIthemTable').find('tbody');
			if (thisInstance.isIndividualTaxMode()) {
				groupTax.addClass('d-none');
				items.find('.changeTax').removeClass('d-none');
				newRow.find('.changeTax').removeClass('d-none');
				let parentRow = thisInstance.getInventoryItemsContainer();
				let taxParam = { aggregationType: 'global' };

				parentRow.find(thisInstance.rowClass).each(function () {
					let thisItem = $(this);
					taxParam['globalTax'] = parseFloat(thisItem.find('.js-tax').attr('data-default-tax'));
					thisInstance.setTaxParam(thisItem, taxParam);
				});
			} else {
				thisInstance.setTax(items, 0);
				thisInstance.setTaxPercent(items, 0);
				thisInstance.setTaxParam(items, []);
				thisInstance.setDefaultGlobalTax();
				groupTax.removeClass('d-none');
				items.find('.changeTax').addClass('d-none');
				newRow.find('.changeTax').addClass('d-none');
			}
			thisInstance.rowsCalculations();
		},
		setDefaultGlobalTax: function () {
			let thisInstance = this;
			let parentRow = thisInstance.getInventoryItemsContainer();
			let taxDefaultValue = thisInstance
				.getInventorySummaryTaxesContainer()
				.find('.js-default-tax')
				.data('tax-default-value');
			let isGroupTax = thisInstance.isGroupTaxMode();
			let summaryContainer = $('#blackIthemTable');
			if (isGroupTax) {
				let taxParam = thisInstance.getTaxParams(summaryContainer);
				if (taxParam === false && taxDefaultValue) {
					taxParam = { aggregationType: 'global' };
					taxParam['globalTax'] = taxDefaultValue;
					taxParam['individualTax'] = '';
				}
				if (taxParam) {
					thisInstance.setTaxParam(summaryContainer, taxParam);
					thisInstance.setTaxParam(parentRow, taxParam);
					parentRow.closest('.inventoryItems').data('taxParam', JSON.stringify(taxParam));
					parentRow.find(thisInstance.rowClass).each(function () {
						thisInstance.quantityChangeActions($(this));
					});
				}
			} else {
				thisInstance.setTaxParam(summaryContainer, []);
				parentRow.closest('.inventoryItems').data('taxParam', '[]');
			}
		},
		/**
		 * Get discount mode
		 * @returns {int}
		 */
		getDiscountMode: function () {
			let selectedOption = this.getContainer().find('.js-discountmode option:selected');
			return selectedOption.length ? selectedOption.val() : this.discount.default_mode;
		},
		getCurrency: function () {
			return $('.js-currency', this.getInventoryHeadContainer()).find('option:selected').val();
		},
		/**
		 * Get discount aggregation
		 * @returns {int}
		 */
		getDiscountAggregation: function () {
			const element = $('.js-discount_aggreg', this.getInventoryHeadContainer()).find('option:selected');
			if (element.length) {
				return parseInt(element.val());
			}
			return parseInt(this.discount.aggregation);
		},
		getTax: function (row) {
			const self = this;
			let taxParams = row.find('.taxParam').val();
			if (taxParams == '' || taxParams == '[]' || taxParams == undefined) return 0;
			taxParams = JSON.parse(taxParams);
			let valuePrices = this.getNetPrice(row);
			let taxRate = 0;
			let types = taxParams.aggregationType;
			if (typeof types == 'string') {
				types = [types];
			}
			if (types) {
				types.forEach(function (entry) {
					let taxValue = 0;
					switch (entry) {
						case 'individual':
							taxValue = taxParams.individualTax;
							break;
						case 'global':
							taxValue = taxParams.globalTax;
							break;
						case 'group':
							taxValue = taxParams.groupTax;
							break;
						case 'regional':
							taxValue = taxParams.regionalTax;
							break;
					}
					taxRate += valuePrices * (taxValue / 100);
					if (self.tax.aggregation == 2) {
						valuePrices = valuePrices + taxRate;
					}
				});
			}
			return taxRate;
		},
		getTaxPercent: function (row) {
			let taxParams = row.find('.taxParam').val();
			if (taxParams == '' || taxParams == '[]' || taxParams == undefined) return 0;
			taxParams = JSON.parse(taxParams);
			let taxPercent = 0;
			let types =
				typeof taxParams.aggregationType === 'string' ? [taxParams.aggregationType] : taxParams.aggregationType;
			types.forEach(function (aggregationType) {
				taxPercent += taxParams[aggregationType + 'Tax'] || 0;
			});
			return taxPercent;
		},
		getTaxParams: function (row) {
			let taxParams = row.find('.taxParam').val();
			if (taxParams == '' || taxParams == '[]' || taxParams == undefined) return false;
			return JSON.parse(taxParams);
		},
		getQuantityValue: function (row) {
			return $('.qty', row).getNumberFromValue();
		},
		getUnitPriceValue: function (row) {
			return $('.unitPrice', row).getNumberFromValue();
		},
		getDiscount: function (row) {
			let discountParams = row.find('.discountParam').val();
			if (discountParams == '' || discountParams == 'null' || discountParams == '[]' || discountParams == undefined) {
				return 0;
			}
			const aggregation = this.getDiscountAggregation();
			discountParams = JSON.parse(discountParams);
			let valuePrices = this.getTotalPrice(row),
				discountRate = 0,
				types = discountParams.aggregationType;
			if (typeof types == 'string') {
				types = [types];
			}
			if (types) {
				types.forEach((entry) => {
					switch (entry) {
						case 'global':
							discountRate += valuePrices * (discountParams.globalDiscount / 100);
							break;
						case 'group':
							discountRate += valuePrices * ((discountParams.groupDiscount ? discountParams.groupDiscount : 0) / 100);
							break;
						case 'individual':
							if (discountParams.individualDiscountType === 'percentage') {
								discountRate += valuePrices * (discountParams.individualDiscount / 100);
							} else {
								discountRate += discountParams.individualDiscount;
							}
							break;
						case 'additional':
							discountRate += valuePrices * (discountParams.additionalDiscount / 100);
							break;
					}
					if (aggregation == this.AGGREGATION_CASCADE) {
						valuePrices = valuePrices - (discountParams.type === 'markup' ? -discountRate : discountRate);
					}
				});
			}
			return discountRate;
		},
		getNetPrice: function (row) {
			let discount = this.getDiscount(row);
			let discountParams = row.find('.discountParam').val();
			if (discount && discountParams && JSON.parse(discountParams).type === 'markup') {
				discount = -discount;
			}

			return this.getTotalPrice(row) - discount;
		},
		getTotalPrice: function (row) {
			return this.getQuantityValue(row) * this.getUnitPriceValue(row);
		},
		getGrossPrice: function (row) {
			return $('.grossPrice', row).getNumberFromValue();
		},
		getPurchase: function (row) {
			let qty = this.getQuantityValue(row);
			let element = $('.purchase', row);
			let purchase = 0;
			if (element.length > 0) {
				purchase = App.Fields.Double.formatToDb(element.val());
			}
			return purchase * qty;
		},
		getSummaryGrossPrice: function () {
			let thisInstance = this;
			let price = 0;
			this.getInventoryItemsContainer()
				.find(thisInstance.rowClass)
				.each(function () {
					price += thisInstance.getGrossPrice($(this));
				});
			return App.Fields.Double.formatToDb(price);
		},
		/**
		 * Set currency
		 * @param {int} val
		 */
		setCurrency(val) {
			this.getInventoryHeadContainer().find('.js-currency').val(val).trigger('change');
		},
		/**
		 * Set currency param
		 * @param {string} val json string
		 */
		setCurrencyParam(val) {
			this.getInventoryHeadContainer().find('.js-currencyparam').val(val);
		},
		/**
		 * Set discount mode
		 * @param {int} val
		 */
		setDiscountMode(val) {
			this.getInventoryHeadContainer().find('.js-discountmode').val(val).trigger('change');
		},
		/**
		 * Set tax mode
		 * @param {int} val
		 */
		setTaxMode(val) {
			this.getInventoryHeadContainer().find('.js-taxmode').val(val).trigger('change');
		},
		/**
		 * Set inventory id
		 * @param {jQuery} row
		 * @param {int} val
		 * @param {string} display
		 */
		setName(row, val, display) {
			row.find('.js-name').val(val).trigger('change');
			row.find('.js-name_display').val(display).attr('readonly', 'true').trigger('change');
		},
		/**
		 * Set inventory row quantity
		 * @param {jQuery} row
		 * @param {int} val
		 */
		setQuantity(row, val) {
			row.find('.qty').val(val).trigger('change');
		},
		/**
		 * Set unit original (db) value
		 * @param {jQuery} row
		 * @param {string} val
		 * @param {string} display
		 */
		setUnit(row, val, display) {
			row.find('.unit').val(val).trigger('change');
			row.find('.unitText').text(display).trigger('change');
		},
		/**
		 * Set subUnit original (db) value
		 * @param {jQuery} row
		 * @param {string} val
		 * @param {string} display
		 */
		setSubUnit(row, val, display) {
			row.find('.subunit').val(val);
			row.find('.subunitText').val(display);
		},
		/**
		 * Set inventory row comment
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setComment(row, val) {
			row
				.parent()
				.find('[numrowex=' + row.attr('numrow') + '] .comment')
				.val(val);
		},
		/**
		 * Set inventory row unit price
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setUnitPrice: function (row, val) {
			val = App.Fields.Double.formatToDisplay(val);
			row.find('.unitPrice').val(val).attr('title', val);
			return this;
		},
		/**
		 * Set inventory row purchase
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setPurchase: function (row, val) {
			row.find('.purchase').val(App.Fields.Double.formatToDisplay(val));
			return this;
		},
		/**
		 * Set inventory row net price
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setNetPrice: function (row, val) {
			val = App.Fields.Double.formatToDisplay(val);
			$('.netPriceText', row).text(val);
			$('.netPrice', row).val(val);
		},
		/**
		 * Set inventory row gross price
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setGrossPrice: function (row, val) {
			val = App.Fields.Double.formatToDisplay(val);
			$('.grossPriceText', row).text(val);
			$('.grossPrice', row).val(val);
		},
		/**
		 * Set inventory row total price
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setTotalPrice: function (row, val) {
			val = App.Fields.Double.formatToDisplay(val);
			$('.totalPriceText', row).text(val);
			$('.totalPrice', row).val(val);
		},
		/**
		 * Set inventory row margin
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setMargin: function (row, val) {
			$('.margin', row).val(App.Fields.Double.formatToDisplay(val));
		},
		/**
		 * Set inventory row margin percent
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setMarginP: function (row, val) {
			$('.marginp', row).val(App.Fields.Double.formatToDisplay(val));
		},
		/**
		 * Set inventory row discount
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setDiscount: function (row, val) {
			$('.discount', row).val(App.Fields.Double.formatToDisplay(val));
		},
		/**
		 * Set inventory row discount param
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setDiscountParam: function (row, val) {
			$('.discountParam', row).val(JSON.stringify(val));
		},
		/**
		 * Set inventory row tax
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setTax: function (row, val) {
			$('.tax', row).val(App.Fields.Double.formatToDisplay(val));
		},
		/**
		 * Set inventory row tax percent
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setTaxPercent: function (row, val) {
			$('.js-tax-percent', row).val(App.Fields.Double.formatToDisplay(val));
		},
		/**
		 * Set inventory row tax param
		 * @param {jQuery} row
		 * @param {string} val
		 */
		setTaxParam: function (row, val) {
			$('.taxParam', row).val(JSON.stringify(val));
		},
		quantityChangeActions: function (row) {
			this.rowCalculations(row);
			this.summaryCalculations();
		},
		rowCalculations: function (row) {
			this.calculateTotalPrice(row);
			this.calculateDiscounts(row);
			this.calculateNetPrice(row);
			this.calculateTaxes(row);
			this.calculateGrossPrice(row);
			this.calculateMargin(row);
			app.event.trigger('Inventory.RowCalculations', this, row);
		},
		rowsCalculations: function () {
			const self = this;
			this.getInventoryItemsContainer()
				.find(self.rowClass)
				.each(function () {
					let row = $(this);
					self.syncHeaderData(row);
					self.quantityChangeActions(row);
				});
			self.calculateItemNumbers();
		},
		calculateDiscounts: function (row) {
			this.setDiscount(row, this.getDiscount(row));
		},
		calculateTaxes: function (row) {
			this.setTax(row, this.getTax(row));
			this.setTaxPercent(row, this.getTaxPercent(row));
		},
		summaryCalculations: function () {
			this.getInventoryItemsContainer()
				.find('tfoot .wisableTd')
				.each((_n, e) => {
					this.calculateSummary($(e));
				});
			this.calculateDiscountSummary();
			this.calculateTaxSummary();
			this.calculateCurrenciesSummary();
			this.calculateMarginPSummary();
			this.summaryGroupCalculations();
		},
		/**
		 * Get Items
		 * @returns {jQuery} Items
		 */
		getItems: function () {
			return this.getInventoryItemsContainer().find('.inventoryRow');
		},
		/**
		 * Get groups
		 * @returns {jQuery} Group rows
		 */
		getGroups: function () {
			return this.getInventoryItemsContainer().find('.inventoryRowGroup');
		},
		/**
		 * Get group items
		 * @param {jQuery} groupRow
		 * @returns {jQuery} Items
		 */
		getGroupItems: function (groupRow) {
			return groupRow.nextUntil('tr.inventoryRowGroup').filter('tr.inventoryRow');
		},
		/**
		 * Get item group
		 * @param {jQuery} row
		 * @returns {jQuery} group row
		 */
		getGroupFromItem: function (row) {
			let classElement = 'inventoryRowGroup';
			while (row.is('tr') && !row.hasClass(classElement)) {
				row = row.prev();
			}

			return row.hasClass(classElement) ? row : null;
		},
		summaryGroupCalculations: function () {
			this.getGroups().each((_n, e) => {
				let groupRow = $(e);
				let items = this.getGroupItems(groupRow);
				groupRow.find('.js-inv-container-group-summary').each((_n, e) => {
					this.calculateSummary($(e), items);
				});
				this.calculateMarginPSummary(groupRow);
			});
		},
		calculateSummary: function (element, rows) {
			let thisInstance = this;
			let sum = 0;
			let fieldName = typeof element === 'string' ? element : element.data('sumfield');
			if (!rows) {
				rows = this.getInventoryItemsContainer().find(thisInstance.rowClass);
			}
			rows.each(function () {
				let e = $(this).find('.' + fieldName);
				if (e.length > 0) {
					sum += App.Fields.Double.formatToDb(e.val());
				}
			});
			if (typeof element === 'object') {
				element.get(0).innerText = App.Fields.Double.formatToDisplay(sum);
			}

			return sum;
		},
		calculateMarginPSummary: function (sumRow) {
			if (!sumRow) {
				sumRow = this.getInventoryItemsContainer().find('tfoot');
			}
			let totalPriceField =
					sumRow.find('[data-sumfield="netPrice"]').length > 0
						? sumRow.find('[data-sumfield="netPrice"]')
						: sumRow.find('[data-sumfield="totalPrice"]'),
				sumPrice = totalPriceField.getNumberFromText(),
				purchase = 0,
				marginp = 0;

			let rows = sumRow.hasClass('inventoryRowGroup')
				? this.getGroupItems(sumRow)
				: this.getInventoryItemsContainer().find(this.rowClass);

			rows.each(function () {
				let qty = $(this).find('.qty').getNumberFromValue(),
					purchasePrice = $(this).find('.purchase').getNumberFromValue();
				if (qty > 0 && purchasePrice > 0) {
					purchase += qty * purchasePrice;
				}
			});

			let subtraction = sumPrice - purchase;
			if (purchase !== 0 && sumPrice !== 0) {
				marginp = (subtraction * 100) / purchase;
			}
			sumRow.find('[data-sumfield="marginP"]').text(App.Fields.Double.formatToDisplay(marginp) + '%');
		},
		calculateDiscountSummary: function () {
			let thisInstance = this;
			let discount = thisInstance.getAllDiscount();
			let container = thisInstance.getInventorySummaryDiscountContainer();
			container.find('input').val(App.Fields.Double.formatToDisplay(discount));
		},
		getAllDiscount: function () {
			let thisInstance = this;
			let discount = 0;
			this.getInventoryItemsContainer()
				.find(thisInstance.rowClass)
				.each(function (index) {
					let row = $(this);
					discount += thisInstance.getDiscount(row);
				});
			return discount;
		},
		calculateCurrenciesSummary: function () {
			let container = this.getInventorySummaryCurrenciesContainer(),
				selected = $('.js-currency option:selected', this.getInventoryHeadContainer()),
				base = $('.js-currency option[data-base-currency="1"]', this.getInventoryHeadContainer()),
				conversionRate = selected.data('conversionRate'),
				baseConversionRate = base.data('conversionRate');
			if (conversionRate == baseConversionRate) {
				container.addClass('d-none');
				return;
			}
			conversionRate = parseFloat(baseConversionRate) / parseFloat(conversionRate);
			container.removeClass('d-none');
			let taxes = this.getAllTaxes();
			let sum = 0;
			container.find('.js-panel__body').html('');
			$.each(taxes, function (index, value) {
				if (value != undefined) {
					value = value * conversionRate;
					let row = container.find('.d-none .form-group').clone();
					row.find('.percent').text(index + '%');
					row.find('input').val(App.Fields.Double.formatToDisplay(value));
					row.appendTo(container.find('.js-panel__body'));
					sum += value;
				}
			});
			container.find('.js-panel__footer input').val(App.Fields.Double.formatToDisplay(sum));
		},
		calculateTaxSummary: function () {
			let thisInstance = this;
			let taxes = thisInstance.getAllTaxes();
			let container = thisInstance.getInventorySummaryTaxesContainer();
			container.find('.js-panel__body').html('');
			let sum = 0;
			for (let index in taxes) {
				let row = container.find('.d-none .form-group').clone();
				row.find('.percent').text(App.Fields.Double.formatToDisplay(index) + '%');
				row.find('input').val(App.Fields.Double.formatToDisplay(taxes[index]));
				row.appendTo(container.find('.js-panel__body'));
				sum += taxes[index];
			}
			container.find('.js-panel__footer input').val(App.Fields.Double.formatToDisplay(sum));
		},
		getAllTaxes: function () {
			let thisInstance = this;
			let tax = [];
			let typeSummary = $('.aggregationTypeTax').val();
			this.getInventoryItemsContainer()
				.find(thisInstance.rowClass)
				.each(function () {
					let row = $(this);
					let netPrice = thisInstance.getNetPrice(row);
					let params = row.find('.taxParam').val();
					if (params != '' && params != '[]' && params != undefined) {
						let param = JSON.parse(params);
						if (typeof param.aggregationType == 'string') {
							param.aggregationType = [param.aggregationType];
						}
						if (param.aggregationType)
							$.each(param.aggregationType, function (_, name) {
								name = name + 'Tax';
								if (param[name] == undefined) {
									return;
								}
								let percent = parseFloat(param[name]);
								let old = 0;
								if (tax[percent] != undefined) {
									old = parseFloat(tax[percent]);
								}
								let taxRate = netPrice * (percent / 100);
								tax[percent] = old + taxRate;
								if (typeSummary == '2') {
									netPrice += taxRate;
								}
							});
					}
				});
			return tax;
		},
		calculateNetPrice: function (row) {
			this.setNetPrice(row, this.getNetPrice(row));
		},
		calculateGrossPrice: function (row) {
			let netPrice = this.getNetPrice(row);
			if (this.isIndividualTaxMode(row) || this.isGroupTaxMode(row)) {
				netPrice += this.getTax(row);
			}
			this.setGrossPrice(row, netPrice);
		},
		calculateTotalPrice: function (row) {
			this.setTotalPrice(row, this.getTotalPrice(row));
		},
		calculateMargin: function (row) {
			let netPrice;
			if ($('.netPrice', row).length) {
				netPrice = this.getNetPrice(row);
			} else {
				netPrice = this.getTotalPrice(row) - this.getDiscount(row);
			}
			let purchase = this.getPurchase(row);
			let margin = netPrice - purchase;
			this.setMargin(row, margin);
			let marginp = '0';
			if (purchase !== 0) {
				marginp = (margin / purchase) * 100;
			}
			this.setMarginP(row, marginp);
		},
		AGGREGATION_CANNOT_BE_COMBINED: 0,
		AGGREGATION_IN_TOTAL: 1,
		AGGREGATION_CASCADE: 2,
		calculateDiscount: function (_row, modal) {
			const netPriceBeforeDiscount = App.Fields.Double.formatToDb(modal.find('.valueTotalPrice').text()),
				aggregationType = modal.find('.aggregationType').val(),
				isMarkup = modal.find('.js-inv--discount-type.markup:checked').length === 1;
			let valuePrices = netPriceBeforeDiscount;

			let getValue = function (type, netPrice, isMarkup) {
				let value = 0;
				let element = modal.find(`.js-active .${type}`);
				let customCheckTrue = true;
				if (type === 'groupValue' && modal.find('.js-active .groupCheckbox').prop('checked') !== true) {
					customCheckTrue = false;
				}
				if (element.length > 0 && customCheckTrue) {
					value = App.Fields.Double.formatToDb(element.val());
				}
				if (
					type === 'individualDiscountValue' &&
					modal.find('.js-active .individualDiscountType:checked').val() == 'percentage'
				) {
					value = netPrice * (value / 100);
				}

				return value && isMarkup ? -value : value;
			};

			let globalDiscount, accountDiscount, individualDiscount, additionalDiscount;
			if (aggregationType == this.AGGREGATION_CANNOT_BE_COMBINED || aggregationType == this.AGGREGATION_IN_TOTAL) {
				globalDiscount = getValue('globalDiscount', netPriceBeforeDiscount, isMarkup); // percentage
				accountDiscount = getValue('groupValue', netPriceBeforeDiscount, isMarkup); // percentage
				individualDiscount = getValue('individualDiscountValue', netPriceBeforeDiscount, isMarkup); // amount
				additionalDiscount = getValue('additionalDiscountValue', netPriceBeforeDiscount, isMarkup); // percentage

				valuePrices -= netPriceBeforeDiscount * (globalDiscount / 100);
				valuePrices -= netPriceBeforeDiscount * (additionalDiscount / 100);
				valuePrices -= individualDiscount;
				valuePrices -= netPriceBeforeDiscount * (accountDiscount / 100);
			} else if (aggregationType == this.AGGREGATION_CASCADE) {
				globalDiscount = getValue('globalDiscount', valuePrices, isMarkup); // percentage
				valuePrices = valuePrices * ((100 - globalDiscount) / 100);
				accountDiscount = getValue('groupValue', valuePrices, isMarkup); // percentage
				valuePrices = valuePrices * ((100 - accountDiscount) / 100);
				individualDiscount = getValue('individualDiscountValue', valuePrices, isMarkup); // amount
				valuePrices = valuePrices - individualDiscount;
				additionalDiscount = getValue('additionalDiscountValue', valuePrices, isMarkup); // percentage
				valuePrices = valuePrices * ((100 - additionalDiscount) / 100);
			}

			let discountValue = netPriceBeforeDiscount - valuePrices;
			modal.find('.valuePrices').text(App.Fields.Double.formatToDisplay(valuePrices));
			modal.find('.valueDiscount').text(App.Fields.Double.formatToDisplay(isMarkup ? -discountValue : discountValue));
		},
		calculateTax: function (_row, modal) {
			let netPriceWithoutTax = App.Fields.Double.formatToDb(modal.find('.valueNetPrice').text()),
				valuePrices = netPriceWithoutTax,
				globalTax = 0,
				groupTax = 0,
				regionalTax = 0,
				individualTax = 0;

			let taxType = modal.find('.taxsType').val();
			if (taxType == '0' || taxType == '1') {
				if (modal.find('.js-active .globalTax').length > 0) {
					globalTax = App.Fields.Double.formatToDb(modal.find('.js-active .globalTax').val());
				}
				if (modal.find('.js-active .individualTaxValue').length > 0) {
					let value = App.Fields.Double.formatToDb(modal.find('.js-active .individualTaxValue').val());
					individualTax = (value / 100) * valuePrices;
				}
				if (modal.find('.js-active .groupTax').length > 0) {
					groupTax = App.Fields.Double.formatToDb(modal.find('.groupTax').val());
					groupTax = netPriceWithoutTax * (groupTax / 100);
				}
				if (modal.find('.js-active .regionalTax').length > 0) {
					regionalTax = App.Fields.Double.formatToDb(modal.find('.regionalTax').val());
					regionalTax = netPriceWithoutTax * (regionalTax / 100);
				}

				valuePrices = valuePrices * ((100 + globalTax) / 100);
				valuePrices = valuePrices + individualTax;
				valuePrices = valuePrices + groupTax;
				valuePrices = valuePrices + regionalTax;
			} else if (taxType == '2') {
				modal.find('.js-active').each(function () {
					let panel = $(this);
					if (panel.find('.globalTax').length > 0) {
						valuePrices = valuePrices * ((100 + App.Fields.Double.formatToDb(panel.find('.globalTax').val())) / 100);
					} else if (panel.find('.groupTax').length > 0) {
						valuePrices = valuePrices * ((100 + App.Fields.Double.formatToDb(panel.find('.groupTax').val())) / 100);
					} else if (panel.find('.regionalTax').length > 0) {
						valuePrices = valuePrices * ((100 + App.Fields.Double.formatToDb(panel.find('.regionalTax').val())) / 100);
					} else if (panel.find('.individualTaxValue').length > 0) {
						valuePrices =
							((App.Fields.Double.formatToDb(panel.find('.individualTaxValue').val()) + 100) / 100) * valuePrices;
					}
				});
			}
			if (netPriceWithoutTax) {
				let taxValue = ((valuePrices - netPriceWithoutTax) / netPriceWithoutTax) * 100;
				modal.find('.js-tax-value').text(App.Fields.Double.formatToDisplay(taxValue));
			}
			modal.find('.valuePrices').text(App.Fields.Double.formatToDisplay(valuePrices));
			modal.find('.valueTax').text(App.Fields.Double.formatToDisplay(valuePrices - netPriceWithoutTax));
		},
		updateRowSequence: function () {
			let items = this.getInventoryItemsContainer();
			items.find(this.rowClass).each(function (index) {
				$(this)
					.find('.sequence')
					.val(index + 1);
			});
		},
		registerInventorySaveData: function () {
			app.event.on('EditView.preValidation', (_e, _view) => {
				this.showAllItems();
			});
			this.form.on(Vtiger_Edit_Js.recordPreSave, () => {
				this.syncHeaderData();
				if (!this.checkLimits(this.form)) {
					return false;
				}
			});
		},
		syncHeaderData(container) {
			this.renumberHeaderItems();
			let header = this.getInventoryHeadContainer();
			if (typeof container === 'undefined') {
				container = this.getContainer();
			}
			container.find('.js-sync').each((_, e) => {
				let element = $(e);
				let value;
				let name = element.data('syncId');
				let classElement = '.js-' + element.data('syncId');
				if (name === 'grouplabel' || name === 'groupid') {
					let row = element.closest(this.rowClass);
					while (row.is('tr') && row.prev().find(classElement).length < 1) {
						row = row.prev();
					}
					value = row.prev().find(classElement);
				} else {
					value = header.find(classElement);
				}
				element.val(value.length ? value.val() : element.data('default'));
			});
		},
		/**
		 * Renumber header items
		 */
		renumberHeaderItems() {
			this.getContainer()
				.find('.js-inv-container-content .js-groupid')
				.each((n, e) => {
					e.value = n + 1;
				});
		},
		/**
		 * Function which will be used to handle price book popup
		 * @params :  element - popup image element
		 */
		pricebooksModalHandler: function (element) {
			let lineItemRow = element.closest(this.rowClass);
			let rowName = lineItemRow.find('.rowName');
			let currencyId = this.getCurrency() || CONFIG.defaultCurrencyId;
			app.showRecordsList(
				{
					module: 'PriceBooks',
					src_module: $('[name="popupReferenceModule"]', rowName).val(),
					src_record: $('.sourceField', rowName).val(),
					src_field: $('[name="popupReferenceModule"]', rowName).data('field'),
					search_params: JSON.stringify([
						[
							['currency_id', 'e', currencyId],
							['active', 'e', 1]
						]
					]),
					lockedFields: ['currency_id', 'active'],
					cvEnabled: false,
					currency_id: currencyId,
					additionalData: { currency_id: currencyId }
				},
				(_modal, instance) => {
					instance.setSelectEvent((responseData) => {
						AppConnector.request({
							module: 'PriceBooks',
							action: 'ProductListPrice',
							record: responseData.id,
							src_record: $('.sourceField', rowName).val()
						}).done((data) => {
							if (data.result) {
								this.setUnitPrice(lineItemRow, data.result);
								this.quantityChangeActions(lineItemRow);
							} else {
								app.errorLog('Incorrect data', responseData);
							}
						});
					});
				}
			);
		},
		subProductsCashe: [],
		loadSubProducts: function (parentRow, indicator) {
			let thisInstance = this;
			let progressInstace;
			let recordId = $('input.sourceField.js-name', parentRow).val();
			let recordModule = parentRow.find('.rowName input[name="popupReferenceModule"]').val();
			thisInstance.removeSubProducts(parentRow);
			if (recordId == '0' || recordId == '' || $.inArray(recordModule, ['Products', 'Services']) < 0) {
				return false;
			}
			if (thisInstance.subProductsCashe[recordId]) {
				thisInstance.addSubProducts(parentRow, thisInstance.subProductsCashe[recordId]);
				return false;
			}
			let subProrductParams = {
				module: 'Products',
				action: 'SubProducts',
				record: recordId
			};
			if (indicator) {
				progressInstace = $.progressIndicator();
			}
			AppConnector.request(subProrductParams)
				.done(function (data) {
					let responseData = data.result;
					thisInstance.subProductsCashe[recordId] = responseData;
					thisInstance.addSubProducts(parentRow, responseData);
					if (progressInstace) {
						progressInstace.hide();
					}
				})
				.fail(function () {
					if (progressInstace) {
						progressInstace.hide();
					}
				});
		},
		removeSubProducts: function (parentRow) {
			let subProductsContainer = $('.subProductsContainer ul', parentRow);
			subProductsContainer.find('li').remove();
		},
		addSubProducts: function (parentRow, responseData) {
			let subProductsContainer = $('.subProductsContainer ul', parentRow);
			for (let id in responseData) {
				subProductsContainer.append($('<li>').text(responseData[id]));
			}
		},
		mapResultsToFields: function (referenceModule, parentRow, responseData) {
			let unit,
				taxParam = [];
			let thisInstance = this;
			let isGroupTax = thisInstance.isGroupTaxMode();
			for (let id in responseData) {
				let recordData = responseData[id];
				let description = recordData.description;
				let unitPriceValues = recordData.unitPriceValues;
				let unitPriceValuesJson = JSON.stringify(unitPriceValues);
				// Load taxes detail
				if (isGroupTax) {
					let parameters = parentRow.closest('.inventoryItems').data('taxParam');
					if (parameters) {
						taxParam = JSON.parse(parameters);
					}
				} else if (recordData['taxes']) {
					taxParam = { aggregationType: recordData.taxes.type };
					taxParam[recordData.taxes.type + 'Tax'] = recordData.taxes.value;
				}
				if (recordData['taxes']) {
					parentRow.find('.js-tax').attr('data-default-tax', recordData.taxes.value);
				}
				thisInstance.setPurchase(parentRow, recordData.purchase);
				thisInstance.setTaxParam(parentRow, taxParam);
				thisInstance.setTax(parentRow, 0);
				thisInstance.setTaxPercent(parentRow, 0);

				for (let field in recordData['autoFields']) {
					let inputField = parentRow.find(`input.${field},select.${field}`);
					if (inputField.attr('type') === 'checkbox') {
						inputField.prop('checked', recordData['autoFields'][field]);
					} else if (inputField.is('select')) {
						inputField.val(recordData['autoFields'][field]).trigger('change');
					} else {
						inputField.val(recordData['autoFields'][field]);
					}
					if (recordData['autoFields'][field + 'Text']) {
						parentRow.find('.' + field + 'Text').text(recordData['autoFields'][field + 'Text']);
					}
				}
				if (recordData.price !== undefined) {
					thisInstance.setUnitPrice(parentRow, recordData.price);
				}
				if (unitPriceValuesJson !== undefined) {
					$('input.unitPrice', parentRow).attr('list-info', unitPriceValuesJson);
				}
				let commentElement = $('textarea.js-inventory-item-comment', parentRow.next());
				let editorInstance = CKEDITOR.instances[commentElement.attr('id')];
				if (editorInstance) {
					editorInstance.setData(description);
				} else {
					commentElement.val(description);
				}
				if (typeof recordData['autoFields']['unit'] !== 'undefined') {
					unit = recordData['autoFields']['unit'];
				}
				app.event.trigger('Inventory.SelectionItem', thisInstance, parentRow, recordData, referenceModule);
				this.triggerQtyParam(unit, recordData.qtyPerUnit, parentRow);
			}
			if (referenceModule === 'Products') {
				thisInstance.loadSubProducts(parentRow, true);
			}
			thisInstance.quantityChangeActions(parentRow);
		},
		/**
		 * Update qtyparam
		 * @param {null|string} unit
		 * @param int perUnit
		 * @param {jQuery} parentRow
		 */
		triggerQtyParam(unit, perUnit, parentRow) {
			let validationEngine;
			switch (unit) {
				default:
					$('.qtyParamInfo', parentRow).addClass('d-none');
					validationEngine = 'validate[required,funcCall[Vtiger_NumberUserFormat_Validator_Js.invokeValidation]]';
					break;
				case 'pack':
					$('.qtyParamInfo', parentRow).removeClass('d-none').removeClass('active');
					$('.qtyParamInfo', parentRow).attr('data-content', perUnit);
					validationEngine = 'validate[required,funcCall[Vtiger_WholeNumber_Validator_Js.invokeValidation]]';
					break;
				case 'pcs':
					$('.qtyParamInfo', parentRow).addClass('d-none');
					validationEngine = 'validate[required,funcCall[Vtiger_WholeNumber_Validator_Js.invokeValidation]]';
					break;
			}
			$('input.qty', parentRow).attr('data-validation-engine', validationEngine);
		},
		saveDiscountsParameters: function (parentRow, modal) {
			const typeName = 'aggregationType',
				panels = modal.find('[name="' + typeName + '"]:checked');
			let info = {};
			info[typeName] = [];
			panels.each(function () {
				let type = $(this).val(),
					container = $(this).closest('.js-panel');
				if (panels.length > 1) {
					info[typeName].push(type);
				} else {
					info[typeName] = type;
				}
				container.find('[name="' + type + 'Discount"]').each(function () {
					let param = type + 'Discount';
					let element = $(this);
					switch (type) {
						case 'group':
							if (element.closest('.input-group').find('.groupCheckbox').prop('checked')) {
								info[param] = App.Fields.Double.formatToDb(element.val());
							}
							break;
						case 'individual':
							let name = 'individualDiscountType';
							info[name] = container.find('[name="' + name + '"]:checked').val();
							info[param] = App.Fields.Double.formatToDb(element.val());
							break;
						case 'global':
						case 'additional':
							info[param] = App.Fields.Double.formatToDb(element.val());
							break;
					}
				});
			});
			if (modal.find('.js-inv--discount-type.markup:checked').length) {
				info['type'] = 'markup';
			}
			let discoutMode = modal.find('.discountMode').val();
			if (discoutMode == this.DISCOUNT_MODE_GROUP) {
				this.setDiscountParam(this.getGroupItems(parentRow), info);
			} else if (discoutMode == this.DISCOUNT_MODE_GLOBAL) {
				this.setDiscountParam($('#blackIthemTable'), info);
			}
			this.setDiscountParam(parentRow, info);
		},
		saveTaxsParameters: function (parentRow, modal) {
			let info = {};
			const extend = ['aggregationType', 'groupCheckbox', 'individualTaxType'];
			$.each(this.taxModalFields, function (_, param) {
				if ($.inArray(param, extend) >= 0) {
					if (modal.find('[name="' + param + '"]:checked').length > 1) {
						info[param] = [];
						modal.find('[name="' + param + '"]:checked').each(function () {
							info[param].push($(this).val());
						});
					} else {
						info[param] = modal.find('[name="' + param + '"]:checked').val();
					}
				} else {
					info[param] = App.Fields.Double.formatToDb(modal.find('[name="' + param + '"]').val());
				}
			});
			parentRow.data('taxParam', JSON.stringify(info));
			this.setTaxParam(parentRow, info);
			this.setTaxParam($('#blackIthemTable'), info);
		},
		showExpandedRow: function (row) {
			const inventoryRowExpanded = this.getInventoryItemsContainer().find('[numrowex="' + row.attr('numrow') + '"]');
			const element = row.find('.toggleVisibility');
			element.data('status', '1');
			element.removeClass(element.data('off')).addClass(element.data('on'));
			inventoryRowExpanded.removeClass('d-none');
			this.markCommentBtn(row);
		},
		hideExpandedRow: function (row) {
			const inventoryRowExpanded = this.getInventoryItemsContainer().find('[numrowex="' + row.attr('numrow') + '"]');
			const element = row.find('.toggleVisibility');
			element.data('status', '0');
			element.removeClass(element.data('on')).addClass(element.data('off'));
			inventoryRowExpanded.addClass('d-none');
			this.markCommentBtn(row);
		},
		/**
		 * Mark button for extended fields
		 * @param {jQuery} row
		 */
		markCommentBtn: function (row) {
			let rowExpanded = this.getInventoryItemsContainer().find('[numrowex="' + row.attr('numrow') + '"]');
			let value = rowExpanded.find('.js-inventory-item-comment').val();
			const element = row.find('.js-inv-item-btn-icon');
			let removeClass = element.data('active');
			let addClass = element.data('inactive');
			if (value) {
				removeClass = addClass;
				addClass = element.data('active');
			}
			element.removeClass(removeClass).addClass(addClass);
		},
		initTaxParameters: function (parentRow, modal) {
			let parameters;
			if (parentRow.data('taxParam')) {
				parameters = parentRow.data('taxParam');
			} else {
				parameters = parentRow.find('.taxParam').val();
			}
			if (!parameters) {
				return;
			}
			parameters = JSON.parse(parameters.toString());
			$.each(this.taxModalFields, function (_, param) {
				let parameter = parameters[param],
					field = modal.find('[name="' + param + '"]');

				if (field.attr('type') === 'checkbox' || field.attr('type') === 'radio') {
					let array = parameter,
						value;
					if (!$.isArray(array)) {
						array = [array];
					}
					$.each(array, function (_, arrayValue) {
						value = field.filter('[value="' + arrayValue + '"]').prop('checked', true);
						if (param === 'aggregationType') {
							value.closest('.js-panel').find('.js-panel__body').removeClass('d-none');
							value.closest('.js-panel').addClass('js-active');
						}
					});
				} else if (field.prop('tagName') === 'SELECT') {
					field
						.find('option[value="' + parameter + '"]')
						.prop('selected', 'selected')
						.change();
				} else {
					let input = modal.find('[name="' + param + '"]');
					input.val(App.Fields.Double.formatToDisplay(parameter));
					if (param === 'individualTax') {
						input.formatNumber();
					}
				}
			});
			this.calculateTax(parentRow, modal);
		},
		limitEnableSave: false,
		checkLimits: function () {
			const account = this.getAccountId(),
				limit = parseInt(app.getMainParams('inventoryLimit'));
			let response = true;
			if (account == '' || this.limitEnableSave || !limit) {
				return response;
			}
			let progressInstace = $.progressIndicator();
			AppConnector.request({
				async: false,
				dataType: 'json',
				data: {
					module: app.getModuleName(),
					action: 'Inventory',
					mode: 'checkLimits',
					record: account,
					currency: this.getCurrency(),
					price: thisInthisstance.getSummaryGrossPrice()
				}
			})
				.done(function (data) {
					progressInstace.hide();
					if (data.result.status == false) {
						app.showModalWindow(data.result.html, function () {});
						response = false;
					}
				})
				.fail(function () {
					progressInstace.hide();
				});
			return response;
		},
		currencyChangeActions: function (select, option) {
			if (option.data('baseCurrency') !== select.val()) {
				this.showCurrencyChangeModal(select, option);
			} else {
				this.currencyConvertValues(select, option);
				select.data('oldValue', select.val());
			}
		},
		showCurrencyChangeModal: function (select, option) {
			let thisInstance = this;
			if (thisInstance.lockCurrencyChange == true) {
				return;
			}
			thisInstance.lockCurrencyChange = true;
			let block = select.closest('th');
			let modal = block.find('.modelContainer').clone();
			app.showModalWindow(modal, function (data) {
				let modal = $(data);
				let currencyParam = JSON.parse(block.find('.js-currencyparam').val());

				if (currencyParam != false) {
					if (typeof currencyParam[option.val()] === 'undefined') {
						let defaultCurrencyParams = {
							value: 1,
							date: ''
						};
						currencyParam[option.val()] = defaultCurrencyParams;
					}
					modal.find('.currencyName').text(option.text());
					modal.find('.currencyRate').val(currencyParam[option.val()]['value']);
					modal.find('.currencyDate').text(currencyParam[option.val()]['date']);
				}
				modal
					.on('click', 'button[type="submit"]', function () {
						let rate = modal.find('.currencyRate').val();
						let value = App.Fields.Double.formatToDb(rate);
						let conversionRate = 1 / App.Fields.Double.formatToDb(rate);

						option.data('conversionRate', conversionRate);
						currencyParam[option.val()] = {
							date: option.data('conversionDate'),
							value: value.toString(),
							conversion: conversionRate.toString()
						};
						block.find('.js-currencyparam').val(JSON.stringify(currencyParam));

						thisInstance.currencyConvertValues(select, option);
						select.data('oldValue', select.val());
						app.hideModalWindow();
						thisInstance.lockCurrencyChange = false;
					})
					.one('hidden.bs.modal', function () {
						select.val(select.data('oldValue')).change();
						thisInstance.lockCurrencyChange = false;
					});
			});
		},
		currencyConvertValues: function (select, selected) {
			const self = this;
			let previous = select.find('option[value="' + select.data('oldValue') + '"]');
			let conversionRate = selected.data('conversionRate');
			let prevConversionRate = previous.data('conversionRate');
			conversionRate = parseFloat(conversionRate) / parseFloat(prevConversionRate);
			this.getInventoryItemsContainer()
				.find(self.rowClass)
				.each(function (_) {
					let row = $(this);
					self.syncHeaderData(row);
					self.setUnitPrice(row, self.getUnitPriceValue(row) * conversionRate);
					self.setDiscount(row, self.getDiscount(row) * conversionRate);
					self.setTax(row, self.getTax(row) * conversionRate);
					self.setPurchase(row, self.getPurchase(row) * conversionRate);
					self.quantityChangeActions(row);
				});
		},
		/**
		 * Set up all row data that comes from request
		 * @param {jQuery} row
		 * @param {object} rowData
		 */
		setRowData(row, rowData) {
			this.setName(row, rowData.name, rowData.info.name);
			this.setQuantity(
				row,
				App.Fields.Double.formatToDisplay(rowData.qty, App.Fields.Double.FORMAT_TRUNCATE_TRAILING_ZEROS)
			);
			this.setUnit(row, rowData.info.autoFields.unit, rowData.info.autoFields.unitText);
			if (typeof rowData.info.autoFields !== 'undefined' && typeof rowData.info.autoFields.subunit !== 'undefined') {
				this.setSubUnit(row, rowData.info.autoFields.subunit, rowData.info.autoFields.subunitText);
			}
			this.setComment(row, rowData.comment1);
			this.setUnitPrice(row, rowData.price);
			this.setNetPrice(row, rowData.net);
			this.setGrossPrice(row, rowData.gross);
			this.setTotalPrice(row, rowData.total);
			this.setDiscountParam(row, rowData.discountparam ? JSON.parse(rowData.discountparam) : []);
			this.setDiscount(row, rowData.discount);
			this.setTaxParam(row, rowData.taxparam ? JSON.parse(rowData.taxparam) : []);
			this.setTax(row, rowData.tax);
			this.setTaxPercent(row, rowData.tax_percent);
		},
		/**
		 * Add new row to inventory list
		 * @param {string} module
		 * @param {string} baseTableId
		 * @param {object} rowData [optional]
		 */
		addItem(module, baseTableId, rowData = false) {
			const items = this.getInventoryItemsContainer();
			let newRow = this.getBasicRow();
			const sequenceNumber = this.getNextLineItemRowNumber();
			const replaced = newRow.html().replace(/\_NUM_/g, sequenceNumber);
			const moduleLabels = newRow.data('moduleLbls');
			newRow.html(replaced);
			newRow = newRow.find('tr.inventoryRow, tr.inventoryRowExpanded').appendTo(items.find('.js-inventory-items-body'));
			newRow.find('.rowName input[name="popupReferenceModule"]').val(module).data('field', baseTableId);
			newRow.find('.js-module-icon').removeClass().addClass(`yfm-${module}`);
			newRow.find('.rowName span.input-group-text').attr('data-content', moduleLabels[module]);
			newRow.find('.colPicklistField select').each(function (_, select) {
				select = $(select);
				select.find('option').each(function (_, option) {
					option = $(option);
					if (option.data('module') !== module) {
						option.remove();
					}
				});
			});
			this.initItem(newRow);
			Vtiger_Edit_Js.getInstance().registerAutoCompleteFields(newRow);
			if (rowData) {
				this.setRowData(newRow, rowData);
				this.quantityChangeActions(newRow);
			} else {
				this.itemChangeEvent(newRow);
			}
			return newRow;
		},
		/**
		 * Item change post event
		 * @param {jQuery} row
		 */
		itemChangeEvent: function (row) {
			if (row.hasClass('inventoryRowGroup')) {
				this.getItems().each((_, e) => {
					this.itemChangeEvent($(e));
				});
			} else if (this.getDiscountMode() == this.DISCOUNT_MODE_GROUP) {
				let parentRow = this.getGroupFromItem(row);
				let params = parentRow ? parentRow.find('.discountParam').val() : null;
				this.setDiscountParam(row, params ? JSON.parse(params) : []);
				this.rowCalculations(row);
			}
		},
		/**
		 * Add header(group) item
		 * @returns
		 */
		addHeaderItem(data = {}) {
			const items = this.getInventoryItemsContainer();
			let newRow = this.getBasicRow();
			if (data['grouplabel']) {
				let value = data['grouplabel'];
				let el = newRow.find('.js-grouplabel');
				if (
					el.is('select') &&
					![...el.get(0).options]
						.map((o) => o.value)
						.filter((e) => e !== '')
						.includes(value)
				) {
					let newOption = new Option(value, value, true, true);
					el.append(newOption);
				}
				el.val(value);
			}
			newRow = newRow.find('tr.inventoryRowGroup').appendTo(items.find('.js-inventory-items-body'));
			this.initItem(newRow);

			return newRow;
		},
		/**
		 * Get next block ID
		 * @returns int
		 */
		getNextBlockId() {
			let data = [...this.getContainer().find('.js-groupid')].map((o) => parseInt(o.value));
			return Math.max(...data) + 1;
		},

		/**
		 * Register add item button click
		 */
		registerAddItem() {
			this.getContainer()
				.find('.js-inv-add-item')
				.on('click', (e) => {
					this.addItem(e.currentTarget.dataset.module, e.currentTarget.dataset.field, false);
				});
		},
		/**
		 * Register add header row
		 */
		registerAddHeaderItem() {
			this.getContainer()
				.find('.js-inv-add-group')
				.on('click', () => {
					this.addHeaderItem();
				});
		},
		registerSortableItems: function () {
			let thisInstance = this;
			let items = thisInstance.getContainer();
			items.sortable({
				handle: '.dragHandle',
				items: thisInstance.rowClass + ',.inventoryRowGroup',
				revert: true,
				tolerance: 'pointer',
				placeholder: 'ui-state-highlight',
				helper: function (_e, ui) {
					ui.children().each(function (_, element) {
						element = $(element);
						element.width(element.width());
					});
					return ui;
				},
				start: function (_, ui) {
					items.find(thisInstance.rowClass).each(function (_, element) {
						let row = $(element);
						thisInstance.hideExpandedRow(row);
					});
					let num = $(ui.item).attr('numrow');
					items.find('[numrowex="' + num + '"] .js-inventory-item-comment').each(function () {
						App.Fields.Text.destroyEditor($(this));
					});
					ui.item.startPos = ui.item.index();
				},
				stop: function (_, ui) {
					let numrow = $(ui.item).attr('numrow');
					let child = items.find('.numRow' + numrow);
					items.find('[numrow="' + numrow + '"]').after(child);
					App.Fields.Text.Editor.register(child);
					thisInstance.updateRowSequence();
					thisInstance.itemChangeEvent(ui.item);
					thisInstance.summaryGroupCalculations();
				}
			});
		},
		registerShowHideExpanded: function () {
			this.getInventoryItemsContainer().on('click', '.toggleVisibility', (e) => {
				let element = $(e.currentTarget);
				let row = this.getClosestRow(element);
				if (element.data('status') == 0) {
					this.showExpandedRow(row);
				} else {
					this.hideExpandedRow(row);
				}
			});
		},
		/**
		 * Show/Hide group items
		 */
		registerShowHideExpandedGroup: function () {
			this.getInventoryItemsContainer().on('click', '.js-inv-group-collapse-btn', (e) => {
				let btn = $(e.currentTarget);
				let icon = btn.find('.js-toggle-icon');
				let row = btn.closest('tr.inventoryRowGroup');
				let items = this.getGroupItems(row);
				if (icon.hasClass(icon.data('active'))) {
					items.find(`.toggleVisibility.active`).closest('.toggleVisibility').trigger('click');
					items.addClass('d-none');
					btn.removeClass('js-inv-group-collapse-btn__active');
				} else {
					items.removeClass('d-none');
					btn.addClass('js-inv-group-collapse-btn__active');
				}
			});
		},
		showAllItems: function () {
			this.getGroups().find('.js-inv-group-collapse-btn:not(.js-inv-group-collapse-btn__active)').trigger('click');
		},
		registerPriceBookModal: function (container) {
			container.find('.js-price-book-modal').on('click', (e) => {
				let element = $(e.currentTarget);
				let response = this.isRecordSelected(element);
				if (!response) {
					this.pricebooksModalHandler(element);
				}
			});
		},
		registerRowChangeEvent: function (container) {
			container.on('focusout', '.js-inv-format_number', (e) => {
				$(e.currentTarget).formatNumber(e.currentTarget.dataset.format);
			});
			container.on('focusout', '.qty', (e) => {
				let element = $(e.currentTarget);
				element.formatNumber(App.Fields.Double.FORMAT_TRUNCATE_TRAILING_ZEROS);
				this.quantityChangeActions(this.getClosestRow(element));
			});
			container.on('focusout', '.unitPrice', (e) => {
				let element = $(e.currentTarget);
				element.formatNumber();
				this.quantityChangeActions(this.getClosestRow(element));
			});
			container.on('focusout', '.purchase', (e) => {
				let element = $(e.currentTarget);
				element.formatNumber();
				this.quantityChangeActions(this.getClosestRow(element));
			});
			let headContainer = this.getInventoryHeadContainer();
			headContainer.on('change', '.js-taxmode', () => {
				this.showIndividualTax();
			});
			headContainer.on('change', '.js-discountmode', (e) => {
				let element = $(e.currentTarget);
				let mode = parseInt(element.val());
				this.getContainer().find(`.js-change-discount:not([data-mode="${mode}"])`).addClass('d-none');
				this.getContainer().find(`.js-change-discount[data-mode="${mode}"]`).removeClass('d-none');
				let items = this.getInventoryItemsContainer().find('.js-inventory-items-body');

				this.setDiscount(items, 0);
				this.setDiscountParam(items, []);
				this.rowsCalculations();
			});
		},
		registerSubProducts: function () {
			const thisInstance = this;
			thisInstance.form.find('.inventoryItems ' + thisInstance.rowClass).each(function () {
				thisInstance.loadSubProducts($(this), false);
			});
		},
		/**
		 * Register clear reference selection
		 */
		registerClearReferenceSelection() {
			this.form.on('click', '.clearReferenceSelection', (e) => {
				const referenceGroup = $(e.currentTarget).closest('div.referenceGroup');
				if (referenceGroup.length) {
					referenceGroup.find('input[id$="_display"]').val('').removeAttr('readonly');
				} else {
					const row = this.getClosestRow($(e.currentTarget));
					this.removeSubProducts(row);
					row
						.find('.unitPrice,.tax,.discount,.margin,.purchase,.js-tax-percent')
						.val(App.Fields.Double.formatToDisplay(0));
					row.find('.qty').val(1);
					row.find('textarea,.valueVal').val('');
					row.find('.valueText').text('');
					row.find('.qtyParamInfo').addClass('d-none');
					row.find('.recordLabel').val('').removeAttr('readonly');
					if (!this.isGroupTaxMode()) {
						this.setTaxParam(row, []);
					}
					this.quantityChangeActions(row);
				}
			});
		},
		registerDeleteLineItemEvent: function (container) {
			container.on('click', '.deleteRow', (e) => {
				let num = this.getClosestRow($(e.currentTarget)).attr('numrow');
				this.deleteLineItem(num);
			});
			container.on('click', '.js-delete-header-item', (e) => {
				$(e.currentTarget).closest('tr').remove();
				this.syncHeaderData();
				this.rowsCalculations();
			});
		},
		deleteLineItem: function (num) {
			this.getInventoryItemsContainer()
				.find('[numrowex="' + num + '"] .js-inventory-item-comment')
				.each(function () {
					App.Fields.Text.destroyEditor($(this));
				});
			this.getInventoryItemsContainer()
				.find('[numrow="' + num + '"], [numrowex="' + num + '"]')
				.remove();
			this.checkDeleteIcon();
			this.rowsCalculations();
			if (this.getInventoryItemsContainer().find('.inventoryRow').length === 0) {
				$('#inventoryItemsNo').val(0);
			}
			this.updateRowSequence();
		},
		DISCOUNT_MODE_GLOBAL: 0,
		DISCOUNT_MODE_INDIVIDUAL: 1,
		DISCOUNT_MODE_GROUP: 2,
		registerChangeDiscount: function () {
			this.form.on('click', '.js-change-discount', (e) => {
				let parentRow;
				const element = $(e.currentTarget);
				let params = {
					module: app.getModuleName(),
					view: 'Inventory',
					mode: 'showDiscounts',
					currency: this.getCurrency(),
					discountAggregation: this.getDiscountAggregation(),
					relatedRecord: this.getAccountId(),
					discountMode: e.currentTarget.dataset.mode
				};
				if (e.currentTarget.dataset.mode == this.DISCOUNT_MODE_GLOBAL) {
					parentRow = this.getInventoryItemsContainer();
					if (parentRow.find('tfoot .colTotalPrice:not(.hideTd)').length != 0) {
						params.totalPrice = App.Fields.Double.formatToDb(parentRow.find('tfoot .colTotalPrice').text());
					} else {
						params.totalPrice = this.calculateSummary('totalPrice');
					}
				} else if (e.currentTarget.dataset.mode == this.DISCOUNT_MODE_GROUP) {
					parentRow = element.closest('tr.inventoryRowGroup');
					params.totalPrice = this.calculateSummary('totalPrice', this.getGroupItems(parentRow));
				} else {
					parentRow = element.closest(this.rowClass);
					params.totalPrice = this.getTotalPrice(parentRow);
				}
				params.discountParam = parentRow.find('.discountParam').val();
				let progressInstace = $.progressIndicator();

				AppConnector.request(params)
					.done((data) => {
						app.showModalWindow(data, (data) => {
							this.registerChangeDiscountModal(data, parentRow, params);
							this.calculateDiscount(parentRow, data);
						});
						progressInstace.hide();
					})
					.fail(function () {
						progressInstace.hide();
					});
			});
		},
		registerChangeDiscountModal: function (modal, parentRow, params) {
			let thisInstance = this;
			let form = modal.find('form');
			form.validationEngine(app.validationEngineOptions);
			modal.on('change', '.individualDiscountType', function (e) {
				let element = $(e.currentTarget);
				modal.find('.individualDiscountContainer .input-group-text').text(element.data('symbol'));
			});
			modal.on('change', '.activeCheckbox[name="aggregationType"]', function (e) {
				let element = $(e.currentTarget);

				if (element.attr('type') == 'checkbox' && this.checked) {
					element.closest('.js-panel').find('.js-panel__body').removeClass('d-none');
					element.closest('.js-panel').addClass('js-active');
				} else if (element.attr('type') == 'radio') {
					modal.find('.js-panel').removeClass('js-active');
					modal.find('.js-panel .js-panel__body').addClass('d-none');
					element.closest('.js-panel').find('.js-panel__body').removeClass('d-none');
					element.closest('.js-panel').addClass('js-active');
				} else {
					element.closest('.js-panel').find('.js-panel__body').addClass('d-none');
					element.closest('.js-panel').removeClass('js-active');
				}
			});
			modal.on(
				'change',
				'.activeCheckbox, .globalDiscount,.individualDiscountValue,.individualDiscountType,.groupCheckbox,.additionalDiscountValue,.js-inv--discount-type',
				function () {
					thisInstance.calculateDiscount(parentRow, modal);
				}
			);
			modal.on('click', '.js-save-discount', function () {
				if (form.validationEngine('validate') === false) {
					return;
				}
				thisInstance.saveDiscountsParameters(parentRow, modal);
				if (params.discountMode == thisInstance.DISCOUNT_MODE_INDIVIDUAL) {
					thisInstance.setDiscount(parentRow, App.Fields.Double.formatToDb(modal.find('.valueDiscount').text()));
					thisInstance.quantityChangeActions(parentRow);
				} else if (params.discountMode == thisInstance.DISCOUNT_MODE_GROUP) {
					let rate =
						App.Fields.Double.formatToDb(modal.find('.valueDiscount').text()) /
						App.Fields.Double.formatToDb(modal.find('.valueTotalPrice').text());
					thisInstance.getGroupItems(parentRow).each(function () {
						thisInstance.setDiscount($(this), thisInstance.getTotalPrice($(this)) * rate);
						thisInstance.quantityChangeActions($(this));
					});
				} else {
					let rate =
						App.Fields.Double.formatToDb(modal.find('.valueDiscount').text()) /
						App.Fields.Double.formatToDb(modal.find('.valueTotalPrice').text());
					parentRow.find(thisInstance.rowClass).each(function () {
						thisInstance.setDiscount($(this), thisInstance.getTotalPrice($(this)) * rate);
						thisInstance.quantityChangeActions($(this));
					});
				}
				app.hideModalWindow();
			});
		},
		registerChangeTax: function () {
			const thisInstance = this;
			thisInstance.form.on('click', '.changeTax', function (e) {
				let parentRow;
				let element = $(e.currentTarget);
				let params = {
					module: app.getModuleName(),
					view: 'Inventory',
					mode: 'showTaxes',
					currency: thisInstance.getCurrency(),
					relatedRecord: thisInstance.getAccountId()
				};
				if (element.hasClass('js-inv-tax_global')) {
					parentRow = thisInstance.getInventoryItemsContainer();
					let totalPrice = 0;
					if (parentRow.find('tfoot .colNetPrice').length > 0) {
						totalPrice = parentRow.find('tfoot .colNetPrice').text();
					} else if (parentRow.find('tfoot .colTotalPrice ').length > 0) {
						totalPrice = parentRow.find('tfoot .colTotalPrice ').text();
					}
					params.totalPrice = App.Fields.Double.formatToDb(totalPrice);
					params.taxType = 1;
				} else {
					parentRow = element.closest(thisInstance.rowClass);
					let sourceRecord = parentRow.find('.rowName .sourceField').val();
					params.totalPrice = thisInstance.getNetPrice(parentRow);
					params.taxType = 0;
					if (sourceRecord) {
						params.record = sourceRecord;
					}
					params.recordModule = parentRow.find('.rowName [name="popupReferenceModule"]').val();
				}
				let progressInstace = $.progressIndicator();
				AppConnector.request(params)
					.done(function (data) {
						app.showModalWindow(data, function (data) {
							thisInstance.initTaxParameters(parentRow, $(data));
							thisInstance.registerChangeTaxModal(data, parentRow, params);
						});
						progressInstace.hide();
					})
					.fail(function () {
						progressInstace.hide();
					});
			});
		},
		lockCurrencyChange: false,
		registerChangeCurrency() {
			this.getInventoryHeadContainer().on('change', '.js-currency', (e) => {
				let element = $(e.currentTarget),
					symbol = element.find('option:selected').data('conversionSymbol');
				this.currencyChangeActions(element, element.find('option:selected'));
				this.form.find('.currencySymbol').text(symbol);
			});
		},
		registerChangeDiscountAggregation() {
			this.getInventoryHeadContainer().on('change', '.js-discount_aggreg', (e) => {
				this.rowsCalculations();
			});
		},
		registerChangeTaxModal: function (modal, parentRow, params) {
			let thisInstance = this;
			let form = modal.find('form');
			form.validationEngine(app.validationEngineOptions);
			modal.on('change', '.individualTaxType', function (e) {
				let element = $(e.currentTarget);
				modal.find('.individualTaxContainer .input-group-text').text(element.data('symbol'));
			});
			modal.on('change', '.activeCheckbox[name="aggregationType"]', function (e) {
				let element = $(e.currentTarget);
				if (element.attr('type') == 'checkbox' && this.checked) {
					element.closest('.js-panel').find('.js-panel__body').removeClass('d-none');
					element.closest('.js-panel').addClass('js-active');
				} else if (element.attr('type') == 'radio') {
					modal.find('.js-panel').removeClass('js-active');
					modal.find('.js-panel .js-panel__body').addClass('d-none');
					element.closest('.js-panel').find('.js-panel__body').removeClass('d-none');
					element.closest('.js-panel').addClass('js-active');
				} else {
					element.closest('.js-panel').find('.js-panel__body').addClass('d-none');
					element.closest('.js-panel').removeClass('js-active');
				}
			});
			modal.on('change', '.activeCheckbox, .globalTax, .individualTaxValue, .groupTax, .regionalTax', function () {
				thisInstance.calculateTax(parentRow, modal);
			});
			modal.on('click', '.js-save-taxs', function () {
				if (form.validationEngine('validate') === false) {
					return;
				}
				thisInstance.saveTaxsParameters(parentRow, modal);
				if (params.taxType == '0') {
					thisInstance.setTax(parentRow, App.Fields.Double.formatToDb(modal.find('.valueTax').text()));
					thisInstance.setTaxPercent(parentRow, App.Fields.Double.formatToDb(modal.find('.js-tax-value').text()));
					thisInstance.quantityChangeActions(parentRow);
				} else {
					let rate =
						App.Fields.Double.formatToDb(modal.find('.valueTax').text()) /
						App.Fields.Double.formatToDb(modal.find('.valueNetPrice').text());
					parentRow.find(thisInstance.rowClass).each(function () {
						let totalPrice;
						if ($('.netPrice', $(this)).length > 0) {
							totalPrice = thisInstance.getNetPrice($(this));
						} else if ($('.totalPrice', $(this)).length > 0) {
							totalPrice = thisInstance.getTotalPrice($(this));
						}
						thisInstance.setTax($(this), totalPrice * rate);
						thisInstance.setTaxPercent($(this), App.Fields.Double.formatToDb(modal.find('.js-tax-value').text()));
						thisInstance.quantityChangeActions($(this));
					});
				}
				app.hideModalWindow();
			});
		},
		registerRowAutoComplete: function (container) {
			const thisInstance = this;
			let sourceFieldElement = container.find('.sourceField.js-name');
			sourceFieldElement.on(Vtiger_Edit_Js.referenceSelectionEvent, (e, params) => {
				let record = params.record;
				let element = $(e.currentTarget);
				let parentRow = element.closest(thisInstance.rowClass);
				let selectedModule = parentRow.find('.rowName [name="popupReferenceModule"]').val();
				let formParam = {
					module: app.getModuleName(),
					action: 'Inventory',
					mode: 'getDetails',
					record: record,
					fieldname: element.data('columnname')
				};
				if (this.getCurrency()) {
					formParam.currency_id = this.getCurrency();
					formParam.currencyParams = this.getInventoryHeadContainer().find('.js-currencyparam').val();
				}
				AppConnector.request(formParam).done(function (data) {
					for (let id in data) {
						if (typeof data[id] == 'object') {
							let recordData = data[id];
							thisInstance.mapResultsToFields(selectedModule, parentRow, recordData);
						}
					}
				});
			});
		},

		/**
		 * Mass add entries.
		 */
		registerMassAddItem: function () {
			this.getForm().on('click', '.js-mass-add', (e) => {
				let currentTarget = $(e.currentTarget);
				let moduleName = currentTarget.data('module');
				let url = currentTarget.data('url');
				app.showRecordsList(url, (_, instance) => {
					instance.setSelectEvent((data) => {
						for (let i in data) {
							let parentElem = this.addItem(moduleName, '', false, currentTarget.closest('.js-inv-container-content'));
							Vtiger_Edit_Js.getInstance().setReferenceFieldValue(parentElem.find('.rowName'), {
								name: data[i],
								id: i
							});
						}
					});
				});
			});
		},

		calculateItemNumbers: function () {
			let thisInstance = this;
			let items = this.getInventoryItemsContainer();
			let i = 1;
			items.find(thisInstance.rowClass).each(function () {
				$(this).find('.itemNumberText').text(i);
				i++;
			});
		},
		initItem: function (container) {
			let thisInstance = this;
			if (typeof container === 'undefined') {
				container = thisInstance.getInventoryItemsContainer();
			}
			thisInstance.registerDeleteLineItemEvent(container);
			thisInstance.registerPriceBookModal(container);
			thisInstance.registerRowChangeEvent(container);
			thisInstance.registerRowAutoComplete(container);
			thisInstance.checkDeleteIcon();
			thisInstance.rowsCalculations();
			thisInstance.updateRowSequence();
			App.Fields.Picklist.showSelect2ElementView(container.find('.selectInv'));
			App.Fields.Date.register(container, true, {}, 'dateFieldInv');
			container.validationEngine('detach');
			container.validationEngine(app.validationEngineOptions);
			App.Fields.Text.Editor.register(container);
		},
		/**
		 * Load inventory data for specified record
		 * @param {int} recordId
		 * @param {string} sourceModule
		 * @param {function|bool} success callback
		 * @param {function|bool} fail callback
		 * @returns Promise
		 */
		loadInventoryData(recordId, sourceModule, success = false, fail = false) {
			const progressLoader = $.progressIndicator({ blockInfo: { enabled: true } });
			return new Promise((resolve, reject) => {
				AppConnector.request({
					module: app.getModuleName(),
					src_module: sourceModule,
					src_record: recordId,
					action: 'Inventory',
					mode: 'getTableData',
					record: app.getRecordId()
				})
					.done((response) => {
						let activeModules = [];
						this.getContainer()
							.find('.js-inv-add-item')
							.each((_, addBtn) => {
								activeModules.push($(addBtn).data('module'));
							});
						progressLoader.progressIndicator({ mode: 'hide' });
						const oldCurrencyChangeAction = this.currencyChangeActions;
						this.currencyChangeActions = function changeCurrencyActions(select, option) {
							this.currencyConvertValues(select, option);
							select.data('oldValue', select.val());
						};
						const first = response.result[Object.keys(response.result)[0]];
						this.setCurrencyParam(first.currencyparam);
						this.setCurrency(first.currency);
						this.setDiscountMode(first.discountmode);
						this.setTaxMode(first.taxmode);
						this.currencyChangeActions = oldCurrencyChangeAction;
						this.clearInventory();
						$.each(response.result, (_, row) => {
							if (activeModules.indexOf(row.moduleName) !== -1) {
								if (row.groupid && row.add_header) {
									this.addHeaderItem(row);
								}
								this.addItem(row.moduleName, row.basetableid, row);
							} else {
								Vtiger_Helper_Js.showMessage({
									type: 'error',
									textTrusted: false,
									text: app
										.vtranslate('JS_INVENTORY_ITEM_MODULE_NOT_FOUND')
										.replace('${sourceModule}', row.moduleName)
										.replace('${position}', row.info.name)
								});
							}
						});
						this.summaryCalculations();
						resolve(response.result);
						if (typeof success === 'function') {
							success(response.result);
						}
					})
					.fail((error, err) => {
						progressLoader.progressIndicator({ mode: 'hide' });
						reject(error, err);
						if (typeof fail === 'function') {
							fail(error, err);
						}
					});
			});
		},
		/**
		 * Clear inventory data
		 */
		clearInventory: function () {
			this.getInventoryItemsContainer()
				.find('.deleteRow,.js-delete-header-item')
				.each((_, e) => {
					$(e).trigger('click');
				});
		},
		/**
		 * Register currency converter
		 */
		registerCurrencyConverter() {
			this.form.on('click', '.js-currency-converter-event', (e) => {
				let row = $(e.currentTarget).closest(this.rowClass);
				let unitPrice = this.getUnitPriceValue(row) || 0;
				let currencyId = this.getCurrency();
				App.Components.CurrencyConverter.modalView({
					currencyId: currencyId,
					amount: App.Fields.Double.formatToDisplay(unitPrice),
					currencyParam: this.form.find('.js-currencyparam').val()
				}).done((data) => {
					let value;
					if (currencyId === data.currency_target_id) {
						value = data.currency_target_value;
					} else if (currencyId === data.currency_base_id) {
						value = data.currency_base_value;
					}
					if (value) {
						this.setUnitPrice(row, App.Fields.Double.formatToDb(value));
						this.quantityChangeActions(row);
					}
				});
			});
		},
		/**
		 * Register head element events
		 */
		registerHeadEvents() {
			this.getInventoryHeadContainer()
				.find('.js-inv-cuurency_reset')
				.on('click', (e) => {
					app.showConfirmModal({
						textTrusted: false,
						text: e.currentTarget.dataset.confirmation,
						confirmedCallback: () => {
							AppConnector.request(e.currentTarget.dataset.url).done((response) => {
								if (response.result && Object.keys(response.result).length) {
									this.setCurrencyParam(JSON.stringify(response.result));
									this.syncHeaderData();
								}
							});
						}
					});
				});
		},
		/**
		 * Function which will register all the events
		 */
		registerEvents: function (container) {
			this.form = container;
			this.loadConfig();
			this.registerInventorySaveData();
			this.registerAddItem();
			this.registerAddHeaderItem();
			this.registerMassAddItem();
			this.initItem();
			this.registerSortableItems();
			this.registerSubProducts();
			this.registerChangeDiscount();
			this.registerChangeTax();
			this.registerClearReferenceSelection();
			this.registerShowHideExpanded();
			this.registerChangeCurrency();
			this.registerChangeDiscountAggregation();
			this.setDefaultGlobalTax(container);
			this.registerShowHideExpandedGroup();
			app.registerBlockToggleEvent(container);
			this.registerCurrencyConverter();
			this.registerHeadEvents();
		}
	}
);
