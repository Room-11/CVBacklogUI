/**
 * @author Kyra D. <kyra@existing.me>
 * @author Chris Wright <cvbacklogui@daverandom.com>
 */

(function () { // sticky header
    'use strict';

    var tableHeader = document.getElementById('data-table-head'),
        origOffsetY = tableHeader.offsetTop;

    document.getElementById('sticky-legend-icons').innerHTML = document.getElementById('legend-icons').innerHTML;

    window.addEventListener('scroll', function () {
        if (window.scrollY >= origOffsetY) {
            tableHeader.classList.add('sticky');
        } else {
            tableHeader.classList.remove('sticky');
        }
    });
}());

(function () { // localize cache timestamp
    'use strict';

    var timestamp = document.getElementById('questions-timestamp'),
        date = new Date(Number(timestamp.getAttribute('data-timestamp')) * 1000),
        hours = date.getHours(),
        mins = date.getMinutes(),
        secs = date.getSeconds();

    hours = hours < 10 ? '0' + hours : hours;
    mins = mins < 10 ? '0' + mins : mins;
    secs = secs < 10 ? '0' + secs : secs;

    timestamp.textContent = hours + ':' + mins + ':' + secs;
}());
