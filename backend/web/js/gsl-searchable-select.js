(function ($) {
    function enhance($select) {
        if ($select.parent().hasClass('gsl-ss')) {
            return $select.data('gslSs');
        }

        var $wrap = $('<div class="gsl-ss"></div>');
        $select.after($wrap);
        $wrap.append($select);
        $select.addClass('gsl-ss-native').attr('tabindex', -1);

        var $input = $('<input type="text" class="form-control gsl-ss-input" autocomplete="off" spellcheck="false">');
        var $list = $('<div class="gsl-ss-list" hidden></div>');
        $wrap.append($input).append($list);

        var api = {
            $select: $select,
            $input: $input,
            $list: $list,
            syncFromSelect: function () {
                syncDisplay($select, $input);
                renderList($select, $list, '');
            }
        };
        $select.data('gslSs', api);

        syncDisplay($select, $input);

        $input.on('focus', function () {
            $input.val('');
            openList($select, $input, $list, '');
        });

        $input.on('input', function () {
            openList($select, $input, $list, $input.val());
        });

        $input.on('keydown', function (e) {
            handleKey($select, $input, $list, e);
        });

        $list.on('mousedown', '.gsl-ss-item', function (e) {
            e.preventDefault();
            choose($select, $input, $list, $(this).data('value'));
        });

        $input.on('blur', function () {
            setTimeout(function () {
                closeList($list);
                syncDisplay($select, $input);
            }, 120);
        });

        return api;
    }

    function optionText($select, value) {
        var $opt = $select.find('option').filter(function () {
            return String(this.value) === String(value);
        }).first();
        return $opt.length ? $opt.text() : '';
    }

    function promptText($select) {
        var $prompt = $select.find('option[value=""]').first();
        return $prompt.length ? $prompt.text() : '';
    }

    function syncDisplay($select, $input) {
        var value = $select.val();
        $input.attr('placeholder', promptText($select));
        $input.val(value ? optionText($select, value) : '');
    }

    function matchingOptions($select, term) {
        var query = $.trim(term || '').toUpperCase();
        var items = [];
        $select.find('option').each(function () {
            var value = this.value;
            var text = $(this).text();
            if (value === '') {
                return;
            }
            if (query && text.toUpperCase().indexOf(query) !== 0) {
                return;
            }
            items.push({ value: value, text: text });
        });
        return items;
    }

    function renderList($select, $list, term) {
        var items = matchingOptions($select, term);
        var current = String($select.val() || '');
        $list.empty();
        if (!items.length) {
            $list.append($('<div class="gsl-ss-empty"></div>').text($list.data('emptyText') || ''));
            return;
        }
        items.forEach(function (item) {
            var $item = $('<div class="gsl-ss-item"></div>')
                .attr('data-value', item.value)
                .text(item.text);
            if (item.value === current) {
                $item.addClass('active');
            }
            $list.append($item);
        });
    }

    function openList($select, $input, $list, term) {
        renderList($select, $list, term);
        $list.prop('hidden', false);
        scrollActive($list);
    }

    function closeList($list) {
        $list.prop('hidden', true);
    }

    function choose($select, $input, $list, value) {
        $select.val(value).trigger('change');
        closeList($list);
        syncDisplay($select, $input);
        $input.blur();
    }

    function scrollActive($list) {
        var $active = $list.find('.gsl-ss-item.active').first();
        if ($active.length) {
            var top = $active.position().top;
            var height = $list.height();
            if (top < 0 || top > height - $active.outerHeight()) {
                $list.scrollTop($list.scrollTop() + top - 8);
            }
        }
    }

    function handleKey($select, $input, $list, e) {
        var $items = $list.find('.gsl-ss-item');
        var $active = $items.filter('.active').first();
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if ($list.prop('hidden')) {
                openList($select, $input, $list, $input.val());
                $items = $list.find('.gsl-ss-item');
                $active = $items.filter('.active').first();
            }
            if (!$items.length) {
                return;
            }
            var index = $items.index($active);
            if (e.key === 'ArrowDown') {
                index = index < 0 ? 0 : Math.min($items.length - 1, index + 1);
            } else {
                index = index < 0 ? $items.length - 1 : Math.max(0, index - 1);
            }
            $items.removeClass('active').eq(index).addClass('active');
            scrollActive($list);
            return;
        }
        if (e.key === 'Enter') {
            if (!$list.prop('hidden') && $active.length) {
                e.preventDefault();
                choose($select, $input, $list, $active.data('value'));
            }
            return;
        }
        if (e.key === 'Escape') {
            closeList($list);
            syncDisplay($select, $input);
            $input.blur();
        }
    }

    window.GslSearchableSelect = {
        bind: function (selector, emptyText) {
            $(selector).each(function () {
                var api = enhance($(this));
                api.$list.data('emptyText', emptyText || '');
                api.syncFromSelect();
            });
        },
        refresh: function (select) {
            var api = $(select).data('gslSs');
            if (api) {
                api.syncFromSelect();
            }
        }
    };
})(jQuery);
