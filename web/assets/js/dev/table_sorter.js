/** original code from https://github.com/roparz/Light-Javascript-Table-Sorter */
/** @author WesNetmo <https://github.com/WesNetmo> */

(function () {
    'use strict';

    var TableSorter = (function (Arr) {

        var thCol, cellIndex, sortOrder = '';

        function getText(row) {
            return row.cells.item(cellIndex).textContent.toLowerCase();
        }

        function sortCellsPositiveInteger(a, b) {
            var va = parseInt(getText(a), 10),
                vb = parseInt(getText(b), 10);
            va = isNaN(va) ? -1 : va;
            vb = isNaN(vb) ? -1 : vb;
            return va > vb ? 1 : va < vb ? -1 : 0;
        }

        function sortCellsNatural(a, b) {
            var va = getText(a), vb = getText(b);
            return va > vb ? 1 : va < vb ? -1 : 0;
        }

        function toggleSort() {
            var c = sortOrder !== 'asc' ? 'asc' : 'desc';
            thCol.className = (thCol.className.replace(sortOrder, '') + ' ' + c).trim();
            sortOrder = c;
        }

        function resetSort() {
            thCol.className = thCol.className.replace('asc', '').replace('desc', '');
            sortOrder = '';
        }

        function onClickEvent(e) {
            if (thCol && cellIndex !== e.target.cellIndex) {
                resetSort();
            }
            thCol = e.target;
            if (thCol.nodeName.toLowerCase() === 'th') {

                cellIndex = thCol.cellIndex;

                var tbody = document.getElementById('data-table-body'),
                    rows = tbody.rows,
                    useSortFunc;

                if (rows) {

                    switch (thCol.getAttribute('data-treat-as')) {
                        case 'int':
                            useSortFunc = sortCellsPositiveInteger;
                            break;
                        default:
                            useSortFunc = sortCellsNatural;
                    }

                    rows = Arr.sort.call(Arr.slice.call(rows, 0), useSortFunc);
                    if (sortOrder === 'asc') {
                        Arr.reverse.call(rows);
                    }
                    toggleSort();
                    tbody.innerHtml = '';
                    Arr.forEach.call(rows, function (row) {
                        tbody.appendChild(row);
                    });
                }
            }
        }

        return {
            init: function () {
                var columns = document.getElementById('table-column-names').getElementsByTagName('th');
                Arr.forEach.call(columns, function (column) {
                    column.onclick = onClickEvent;
                });
            }
        };

    }(Array.prototype));

    document.addEventListener('readystatechange', function () {
        if ('complete' === document.readyState) {
            TableSorter.init();
        }
    });

}());
