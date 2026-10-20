// Ortak rapor tablosu sıralaması. Yeni rapor tablolarında data-report-table
// özniteliği kullanılması yeterlidir.
(function () {
  'use strict';
  function clean(value) { return String(value == null ? '' : value).replace(/\s+/g, ' ').trim(); }
  function durationSeconds(value) {
    var text = clean(value).toLocaleLowerCase('tr-TR'), total = 0, found = false, match;
    match = /(\d+)\s*sa/.exec(text); if (match) { total += Number(match[1]) * 3600; found = true; }
    match = /(\d+)\s*dk/.exec(text); if (match) { total += Number(match[1]) * 60; found = true; }
    match = /(\d+)\s*sn/.exec(text); if (match) { total += Number(match[1]); found = true; }
    return found ? total : null;
  }
  function turkishNumber(value) {
    var text = clean(value).replace(/[^0-9,.-]/g, '');
    if (!text) return null;
    // 17.491,25 ve 17491.25 biçimlerinin ikisini de sayı olarak ele alır.
    if (text.indexOf(',') !== -1) text = text.replace(/\./g, '').replace(',', '.');
    var result = Number(text); return isFinite(result) ? result : null;
  }
  function columnKind(title) {
    var text = clean(title).toLocaleLowerCase('tr-TR');
    if (text.indexOf('süre') !== -1 || text.indexOf('son veri') !== -1) return 'duration';
    if (text.indexOf('tarih') !== -1) return 'date';
    if (/(^id$|tezgâh|adet|duruş|metraj|üretim|hız|randıman|oran|hedef|gerçekleşen|atkı|etkilenen)/.test(text)) return 'number';
    return 'text';
  }
  function cellValue(row, index, kind, headerText) {
    var cell = row.cells[index], value = clean(cell ? cell.textContent : '');
    if (kind === 'duration') { var seconds = durationSeconds(value); return seconds === null ? value : seconds; }
    if (kind === 'date') { var parsed = Date.parse(value); return isNaN(parsed) ? value : parsed; }
    if (kind === 'number') {
      // "Tezgâh" hücreleri "25 10LU DÖNER" şeklinde ad da içerir; yalnız
      // baştaki gerçek ID'yi dikkate alırız.
      if (clean(headerText).toLocaleLowerCase('tr-TR') === 'tezgâh') {
        var loomId = /^\s*(\d+)/.exec(value); if (loomId) return Number(loomId[1]);
      }
      var number = turkishNumber(value); return number === null ? value : number;
    }
    return value.toLocaleLowerCase('tr-TR');
  }
  function attach(table) {
    if (table.dataset.reportSortReady === '1') return;
    table.dataset.reportSortReady = '1';
    var headers = table.querySelectorAll('thead th');
    Array.prototype.forEach.call(headers, function (header, index) {
      header.classList.add('report-sort-header'); header.setAttribute('tabindex', '0'); header.setAttribute('role', 'button'); header.setAttribute('aria-sort', 'none');
      function sort() {
        var body = table.tBodies[0]; if (!body) return;
        var kind = columnKind(header.textContent);
        var direction = header.dataset.sortDirection === 'asc' ? -1 : 1;
        var rows = Array.prototype.slice.call(body.rows).filter(function (row) { return row.cells.length > 1; });
        rows.sort(function (left, right) {
          var a = cellValue(left, index, kind, header.textContent), b = cellValue(right, index, kind, header.textContent);
          if (typeof a === 'number' && typeof b === 'number') return (a - b) * direction;
          return String(a).localeCompare(String(b), 'tr', { numeric: true, sensitivity: 'base' }) * direction;
        });
        rows.forEach(function (row) { body.appendChild(row); });
        Array.prototype.forEach.call(headers, function (other) { other.dataset.sortDirection = ''; other.setAttribute('aria-sort', 'none'); other.classList.remove('is-sort-asc', 'is-sort-desc'); });
        header.dataset.sortDirection = direction === 1 ? 'asc' : 'desc';
        header.setAttribute('aria-sort', direction === 1 ? 'ascending' : 'descending');
        header.classList.add(direction === 1 ? 'is-sort-asc' : 'is-sort-desc');
      }
      header.addEventListener('click', sort);
      header.addEventListener('keydown', function (event) { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); sort(); } });
    });
  }
  function initialise() { Array.prototype.forEach.call(document.querySelectorAll('table[data-report-table]'), attach); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialise); else initialise();
}());
