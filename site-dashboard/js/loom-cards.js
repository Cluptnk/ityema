/* Masaüstü haritayı korur; yalnızca dar ekranda kart moduna geçer. */
(function () {
    var mobileFilters = {};
    var dailyStopStats = null;
    var dailyStopStatsLoaded = false;
    var dailyStopRequestInProgress = false;
    var filterNames = {
        1: 'Manuel',
        2: 'Çalışıyor',
        3: 'Rocca',
        4: 'Trama',
        5: 'Ordito'
    };

    function isCardView() {
        return document.body && document.body.classList.contains('loom-card-view');
    }

    function selectedFilterCount() {
        return Object.keys(mobileFilters).length;
    }

    function refreshMobileFilters() {
        if (!isCardView()) {
            return;
        }

        var selected = Object.keys(mobileFilters);
        $('#droppable2 > .panel').each(function () {
            var status = $(this).find('.panel-cmdd-heading > span').first();
            var isSelected = selected.some(function (type) {
                return status.hasClass('icon-' + ({
                    1: 'manuale',
                    2: 'run',
                    3: 'rocca',
                    4: 'trama',
                    5: 'ordito'
                })[type]);
            });
            $(this).toggleClass('mobile-filter-hidden', selected.length > 0 && !isSelected);
        });

        var summary = $('.loom-mobile-filter-summary');
        if (summary.length) {
            summary.text(selected.length ? selected.length + ' filtre seçili' : 'Tüm tezgâhlar gösteriliyor');
        }
    }

    function prepareMobileFilterControls() {
        var controls = $('.loom-filter-controls');
        if (!controls.length || controls.find('.loom-mobile-filter-summary').length) {
            return;
        }

        controls.find('button[onclick^="filter"]').each(function (index) {
            var type = index + 1;
            $(this)
                .attr('data-mobile-filter', type)
                .attr('aria-pressed', 'false')
                .attr('title', filterNames[type]);
        });
        $('<div class="loom-mobile-filter-summary" aria-live="polite">Tüm tezgâhlar gösteriliyor</div>').appendTo(controls);
    }

    function readCardFields($body) {
        var fields = [];
        var pendingLabel = '';
        $body.children().each(function () {
            var $child = $(this);
            if ($child.hasClass('loom-card-clean-data') || $child.hasClass('loom-daily-stop-summary')) {
                return;
            }
            if ($child.hasClass('label-primary')) {
                pendingLabel = $.trim($child.text());
                return;
            }
            if (!$child.hasClass('row') || $child.hasClass('row-segnalation')) {
                return;
            }

            var $spans = $child.children('span');
            var $label = $spans.filter('.label-primary').first();
            var label = $label.length ? $.trim($label.text()) : pendingLabel;
            var $values = $spans.not('.label-primary');
            var value = $values.length ? $.trim($values.eq(0).text()) : '';
            var unit = $values.length > 1 ? $.trim($values.eq(1).text()) : '';
            if (label || value || unit) {
                fields.push({ label: label, value: value, unit: unit });
            }
            pendingLabel = '';
        });
        if (!fields.length) {
            var $labels = $body.find('.label-primary');
            var $values = $body.find('[id^="text"]');
            var $units = $body.find('[id^="unit"]');
            for (var index = 0; index < $values.length; index++) {
                fields.push({
                    label: $.trim($labels.eq(index).text()),
                    value: $.trim($values.eq(index).text()),
                    unit: $.trim($units.eq(index).text())
                });
            }
        }
        return fields;
    }

    function stopDurationText(stats) {
        var seconds = Math.max(0, Number(stats.seconds));
        if (!isFinite(seconds)) {
            return '—';
        }
        var minutes = Math.floor(seconds / 60);
        var remainingSeconds = Math.round(seconds % 60);
        if (minutes === 0) {
            return remainingSeconds + ' sn';
        }
        return minutes + ' dk ' + remainingSeconds + ' sn';
    }

    function statusInfo($panel) {
        var $status = $panel.find('.panel-cmdd-heading > span').first();
        if ($status.hasClass('icon-run')) return { key: 'running', text: 'Çalışıyor' };
        if ($status.hasClass('icon-manuale')) return { key: 'manual', text: 'Manuel duruş' };
        if ($status.hasClass('icon-rocca')) return { key: 'rocca', text: 'Rocca değişimi' };
        if ($status.hasClass('icon-trama')) return { key: 'trama', text: 'Atkı uyarısı' };
        if ($status.hasClass('icon-ordito')) return { key: 'ordito', text: 'Çözgü uyarısı' };
        return { key: 'unknown', text: 'Durum bekleniyor' };
    }

    // Eski widget veri kaynağı olarak kalır; modern kart sadece onun üstünde
    // çizilir. Kaynak DOM'a yazmadığımız için güncelleme döngüsü oluşmaz.
    function renderOwnedCard($panel, stats) {
        var $body = $panel.find('.panel-cmdd-body').first();
        if (!$body.length) {
            return;
        }
        var fields = readCardFields($body);
        var title = $.trim($panel.find('.panel-cmdd-title').first().text());
        var status = statusInfo($panel);
        var signature = [title, status.key, stats ? stats.count : '', stats ? stats.seconds : ''].concat(fields.map(function (field) {
            return [field.label, field.value, field.unit].join('\u001f');
        })).join('\u001e');
        var $card = $panel.children('.loom-owned-card');
        if ($card.length && $card.attr('data-signature') === signature) {
            return;
        }
        if (!$card.length) {
            $card = $('<section class="loom-owned-card" aria-label="Tezgâh bilgisi"></section>').appendTo($panel);
        }
        $panel.addClass('loom-modern-card-active');
        $card.empty().attr('data-signature', signature).removeClass(function (index, className) {
            return (String(className || '').match(/(^|\s)loom-status-\S+/g) || []).join(' ');
        }).addClass('loom-status-' + status.key);

        var $header = $('<div class="loom-owned-card-header"></div>').appendTo($card);
        $('<strong class="loom-owned-card-title"></strong>').text(title || 'Tezgâh').appendTo($header);
        $('<span class="loom-owned-card-status"></span>').attr('title', status.text).text('●').appendTo($header);

        var $data = $('<div class="loom-owned-card-data"></div>').appendTo($card);
        (fields.length ? fields.slice(0, 4) : [{ label: 'Canlı veri', value: 'Yükleniyor', unit: '' }]).forEach(function (field) {
            var $row = $('<div class="loom-owned-card-row"></div>').appendTo($data);
            $('<span class="loom-owned-card-label"></span>').text(field.label || '—').appendTo($row);
            $('<span class="loom-owned-card-value"></span>').text(field.value || '—').attr('title', field.value || '').appendTo($row);
            $('<span class="loom-owned-card-unit"></span>').text(field.unit).appendTo($row);
            if (!field.unit) {
                $row.addClass('loom-owned-card-wide');
            }
        });

        var $stops = $('<div class="loom-owned-card-stops"></div>').appendTo($card);
        $('<span></span>').text('Duruş (00:00)').appendTo($stops);
        $('<strong></strong>').text(stats ? stats.count + ' kez' : 'Hesaplanıyor').appendTo($stops);
        $('<span></span>').text('Toplam süre').appendTo($stops);
        $('<strong></strong>').text(stats ? stopDurationText(stats) : 'Hesaplanıyor').appendTo($stops);
    }

    function renderAllOwnedCards() {
        $('#droppable2 > .panel[data-idtelaio]').each(function () {
            var $panel = $(this);
            var loomId = String($panel.attr('data-idtelaio'));
            var stats = dailyStopStats && dailyStopStats[loomId]
                ? dailyStopStats[loomId]
                : (dailyStopStatsLoaded ? { count: 0, seconds: 0 } : null);
            renderOwnedCard($panel, stats);
        });
    }

    function renderDailyStopStats() {
        $('#droppable2 > .panel[data-idtelaio]').each(function () {
            var $panel = $(this);
            var loomId = String($panel.attr('data-idtelaio'));
            var stats = dailyStopStats && dailyStopStats[loomId]
                ? dailyStopStats[loomId]
                : (dailyStopStatsLoaded ? { count: 0, seconds: 0 } : null);
            var $body = $panel.find('.panel-cmdd-body').first();
            if (!$body.length) {
                return;
            }
            var $summary = $body.children('.loom-daily-stop-summary');
            if (!$summary.length) {
                $summary = $('<div class="loom-daily-stop-summary" aria-label="Bugünkü duruş özeti"></div>').appendTo($body);
                $summary.append('<div class="loom-daily-stop-row"><span>Duruş (00:00)</span><strong></strong></div>');
                $summary.append('<div class="loom-daily-stop-row"><span>Toplam süre</span><strong></strong></div>');
            }
            $summary.find('.loom-daily-stop-row').eq(0).find('strong').text(stats ? stats.count + ' kez' : 'Hesaplanıyor');
            $summary.find('.loom-daily-stop-row').eq(1).find('strong').text(stats ? stopDurationText(stats) : 'Hesaplanıyor');
            renderOwnedCard($panel, stats);
        });
    }

    function loadDailyStopStats() {
        if (dailyStopRequestInProgress) {
            return;
        }
        dailyStopRequestInProgress = true;
        $.getJSON('loom_daily_stops.php')
            .done(function (data) {
                dailyStopStats = data && data.stops ? data.stops : {};
                dailyStopStatsLoaded = true;
                renderDailyStopStats();
            })
            .always(function () {
                dailyStopRequestInProgress = false;
            });
    }

    function syncViewMode() {
        if (!document.body) {
            return;
        }
        var cardMode = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
        document.body.classList.toggle('loom-card-view', cardMode);
        document.body.classList.toggle('loom-map-view', !cardMode);
        prepareMobileFilterControls();
        if (typeof window.setDim === 'function') {
            window.setDim();
        }
        $('#droppable2').children().each(function () {
            $(this).matrix();
        });
        refreshMobileFilters();
        renderAllOwnedCards();
        renderDailyStopStats();
    }

    var originalMatrix = $.fn.matrix;
    $.fn.matrix = function () {
        if (isCardView()) {
            return this.css({
                transform: 'none',
                '-ms-transform': 'none',
                '-moz-transform': 'none',
                '-webkit-transform': 'none',
                'transform-origin': 'initial'
            });
        }
        return originalMatrix.apply(this, arguments);
    };

    var originalSetDim = window.setDim;
    window.setDim = function () {
        if (isCardView()) {
            return;
        }
        return originalSetDim.apply(this, arguments);
    };

    var originalZoom = window.myZoom;
    window.myZoom = function (event) {
        if (isCardView()) {
            return;
        }
        return originalZoom.apply(this, arguments);
    };

    var originalDown = window.myDown;
    window.myDown = function (event) {
        if (isCardView()) {
            return;
        }
        return originalDown.apply(this, arguments);
    };

    // Masaüstünün eski soluklaştırma filtresini korur. Telefonda ise seçilen
    // durumlar (birden fazlası da olabilir) dışındaki kartları tamamen gizler.
    var originalFilter = window.filter;
    window.filter = function (button, type) {
        if (!isCardView()) {
            return originalFilter.apply(this, arguments);
        }

        var $button = $(button);
        if (mobileFilters[type]) {
            delete mobileFilters[type];
            $button.removeClass('mobile-filter-active').attr('aria-pressed', 'false');
        } else {
            mobileFilters[type] = true;
            $button.addClass('mobile-filter-active').attr('aria-pressed', 'true');
        }
        refreshMobileFilters();
    };

    $(function () {
        prepareMobileFilterControls();
        var target = document.getElementById('droppable2');
        if (target && window.MutationObserver) {
            new MutationObserver(function () {
                refreshMobileFilters();
            }).observe(target, { subtree: true, attributes: true, attributeFilter: ['class'] });
        }
        loadDailyStopStats();
        window.setInterval(loadDailyStopStats, 60000);
        window.setTimeout(renderAllOwnedCards, 1000);
        window.setTimeout(renderAllOwnedCards, 3000);
        window.setInterval(function () {
            renderAllOwnedCards();
        }, 5000);
    });

    $(syncViewMode);
    $(window).on('resize', syncViewMode);
}());
