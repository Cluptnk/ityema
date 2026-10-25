(function () {
  'use strict'; var requestNumber=0;
  var date = document.getElementById('management-date');
  var windowSelect = document.getElementById('management-window');
  var loom = document.getElementById('management-loom');
  var exclude = document.getElementById('management-exclude');
  var button = document.getElementById('management-run');
  var excludeTimer = null;

  function escapeHtml(value) { var node = document.createElement('div'); node.textContent = value == null ? '' : String(value); return node.innerHTML; }
  function number(value, digits) { if(value==null)return '—';return Number(value || 0).toLocaleString('tr-TR', { minimumFractionDigits: digits || 0, maximumFractionDigits: digits || 0 }); }
  function duration(value) { if(value==null)return '—';var seconds = Number(value || 0), h = Math.floor(seconds / 3600), m = Math.floor((seconds % 3600) / 60), s = seconds % 60; return (h ? h + ' sa ' : '') + (m ? m + ' dk ' : '') + (s || (!h && !m) ? s + ' sn' : ''); }
  function empty(cols, text) { return '<tr><td class="management-empty" colspan="' + cols + '">' + escapeHtml(text || 'Bu seçimde veri bulunamadı.') + '</td></tr>'; }
  function rows(id, html, cols) { document.getElementById(id).innerHTML = html || empty(cols); }
  function excludedLoomIds() { var values = {}; String(exclude.value || '').split(/[^0-9]+/).forEach(function (value) { var id = Number(value); if (id > 0 && Math.floor(id) === id) values[id] = id; }); return Object.keys(values).map(function (id) { return Number(id); }); }
  function ago(seconds, lastUpdate) { if (seconds == null) return lastUpdate ? escapeHtml(lastUpdate) : 'Veri yok'; if (seconds < 0) return 'Saat uyumsuz'; if (seconds < 60) return seconds + ' sn önce'; if (seconds < 3600) return Math.floor(seconds / 60) + ' dk önce'; return Math.floor(seconds / 3600) + ' sa önce'; }

  function render(data) {
    var summary = data.summary || {}; document.getElementById('management-live-time').textContent=data.liveAsOf || '—';
    document.getElementById('management-stop-count').textContent = number(summary.count) + ' kayıt';
    document.getElementById('management-stop-duration').textContent = duration(summary.seconds);
    document.getElementById('management-offline-count').textContent = number(summary.offlineCount) + ' tezgâh';
    document.getElementById('management-goal-count').textContent = number(summary.goalCount) + ' tezgâh';
    rows('management-shifts', (data.shifts || []).map(function (item) { return '<tr><td><b>' + escapeHtml(item.label) + '</b></td><td>' + number(item.count) + '</td><td>' + duration(item.seconds) + '</td><td>' + number(item.looms) + '</td></tr>'; }).join(''), 4);
    rows('management-problems', (data.problems || []).map(function (item) { return '<tr><td><b>' + number(item.loomId) + '</b> ' + escapeHtml(item.loomName) + '</td><td>' + number(item.count) + '</td><td>' + duration(item.seconds) + '</td></tr>'; }).join(''), 3);
    var causeMax = Math.max.apply(Math, (data.causes || []).map(function (item) { return Number(item.seconds || 0); }).concat([1]));
    document.getElementById('management-pareto').innerHTML = (data.causes || []).map(function (item) { return '<div class="management-bar-row"><div><b title="' + escapeHtml(item.cause) + '">' + escapeHtml(item.cause) + '</b><span>' + number(item.count) + ' kez · ' + duration(item.seconds) + '</span></div><i><em style="width:' + Math.max(0, Number(item.seconds || 0) * 100 / causeMax) + '%"></em></i></div>'; }).join('') || '<p class="management-empty-block">Bu seçimde duruş nedeni bulunamadı.</p>';
    var categoryMax = Math.max.apply(Math, (data.categories || []).map(function (item) { return Number(item.seconds || 0); }).concat([1]));
    document.getElementById('management-categories').innerHTML = (data.categories || []).map(function (item) { return '<div class="management-category"><div><b>' + escapeHtml(item.category) + '</b><span>' + number(item.count) + ' kez</span></div><strong>' + duration(item.seconds) + '</strong><i><em style="width:' + Math.max(0, Number(item.seconds || 0) * 100 / categoryMax) + '%"></em></i></div>'; }).join('') || '<p class="management-empty-block">Bu seçimde kategori bulunamadı.</p>';
    var history = (data.history || {}).rows || [], historyName = (data.history || {}).loomName || '';
    document.getElementById('management-history-title').textContent = historyName ? historyName + ' · Son 14 gün duruş süresi' : 'Grafik için üstten bir tezgâh seçin.';
    var historyMax = Math.max.apply(Math, history.map(function (item) { return Number(item.seconds || 0); }).concat([1]));
    document.getElementById('management-history').innerHTML = history.length ? '<div class="management-history-bars">' + history.map(function (item) { var height = Math.max(0, Number(item.seconds || 0) * 100 / historyMax); return '<div title="' + escapeHtml(item.date + ': ' + duration(item.seconds) + ', ' + number(item.count) + ' duruş') + '"><i style="height:' + height + '%"></i><span>' + escapeHtml(item.date.slice(5)) + '</span></div>'; }).join('') + '</div>' : '<p class="management-empty-block">Tezgâh seçildiğinde son 14 günün duruş grafiği burada görünür.</p>';
    rows('management-patterns', (data.patterns || []).map(function (item) { return '<tr><td title="' + escapeHtml(item.pattern) + '">' + escapeHtml(item.pattern) + '</td><td>' + number(item.looms) + '</td><td>' + number(item.speed, 1) + ' RPM</td><td>' + number(item.efficiency, 2) + ' %</td></tr>'; }).join(''), 4);
    rows('management-offline', (data.offline || []).map(function (item) { return '<tr><td><b>' + number(item.loomId) + '</b> ' + escapeHtml(item.loomName) + '</td><td>' + ago(item.ageSeconds, item.lastUpdate) + '</td><td><span class="management-offline-pill">' + escapeHtml(item.state || 'Veri güncel değil') + '</span></td></tr>'; }).join(''), 3);
    rows('management-goals', (data.goals || []).map(function (item) { return '<tr><td><b>' + number(item.loomId) + '</b> ' + escapeHtml(item.loomName) + '</td><td>' + number(item.actual, 0) + '</td><td>' + number(item.target, 0) + '</td><td><span class="management-goal-pill">' + number(item.percent, 1) + ' %</span></td></tr>'; }).join(''), 4);
  }

  function load() { var requestId=++requestNumber; ReportQuality.loading();
    button.disabled = true; button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Yükleniyor';
    var query = new URLSearchParams({ date: date.value, window: windowSelect.value, loom: loom.value, exclude: excludedLoomIds().join(',') });
    ReportQuality.request('management_reports_data.php?' + query.toString(), { credentials: 'same-origin' }).then(function (response) { if (!response.ok) throw new Error('Analiz verisi alınamadı.'); return response.json(); }).then(function(data){if(requestId!==requestNumber)return;ReportQuality.show(data.quality);render(data);}).catch(function (error) { if(typeof requestId!=='undefined'&&requestId!==requestNumber)return;ReportQuality.error(); ['management-shifts','management-problems','management-patterns','management-offline','management-goals'].forEach(function (id) { rows(id, empty(id === 'management-shifts' ? 4 : id === 'management-problems' ? 3 : 4, error.message), 1); }); }).finally(function () { if(requestId!==requestNumber)return; button.disabled = false; button.innerHTML = '<i class="fa fa-refresh"></i> Analizi getir'; });
  }
  button.addEventListener('click', load);
  [date, windowSelect, loom].forEach(function (control) { control.addEventListener('change', load); });
  exclude.addEventListener('input', function () { window.clearTimeout(excludeTimer); excludeTimer = window.setTimeout(load, 450); });
  load();
}());
