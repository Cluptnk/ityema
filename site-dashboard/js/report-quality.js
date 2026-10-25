(function () {
  'use strict';
  function banner(text, summary) {
    var node = document.getElementById('report-data-quality');
    if (!node) {
      node = document.createElement('div'); node.id = 'report-data-quality';
      node.style.cssText = 'margin:10px 0;color:#64748b;font-size:13px;line-height:1.5';
      var root = document.querySelector('main'); if (root) root.insertBefore(node, root.children[1] || null);
    }
    node.textContent = ''; node.hidden = !text;
    if (!text) return;
    if (!summary) { node.textContent = text; return; }
    var details=document.createElement('details'), title=document.createElement('summary'), body=document.createElement('p');
    title.textContent=summary; title.style.cursor='pointer'; body.textContent=text;
    details.appendChild(title);details.appendChild(body);node.appendChild(details);
  }
  window.ReportQuality = {
    request: function (url, options) {
      options=options||{};options.headers=Object.assign({},options.headers||{}, {'X-Report-Version':'20261022-6'});
      return fetch(url,options).then(function(response){if(response.ok)return response;return response.json().then(function(data){throw new Error(data.error||'Rapor doğrulanamadı.');});});
    },
    show: function (quality) { var q=quality||{}; var ids=q.incompleteLooms||[]; var scope=q.selectedLoomCount?'Kayıtlı veriler: '+q.recordedLoomCount+'/'+q.selectedLoomCount+' tezgâh · metraj '+q.meterLoomCount+' tezgâh · cihaz saatine göre dönem. ':'Kayıtlı veriler · cihaz saatine göre dönem. '; var label=scope+(ids.length?ids.length+' tezgâhta veri eksik · Veri ayrıntıları':(q.lengthReliable===false?'Bazı metraj kayıtları tutarsız · Veri ayrıntıları':'Veri ayrıntıları')); banner((q.messages||[]).join(' '),label); },
    loading: function () {
      banner('Rapor yükleniyor…');
      document.querySelectorAll('main strong[id]').forEach(function (node) { node.textContent = '—'; });
      document.querySelectorAll('main tbody').forEach(function (node) { node.textContent = ''; });
      ['report-pie','report-legend','meterage-chart','hourly-production-chart','management-pareto','management-categories','management-history'].forEach(function (id) { var node=document.getElementById(id);if(node){node.textContent='';node.style.background='';} });
    },
    error: function () { banner('Rapor alınamadı veya doğrulanamadı. Boş alanlar sıfır değer değildir.'); },
    number: function (value, digits) { return value == null ? '—' : Number(value).toLocaleString('tr-TR', { minimumFractionDigits: digits || 0, maximumFractionDigits: digits == null ? 2 : digits }); }
  };
}());
