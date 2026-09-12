define([
    'jquery',
    'underscore',
    'mage/template',
    'mage/translate',
    'jquery/ui'
], function($, _, template, $t) {
    "use strict";

    $.widget('mageside.farbtastic', {
        options: {
            container: '<div class="action-menu color-chooser">' +
            '   <div class="farbtastic">' +
            '       <div class="color"></div>' +
            '       <div class="wheel"></div>' +
            '       <div class="overlay"></div>' +
            '       <div class="h-marker marker"></div>' +
            '       <div class="sl-marker marker"></div>' +
            '   </div>' +
            '   <div class="admin__fieldset">' +
            '       <div class="admin__field">' +
            '           <div class="admin__field-label">' +
            '               <span ><%= $t("Red") %></span>' +
            '           </div>' +
            '           <div class="admin__field-control" >' +
            '               <input class="admin__control-text rgb" type="text" name="red">' +
            '           </div>' +
            '       </div>' +
            '       <div class="admin__field">' +
            '           <div class="admin__field-label">' +
            '               <span ><%= $t("Green") %></span>' +
            '           </div>' +
            '           <div class="admin__field-control" >' +
            '               <input class="admin__control-text rgb" type="text" name="green">' +
            '           </div>' +
            '       </div>' +
            '       <div class="admin__field">' +
            '           <div class="admin__field-label">' +
            '               <span ><%= $t("Blue") %></span>' +
            '           </div>' +
            '           <div class="admin__field-control" >' +
            '               <input class="admin__control-text rgb" type="text" name="blue">' +
            '           </div>' +
            '       </div>' +
            '       <div class="admin__field transparent">' +
            '           <div class="admin__field-label">' +
            '               <span><%= $t("Transparent") %></span>' +
            '           </div>' +
            '           <div class="admin__field-control">' +
            '               <div class="admin__actions-switch">' +
            '                   <input type="checkbox" class="admin__actions-switch-checkbox" name="transparent" value="on">' +
            '                   <div class="admin__actions-switch-label">' +
            '                       <span class="admin__actions-switch-text" data-text-on="<%= $t(\'Yes\') %>" data-text-off="<%= $t(\'No\') %>"></span>' +
            '                   </div>' +
            '               </div>' +
            '           </div>' +
            '       </div>' +
            '   </div>' +
            '   <div class="admin__action-multiselect-actions-wrap">' +
            '       <button class="action-default" type="button">' +
            '           <span><%= $t("Done") %></span>' +
            '       </button>' +
            '   </div>' +
            '</div>',
            colorPickSelector: '.farbtastic',
            radius: 84,
            square: 100,
            width: 194
        },
        wheel: null,
        colorInput: null,
        container: null,
        menu: null,
        transparent: null,

        /**
         * jQuery.farbtastic('#picker').linkTo('#color');
         * @private
         */
        _create: function() {
            this.initMenu();
            this._bind();
            var color = this.input.val() || 'transparent';
            var initColor = (color === 'transparent') ? '#ffffff' : color;
            this.setColor(initColor);

            if (color === 'transparent') {
                this.input.val(color);
                this.transparent.prop('checked', true);
            }
        },

        initMenu: function () {
            this.input = $(this.element);
            this.container = this.input.parent();

            this.container.css('position', 'relative');
            this.input.after(template(this.options.container, {$t: $t}));
            this.menu = this.input.siblings('.action-menu');
            this.transparent = $('input[name="transparent"]', this.menu);

            this.farbtastic = this.menu.find(this.options.colorPickSelector);
            this.wheel = $('.wheel', this.farbtastic).get(0);
        },

        _bind: function () {
            var self = this;
            this.input.on('focus', function() {
                self.container.addClass('_active');
                self.menu.addClass('_active');
            });
            this.menu.find('.action-default').on('click', function() {
                self.container.removeClass('_active');
                self.menu.removeClass('_active');
            });
            this.input.on('keyup', $.proxy(this.updateValue, this));
            this.menu.find('.admin__field.transparent').on('click', $.proxy(this.toggleTransparent, this));
            this.menu.find('input.rgb').on('keyup', $.proxy(this.updateValueRGB, this));
            $('*', this.farbtastic).on('mousedown', $.proxy(this.mousedown, this));
        },

        updateValue: function (event) {
            var value = event.currentTarget.value;
            if (value && value != this.color) {
                this.setColor(value);
            }
        },

        toggleTransparent: function (event) {
            this.transparent.prop("checked", function(i, val) {
                return !val;
            });
            if (this.transparent.is(":checked")) {
                this.input.val('transparent');
                this.input.css({
                    backgroundColor: 'inherit',
                    color: 'inherit'
                });
            } else {
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
                this.updateDisplay();
            }
            return this;
        },

        updateValueRGB: function (event) {
            if (event.which == 38 || event.which == 39) {
                event.preventDefault();
                event.currentTarget.value++;
            }
            if (event.which == 37 || event.which == 40) {
                event.preventDefault();
                event.currentTarget.value--;
            }

            var value = event.currentTarget.value;
            value = (value > 0) ? ((value < 255) ? value : 255) : 0;
            event.currentTarget.value = value;

            var rgb = [];
            rgb[0] = this.menu.find('input[name="red"]').val() / 255;
            rgb[1] = this.menu.find('input[name="green"]').val() / 255;
            rgb[2] = this.menu.find('input[name="blue"]').val() / 255;
            this.setColor(this.pack(rgb));
        },

        /**
         * Change color with HSL triplet [0..1, 0..1, 0..1]
         */
        setHSL: function (hsl) {
            this.hsl = hsl;
            this.rgb = this.HSLToRGB(hsl);
            this.color = this.pack(this.rgb);
            this.updateDisplay();
            return this;
        },

        /**
         * Retrieve the coordinates of the given event relative to the center
         * of the widget.
         */
        widgetCoords: function (event) {
            var x, y;
            var el = event.target || event.srcElement;
            var reference = this.wheel;

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
            return { x: x - this.options.width / 2, y: y - this.options.width / 2 };
        },

        /**
         * Mousedown handler
         */
        mousedown: function (event) {
            // Capture mouse
            if (!document.dragging) {
                $(document)
                    .on('mousemove', this.options.colorPickSelector, $.proxy(this.mousemove, this))
                    .on('mouseup', this.options.colorPickSelector, $.proxy(this.mouseup, this));
                document.dragging = true;
            }
    
            // Check which area is being dragged
            var pos = this.widgetCoords(event);
            this.circleDrag = Math.max(Math.abs(pos.x), Math.abs(pos.y)) * 2 > this.options.square;
    
            // Process
            this.mousemove(event);
            return false;
        },

        /**
         * Mouseup handler
         */
        mouseup: function () {
            // Uncapture mouse
            $(document)
                .off('mousemove', this.options.colorPickSelector, $.proxy(this.mousemove, this))
                .off('mouseup', this.options.colorPickSelector, $.proxy(this.mouseup, this));
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
                var sat = Math.max(0, Math.min(1, -(pos.x / this.options.square) + .5));
                var lum = Math.max(0, Math.min(1, -(pos.y / this.options.square) + .5));
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
                self = this;

            $('.h-marker', this.farbtastic).css({
                left: Math.round(Math.sin(angle) * this.options.radius + this.options.width / 2) + 'px',
                top: Math.round(-Math.cos(angle) * this.options.radius + this.options.width / 2) + 'px'
            });

            $('.sl-marker', this.farbtastic).css({
                left: Math.round(this.options.square * (.5 - this.hsl[1]) + this.options.width / 2) + 'px',
                top: Math.round(this.options.square * (.5 - this.hsl[2]) + this.options.width / 2) + 'px'
            });

            // Saturation/Luminance gradient
            $('.color', this.farbtastic).css('backgroundColor', this.pack(this.HSLToRGB([this.hsl[0], 1, 0.5])));

            // Set background/foreground color
            this.input.css({
                backgroundColor: this.color,
                color: this.hsl[2] > 0.5 ? '#000' : '#fff'
            });

            // Change linked value
            this.input.each(function() {
                if (this.value && this.value != self.color) {
                    this.value = self.color;
                }
            });

            this.updateRGBInputs();
            this.transparent.removeProp('checked');
        },

        updateRGBInputs: function () {
            var red = Math.round(this.rgb[0] * 255),
                green = Math.round(this.rgb[1] * 255),
                blue = Math.round(this.rgb[2] * 255);
            this.menu.find('input[name="red"]').val(red);
            this.menu.find('input[name="green"]').val(green);
            this.menu.find('input[name="blue"]').val(blue);
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

    return $.mageside.farbtastic;
});