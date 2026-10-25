(function () {
  'use strict'; var requestNumber=0;
  var date = document.getElementById('hourly-production-date');
  var loom = document.getElementById('hourly-production-loom');
  var exclude = document.getElementById('hourly-production-exclude');
  var button = document.getElementById('hourly-production-run');
  var excludeTimer = null;
  function number(value) { if(value==null)return '—';return Number(value || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  function escapeHtml(value) { var node = document.createElement('div'); node.textContent = value == null ? '' : String(value); return node.innerHTML; }
  function excludedIds() { var values = {}; String(exclude.value || '').split(/[^0-9]+/).forEach(function (value) { var id=Number(value); if (id>0 && Math.floor(id)===id) values[id]=id; }); return Object.keys(values).map(function (key) { return Number(key); }); }
  function label(hour) { return String(hour).padStart(2, '0') + ':00 – ' + String((Number(hour) + 1) % 24).padStart(2, '0') + ':00'; }
  function render(data) {
    var summary = data.summary || {}, hourly = data.hourly || [];
    document.getElementById('hourly-production-total').textContent = number(summary.meters) + ' m';
    document.getElementById('hourly-production-peak').textContent = summary.peakHour == null ? '—' : label(summary.peakHour) + ' · ' + number(summary.peakMeters) + ' m';
    document.getElementById('hourly-production-average').textContent = number(summary.average) + ' m';
    document.getElementById('hourly-production-looms').textContent = (summary.loomCount==null?'—':Number(summary.loomCount)+' tezgâh');
    document.getElementById('hourly-production-period').textContent = data.date || '';
    document.getElementById('hourly-production-rows').innerHTML = hourly.map(function (item) { return '<tr><td><b>' + label(item.hour) + '</b></td><td>' + number(item.meters) + ' m</td><td>' + (item.loomCount==null?'—':Number(item.loomCount)) + '</td></tr>'; }).join('');
    var max=Math.max.apply(Math,hourly.map(function(item){return Number(item.meters||0);}).concat([1]));
    document.getElementById('hourly-production-chart').innerHTML='<div class="hourly-production-bars">'+hourly.map(function(item){var height=Math.max(0,Number(item.meters||0)*100/max);return '<div title="'+escapeHtml(label(item.hour)+': '+number(item.meters)+' m')+'"><b>'+number(item.meters)+' m</b><i style="height:'+height+'%"></i><span>'+String(item.hour).padStart(2,'0')+'</span></div>';}).join('')+'</div>';
  }
  function load() { var requestId=++requestNumber; ReportQuality.loading();
    button.disabled=true; button.innerHTML='<i class="fa fa-spinner fa-spin"></i> Yükleniyor';
    var query=new URLSearchParams({date:date.value,loom:loom.value,exclude:excludedIds().join(',')});
    ReportQuality.request('hourly_production_data.php?'+query.toString(),{credentials:'same-origin'}).then(function(response){if(!response.ok)throw new Error('Saatlik üretim verisi alınamadı.');return response.json();}).then(function(data){if(requestId!==requestNumber)return;ReportQuality.show(data.quality);render(data);}).catch(function(error){ if(typeof requestId!=='undefined'&&requestId!==requestNumber)return;ReportQuality.error();document.getElementById('hourly-production-rows').innerHTML='<tr><td class="hourly-production-empty" colspan="3">'+escapeHtml(error.message)+'</td></tr>';}).finally(function(){ if(requestId!==requestNumber)return;button.disabled=false;button.innerHTML='<i class="fa fa-bar-chart"></i> Raporu getir';});
  }
  button.addEventListener('click',load); [date,loom].forEach(function(control){control.addEventListener('change',load);}); exclude.addEventListener('input',function(){window.clearTimeout(excludeTimer);excludeTimer=window.setTimeout(load,450);}); load();
}());
