(function () {
  'use strict';
  var table = document.getElementById('ilca-status-table');
  if (!table) return;
  function value(cell, mode) {
    var text = String(cell.textContent || '').trim();
    if (mode === 'duration') return Number((text.match(/(\d+)\s*dk/) || [0, 0])[1]) * 60 + Number((text.match(/(\d+)\s*sn/) || [0, 0])[1]);
    if (mode === 'number') { var number = Number(text.replace(/[^0-9,.-]/g, '').replace(',', '.')); return isFinite(number) ? number : -Infinity; }
    return text.toLocaleLowerCase('tr-TR');
  }
  var modes = ['number', 'text', 'text', 'text', 'duration', 'text', 'number', 'number', 'number', 'text', 'number', 'duration'];
  var current = -1, direction = 1;
  Array.prototype.forEach.call(table.querySelectorAll('thead th'), function (header, index) {
    header.classList.add('ilca-sortable'); header.tabIndex = 0; header.setAttribute('role', 'button'); header.setAttribute('aria-sort', 'none');
    function sort() {
      direction = current === index ? direction * -1 : 1; current = index;
      var rows = Array.prototype.slice.call(table.tBodies[0].rows);
      rows.sort(function (left, right) { var a = value(left.cells[index], modes[index]), b = value(right.cells[index], modes[index]); return typeof a === 'string' ? a.localeCompare(b, 'tr') * direction : (a - b) * direction; });
      rows.forEach(function (row) { table.tBodies[0].appendChild(row); });
      Array.prototype.forEach.call(table.querySelectorAll('thead th'), function (item, itemIndex) { item.classList.toggle('is-sorted', itemIndex === index); item.classList.toggle('is-desc', itemIndex === index && direction < 0); item.setAttribute('aria-sort', itemIndex === index ? (direction > 0 ? 'ascending' : 'descending') : 'none'); });
    }
    header.addEventListener('click', sort); header.addEventListener('keydown', function (event) { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); sort(); } });
  });
}());
