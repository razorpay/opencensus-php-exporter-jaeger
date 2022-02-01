<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Razorpay - Authorize {{$data['application']['name']}}</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script>
    !function(){var analytics=window.analytics=window.analytics||[];if(!analytics.initialize)if(analytics.invoked)window.console&&console.error&&console.error("Segment snippet included twice.");else{analytics.invoked=!0;analytics.methods=["trackSubmit","trackClick","trackLink","trackForm","pageview","identify","reset","group","track","ready","alias","debug","page","once","off","on","addSourceMiddleware","addIntegrationMiddleware","setAnonymousId","addDestinationMiddleware"];analytics.factory=function(t){return function(){var e=Array.prototype.slice.call(arguments);e.unshift(t);analytics.push(e);return analytics}};for(var t=0;t<analytics.methods.length;t++){var e=analytics.methods[t];analytics[e]=analytics.factory(e)}analytics.load=function(t,e){var n=document.createElement("script");n.type="text/javascript";n.async=!0;n.src="https://cdn.segment.com/analytics.js/v1/"+t+"/analytics.min.js";var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(n,a);analytics._loadOptions=e};analytics.SNIPPET_VERSION="4.1.0";
        const SEGMENT_API_KEY = "STNWyMUNH6L1Tzn4CBqOAlAuIbHzorJ4";
        analytics.load(SEGMENT_API_KEY);
        analytics.page();
        }}();
</script>
<script> 
    function emitSegment({eventName = '', properties = {}, toCleverTap = false}) {
        // This is segment's analytics window object not our analytics object
        const deviceType = /iPad/.test(navigator.userAgent)
            ? 't'
            : /Mobile|iP(hone|od)| Android|BlackBerry|IEMobile|Silk/.test(navigator.userAgent)
            ? 'm'
            : 'd';
        Object.assign(properties, {
            pageUrl: window.location.href,
            eventTimestamp: new Date().toISOString(),
            deviceType,
            
        });
        if (window.analytics && window.analytics.track) {
            window.analytics.track(eventName, properties, {
            integrations: {
                CleverTap: toCleverTap,
            },
            });
        }
    }
    emitSegment({eventName: 'OAuth Page Viewed', toCleverTap: true});

    function authorizeBtnClick() {
        emitSegment({eventName: 'Authorize button clicked', toCleverTap: true});
    }

    function cancelBtnClick() {
        emitSegment({eventName: 'Cancel Authorize button clicked', toCleverTap: true});
    }
