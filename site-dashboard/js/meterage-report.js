(function () {
  'use strict'; var requestNumber=0;
  var from = document.getElementById('meterage-from');
  var to = document.getElementById('meterage-to');
  var loom = document.getElementById('meterage-loom');
  var exclude = document.getElementById('meterage-exclude');
  var button = document.getElementById('meterage-run');
  var excludeTimer = null;
  function number(value) { if(value==null)return '—';return Number(value || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  function escapeHtml(value) { var node = document.createElement('div'); node.textContent = value == null ? '' : String(value); return node.innerHTML; }
  function excludedIds() { var values = {}; String(exclude.value || '').split(/[^0-9]+/).forEach(function (value) { var id=Number(value); if (id>0 && Math.floor(id)===id) values[id]=id; }); return Object.keys(values).map(function (key) { return Number(key); }); }
  function render(data) {
    var summary = data.summary || {};
    document.getElementById('meterage-total').textContent = number(summary.meters) + ' m';
    document.getElementById('meterage-average').textContent = number(summary.average) + ' m';
    document.getElementById('meterage-looms').textContent = (summary.loomCount==null?'—':Number(summary.loomCount)+' tezgâh');
    document.getElementById('meterage-days').textContent = Number(summary.dayCount || 0) + ' gün';
    document.getElementById('meterage-period').textContent = data.from + ' – ' + data.to;
    var daily = data.daily || [];
    document.getElementById('meterage-daily').innerHTML = daily.map(function (item) { return '<tr><td><b>' + escapeHtml(item.date) + '</b></td><td>' + number(item.meters) + ' m</td><td>' + (item.loomCount==null?'—':Number(item.loomCount)) + '</td></tr>'; }).join('') || '<tr><td class="meterage-empty" colspan="3">Bu tarih aralığında veri bulunamadı.</td></tr>';
    var max = Math.max.apply(Math, daily.map(function (item) { return Number(item.meters || 0); }).concat([1]));
    document.getElementById('meterage-chart').innerHTML = daily.length ? '<div class="meterage-bars">' + daily.map(function (item) { var height = Math.max(0, Number(item.meters || 0) * 100 / max); return '<div title="' + escapeHtml(item.date + ': ' + number(item.meters) + ' m') + '"><b>' + number(item.meters) + ' m</b><i style="height:' + height + '%"></i><span>' + escapeHtml(item.date.slice(5)) + '</span></div>'; }).join('') + '</div>' : '<p class="meterage-empty-block">Bu tarih aralığında metraj verisi bulunamadı.</p>';
  }
  function load() { var requestId=++requestNumber; ReportQuality.loading();
    button.disabled=true; button.innerHTML='<i class="fa fa-spinner fa-spin"></i> Yükleniyor';
    var query=new URLSearchParams({from:from.value,to:to.value,loom:loom.value,exclude:excludedIds().join(',')});
    ReportQuality.request('meterage_data.php?'+query.toString(),{credentials:'same-origin'}).then(function(response){if(!response.ok)throw new Error('Metraj verisi alınamadı.');return response.json();}).then(function(data){if(requestId!==requestNumber)return;ReportQuality.show(data.quality);render(data);}).catch(function(error){ if(typeof requestId!=='undefined'&&requestId!==requestNumber)return;ReportQuality.error();document.getElementById('meterage-daily').innerHTML='<tr><td class="meterage-empty" colspan="3">'+escapeHtml(error.message)+'</td></tr>';}).finally(function(){ if(requestId!==requestNumber)return;button.disabled=false;button.innerHTML='<i class="fa fa-bar-chart"></i> Metrajı getir';});
  }
  button.addEventListener('click',load); [from,to,loom].forEach(function(control){control.addEventListener('change',load);}); exclude.addEventListener('input',function(){window.clearTimeout(excludeTimer);excludeTimer=window.setTimeout(load,450);}); load();
}());
