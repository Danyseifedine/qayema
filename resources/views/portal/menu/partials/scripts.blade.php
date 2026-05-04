<script>
    // Track time spent on page
    let startTime = Date.now();
    let timeSpent = 0;
    let lastUpdateTime = startTime;

    // Track time when page becomes hidden (user switches tab or closes)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page is hidden, calculate time spent so far
            timeSpent = Math.floor((Date.now() - startTime) / 1000);
            trackExit();
        } else {
            // Page is visible again, continue tracking from where we left off
            // Don't reset startTime, just update lastUpdateTime
            lastUpdateTime = Date.now();
        }
    });

    // Track time when user leaves the page
    window.addEventListener('beforeunload', function() {
        timeSpent = Math.floor((Date.now() - startTime) / 1000);
        trackExit();
    });

    // Also send periodic updates (every 30 seconds) to track active time
    setInterval(function() {
        if (!document.hidden) {
            timeSpent = Math.floor((Date.now() - startTime) / 1000);
            // Send heartbeat to update time spent
            updateTimeSpent(timeSpent);
        }
    }, 30000); // Every 30 seconds

    function updateTimeSpent(seconds) {
        if (seconds > 0) {
            const formData = new FormData();
            formData.append('time_spent', seconds);

            fetch('{{ route('public.menu.track-exit', $menu->slug) }}', {
                method: 'POST',
                body: formData,
                keepalive: true
            }).catch(() => {
                // Ignore errors
            });
        }
    }

    function trackExit() {
        if (timeSpent > 0) {
            // Use URL-encoded string for sendBeacon
            const data = 'time_spent=' + encodeURIComponent(timeSpent);
            const blob = new Blob([data], {
                type: 'application/x-www-form-urlencoded'
            });
            navigator.sendBeacon('{{ route('public.menu.track-exit', $menu->slug) }}', blob);
        }
    }
</script>
