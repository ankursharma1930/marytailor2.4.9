/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/element/abstract'
], function ($, _, Element) {
    'use strict';

    return Element.extend({
        defaults: {
            colorMenuActive: false,
            isTransparent: false,
            disableLabel: false,
            red: 0,
            green: 0,
            blue: 0,
            elementTmpl: 'Mageside_Recipe/form/element/color',
            closeBtnLabel: 'Done',
            radius: 84,
            square: 100,
            width: 194,
            listens: {
                value: 'setDifferedFromDefault setColor',
                isTransparent: 'toggleTransparent'
            }
        },

        initialize: function () {
            this._super();
            this.initColor();

            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('colorMenuActive isTransparent red green blue');

            return this;
        },

        initColor: function () {
            var color = this.value() || 'transparent';
            var initColor = (color === 'transparent') ? '#ffffff' : color;
            this.setColor(initColor);
            if (color === 'transparent') {
                this.value(color);
                this.isTransparent(true);
            }
        },

        onFocusIn: function () {
            this.colorMenuActive(true);
        },

        outerClick: function () {
            this.colorMenuActive() ? this.colorMenuActive(false) : false;
        },

        toggleTransparent: function () {
            if (this.isTransparent()) {
                this.value('transparent');
                $('#' + this.uid).css({
                    backgroundColor: 'inherit',
                    color: 'inherit'
                });
            } else {
                this.value(this.color);
                this.updateDisplay();
            }
        },

        /**
         * Change color with HTML syntax #123456
         */
        setColor: function (color) {
            var unpack = this.unpack(color);
            if (this.color != color && unpack) {
                this.color = color;
                this.rgb = unpack;
                this.hsl = this.RGBToHSL(this.rgb);
                this.updateObservables();
                this.updateDisplay();
            }
            return this;
        },

        setRGB: function (event) {
            this.rgb[0] = this.validateColor(this.red);
            this.rgb[1] = this.validateColor(this.green);
            this.rgb[2] = this.validateColor(this.blue);
            this.color = this.pack(this.rgb);
            this.hsl = this.RGBToHSL(this.rgb);
            this.value(this.color);
            this.isTransparent(false);
            this.updateDisplay();
        },

        validateColor: function(color) {
            var value = color();
            value = (value > 0) ? ((value < 255) ? value : 255) : 0;
            if (value !== color()) {
                color(value);
            }

            return value / 255;
        },

        /**
         * Change color with HSL triplet [0..1, 0..1, 0..1]
         */
        setHSL: function (hsl) {
            this.hsl = hsl;
            this.rgb = this.HSLToRGB(hsl);
            this.color = this.pack(this.rgb);
            this.updateDisplay();
            this.updateObservables();
            return this;
        },

        updateObservables: function () {
            this.value(this.color);
            this.isTransparent(false);
            this.red(Math.round(this.rgb[0] * 255));
            this.green(Math.round(this.rgb[1] * 255));
            this.blue(Math.round(this.rgb[2] * 255));
        },

        /**
         * Retrieve the coordinates of the given event relative to the center
         * of the widget.
         */
        widgetCoords: function (event) {
            var x, y;
            var el = event.target || event.srcElement;
            var reference = $('.wheel', $('#wrap' + this.uid)).get(0);

            if (typeof event.offsetX != 'undefined') {
                // Use offset coordinates and find common offsetParent
                var pos = { x: event.offsetX, y: event.offsetY };

                // Send the coordinates upwards through the offsetParent chain.
                var e = el;
                while (e) {
                    e.mouseX = pos.x;
                    e.mouseY = pos.y;
                    pos.x += e.offsetLeft;
                    pos.y += e.offsetTop;
                    e = e.offsetParent;
                }

                // Look for the coordinates starting from the wheel widget.
                var e = reference;
                var offset = { x: 0, y: 0 };
                while (e) {
                    if (typeof e.mouseX != 'undefined') {
                        x = e.mouseX - offset.x;
                        y = e.mouseY - offset.y;
                        break;
                    }
                    offset.x += e.offsetLeft;
                    offset.y += e.offsetTop;
                    e = e.offsetParent;
                }

                // Reset stored coordinates
                e = el;
                while (e) {
                    e.mouseX = undefined;
                    e.mouseY = undefined;
                    e = e.offsetParent;
                }
            }
            else {
                // Use absolute coordinates
                var pos = this.absolutePosition(reference);
                x = (event.pageX || 0*(event.clientX + $('html').get(0).scrollLeft)) - pos.x;
                y = (event.pageY || 0*(event.clientY + $('html').get(0).scrollTop)) - pos.y;
            }
            // Subtract distance to middle
            return { x: x - this.width / 2, y: y - this.width / 2 };
        },

        /**
         * Mousedown handler
         */
        mousedown: function (event) {
            // Capture mouse
            var self = this;
            if (!document.dragging) {
                $(document)
                    .on('mousemove.farbtastic', function(event) {self.mousemove(event)}.bind(this))
                    .on('mouseup.farbtastic', function(event) {self.mouseup(event)}.bind(this));
                document.dragging = true;
            }

            // Check which area is being dragged
            var pos = this.widgetCoords(event);
            this.circleDrag = Math.max(Math.abs(pos.x), Math.abs(pos.y)) * 2 > this.square;

            // Process
            this.mousemove(event);
            return false;
        },

        /**
         * Mouseup handler
         */
        mouseup: function () {
            // Uncapture mouse
            $(document).off('mousemove.farbtastic').off('mouseup.farbtastic');
            document.dragging = false;
        },

        /**
         * Mousemove handler
         */
        mousemove: function (event) {
            // Get coordinates relative to color picker center
            var pos = this.widgetCoords(event);

            // Set new HSL parameters
            if (this.circleDrag) {
                var hue = Math.atan2(pos.x, -pos.y) / 6.28;
                if (hue < 0) hue += 1;
                this.setHSL([hue, this.hsl[1], this.hsl[2]]);
            }
            else {
                var sat = Math.max(0, Math.min(1, -(pos.x / this.square) + .5));
                var lum = Math.max(0, Math.min(1, -(pos.y / this.square) + .5));
                this.setHSL([this.hsl[0], sat, lum]);
            }
            return false;
        },

        /**
         * Update the markers and styles
         */
        updateDisplay: function () {
            // Markers
            var angle = this.hsl[0] * 6.28,
                farbtastic = $('#wrap' + this.uid);

            $('.h-marker', farbtastic).css({
                left: Math.round(Math.sin(angle) * this.radius + this.width / 2) + 'px',
                top: Math.round(-Math.cos(angle) * this.radius + this.width / 2) + 'px'
            });

            $('.sl-marker', farbtastic).css({
                left: Math.round(this.square * (.5 - this.hsl[1]) + this.width / 2) + 'px',
                top: Math.round(this.square * (.5 - this.hsl[2]) + this.width / 2) + 'px'
            });

            // Saturation/Luminance gradient
            $('.color', farbtastic).css('backgroundColor', this.pack(this.HSLToRGB([this.hsl[0], 1, 0.5])));

            // Set background/foreground color
            $('#' + this.uid).css({
                backgroundColor: this.color,
                color: this.hsl[2] > 0.5 ? '#000' : '#fff'
            });
        },

        /**
         * Get absolute position of element
         */
        absolutePosition: function (el) {
            var r = { x: el.offsetLeft, y: el.offsetTop };
            // Resolve relative to offsetParent
            if (el.offsetParent) {
                var tmp = this.absolutePosition(el.offsetParent);
                r.x += tmp.x;
                r.y += tmp.y;
            }
            return r;
        },

        /* Various color utility functions */
        pack: function (rgb) {
            var r = Math.round(rgb[0] * 255);
            var g = Math.round(rgb[1] * 255);
            var b = Math.round(rgb[2] * 255);
            return '#' + (r < 16 ? '0' : '') + r.toString(16) +
                (g < 16 ? '0' : '') + g.toString(16) +
                (b < 16 ? '0' : '') + b.toString(16);
        },

        unpack: function (color) {
            if (color.length == 7) {
                return [parseInt('0x' + color.substring(1, 3)) / 255,
                    parseInt('0x' + color.substring(3, 5)) / 255,
                    parseInt('0x' + color.substring(5, 7)) / 255];
            }
            else if (color.length == 4) {
                return [parseInt('0x' + color.substring(1, 2)) / 15,
                    parseInt('0x' + color.substring(2, 3)) / 15,
                    parseInt('0x' + color.substring(3, 4)) / 15];
            }
        },

        HSLToRGB: function (hsl) {
            var m1, m2, r, g, b;
            var h = hsl[0], s = hsl[1], l = hsl[2];
            m2 = (l <= 0.5) ? l * (s + 1) : l + s - l*s;
            m1 = l * 2 - m2;
            return [this.hueToRGB(m1, m2, h+0.33333),
                this.hueToRGB(m1, m2, h),
                this.hueToRGB(m1, m2, h-0.33333)];
        },

        hueToRGB: function (m1, m2, h) {
            h = (h < 0) ? h + 1 : ((h > 1) ? h - 1 : h);
            if (h * 6 < 1) return m1 + (m2 - m1) * h * 6;
            if (h * 2 < 1) return m2;
            if (h * 3 < 2) return m1 + (m2 - m1) * (0.66666 - h) * 6;
            return m1;
        },

        RGBToHSL: function (rgb) {
            var min, max, delta, h, s, l;
            var r = rgb[0], g = rgb[1], b = rgb[2];
            min = Math.min(r, Math.min(g, b));
            max = Math.max(r, Math.max(g, b));
            delta = max - min;
            l = (min + max) / 2;
            s = 0;
            if (l > 0 && l < 1) {
                s = delta / (l < 0.5 ? (2 * l) : (2 - 2 * l));
            }
            h = 0;
            if (delta > 0) {
                if (max == r && max != g) h += (g - b) / delta;
                if (max == g && max != b) h += (2 + (b - r) / delta);
                if (max == b && max != r) h += (4 + (r - g) / delta);
                h /= 6;
            }
            return [h, s, l];
        }
    });
});