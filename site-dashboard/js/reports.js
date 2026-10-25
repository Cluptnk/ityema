(function () {
  'use strict'; var requestNumber=0;
  var colors = ['#3f7cac', '#be754e', '#8d61a9', '#3e996f', '#c15f66', '#c79b37', '#5670b7', '#778594'];
  var from = document.getElementById('report-from');
  var to = document.getElementById('report-to');
  var windowSelect = document.getElementById('report-window');
  var loom = document.getElementById('report-loom');
  var loomSearch = document.getElementById('report-loom-search');
  var loomModeButton = document.getElementById('report-loom-mode');
  var loomFilterHelp = document.getElementById('report-loom-filter-help');
  var button = document.getElementById('report-run');
  var excelButton = document.getElementById('report-excel');
  var pdfButton = document.getElementById('report-pdf');
  var loomRanking = [];
  var causeRanking = [];
  var selectedCauseCode = 0;
  var lastData = null;
  var searchTimer = null;
  // Arama alanının varsayılan amacı, yazılan tezgâhları tüm sonuçlardan
  // çıkarmaktır. Kullanıcı düğmeye basarsa yalnız o ID'leri gösterir.
  var loomFilterMode = 'exclude';

  function duration(seconds) { if(seconds==null)return '—';
    seconds = Number(seconds || 0);
    var hours = Math.floor(seconds / 3600);
    var minutes = Math.floor((seconds % 3600) / 60);
    var rest = seconds % 60;
    return (hours ? hours + ' sa ' : '') + (minutes ? minutes + ' dk ' : '') + (rest || (!hours && !minutes) ? rest + ' sn' : '');
  }

  function escapeHtml(value) {
    var node = document.createElement('div'); node.textContent = value == null ? '' : String(value); return node.innerHTML;
  }

  function rows(target, html, colspan) {
    document.getElementById(target).innerHTML = html || '<tr><td class="report-empty" colspan="' + colspan + '">Bu tarih aralığında duruş kaydı bulunamadı.</td></tr>';
  }

  function searchedLoomIds() {
    var ids = {};
    String(loomSearch.value || '').split(/[^0-9]+/).forEach(function (value) {
      var id = Number(value);
      if (id > 0 && Math.floor(id) === id) ids[id] = id;
    });
    return Object.keys(ids).map(function (id) { return Number(id); });
  }

  function updateLoomFilterMode() {
    var excluding = loomFilterMode === 'exclude';
    loomModeButton.classList.toggle('is-exclude', excluding);
    loomModeButton.setAttribute('aria-pressed', excluding ? 'true' : 'false');
    loomModeButton.title = excluding ? 'Yazılan tezgâhlar rapordan çıkarılır' : 'Yalnız yazılan tezgâhlar gösterilir';
    loomModeButton.innerHTML = excluding
      ? '<i class="fa fa-minus-circle"></i><span>Hariç tut</span>'
      : '<i class="fa fa-filter"></i><span>Yalnız göster</span>';
    loomFilterHelp.textContent = excluding
      ? "Yazılan ID'leri virgülle ayırın; bu tezgâhlar rapordan çıkarılır."
      : "Yazılan ID'leri virgülle ayırın; yalnız bu tezgâhlar rapora gelir.";
  }

  function renderLoomOptions() {
    var selected = String(loom.value || '0');
    var options = ['<option value="0">Tüm tezgâhlar</option>'].concat(loomRanking.map(function (item) {
      var label = item.loomId + ' ' + escapeHtml(item.loomName);
      if (Number(item.seconds || 0) > 0) label += ' — ' + duration(item.seconds);
      return '<option value="' + Number(item.loomId) + '">' + label + '</option>';
    }));
    loom.innerHTML = options.join('');
    loom.value = selected;
    if (loom.value !== selected) loom.value = '0';
  }

  function drawPie(causes) {
    var pie = document.getElementById('report-pie');
    var legend = document.getElementById('report-legend');
    var total = causes.reduce(function (sum, cause) { return sum + Number(cause.seconds || 0); }, 0); var useSeconds=total>0;
    if (!total) total = causes.reduce(function (sum, cause) { return sum + Number(cause.count || 0); }, 0);
    if (!total) {
      pie.style.background = '#eef2f6';
      pie.innerHTML = '<span>Veri<br>yok</span>';
      legend.innerHTML = '';
      return;
    }
    var progress = 0;
    var parts = causes.map(function (cause, index) {
      var value = useSeconds ? Number(cause.seconds || 0) : Number(cause.count || 0);
      var next = progress + value / total * 100;
      var color = colors[index % colors.length];
      var part = color + ' ' + progress.toFixed(2) + '% ' + next.toFixed(2) + '%';
      progress = next;
      return part;
    });
    pie.style.background = 'conic-gradient(' + parts.join(',') + ')';
    pie.innerHTML = '<span>Toplam<br>' + duration(causes.reduce(function (sum, cause) { return sum + Number(cause.seconds || 0); }, 0)) + '</span>';
    legend.innerHTML = causes.map(function (cause, index) {
      return '<li><i class="report-swatch" style="background:' + colors[index % colors.length] + '"></i><b title="' + escapeHtml(cause.cause) + '">' + escapeHtml(cause.cause) + '</b><span>' + duration(cause.seconds) + '</span></li>';
    }).join('');
  }

  function render(data) {
    loomRanking = data.loomRanking || loomRanking;
    causeRanking = data.causeRanking || causeRanking;
    renderLoomOptions();
    lastData = data;
    excelButton.disabled = data.summary.count==null;
    pdfButton.disabled = data.summary.count==null;
    document.getElementById('report-total-count').textContent = (data.summary.count==null?'—':Number(data.summary.count)+' kayıt');
    document.getElementById('report-total-duration').textContent = duration(data.summary.seconds);
    document.getElementById('report-cause-count').textContent = Number(data.summary.causeCount || 0);
    rows('report-causes', causeRanking.map(function (item) {
      var selected = Number(item.code) === Number(selectedCauseCode) ? ' is-selected' : '';
      return '<tr class="report-cause-row' + selected + '" data-cause-code="' + Number(item.code) + '" tabindex="0" role="button" aria-pressed="' + (selected ? 'true' : 'false') + '"><td>' + escapeHtml(item.cause) + '</td><td>' + Number(item.count) + '</td><td>' + duration(item.seconds) + '</td></tr>';
    }).join(''), 3);
    rows('report-details', (data.details || []).map(function (detail) {
      return '<tr><td>' + Number(detail.loomId) + ' ' + escapeHtml(detail.loomName) + '</td><td>' + escapeHtml(detail.cause) + '</td><td>' + Number(detail.count) + '</td><td>' + duration(detail.seconds) + '</td></tr>';
    }).join(''), 4);
    drawPie(data.causes || []);
  }

  function loadReport() { var requestId=++requestNumber; ReportQuality.loading();
    button.disabled = true; button.textContent = 'Yükleniyor…';
    excelButton.disabled = true;
    pdfButton.disabled = true;
    var query = new URLSearchParams({ from: from.value, to: to.value, window: windowSelect.value, loom: loom.value, looms: searchedLoomIds().join(','), loomMode: loomFilterMode, cause: selectedCauseCode });
    ReportQuality.request('reports_data.php?' + query.toString(), { credentials: 'same-origin' })
      .then(function (response) { if (!response.ok) throw new Error('Rapor verisi alınamadı.'); return response.json(); })
      .then(function(data){if(requestId!==requestNumber)return;ReportQuality.show(data.quality);render(data);})
      .catch(function (error) { if(typeof requestId!=='undefined'&&requestId!==requestNumber)return;ReportQuality.error(); lastData = null; rows('report-causes', '<tr><td class="report-empty" colspan="3">' + escapeHtml(error.message) + '</td></tr>', 3); rows('report-details', '', 4); })
      .finally(function () { if(requestId!==requestNumber)return; button.disabled = false; button.innerHTML = '<i class="fa fa-bar-chart"></i> Raporu getir'; });
  }

  function selectedLoomLabel() {
    var ids = searchedLoomIds();
    var selected = Number(loom.value || 0);
    if (loomFilterMode === 'exclude') {
      var scope = selected > 0 ? 'Tezgâh ' + selected : 'Tüm tezgâhlar';
      return ids.length ? scope + ' (hariç: ' + ids.join(', ') + ')' : scope;
    }
    if (selected > 0 && ids.indexOf(selected) === -1) ids.push(selected);
    return ids.length ? ids.join(', ') : 'Tüm tezgâhlar';
  }

  function selectedCauseLabel() {
    var selected = causeRanking.filter(function (item) { return Number(item.code) === Number(selectedCauseCode); })[0];
    return selected ? selected.cause : 'Tüm duruş nedenleri';
  }

  function exportExcel() {
    if (!lastData) return;
    function cell(value) { return '<td>' + escapeHtml(value == null ? '' : value) + '</td>'; }
    function table(title, headers, values) {
      var head = headers.map(function (value) { return '<th>' + escapeHtml(value) + '</th>'; }).join('');
      var body = values.map(function (row) { return '<tr>' + row.map(cell).join('') + '</tr>'; }).join('');
      return '<h2>' + escapeHtml(title) + '</h2><table><thead><tr>' + head + '</tr></thead><tbody>' + body + '</tbody></table>';
    }
    var causes = (lastData.causes || []).map(function (item) { return [item.cause, Number(item.count || 0), duration(item.seconds)]; });
    var details = (lastData.details || []).map(function (item) { return [item.loomId + ' ' + item.loomName, item.cause, Number(item.count || 0), duration(item.seconds)]; });
    var documentHtml = '<!doctype html><html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8">' +
      '<style>body{font-family:Arial,sans-serif;color:#20344b;padding:24px}h1{margin:0;color:#19304c;font-size:24px}h2{margin:25px 0 8px;color:#2f5d8a;font-size:16px}p{color:#667b91}table{border-collapse:collapse;width:100%;margin-bottom:18px}th{background:#2f5d8a;color:#fff;text-align:left;padding:9px;border:1px solid #244d75}td{padding:8px;border:1px solid #d8e2ec}tr:nth-child(even) td{background:#f4f8fb}.summary{width:480px}.summary td:first-child{font-weight:bold;background:#edf3f8;width:220px}</style></head><body>' +
      '<h1>KAYITLI DURUŞ RAPORU</h1><p>Kapsam: yalnız alınmış olay kayıtları; cihaz saatine göre dönem. 100 PiCANOL hariçtir.</p><p>'+escapeHtml((lastData.quality&&lastData.quality.messages||[]).join(' '))+'</p><p>Tarih aralığı: <b>' + escapeHtml(lastData.from + ' - ' + lastData.to) + '</b></p>' +
      '<table class="summary"><tbody><tr><td>Vardiya / dönem</td><td>' + escapeHtml(lastData.windowLabel || '') + '</td></tr><tr><td>Tezgâh seçimi</td><td>' + escapeHtml(selectedLoomLabel()) + '</td></tr><tr><td>Duruş nedeni</td><td>' + escapeHtml(selectedCauseLabel()) + '</td></tr><tr><td>Toplam duruş kaydı</td><td>' + Number(lastData.summary.count || 0) + '</td></tr><tr><td>Toplam duruş süresi</td><td>' + duration(lastData.summary.seconds) + '</td></tr><tr><td>Farklı duruş nedeni</td><td>' + Number(lastData.summary.causeCount || 0) + '</td></tr></tbody></table>' +
      table('Nedene göre özet', ['Duruş nedeni', 'Adet', 'Toplam süre'], causes) +
      table('Tezgâh ve neden detayı', ['Tezgâh', 'Duruş nedeni', 'Adet', 'Toplam süre'], details) + '</body></html>';
    var blob = new Blob(['\ufeff' + documentHtml], { type: 'application/vnd.ms-excel;charset=utf-8' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'durus-raporu-' + lastData.from + '-' + lastData.to + '.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);
  }

  function exportPdf() {
    if (lastData) window.print();
  }

  button.addEventListener('click', loadReport);
  [from, to].forEach(function (control) { control.addEventListener('change', loadReport); });
  windowSelect.addEventListener('change', loadReport);
  loom.addEventListener('change', function () {
    loadReport();
  });
  loomModeButton.addEventListener('click', function () {
    loomFilterMode = loomFilterMode === 'exclude' ? 'include' : 'exclude';
    updateLoomFilterMode();
    loadReport();
  });
  loomSearch.addEventListener('input', function () {
    if (searchTimer) window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(function () {
      if (searchedLoomIds().length || !String(loomSearch.value || '').trim()) loadReport();
    }, 350);
  });
  document.getElementById('report-causes').addEventListener('click', function (event) {
    var row = event.target.closest('.report-cause-row');
    if (!row) return;
    var code = Number(row.getAttribute('data-cause-code'));
    selectedCauseCode = Number(selectedCauseCode) === code ? 0 : code;
    loadReport();
  });
  document.getElementById('report-causes').addEventListener('keydown', function (event) {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    var row = event.target.closest('.report-cause-row');
    if (!row) return;
    event.preventDefault();
    var code = Number(row.getAttribute('data-cause-code'));
    selectedCauseCode = Number(selectedCauseCode) === code ? 0 : code;
    loadReport();
  });
  excelButton.addEventListener('click', exportExcel);
  pdfButton.addEventListener('click', exportPdf);
  updateLoomFilterMode();
  loadReport();
}());
