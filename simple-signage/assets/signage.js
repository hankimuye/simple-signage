/**
 * Simple Signage - Frontend JavaScript
 * @version 1.6
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. Trigger the "IN" animation on page load ---
        // We add the 'is-visible' class to trigger the CSS transition.
        // requestAnimationFrame ensures the browser applies initial styles before animating.
        requestAnimationFrame(function() {
            document.body.classList.add('is-visible');
        });


        // --- 2. Set up timer to navigate to the next slide ---
        if (typeof simpleSignage === 'undefined' || !simpleSignage.next_url) {
            console.log('Simple Signage: Data not found. Auto-transition disabled.');
            return;
        }

        const duration = parseInt(simpleSignage.duration, 10) * 1000;
        const nextUrl = simpleSignage.next_url;

        if (duration <= 0) {
            console.log('Simple Signage: Duration is zero or invalid. Auto-transition disabled.');
            return;
        }

        // After the slide's duration has passed, simply navigate to the next URL.
        setTimeout(function() {
            window.location.href = nextUrl;
        }, duration);
    });
})();
