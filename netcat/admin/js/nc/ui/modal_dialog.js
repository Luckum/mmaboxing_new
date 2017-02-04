/**
 * nc.ui.modal_dialog
 *
 * Класс для создания модальных диалогов.
 *
 * Содержимое диалога может быть загружено с сервера (если в конфигурации задан
 * параметр url, см. ниже) или указано при создании диалога.
 *
 * Использование:
 *    var dialog = nc.ui.modal_dialog(options);
 *    dialog.open();     // открывает диалог
 *    dialog.close();    // закрывает диалог, контент остаётся в body
 *    dialog.destroy();  // полностью убирает диалог
 *
 * options: объект
 *    url: путь для загрузки содержимого диалога
 *    persist: если true, содержимое диалога не сбрасывается при повторном открытии;
 *             если false [по умолчанию], вызов close() вызывает убирает содержимое диалога из DOM
 *    on_show: функция, которую следует выполнить, когда диалог будет открыт
 *    on_resize: функция, срабатывающая при изменении размеров диалога (при изменении размеров окна)
 *    full_markup: полный код диалога (см. ниже). Не учитывается, если задан url.
 *
 */

/**

    При загрузке диалога с сервера (параметр url) ответ должен иметь
    следующую структуру (аналогично для параметра full_markup):

    <div class='nc-modal-dialog'>
        <div class='nc-modal-dialog-header'>
            <h2>Title</h2>
            <div class='nc-modal-dialog-header-tabs'>
                <ul>
                    <li data-tab-for='tab1' class='nc--active'>Tab 1</li>
                    <li data-tab-for='tab2'>Tab 2</li>
                </ul>
            </div>
        </div>
        <div class='nc-modal-dialog-body'>
            <div data-tab='tab1'>
                Если заданы табы, то должны быть <div> с атрибутом data-tab.
                Если табы не заданы, то такие <div> необязательны.
                (@todo NOT YET IMPLEMENTED)
            </div>
        </div>
        <div class='nc-modal-dialog-footer'>
            <button class='nc-btn' data-role='close'>Close</button>
            <button class='nc-btn'>Submit</button>
        </div>
        <script>
            you_can_add_additional_logic_here();
        </script>
    </div>

 */