</script>
    <style>
        body {
            margin: 0;
            /*line-height: 1.4;*/
            color: #8497A0;
            font-size: 12px;
            line-height: 1.8;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
        }

        a {
            color: #09f;
        }

        .header {
            background: #F7F8FA;
            height: 26px;
            padding: 14px 0;
        }

        .header-user-details {
            float: right;
            display: none;
        }

        .emphasis {
            color: #5A666F;
        }

        .content {
            max-width: 588px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .rzp-logo {
            height: 100%;
            background: url(https://razorpay.com/images/logo-black.png);
            background-repeat: no-repeat;
            background-size: contain;
            background-position: 20px 0;
        }

        .content-hero {
            overflow: auto;
            padding: 24px 0 0;
            border-bottom: 1px #eee solid;
        }

        .content-hero>div {
            margin-bottom: 24px;
        }

        .hero-description {
            width: 400px;
            font-size: 24px;
            line-height: 36px;
            float: left;
        }

        .app-logos {
            font-size: 0;
            text-align: right;
        }

        @media (max-width: 600px) {
            .hero-description {
                width: auto;
            }

            .app-logos {
                text-align: left;
            }
        }

        .app-logo {
            display: inline-flex;
            background: #ccc;
            height: 72px;
            width: 72px;
            border-radius: 4px;
            border: 1px #eee solid;
        }

        .app-logo+.app-logo {
            margin-left: 8px;
        }

        ul {
            padding-left: 16px;
            margin: 8px 0 0 0;
        }

        .body-section {}

        p {
            margin: 14px 0 0 0;
        }

        .main-content {
            padding-top: 12px;
        }

        .fade {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .button-toolbar {
            margin-top: 28px;
        }

        button {
            height: 40px;
            width: 124px;
            padding: 0;
            cursor: pointer;
            border-width: 0;
            border-radius: 2px;
            border-style: solid;
            font: inherit;
            line-height: inherit;
            margin: 0;
            line-height: 40px;
            color: white;
            background: #FAFAFA;
            outline: none;
        }

        button.btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        button.btn-submit {
            background: #7268B7;
        }

        button.btn-default {
            border: 1px #eee;
            color: #8497A0;
            margin-left: 14px;
        }

        form {
            display: inline-block;
        }

        .error-container {
            display: none;
            margin-top: 20px;
            border: 1px #FAEDD1 solid;
            background: #FCF8E3;
            color: #907545;
            padding: 12px;
            line-height: 1.4;
        }

        .close-window {
            color: #907545;
        }

        .tc-link {
            color: #3987F0;
            font-weight: bold;
            font-size: 14px;
        }

        .line-break {
            border: 1px solid #E6EBF3;
            margin: 16px 0;
        }

        .tc-content {
            position: relative;
            top: -2px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="content rzp-logo">
            <div class="header-user-details">Logged in as <span id="user_email">foo@bar.com</span>.</div>
        </div>
    </div>
    <div class="body-section content">
        <div class="error-container"></div>

        <div class="inner-content">
            <div class="content-hero">
                <div class="hero-description">
                    Allow <span class="emphasis">{{$data['application']['name']}}</span> to access your <span class="emphasis merchant-name"></span> account on Razorpay?
                </div>

                <div class="app-logos">
                    <div style="padding:0" class="app-logo"><img style="width:100%" class="application-logo" /></div>
                    <div style="padding:0" class="app-logo"><img style="width:100%" class="merchant-logo" /></div>
                </div>
            </div>

            <div class="main-content">
                <p class="emphasis"><strong>This will allow {{$data['application']['name']}} to take following actions:</strong></p>
                <ul id="scopes">
                    @if($data['scopes'])
                    @foreach($data['scopes'] as $item)
                    <li> {{$item}}</li>
                    @endforeach
                    @endif

                </ul>
            </div>
            <div class="line-break"></div>
            <div>
                <input type="checkbox" class="checkbox">
                <span class="tc-content">
                    I've read <a href='https://razorpay.com/terms/razorpayx/partnership/' target="_blank" class="tc-link">Terms & Condition</a> before granting access to my Razorpay Account.
                </span>
            </div>
            <div class="button-toolbar">
                <form method="POST" action="/authorize-multi-token">
                    <input type="hidden" name="token" class="verify_token" value="" />
                    <input type="hidden" name="merchant_id" class="merchant-id" value="" />
                    <button class="btn btn-submit" disabled onclick="authorizeBtnClick()">Authorize</button>
                </form>
                <form method='POST' action="/authorize-multi-token">
                    {{ method_field('DELETE') }}
                    <input type="hidden" name="token" class="verify_token" value="" />
                    <input type="hidden" name="merchant_id" class="merchant-id" value="" />
                    <button class="btn btn-default" disabled onclick="cancelBtnClick()">Cancel</button>
                </form>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        (function() {
            var dashboardUrl = "{{$data['dashboard_url']}}";
            var queryParams = "{{$data['query_params']}}";
            var pageUrl = window.location.href;
            var verifyToken = null;
            var elements = {
                user_email: $('#user_email'),
                user_details: $('.header-user-details'),
                buttons: $('.btn'),
                token: $('.verify_token'),
                error_pane: $('.error-container'),
                page_container: $('.inner-content'),
                merchant_logo: $('.merchant-logo'),
                merchant_name: $('.merchant-name'),
                merchant_id: $('.merchant-id'),
                application_logo: $('.application-logo')
            };

            var errorHtml = {
                invalid_role: '<strong>You are not allowed to authorize this app.</strong> <br /> Contact one of your admins to add this app to your dashboard.',
                unknown_error: '<strong>An unknown error occurred.</strong> <br /> Please contact Razorpay support to report this issue.'
            };

            $('.checkbox').change(function() {
                if ($(this).is(':checked')) {
                    $('.btn-submit').prop("disabled", false);
                } else {
                    $('.btn-submit').prop("disabled", true);
                }
            });

            function showError(type) {
                var error = errorHtml[type];

                elements.buttons.prop('disabled', true);
                elements.error_pane.html(error);
                elements.error_pane.show();
                elements.page_container.addClass('fade');
            }

            function validateResponseData(data) {
                // TODO:
                // Validate if the response has all the fields we need
                // Error if not.
            }

            function enableButtonsAndShowEmail() {
                //elements.buttons.prop('disabled', false);
                $('.btn-default').prop("disabled", false);
                elements.user_details.show();
            }

            function handleUserSuccess(data) {
                validateResponseData(data);

                verifyToken = data.token;
                elements.user_email.text(data.email);
                elements.merchant_logo.attr('src', data.logo);
                elements.merchant_name.text(data.merchant_name);
                elements.token.attr('value', verifyToken);
                elements.merchant_id.attr('value', data.merchant_id);

                enableButtonsAndShowEmail();

                emitSegment({eventName: 'Login Result', toCleverTap: true});
            }

            function getUser() {
                var userUrl = dashboardUrl + '/user/session';
                $.get({
                        url: userUrl,
                        data: {
                            query: queryParams
                        },
                        dataType: "json",
                        xhrFields: {
                            withCredentials: true
                        },
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .done(function(res, textStatus, xhr) {
                        var status = xhr.status;

                        if (status === 200) {
                            if (res.success === true) {
                                if (res.data.role === 'owner') {
                                    handleUserSuccess(res.data);
                                } else {
                                    showError('invalid_role');
                                }
                            } else {
                                showError('unknown_error');
                            }
                        }
                    })
                    .fail(function(xhr, textStatus, thrownError) {
                        if (xhr.status === 401) {
                            emitSegment({eventName: 'Login Request', toCleverTap: true});
                            pageUrl = window.location.href;
                            var pageUrl = encodeURIComponent(pageUrl);
                            var signinUrl = dashboardUrl + '?next=' + pageUrl;

                            window.location.href = signinUrl;
                        } else {
                            showError('unknown_error');
                        }
                    });
            }

            function getAppLogoFullUrl(logoUrl) {
                if (logoUrl !== null && !/^http/.test(logoUrl)) {
                    logoUrl = `https://cdn.razorpay.com${logoUrl.replace(/\.([^\.]+$)/, '_medium.$1')}`;
                }
                elements.application_logo.attr('src', logoUrl);
            }

            window.RazorpayAuthorize = {
                getUser: getUser,
                verifyToken: verifyToken,
                showError: showError,
                elements: elements,
                getAppLogoFullUrl: getAppLogoFullUrl
            };
        })();

        RazorpayAuthorize.getAppLogoFullUrl(`{{$data['application']['logo']}}`);
        RazorpayAuthorize.getUser();
    </script>
</body>

</html>