define([
    'moment',
    'mageUtils',
    'Magento_Ui/js/form/element/date',
    'moment-timezone-with-data'
], function (moment, utils, Date) {
    'use strict';

    return Date.extend({
        onValueChange: function (value) {
            var shiftedValue;

            if (value) {
                shiftedValue = moment(value, this.pickerTimeFormat);
                shiftedValue = shiftedValue.format(this.pickerTimeFormat);
            } else {
                shiftedValue = '';
            }

            if (shiftedValue !== this.shiftedValue()) {
                this.shiftedValue(shiftedValue);
            }
        },

        onShiftedValueChange: function (shiftedValue) {
            var value,
                momentValue;

            if (shiftedValue) {
                momentValue = moment(shiftedValue, this.pickerTimeFormat);
                value = momentValue.format(this.pickerTimeFormat);
            } else {
                value = '';
            }

            if (value !== this.value()) {
                this.value(value);
            }
        },

        prepareDateTimeFormats: function () {
            this.pickerTimeFormat = utils.convertToMomentFormat(this.options.timeFormat);
            this.validationParams.dateFormat = this.pickerTimeFormat;
        }
    });
});