(function($) {

    // --- Constructor ---------------------------------------------------------
    /**
     *
     * @param {Object} options
     * @returns {modal_dialog}
     */
    function modal_dialog(options) {
        // позволим выполнять конструктор как функцию без new:
        if (!(this instanceof modal_dialog)) {
            return new modal_dialog(options);
        }

        this.set_options(options);
        if (!this.options.url) {
            this.is_loaded = true;
        }

        return this;
    }

    // --- Initialize resize handlers ------------------------------------------
    if (typeof $ === 'undefined') { return /* WTF */;}

    $(window).on("resize.modal_dialog", function(){
        var instance = $('.nc-modal-dialog:visible').data('modal_dialog');
        if (instance && instance.resize) { instance.resize(); }
    });

    // --- Private variables ---------------------------------------------------
    var opened_dialog = null;

    // --- "Static" methods ----------------------------------------------------
    modal_dialog.get_opened_dialog = function() {
        return opened_dialog;
    };

    // --- Instance methods ----------------------------------------------------
    modal_dialog.prototype = {
        options: {
            url: null,
            persist: false,
            on_show: $.noop,
            on_resize: $.noop,
            min_width: 600,
            max_width: 1200,
            full_markup:
                '<div class="nc-modal-dialog">' +
                    '<div class="nc-modal-dialog-header">' +
                        '<h2>&nbsp;</h2>' +
                        '<div class="nc-modal-dialog-header-tabs"><ul><li></li></ul></div>' +
                    '</div>' +
                    '<div class="nc-modal-dialog-body"></div>' +
                    '<div class="nc-modal-dialog-footer"></div>' +
                '</div>'
        },

        loaded_markup: null,

        dialog_container: null,
        parts: {
            title:  '.nc-modal-dialog-header h2',
            tabs:   '.nc-modal-dialog-header-tabs',
            body:   '.nc-modal-dialog-body',
            footer: '.nc-modal-dialog-footer'
        },

        is_loaded: false,
        is_open: false,

        /**
         *
         * @param options
         */
        set_options: function(options) {
            this.options = $.extend(this.options, options);
        },

        /**
         *
         * @returns {}
         */
        load: function() {
            var dialog = this;
            return $.get(this.options.url)
                    .done(function(result) {
                        dialog.loaded_markup = $.trim(result);
                    })
                    .always(function() {
                        dialog.is_loaded = true;
                    });
        },

        /**
         *
         */
        create: function() {
            if (!this.is_loaded) {
                nc.process_start('ui.modal_dialog.load');
                return this.load().done($.proxy(this, 'create')); // recursive call to create()
            }

            nc.process_stop('ui.modal_dialog.load', 0);

            var options = this.options,
                dialog;

            if (this.loaded_markup && $(this.loaded_markup).is(".nc-modal-dialog")) {
                // ответ по крайней мере отдалённо похож на полный диалог
                dialog = $(this.loaded_markup);
            }
            else {
                dialog = $(options.full_markup);
                if (this.loaded_markup) {
                    // Мы что-то загрузили, но это не полный диалог. Добавим ответ в тело диалога:
                    dialog.find(".nc-modal-dialog-body").append(this.loaded_markup);
                }
            }

            this.dialog_container = dialog
                .hide()
                .data('modal_dialog', this)
                .appendTo('body');

            this.get_part('footer').find('button[data-role=close]')
                .on('click.modal_dialog', $.proxy(this, 'close'));

            return $.Deferred().resolve();
        },

        /**
         *
         */
        open: function() {
            if (opened_dialog) { opened_dialog.close(); }

            if (!this.dialog_container) {
                this.create().done($.proxy(this, 'when_ready_to_open'));
            }
            else {
                this.when_ready_to_open();
            }
        },

        /**
         *
         */
        when_ready_to_open: function() {
            $.modal(this.dialog_container, {
                onShow: $.proxy(this, 'on_show'),
                persist: this.options.persist,
                closeHTML: '<a class="modalCloseImg" title="' + ncLang.Close + '"></a>'
            });

            this.is_open = true;
            opened_dialog = this;
            this.resize();
        },

        /**
         *
         */
        on_show: function() {
            this.options.on_show(this);
        },

        /**
         *
         */
        close: function() {
            if (this.is_open) {
                $.modal.close();
                this.is_open = false;
                opened_dialog = null;
                if (!this.options.persist) { this.dialog_container.remove(); }
            }
        },

        /**
         *
         */
        resize: function() {
            if (!this.is_open) { return; }

            var $window = $(top.window),
                w = $window.width()  - 100 * 2,
                h = $window.height() - 100 * 2;

            if (w > this.options.max_width) { w = this.options.max_width; }
            if (w < this.options.min_width) { w = this.options.min_width; }

            $('#simplemodal-container').css({
                width: w + 'px',
                height: h + 'px',
                left: Math.round(($window.width() - w) / 2 - 10) + 'px',
                top: '100px'
            }).find('.simplemodal-wrap').css('overflow', 'auto');

            this.options.on_resize();
        },

        /**
         *
         */
        destroy: function() {
            this.close();
            this.dialog_container.remove();
        },

        /**
         *
         */
        clear_all: function() {
            for (var part in this.parts) { this.clear(part); }
        },

        /**
         *
         * @param part_name
         */
        clear_part: function(part_name) {
            this.get_part(part_name).empty();
        },

        /**
         *
         * @param selector
         * @returns {}
         */
        find: function(selector) {
            if (!this.dialog_container) { this.create(); }
            return this.dialog_container.find(selector);
        },

        /**
         *
         * @param part_name
         * @returns {}
         */
        get_part: function(part_name) {
            return this.find(this.parts[part_name]);
        }

    };

    nc.ext('modal_dialog', modal_dialog, 'ui');

})(window.top.$nc);