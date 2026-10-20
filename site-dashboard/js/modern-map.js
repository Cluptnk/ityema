(function () {
  'use strict';

  var canvas = document.getElementById('modern-map-canvas');
  var viewport = document.getElementById('modern-map-viewport');
  var viewSelect = document.getElementById('modern-view-select');
  var shiftSelect = document.getElementById('modern-shift-select');
  var empty = document.getElementById('modern-map-empty');
  var currentView = Number(window.MODERN_MAP_INITIAL_VIEW || (viewSelect && viewSelect.value) || -1);
  var mapItems = [];
  var mapItemsLoaded = false;
  var liveItems = [];
  var stopItems = {};
  var stopStatsLoaded = false;
  var stopLabel = 'Vardiya duruşu';
  var activeFilters = new Set();
  var transform = { scale: 1, x: 0, y: 0 };
  var drag = null;
  var refreshTimer = null;
  var stopRefreshTimer = null;
  var mapRequestInProgress = false;
  var stopRequestInProgress = false;
  var fullscreenTransform = null;
  var temporaryFitActive = false;
  var lastRenderedMobileLayout = null;

  function isMobileLayout() {
    return window.matchMedia('(max-width: 767px)').matches;
  }

  // Telefonda kartlar harita koordinatlarından bağımsız, okunabilir bir liste
  // olarak gösterilir. Picanol kartları mobil listede gösterilmez; kalanlar
  // gerçek tezgâh ID'sine göre küçükten büyüğe sıralanır.
  function isPicanol(item) {
    return /p[iı]canol/i.test(String(item && item.nome || ''));
  }

  function loomNumericId(item) {
    var id = Number(item && item.idtelaio);
    if (isFinite(id)) return id;
    var match = String(item && item.nome || '').match(/\d+/);
    return match ? Number(match[0]) : Number.MAX_SAFE_INTEGER;
  }

  function sortItemsForMobile(items) {
    return items.slice().sort(function (left, right) {
      var idDifference = loomNumericId(left) - loomNumericId(right);
      if (idDifference) return idDifference;
      return titleFor(left).localeCompare(titleFor(right), 'tr');
    });
  }

  function transformStorageKey() {
    return 'perdekor-modern-map-transform-v1-' + currentView;
  }

  function saveTransform() {
    // Fitting for reset/full-screen is an on-screen convenience only.  It
    // must never overwrite the user's last deliberately saved map position.
    if (temporaryFitActive) return;
    try {
      localStorage.setItem(transformStorageKey(), JSON.stringify(transform));
    } catch (ignore) { }
  }

  function loadTransform() {
    try {
      var saved = JSON.parse(localStorage.getItem(transformStorageKey()) || 'null');
      if (saved && isFinite(saved.scale) && isFinite(saved.x) && isFinite(saved.y)) {
        transform = {
          scale: Math.max(.35, Math.min(3, Number(saved.scale))),
          x: Number(saved.x),
          y: Number(saved.y)
        };
      }
    } catch (ignore) { }
    applyTransform();
  }

  function request(data) {
    return fetch('JSONmsg.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams(data).toString(),
      credentials: 'same-origin'
    }).then(function (response) {
      if (!response.ok) throw new Error('Sunucu yanıtı ' + response.status);
      return response.json();
    });
  }

  function requestStops() {
    var windowCode = shiftSelect ? shiftSelect.value : 'auto';
    return fetch('loom_daily_stops.php?window=' + encodeURIComponent(windowCode), { credentials: 'same-origin' })
      .then(function (response) { return response.ok ? response.json() : { stops: {} }; })
      .catch(function () { return { stops: {} }; });
  }

  function statusOf(item) {
    if (String(item.online) !== '1' || String(item.enable) !== '1') return 'offline';
    // StatusString comes from the same stato_produzione + lingua tables used
    // by the notification centre.  Prefer it to a broad numeric range so a
    // manual stop, cone change, weft and warp condition cannot be coloured
    // as each other when models use different status-code variants.
    var description = String(item.StatusString || '').toLocaleLowerCase('tr-TR');
    if (description.indexOf('elle') !== -1 || description.indexOf('manuel') !== -1) return 'manual';
    if (description.indexOf('koni') !== -1 || description.indexOf('rocca') !== -1) return 'cone';
    if (description.indexOf('atkı') !== -1 || description.indexOf('rapier') !== -1 || description.indexOf('trama') !== -1) return 'weft';
    if (description.indexOf('çözgü') !== -1 || description.indexOf('ordito') !== -1) return 'warp';
    var code = Number(item.Status || 0) % 256;
    if (code === 0) return 'running';
    if (code === 1 || code === 15 || code === 16) return 'manual';
    if (code === 17 || code === 20) return 'cone';
    if (code === 18) return 'weft';
    if (code === 19) return 'warp';
    if (code >= 2 && code <= 14) return 'weft';
    return 'stopped';
  }

  function statusLabel(status) {
    return ({ running: 'Çalışıyor', manual: 'Manuel duruş', cone: 'Koni değişimi', weft: 'Atkı uyarısı', warp: 'Çözgü uyarısı', stopped: 'Duruş', offline: 'Çevrimdışı' })[status] || 'Bilinmiyor';
  }

  function statusIcon(status) {
    return ({ running: '↻', manual: '✋', cone: '⚑', weft: '☰', warp: '▥', stopped: '●', offline: '×' })[status] || '•';
  }

  function formatDuration(seconds) {
    seconds = Number(seconds || 0);
    if (!seconds) return '0 sn';
    var minutes = Math.floor(seconds / 60);
    var rest = seconds % 60;
    return minutes ? minutes + ' dk' + (rest ? ' ' + rest + ' sn' : '') : rest + ' sn';
  }

  function titleFor(mapItem) {
    return String(mapItem.idtelaio || '') + ' ' + String(mapItem.nome || 'Tezgâh');
  }

  function mergeItems() {
    var liveByMap = {};
    liveItems.forEach(function (item) { liveByMap[item.idDiMappa] = item; });
    return mapItems.map(function (mapItem) {
      var merged = {};
      Object.keys(mapItem).forEach(function (key) { merged[key] = mapItem[key]; });
      var live = liveByMap[mapItem.idDiMappa] || {};
      Object.keys(live).forEach(function (key) { merged[key] = live[key]; });
      return merged;
    });
  }

  function makeRow(label, value, unit) {
    var row = document.createElement('div');
    row.className = 'modern-card-row' + (!unit ? ' is-wide' : '');
    var key = document.createElement('span'); key.className = 'modern-card-key'; key.textContent = displayLabel(label);
    var val = document.createElement('span'); val.className = 'modern-card-value'; val.textContent = displayValue(label, value);
    var u = document.createElement('span'); u.className = 'modern-card-unit'; u.textContent = unit || '';
    row.appendChild(key); row.appendChild(val); row.appendChild(u);
    return row;
  }

  function normalizedLabel(label) {
    return String(label || '').toLocaleLowerCase('tr-TR').replace(/[^a-z0-9çğıöşü]/g, '');
  }

  function displayLabel(label) {
    var key = normalizedLabel(label);
    if (key === 'rpm') return 'T. Hızı';
    if (key === 'eff' || key === 'randiman' || key === 'randıman') return 'Randıman';
    if (key === 'armür1' || key === 'armur1') return 'Desen';
    return label || '—';
  }

  function displayValue(label, value) {
    if (value === undefined || value === null || value === '') return '—';
    var key = normalizedLabel(label);
    if (key === 'eff' || key === 'randiman' || key === 'randıman') {
      var number = Number(String(value).replace(',', '.'));
      if (isFinite(number)) {
        // The source keeps efficiency as hundredths (10000 = 100.00).
        return (number / 100).toFixed(2);
      }
    }
    if (key === 'atkısık' || key === 'atkısik' || key === 'atkisik') {
      return displayWeftDensity(value);
    }
    return value;
  }

  // DensitaTrama is not a normal decimal field.  Its upper bit stores the
  // measurement-system key.  This is the conversion used by the original
  // ILCA function convertiDensitaTrama().
  function displayWeftDensity(value) {
    var raw = Number(String(value).replace(',', '.'));
    if (!isFinite(raw)) return '—';
    var density = (Math.trunc(raw) & 32767);
    if (density < 7000) return String(density / 10);
    if (density < 17000) return String((density - 7000) / 100);
    return String((density - 10000) / 10);
  }

  function render(items) {
    canvas.querySelectorAll('.modern-loom-card').forEach(function (node) { node.remove(); });
    var activeCount = 0;
    var mobileLayout = isMobileLayout();
    // Masaüstünde kartların harita koordinatları ve mevcut konumları aynen
    // korunur. Sıralama yalnız mobil kart listesine uygulanır.
    var displayItems = mobileLayout
      ? sortItemsForMobile(items.filter(function (item) { return !isPicanol(item); }))
      : items;
    displayItems.forEach(function (item) {
      var status = statusOf(item);
      var card = document.createElement('article');
      card.className = 'modern-loom-card loom-state-' + status;
      card.dataset.status = status;
      card.dataset.search = (titleFor(item) + ' ' + (item.StatusString || '')).toLocaleLowerCase('tr-TR');
      card.style.left = (Number(item.PosX) || 0) + 'px';
      card.style.top = (Number(item.PosY) || 0) + 'px';
      card.title = statusLabel(status);

      var header = document.createElement('div'); header.className = 'modern-card-title';
      var title = document.createElement('span'); title.className = 'modern-card-title-text'; title.textContent = titleFor(item);
      var state = document.createElement('span'); state.className = 'modern-card-state'; state.textContent = statusIcon(status); state.setAttribute('aria-label', statusLabel(status));
      header.appendChild(title); header.appendChild(state); card.appendChild(header);

      var metrics = document.createElement('div'); metrics.className = 'modern-card-metrics';
      metrics.appendChild(makeRow(item.dato1s, item.val1, item.um1));
      metrics.appendChild(makeRow(item.dato2s, item.val2, item.um2));
      metrics.appendChild(makeRow(item.dato3s, item.val3, item.um3));
      metrics.appendChild(makeRow(item.dato4s, item.val4, item.um4));
      card.appendChild(metrics);

      var stop = stopItems[String(item.idtelaio)] || (stopStatsLoaded ? { count: 0, seconds: 0 } : null);
      var daily = document.createElement('div'); daily.className = 'modern-card-daily';
      [[stopLabel + ' duruş', stop ? Number(stop.count || 0) + ' kez' : 'Hesaplanıyor'], ['Toplam süre', stop ? formatDuration(stop.seconds) : 'Hesaplanıyor']].forEach(function (item) {
        var line = document.createElement('div'); line.className = 'modern-card-daily-row';
        var label = document.createElement('span'); label.textContent = item[0];
        var value = document.createElement('b'); value.textContent = item[1];
        line.appendChild(label); line.appendChild(value); daily.appendChild(line);
      });
      card.appendChild(daily);
      canvas.appendChild(card);
      activeCount += 1;
    });
    arrangeMapCards();
    lastRenderedMobileLayout = mobileLayout;
    empty.style.display = activeCount ? 'none' : 'block';
    applyFilters();
  }

  // The legacy map coordinates were drawn for very small cards.  Expand both
  // axes just enough for the readable cards while retaining map row/column order.
  function arrangeMapCards() {
    // Telefonda koordinatlı harita yerine doğrudan kart listesi kullanılır.
    // Masaüstü için hesaplanan sabit canvas yüksekliği mobilde kalırsa kartlar
    // arasında büyük boşluk oluşturuyordu.
    if (isMobileLayout()) {
      canvas.style.height = 'auto';
      return;
    }
    var cards = Array.prototype.slice.call(canvas.querySelectorAll('.modern-loom-card'));
    if (cards.length < 2) return;
    function uniqueCoordinates(axis) {
      return cards.map(function (card) { return Number(card.style[axis].replace('px', '')) || 0; })
        .filter(function (value, index, all) { return all.indexOf(value) === index; })
        .sort(function (a, b) { return a - b; });
    }
    function smallestSpacing(coordinates) {
      var differences = [];
      for (var index = 1; index < coordinates.length; index += 1) {
        var difference = coordinates[index] - coordinates[index - 1];
        if (difference >= 20) differences.push(difference);
      }
      return differences.length ? Math.min.apply(Math, differences) : 0;
    }
    var originalRows = uniqueCoordinates('top');
    var originalColumns = uniqueCoordinates('left');
    var verticalGap = smallestSpacing(originalRows);
    var horizontalGap = smallestSpacing(originalColumns);
    if (!verticalGap && !horizontalGap) return;
    var tallest = Math.max.apply(Math, cards.map(function (card) { return card.offsetHeight; }));
    var widest = Math.max.apply(Math, cards.map(function (card) { return card.offsetWidth; }));
    var rowScale = verticalGap ? Math.max(1, (tallest + 12) / verticalGap) : 1;
    var columnScale = horizontalGap ? Math.max(1, (widest + 16) / horizontalGap) : 1;
    var firstRow = originalRows[0];
    var firstColumn = originalColumns[0];
    var maxBottom = 0;
    cards.forEach(function (card) {
      var originalTop = Number(card.style.top.replace('px', '')) || 0;
      var originalLeft = Number(card.style.left.replace('px', '')) || 0;
      var top = firstRow + (originalTop - firstRow) * rowScale;
      var left = firstColumn + (originalLeft - firstColumn) * columnScale;
      card.style.top = Math.round(top) + 'px';
      card.style.left = Math.round(left) + 'px';
      maxBottom = Math.max(maxBottom, top + card.offsetHeight);
    });
    canvas.style.height = Math.max(3600, Math.ceil(maxBottom + 120)) + 'px';
  }

  function applyFilters() {
    var shown = 0;
    canvas.querySelectorAll('.modern-loom-card').forEach(function (card) {
      var hiddenByFilter = activeFilters.size > 0 && !activeFilters.has(card.dataset.status);
      card.classList.toggle('is-filtered', hiddenByFilter);
      if (!hiddenByFilter) shown += 1;
    });
  }

  function applyTransform() {
    if (isMobileLayout()) return;
    constrainTransform();
    canvas.style.transform = 'translate(' + transform.x + 'px,' + transform.y + 'px) scale(' + transform.scale + ')';
  }

  // Keep at least part of the map visible: it cannot be dragged into an
  // empty area outside the workspace, and the reset button is always there.
  function constrainTransform() {
    var width = canvas.offsetWidth * transform.scale;
    var height = canvas.offsetHeight * transform.scale;
    var minX = Math.min(0, viewport.clientWidth - width);
    var minY = Math.min(0, viewport.clientHeight - height);
    transform.x = Math.max(minX, Math.min(0, transform.x));
    transform.y = Math.max(minY, Math.min(0, transform.y));
  }

  function fitMapToViewport() {
    if (isMobileLayout()) return;
    var cards = Array.prototype.slice.call(canvas.querySelectorAll('.modern-loom-card'));
    if (!cards.length || !viewport.clientWidth || !viewport.clientHeight) return;
    var left = Infinity;
    var top = Infinity;
    var right = -Infinity;
    var bottom = -Infinity;
    cards.forEach(function (card) {
      var x = Number(card.style.left.replace('px', '')) || 0;
      var y = Number(card.style.top.replace('px', '')) || 0;
      left = Math.min(left, x);
      top = Math.min(top, y);
      right = Math.max(right, x + card.offsetWidth);
      bottom = Math.max(bottom, y + card.offsetHeight);
    });
    var padding = 36;
    var neededWidth = Math.max(1, right - left + padding * 2);
    var neededHeight = Math.max(1, bottom - top + padding * 2);
    var scaleX = viewport.clientWidth / neededWidth;
    var scaleY = viewport.clientHeight / neededHeight;
    transform.scale = Math.max(.35, Math.min(3, Math.min(scaleX, scaleY)));
    // Keep the content clear of the viewport edges.  constrainTransform()
    // prevents this position from exposing empty space outside the map.
    transform.x = padding - left * transform.scale;
    transform.y = padding - top * transform.scale;
    applyTransform();
  }

  function setBackground() {
    var option = viewSelect.options[viewSelect.selectedIndex];
    var image = option ? option.getAttribute('data-image') : '';
    if (image) {
      canvas.style.backgroundImage = "url('plant/" + image.replace(/'/g, '%27') + "')";
      canvas.classList.add('has-background');
    } else {
      canvas.style.backgroundImage = '';
      canvas.classList.remove('has-background');
    }
  }

  function refreshMap() {
    if (!currentView || currentView < 0) {
      empty.textContent = 'Bu kullanıcıya tanımlı bir görünüm bulunamadı.';
      return;
    }
    if (mapRequestInProgress) return;
    mapRequestInProgress = true;
    // Görünüm yerleşimi sabittir. 5033 sorgusunu yalnız ilk yüklemede (ve
    // görünüm değiştiğinde) çağırırız; her 10 saniyede sadece canlı değerler
    // istenir. Bu, kartların görünme süresini belirgin biçimde azaltır.
    var mapRequest = mapItemsLoaded
      ? Promise.resolve(mapItems)
      : request({ type: '5033', viewid: currentView }).then(function (result) {
          mapItems = Array.isArray(result) ? result : [];
          mapItemsLoaded = true;
          return mapItems;
        });
    mapRequest.then(function () {
      return request({ type: '5035' });
    }).then(function (live) {
      liveItems = Array.isArray(live) ? live : [];
      render(mergeItems());
    }).catch(function (error) {
      empty.textContent = 'Canlı veri alınamadı: ' + error.message;
      empty.style.display = 'block';
    }).finally(function () {
      mapRequestInProgress = false;
    });
  }

  function refreshStopStats() {
    if (stopRequestInProgress) return;
    stopRequestInProgress = true;
    requestStops().then(function (result) {
      stopItems = result.stops || {};
      stopLabel = result.label || 'Vardiya';
      if (shiftSelect && shiftSelect.value === 'auto' && result.label) {
        shiftSelect.options[0].textContent = 'Aktif: ' + result.label;
      }
      stopStatsLoaded = true;
      // Harita zaten yüklendiyse yalnız günlük özet satırlarını tazele.
      if (mapItems.length && liveItems.length) render(mergeItems());
    }).finally(function () {
      stopRequestInProgress = false;
    });
  }

  function bindFilters() {
    document.querySelectorAll('.modern-filter').forEach(function (button) {
      button.addEventListener('click', function () {
        var filter = button.dataset.filter;
        if (filter === 'all') {
          activeFilters.clear();
        } else if (activeFilters.has(filter)) {
          activeFilters.delete(filter);
        } else {
          activeFilters.add(filter);
        }
        document.querySelectorAll('.modern-filter').forEach(function (other) {
          other.classList.toggle('is-active', other.dataset.filter === 'all' ? activeFilters.size === 0 : activeFilters.has(other.dataset.filter));
        });
        applyFilters();
      });
    });
  }

  function bindPanZoom() {
    viewport.addEventListener('wheel', function (event) {
      if (isMobileLayout()) return;
      event.preventDefault();
      var oldScale = transform.scale;
      // Gentle wheel zoom: roughly one-and-a-half visual steps per notch.
      var wheelStep = 1.075;
      transform.scale = Math.max(.35, Math.min(3, transform.scale * (event.deltaY < 0 ? wheelStep : 1 / wheelStep)));
      var rect = viewport.getBoundingClientRect();
      var px = event.clientX - rect.left;
      var py = event.clientY - rect.top;
      transform.x = px - (px - transform.x) * (transform.scale / oldScale);
      transform.y = py - (py - transform.y) * (transform.scale / oldScale);
      applyTransform();
      saveTransform();
    }, { passive: false });
    viewport.addEventListener('pointerdown', function (event) {
      if (isMobileLayout()) return;
      drag = { x: event.clientX, y: event.clientY, tx: transform.x, ty: transform.y };
      viewport.classList.add('is-dragging');
      viewport.setPointerCapture(event.pointerId);
    });
    viewport.addEventListener('pointermove', function (event) {
      if (!drag) return;
      transform.x = drag.tx + event.clientX - drag.x;
      transform.y = drag.ty + event.clientY - drag.y;
      applyTransform();
    });
    ['pointerup', 'pointercancel'].forEach(function (eventName) {
      viewport.addEventListener(eventName, function () { drag = null; viewport.classList.remove('is-dragging'); saveTransform(); });
    });
  }

  function bindFullscreen() {
    var button = document.getElementById('modern-fullscreen');
    if (!button) return;
    var icon = button.querySelector('i');
    var label = button.querySelector('span');
    function isFullscreen() {
      return document.fullscreenElement === viewport || document.webkitFullscreenElement === viewport;
    }
    function updateButton() {
      var active = isFullscreen();
      icon.className = active ? 'fa fa-compress' : 'fa fa-expand';
      label.textContent = active ? 'Çıkış' : 'Tam ekran';
      button.title = active ? 'Tam ekrandan çık' : 'Haritayı tam ekranda aç';
      if (active) {
        temporaryFitActive = true;
        window.setTimeout(fitMapToViewport, 80);
      } else if (fullscreenTransform) {
        transform = fullscreenTransform;
        fullscreenTransform = null;
        temporaryFitActive = false;
        applyTransform();
      }
    }
    button.addEventListener('click', function () {
      if (isFullscreen()) {
        (document.exitFullscreen || document.webkitExitFullscreen).call(document);
      } else {
        fullscreenTransform = { scale: transform.scale, x: transform.x, y: transform.y };
        temporaryFitActive = true;
        var request = viewport.requestFullscreen || viewport.webkitRequestFullscreen;
        if (request) {
          var result = request.call(viewport);
          if (result && result.catch) {
            result.catch(function () { fullscreenTransform = null; temporaryFitActive = false; });
          }
        }
      }
    });
    document.addEventListener('fullscreenchange', updateButton);
    document.addEventListener('webkitfullscreenchange', updateButton);
  }

  function bindMapReset() {
    var button = document.getElementById('modern-map-reset');
    if (!button) return;
    button.addEventListener('click', function () {
      temporaryFitActive = true;
      fitMapToViewport();
      // Keep the stored manual map position untouched.  A subsequent manual
      // pan/zoom is considered a new user choice and will save normally.
      temporaryFitActive = false;
    });
  }

  if (!canvas || !viewport || !viewSelect || !shiftSelect) return;
  setBackground();
  loadTransform();
  bindFilters();
  bindPanZoom();
  bindFullscreen();
  bindMapReset();
  shiftSelect.addEventListener('change', function () { stopStatsLoaded = false; refreshStopStats(); });
  viewSelect.addEventListener('change', function () {
    saveTransform();
    currentView = Number(viewSelect.value);
    transform = { scale: 1, x: 0, y: 0 };
    mapItems = [];
    mapItemsLoaded = false;
    loadTransform();
    setBackground();
    refreshMap();
  });
  refreshMap();
  refreshStopStats();
  window.addEventListener('resize', function () {
    // Ekran yönü değiştiğinde (veya pencere mobil eşiğini geçtiğinde) aynı
    // veriyi yeniden çizer; böylece mobilde sıralı liste, masaüstünde harita
    // koordinatları gösterilmeye devam eder.
    if (mapItems.length && liveItems.length && lastRenderedMobileLayout !== isMobileLayout()) {
      render(mergeItems());
    }
  });
  refreshTimer = window.setInterval(refreshMap, 10000);
  stopRefreshTimer = window.setInterval(refreshStopStats, 60000);
  window.addEventListener('beforeunload', function () { window.clearInterval(refreshTimer); window.clearInterval(stopRefreshTimer); });
}());
