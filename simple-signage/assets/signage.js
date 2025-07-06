/**
 * Simple Signage - Frontend JavaScript
 * @version 1.5
 */
(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof simpleSignage === 'undefined' || !simpleSignage.next_url) {
            console.log('WP Signage: Data not found. Auto-transition disabled.');
            return;
        }
        const duration = parseInt(simpleSignage.duration, 10) * 1000;
        const nextUrl = simpleSignage.next_url;
        if (duration <= 0) {
            console.log('Signage: Duration is zero. Auto-transition disabled.');
            return;
        }
        setTimeout(function() {
            document.body.classList.add('is-transitioning');
            setTimeout(function() {
                window.location.href = nextUrl;
            }, 800);
        }, duration);
    });
})();

