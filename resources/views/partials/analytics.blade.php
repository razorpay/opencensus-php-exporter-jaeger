<script>
    // snippet to import segment analytics library
    ! function() {
        var analytics = window.analytics = window.analytics || [];
        if (!analytics.initialize)
            if (analytics.invoked) window.console && console.error && console.error("Segment snippet included twice.");
            else {
                analytics.invoked = !0;
                analytics.methods = ["trackSubmit", "trackClick", "trackLink", "trackForm", "pageview", "identify", "reset", "group", "track", "ready", "alias", "debug", "page", "once", "off", "on", "addSourceMiddleware", "addIntegrationMiddleware", "setAnonymousId", "addDestinationMiddleware"];
                analytics.factory = function(t) {
                    return function() {
                        var e = Array.prototype.slice.call(arguments);
                        e.unshift(t);
                        analytics.push(e);
                        return analytics
                    }
                };
                for (var t = 0; t < analytics.methods.length; t++) {
                    var e = analytics.methods[t];
                    analytics[e] = analytics.factory(e)
                }
                analytics.load = function(t, e) {
                    var n = document.createElement("script");
                    n.type = "text/javascript";
                    n.async = !0;
                    n.src = "https://cdn.segment.com/analytics.js/v1/" + t + "/analytics.min.js";
                    var a = document.getElementsByTagName("script")[0];
                    a.parentNode.insertBefore(n, a);
                    analytics._loadOptions = e
                };
                analytics.SNIPPET_VERSION = "4.1.0";
                analytics.load(window.SEGMENT_API_KEY);
                analytics.page();
            }
    }();


    const deviceType = /iPad/.test(navigator.userAgent) ?
        't' :
        /Mobile|iP(hone|od)| Android|BlackBerry|IEMobile|Silk/.test(navigator.userAgent) ?
        'm' :
        'd';
    const commonEventProperties = {
        pageUrl: window.location.href,
        eventTimestamp: new Date().toISOString(),
        deviceType,
        user_agent: navigator.userAgent,
        connection: navigator.connection,
        referrer: document.referrer,
    }

    function emitSegment({
        eventName = '',
        properties = {},
        toCleverTap = false
    }) {
        Object.assign(properties, {
            ...commonEventProperties,
        });
        if (window.analytics && window.analytics.track) {
            window.analytics.track(eventName, properties, {
                integrations: {
                    CleverTap: toCleverTap,
                },
            });
        }
    }

    const sendToLumberjack = ({
        eventName,
        properties = {}
    }) => {
        Object.assign(properties, {
            ...commonEventProperties,
        });
        const body = {
            mode: 'live',
            key: window.LUMBERJACK_API_KEY,
            events: [{
                event_type: 'auth-service',
                event: eventName,
                event_version: 'v1',
                timestamp: new Date().getTime(),
                properties: {
                    ...properties,
                },
            }, ],
        };

        fetch(window.LUMBERJACK_API_URL, {
            method: 'post',
            body: JSON.stringify(body),
            headers: {
                'Content-Type': 'application/json',
            },
        })
    };

    const trackEvents = ({
        eventName,
        properties = {},
        toCleverTap = false
    }) => {
        emitSegment(eventName, properties, toCleverTap);
        sendToLumberjack(eventName, properties);
    };
</script>