/**
 * Frontwise grid editor plugin.
 */

jQuery(document).ready(function($){
 
(function ($) {



    $.fn.gridEditor = function (options) {

        var self = this;
        var grideditor = self.data('grideditor');

        /** Methods **/

        if (arguments[0] == 'getHtml') {
            if (grideditor) {
                grideditor.deinit();
                var html = self.html();
                grideditor.init();
                return html;
            } else {
                return self.html();
            }
        }

        /** Initialize plugin */

        self.each(function (baseIndex, baseElem) {
            baseElem = $(baseElem);
            //console.log('Number of row'+ baseElem.find('div.row').index());
            // Wrap content if it is non-bootstrap
            if (baseElem.children().length && !baseElem.find('div.qc-row').length) {
                var children = baseElem.children();
                var newRow = $('<div class="qc-row"><div class="qc-col-md-12"/></div>').appendTo(baseElem);
                newRow.find('.qc-col-md-12').append(children);
            }

            var settings = $.extend({
                'new_row_layouts': [ // Column layouts for add row buttons
                    [12],
                    [6, 6],
                    [4, 4, 4],
                    [3, 3, 3, 3],
                    [2, 2, 2, 2, 2, 2],
                    [2, 8, 2],
                    [4, 8],
                    [8, 4]
                ],
                'row_classes': [{label: 'Example class', cssClass: 'example-class'}],
                'col_classes': [{label: 'Example class', cssClass: 'example-class'}],
                'col_tools': [], /* Example:
                 [ {
                 title: 'Set background image',
                 iconClass: 'glyphicon-picture',
                 on: { click: function() {} }
                 } ]
                 */
                'row_tools': [],
                'custom_filter': '',
                'content_types': ['tinymce'],
                'valid_col_sizes': [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                'source_textarea': ''
            }, options);

            // Elems
            var canvas,
                mainControls,
                addRowGroup,
                htmlTextArea
            ;
            var colClasses = ['qc-col-md-'];
            var curColClassIndex = 0; // Index of the column class we are manipulating currently
            var MAX_COL_SIZE = 12;

            setup();
            init();

            function setup() {
                /* Setup canvas */
                canvas = baseElem.addClass('ge-canvas');

                if (settings.source_textarea) {
                    var sourceEl = $(settings.source_textarea);

                    sourceEl.addClass('ge-html-output');
                    htmlTextArea = sourceEl;

                    if (sourceEl.val()) {
                        self.html(sourceEl.val());
                    }
                }

                if (typeof htmlTextArea === 'undefined' || !htmlTextArea.length) {
                    htmlTextArea = $('<textarea class="ge-html-output"/>').insertBefore(canvas);
                }

                /* Create main controls*/
                mainControls = $('<div class="ge-mainControls" />').insertBefore(htmlTextArea);
                var wrapper = $('<div class="ge-wrapper ge-top" />').appendTo(mainControls);

                // Add row custom-css
                addRowGroup = $('<div class="ge-addRowGroup btn-group" style="width: 100%;" />').appendTo(wrapper);
                $.each(settings.new_row_layouts, function (j, layout) {
                    var btn = $('<a style ="color:#2CC185;width: 19.50%; margin-right:0.50%;padding:5px;font-size:15px;background-color:#fff;border: 1px solid #3c763d ;" class="btn btn-xs" />')
                        .attr('title', 'Add row ' + layout.join('-'))
                        .on('click', function () {
                            var row = createRow().appendTo(canvas);
                            layout.forEach(function (i) {
                                createColumn(i).appendTo(row);
                            });
                            init();
                        })
                        .appendTo(addRowGroup)
                    ;

                    btn.append('<span class="glyphicon glyphicon-plus-sign"/>');

                    var layoutName = layout.join(' - ');
                    var icon = '<div class="qc-row ge-row-icon">';
                    layout.forEach(function (i) {
                        icon += '<div class="column qc-col-xs-' + i + '"/>';
                    });
                    icon += '</div>';
                    btn.append(icon);
                });

                /* // Buttons on right
                 var layoutDropdown = $('<div class="dropdown pull-right ge-layout-mode">' +
                 '<button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown"><span>Desktop</span></button>' +
                 '<ul class="dropdown-menu" role="menu">' +
                 '<li><a data-width="auto" title="Desktop"><span>Desktop</span></a></li>' +
                 '<li><a title="Tablet"><span>Tablet</span></li>' +
                 '<li><a title="Phone"><span>Phone</span></a></li>' +
                 '</ul>' +
                 '</div>')
                 .on('click', 'a', function() {
                 var a = $(this);
                 switchLayout(a.closest('li').index());
                 var btn = layoutDropdown.find('button');
                 btn.find('span').remove();
                 btn.append(a.find('span').clone());
                 })
                 .appendTo(wrapper); */
                /* var btnGroup = $('<div class="btn-group pull-right"/>')
                 .appendTo(wrapper)
                 ;
                 var htmlButton = $('<button title="Edit Source Code" type="button" class="btn btn-xs btn-primary gm-edit-mode"><span class="glyphicon glyphicon-chevron-left"></span><span class="glyphicon glyphicon-chevron-right"></span></button>')
                 .on('click', function() {
                 if (htmlButton.hasClass('active')) {
                 canvas.empty().html(htmlTextArea.val()).show();
                 init();
                 htmlTextArea.hide();
                 } else {
                 deinit();
                 htmlTextArea
                 .height(0.8 * $(window).height())
                 .val(canvas.html())
                 .show()
                 ;
                 canvas.hide();
                 }

                 htmlButton.toggleClass('active btn-danger');
                 })
                 .appendTo(btnGroup)
                 ;
                 var previewButton = $('<button title="Preview" type="button" class="btn btn-xs btn-primary gm-preview"><span class="glyphicon glyphicon-eye-open"></span></button>')
                 .on('mouseenter', function() {
                 canvas.removeClass('ge-editing');
                 })
                 .on('click', function() {
                 previewButton.toggleClass('active btn-danger').trigger('mouseleave');
                 })
                 .on('mouseleave', function() {
                 if (!previewButton.hasClass('active')) {
                 canvas.addClass('ge-editing');
                 }
                 })
                 .appendTo(btnGroup); */

                // Make controls fixed on scroll
                var $window = $(window);
                $window.on('scroll', function (e) {
                    if (
                        $window.scrollTop() > mainControls.offset().top &&
                        $window.scrollTop() < canvas.offset().top + canvas.height()
                    ) {
                        if (wrapper.hasClass('ge-top')) {
                            wrapper
                                .css({
                                    left: wrapper.offset().left,
                                    width: wrapper.outerWidth(),
                                })
                                .removeClass('ge-top')
                                .addClass('ge-fixed')
                            ;
                        }
                    } else {
                        if (wrapper.hasClass('ge-fixed')) {
                            wrapper
                                .css({left: '', width: ''})
                                .removeClass('ge-fixed')
                                .addClass('ge-top')
                            ;
                        }
                    }
                });

                /* Init RTE on click */
                canvas.on('click', '.ge-content', function (e) {
                    var rte = getRTE($(this).data('ge-content-type'));
                    if (rte) {
                        rte.init(settings, $(this));
                    }
                });
            }

            function reset() {
                deinit();
                init();
            }
			
			/*function getRTE(type) {
                return $.fn.gridEditor.RTEs[type];
            }*/

            function init() {
                runFilter(true);
                canvas.addClass('ge-editing');
                addAllColClasses();
                wrapContent();
                createRowControls();
                createColControls();
                makeSortable();
                switchLayout(curColClassIndex);
            }

            function deinit() {
                canvas.removeClass('ge-editing');
                var contents = canvas.find('.ge-content').each(function () {
                    var content = $(this);
                    getRTE(content.data('ge-content-type')).deinit(settings, content);
                });
                canvas.find('.ge-tools-drawer').remove();
                removeSortable();
                runFilter();
            }

            function createRowControls() {
                canvas.find('.qc-row').each(function () {
                    var row = $(this);
                    if (row.find('> .ge-tools-drawer').length) {
                        return;
                    }

                    var drawer = $('<div class="ge-tools-drawer" />').prependTo(row);
                    createTool(drawer, 'Move', 'ge-move', 'glyphicon-move');
                    createTool(drawer, 'Settings', '', 'glyphicon-cog', function () {
                        details.toggle();
                    });
                    settings.row_tools.forEach(function (t) {
                        createTool(drawer, t.title || '', t.className || '', t.iconClass || 'glyphicon-wrench', t.on);
                    });
                    //Hide Delete icon for default grid row.
                    var defaultRow = row.attr('default-row');
                    if (typeof defaultRow == typeof undefined || defaultRow == false) {
                        //if(row.index()>0){
                        createTool(drawer, 'Remove row', '', 'glyphicon-trash', function () {
                            row.slideUp(function () {
                                row.remove();
                            });
                        });
                    }
                    createTool(drawer, 'Add column', 'ge-add-column', 'glyphicon-plus-sign', function () {
                        row.append(createColumn(3));
                        init();
                    });

                    var details = createDetails(row, settings.row_classes).appendTo(drawer);
                });
            }

            function createColControls() {
                canvas.find('.column').each(function () {
                    var col = $(this);
                    if (col.find('> .ge-tools-drawer').length) {
                        return;
                    }

                    var drawer = $('<div class="ge-tools-drawer" />').prependTo(col);

                    createTool(drawer, 'Move', 'ge-move', 'glyphicon-move');

                    createTool(drawer, 'Make column narrower\n(hold shift for min)', 'ge-decrease-col-width', 'glyphicon-minus', function (e) {
                        var colSizes = settings.valid_col_sizes;
                        var curColClass = colClasses[curColClassIndex];
                        var curColSizeIndex = colSizes.indexOf(getColSize(col, curColClass));
                        var newSize = colSizes[clamp(curColSizeIndex - 1, 0, colSizes.length - 1)];
                        if (e.shiftKey) {
                            newSize = colSizes[0];
                        }
                        setColSize(col, curColClass, Math.max(newSize, 1));
                    });

                    createTool(drawer, 'Make column wider\n(hold shift for max)', 'ge-increase-col-width', 'glyphicon-plus', function (e) {
                        var colSizes = settings.valid_col_sizes;
                        var curColClass = colClasses[curColClassIndex];
                        var curColSizeIndex = colSizes.indexOf(getColSize(col, curColClass));
                        var newColSizeIndex = clamp(curColSizeIndex + 1, 0, colSizes.length - 1);
                        var newSize = colSizes[newColSizeIndex];
                        if (e.shiftKey) {
                            newSize = colSizes[colSizes.length - 1];
                        }
                        setColSize(col, curColClass, Math.min(newSize, MAX_COL_SIZE));
                    });

                    createTool(drawer, 'Settings', '', 'glyphicon-cog', function () {
                        details.toggle();
                    });

                    settings.col_tools.forEach(function (t) {
                        createTool(drawer, t.title || '', t.className || '', t.iconClass || 'glyphicon-wrench', t.on);
                    });
                    //Hide Delete icon for default grid.
                    var defaultCol = col.attr('default-col');
                    if (typeof defaultCol == typeof undefined || defaultCol == false) {
                        // if(col.index()>1){
                        createTool(drawer, 'Remove col', '', 'glyphicon-trash', function () {
                            col.animate({
                                opacity: 'hide',
                                width: 'hide',
                                height: 'hide'
                            }, 400, function () {
                                col.remove();
                            });
                        });
                    }

                    createTool(drawer, 'Add row', 'ge-add-row', 'glyphicon-plus-sign', function () {
                        var row = createRow();
                        col.append(row);
                        row.append(createColumn(6)).append(createColumn(6));
                        init();
                    });
                    //adding background color picker
                    createTool(drawer, 'Color Picker', 'jarvis-color-picker', 'colors', function () {
                        $('.jarvis-color-picker').on('change', this, function () {
                            $(this).parent().parent().css({'background-color': $(this).val()})
                        });
                    });

                    var details = createDetails(col, settings.col_classes).appendTo(drawer);
                });
            }

            function createTool(drawer, title, className, iconClass, eventHandlers) {
                if (iconClass == 'colors') {
                    var tool = $('<input title="Background color picker" style="width:20px;padding:0px;border:none;" type="color" class="jarvis-color-picker" value="#ff0000">').appendTo(drawer);
                } else {
                    var tool = $('<a title="' + title + '" class="' + className + '"><span class="glyphicon ' + iconClass + '"></span></a>')
                        .appendTo(drawer)
                }

                if (typeof eventHandlers == 'function') {
                    tool.on('click', eventHandlers);
                }
                if (typeof eventHandlers == 'object') {
                    $.each(eventHandlers, function (name, func) {
                        tool.on(name, func);
                    });
                }
            }

            function createDetails(container, cssClasses) {
                var detailsDiv = $('<div class="ge-details" />');

                $('<input class="ge-id" />')
                    .attr('placeholder', 'id')
                    .val(container.attr('id'))
                    .attr('title', 'Set a unique identifier')
                    .appendTo(detailsDiv)
                ;

                var classGroup = $('<div class="btn-group" />').appendTo(detailsDiv);
                cssClasses.forEach(function (rowClass) {
                    var btn = $('<a class="btn btn-xs btn-default" />')
                        .html(rowClass.label)
                        .attr('title', rowClass.title ? rowClass.title : 'Toggle "' + rowClass.label + '" styling')
                        .toggleClass('active btn-primary', container.hasClass(rowClass.cssClass))
                        .on('click', function () {
                            btn.toggleClass('active btn-primary');
                            container.toggleClass(rowClass.cssClass, btn.hasClass('active'));
                        })
                        .appendTo(classGroup)
                    ;
                });

                return detailsDiv;
            }

            function addAllColClasses() {
                canvas.find('.column, div[class*="qc-col-"]').each(function () {
                    var col = $(this);

                    var size = 2;
                    var sizes = getColSizes(col);
                    if (sizes.length) {
                        size = sizes[0].size;
                    }

                    var elemClass = col.attr('class');
                    colClasses.forEach(function (colClass) {
                        if (elemClass.indexOf(colClass) == -1) {
                            col.addClass(colClass + size);
                        }
                    });

                    col.addClass('column');
                });
            }

            /**
             * Return the column size for colClass, or a size from a different
             * class if it was not found.
             * Returns null if no size whatsoever was found.
             */
            function getColSize(col, colClass) {
                var sizes = getColSizes(col);
                for (var i = 0; i < sizes.length; i++) {
                    if (sizes[i].colClass == colClass) {
                        return sizes[i].size;
                    }
                }
                if (sizes.length) {
                    return sizes[0].size;
                }
                return null;
            }

            function getColSizes(col) {
                var result = [];
                colClasses.forEach(function (colClass) {
                    var re = new RegExp(colClass + '(\\d+)', 'i');
                    if (re.test(col.attr('class'))) {
                        result.push({
                            colClass: colClass,
                            size: parseInt(re.exec(col.attr('class'))[1])
                        });
                    }
                });
                return result;
            }

            function setColSize(col, colClass, size) {
                var re = new RegExp('(' + colClass + '(\\d+))', 'i');
                var reResult = re.exec(col.attr('class'));
                if (reResult && parseInt(reResult[2]) !== size) {
                    col.switchClass(reResult[1], colClass + size, 50);
                } else {
                    col.addClass(colClass + size);
                }
            }

            function makeSortable() {
                canvas.find('.qc-row').sortable({
                    items: '> .column',
                    connectWith: '.ge-canvas .qc-row',
                    handle: '> .ge-tools-drawer .ge-move',
                    start: sortStart,
                    helper: 'clone',
                });
                canvas.add(canvas.find('.column')).sortable({
                    items: '> .qc-row, > .ge-content',
                    connectsWith: '.ge-canvas, .ge-canvas .column',
                    handle: '> .ge-tools-drawer .ge-move',
                    start: sortStart,
                    helper: 'clone',
                });

                function sortStart(e, ui) {
                    ui.placeholder.css({height: ui.item.outerHeight()});
                }
            }

            function removeSortable() {
                canvas.add(canvas.find('.column')).add(canvas.find('.qc-row')).sortable('destroy');
            }

            function createRow() {
                return $('<div class="qc-row" />');
            }

            function createColumn(size) {
                return $('<div/>')
                    .addClass(colClasses.map(function (c) {
                        return c + size;
                    }).join(' '))
                    .append(createDefaultContentWrapper().html(
                        getRTE(settings.content_types[0]).initialContent)
                    )
                    ;
            }

            /**
             * Run custom content filter on init and deinit
             */
            function runFilter(isInit) {
                if (settings.custom_filter.length) {
                    $.each(settings.custom_filter, function (key, func) {
                        if (typeof func == 'string') {
                            func = window[func];
                        }

                        func(canvas, isInit);
                    });
                }
            }

            /**
             * Wrap column content in <div class="ge-content"> where neccesary
             */
            function wrapContent() {
                canvas.find('.column').each(function () {
                    var col = $(this);
                    var contents = $();
                    col.children().each(function () {
                        var child = $(this);
                        if (child.is('.qc-row, .ge-tools-drawer, .ge-content')) {
                            doWrap(contents);
                        } else {
                            contents = contents.add(child);
                        }
                    });
                    doWrap(contents);
                });
            }

            function doWrap(contents) {
                if (contents.length) {
                    var container = createDefaultContentWrapper().insertAfter(contents.last());
                    contents.appendTo(container);
                    contents = $();
                }
            }

            function createDefaultContentWrapper() {
                return $('<div/>')
                    .addClass('ge-content ge-content-type-' + settings.content_types[0])
                    .attr('data-ge-content-type', settings.content_types[0])
                    ;
            }

            function switchLayout(colClassIndex) {
                curColClassIndex = colClassIndex;

                var layoutClasses = ['ge-layout-desktop', 'ge-layout-tablet', 'ge-layout-phone'];
                layoutClasses.forEach(function (cssClass, i) {
                    canvas.toggleClass(cssClass, i == colClassIndex);
                });
            }

            function getRTE(type) {
                return $.fn.gridEditor.RTEs[type];
            }

            function clamp(input, min, max) {
                return Math.min(max, Math.max(min, input));
            }

            baseElem.data('grideditor', {
                init: init,
                deinit: deinit,
            });

        });

        return self;

    };

    $.fn.gridEditor.RTEs = {};

})(jQuery);
(function () {
    $.fn.gridEditor.RTEs.ckeditor = {

        init: function (settings, contentAreas) {

            if (!window.CKEDITOR) {
                console.error(
                    'CKEditor not available! Make sure you loaded the ckeditor and jquery adapter js files.'
                );
            }

            var self = this;
            contentAreas.each(function () {
                var contentArea = $(this);
                if (!contentArea.hasClass('active')) {
                    if (contentArea.html() == self.initialContent) {
                        // CKEditor kills this '&nbsp' creating a non usable box :/
                        contentArea.html('&nbsp;');
                    }

                    // Add the .attr('contenteditable',''true') or CKEditor loads readonly
                    contentArea.addClass('active').attr('contenteditable', 'true');

                    var configuration = $.extend(
                        (settings.ckeditor && settings.ckeditor.config ? settings.ckeditor.config : {}),
                        {
                            // Focus editor on creation
                            on: {
                                instanceReady: function (evt) {
                                    instance.focus();
                                }
                            }
                        }
                    );
                    var instance = CKEDITOR.inline(contentArea.get(0), configuration);
                }
            });
        },

        deinit: function (settings, contentAreas) {
            contentAreas.filter('.active').each(function () {
                var contentArea = $(this);

                // Destroy all CKEditor instances
                $.each(CKEDITOR.instances, function (_, instance) {
                    instance.destroy();
                });

                // Cleanup
                contentArea
                    .removeClass('active cke_focus')
                    .removeAttr('id')
                    .removeAttr('style')
                    .removeAttr('spellcheck')
                    .removeAttr('contenteditable')
                ;
            });
        },

        initialContent: '<p>Lorem initius... </p>',
    }
})();
(function () {

    $.fn.gridEditor.RTEs.summernote = {

        init: function (settings, contentAreas) {

            if (!jQuery().summernote) {
                console.error('Summernote not available! Make sure you loaded the Summernote js file.');
            }

            var self = this;
            contentAreas.each(function () {
                var contentArea = $(this);
                if (!contentArea.hasClass('active')) {
                    if (contentArea.html() == self.initialContent) {
                        contentArea.html('');
                    }
                    contentArea.addClass('active');

                    var configuration = $.extend(
                        (settings.summernote && settings.summernote.config ? settings.summernote.config : {}),
                        {
                            tabsize: 2,
                            airMode: true,
                            // Focus editor on creation
                            callbacks: {
                                onInit: function () {
                                    try {
                                        settings.summernote.config.callbacks.onInit.call(this);
                                    } catch (e) {
                                    }

                                    contentArea.summernote('focus');
                                }
                            }
                        }
                    );
                    contentArea.summernote(configuration);
                }
            });
        },

        deinit: function (settings, contentAreas) {
            contentAreas.filter('.active').each(function () {
                var contentArea = $(this);
                contentArea.summernote('destroy');
                contentArea
                    .removeClass('active')
                    .removeAttr('id')
                    .removeAttr('style')
                    .removeAttr('spellcheck')
                ;
            });
        },

        initialContent: '<p>Lorem ipsum dolores</p>',
    };
})();

(function () {
    $.fn.gridEditor.RTEs.tinymce = {
        init: function (settings, contentAreas) {
            if (!window.tinymce) {
                console.error('tinyMCE not available! Make sure you loaded the tinyMCE js file.');
            }
            if (!contentAreas.tinymce) {
                console.error('tinyMCE jquery integration not available! Make sure you loaded the jquery integration plugin.');
            }
            var self = this;
            contentAreas.each(function () {
                var contentArea = $(this);
                if (!contentArea.hasClass('active')) {
                    if (contentArea.html() == self.initialContent) {
                        contentArea.html('');
                    }
                    contentArea.addClass('active');
                    var configuration = $.extend(
                        {},
                        (settings.tinymce && settings.tinymce.config ? settings.tinymce.config : {}),
                        {
                            inline: true,
                            oninit: function (editor) {
                                // Bring focus to text field
                                $('#' + editor.settings.id).focus();

                                // Call original oninit function, if one was passed in the config
                                if (settings.tinymce.config.oninit && typeof settings.tinymce.config.oninit == 'function') {
                                    settings.tinymce.config.oninit(editor);
                                }
                            }
                        }
                    );
                    var tiny = contentArea.tinymce(configuration);
                }
            });
        },

        deinit: function (settings, contentAreas) {
            contentAreas.filter('.active').each(function () {
                var contentArea = $(this);
                var tiny = contentArea.tinymce();
                if (tiny) {
                    tiny.remove();
                }
                contentArea
                    .removeClass('active')
                    .removeAttr('id')
                    .removeAttr('style')
                    .removeAttr('spellcheck')
                ;
            });
        },

        initialContent: '<p>Lorem ipsum dolores</p>',
    };
})();

});
