<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Razorpay - Authorize {{$data['application']['name']}}</title>
    <script
            src="https://code.jquery.com/jquery-3.2.1.min.js"
            integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
            crossorigin="anonymous"></script>
    <style>
        html {
            line-height: 1.15; /* 1 */
            -ms-text-size-adjust: 100%; /* 2 */
            -webkit-text-size-adjust: 100%; /* 2 */
        }
        a {
            background-color: transparent; /* 1 */
            -webkit-text-decoration-skip: objects; /* 2 */
        }
        strong {
            font-weight: inherit;
            font-weight: bolder;
        }
        small {
            font-size: 80%;
        }
        button,
        input,
        optgroup,
        select,
        textarea {
            font-family: sans-serif; /* 1 */
            font-size: 100%; /* 1 */
            line-height: 1.15; /* 1 */
            margin: 0; /* 2 */
        }
        button,
        input { /* 1 */
            overflow: visible;
        }
        button,
        select { /* 1 */
            text-transform: none;
        }
        button,
        html [type="button"], /* 1 */
        [type="reset"],
        [type="submit"] {
            -webkit-appearance: button; /* 2 */
        }
        button::-moz-focus-inner,
        [type="button"]::-moz-focus-inner,
        [type="reset"]::-moz-focus-inner,
        [type="submit"]::-moz-focus-inner {
            border-style: none;
            padding: 0;
        }
        button:-moz-focusring,
        [type="button"]:-moz-focusring,
        [type="reset"]:-moz-focusring,
        [type="submit"]:-moz-focusring {
            outline: 1px dotted ButtonText;
        }
        html {
            font-family: sans-serif;
        }
        body {
            margin: 0;
        }
        body, a {
            color: #8497A0;
        }
        * {
            box-sizing: border-box;
        }
        .header {
            background: #F7F8FA;
            height: 54px;
            padding: 14px 0;
        }
        .emphasis {
            color: #5A666F;
        }
        .content {
            max-width: 588px;
            margin: 0 auto;
        }
        .rzp-logo {
            height: 100%;
            background: url(https://razorpay.com/images/logo-black.png);
            background-repeat: no-repeat;
            background-size: contain;
        }
        .content-hero {
            padding: 24px 0;
            border-bottom: 1px #eee solid;
        }
        .hero-description {
            width: calc(100% - 170px);
            font-size: 24px;
            line-height: 36px;
            display: inline-block;
        }
        .app-logos {
            float: right;
            height: 48px;
            font-size: 0;
            text-align: right;
        }
        .app-logo {
            display: inline-block;
            background: #ccc;
            height: 72px;
            width: 72px;
            border-radius: 4px;
            border: 1px #eee solid;
        }
        .app-logo + .app-logo {
            margin-left: 8px;
        }
        ul {
            padding-left: 16px;
            margin: 8px 0 0 0;
        }
        .body-section {
            font-size: 12px;
            line-height: 1.8;
        }
        p {
            margin: 14px 0 0 0;
        }
        .main-content {
            padding-top: 12px;
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
            border-radius: 4px;
            border-style: solid;
            font-size: 14px;
            line-height: 40px;
            color: white;
            background: #FAFAFA;
        }
        button.btn:disabled {
            -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=50)";
            filter: alpha(opacity=50);
            -moz-opacity: 0.5;
            -khtml-opacity: 0.5;
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
        .footer {
            display: none;
            margin-top: 28px;
            font-size: 10px;
        }
        .footer-content {
            display: inline-block;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="content rzp-logo"></div>
</div>
<div class="body-section content">
    <div class="content-hero">
        <div class="hero-description">
            Allow <span class="emphasis">{{$data['application']['name']}}</span> to access your <span class="emphasis">Nestaway</span> account on Razorpay?
        </div>

        <div class="app-logos">
            <div class="logo-1 app-logo"></div>
            <div class="logo-2 app-logo"></div>
        </div>
    </div>

    <div class="main-content">
        <p><strong>This will allow {{$data['application']['name']}} to:</strong></p>
        <ul>
            <li>Read all your live transaction data from dashboard</li>
            <li>Create live orders, transactions, refunds and all other entities</li>
        </ul>
        <p><strong>The application will not be able to:</strong></p>
        <ul>
            <li>Access or change your API keys</li>
            <li>Access your organization's private details</li>
            <li>Update your account settings</li>
        </ul>
    </div>

    <div class="button-toolbar">
        <button class="btn btn-submit" disabled>Authorize</button>
        <button class="btn btn-default" disabled>Cancel</button>
    </div>

    <div class="footer">
        <div class="footer-content">Logged in as <span id="user_email"></span>. <a href="#">Not you?</a></div>
        <div class="footer-content bullet">•</div>
        <div class="footer-content"><a href="#">Manage Connected Apps on Dashboard.</a></div>
    </div>
</div>
<script type="text/javascript">
    (function () {
        var dashboardUrl = "{{$data['dashboard_url']}}";
        var pageUrl = window.location.href;
        var verifyToken = null;
        var elements = {
            user_email: $('#user_email'),
            footer: $('.footer'),
            buttons: $('.btn')
        };

        function validateResponseData(data) {
            // TODO:
            // Validate if the response has all the fields we need
            // Error if not.
        }

        function enableButtonsAndShowEmail() {
            elements.buttons.prop('disabled', false);
            elements.footer.show();
        }

        function handleUserSuccess(data) {
            validateResponseData(data);

            verifyToken = data.token;
            elements.user_email.text(data.email);

            enableButtonsAndShowEmail();
        };

        function getUser() {
            var userUrl = dashboardUrl + '/user/logged_in';
            $.get({
                url: userUrl,
                dataType: "json",
                xhrFields: {
                   withCredentials: true
                }
            })
            .done(function(res, textStatus, xhr) {
                var status = xhr.status;

                if (status === 200) {
                    handleUserSuccess(res.data);
                } else if (status === 401) {
                    // Sign-in flow
                } else {
                    // Handle unknown errors
                }
            })
            .fail(function(xhr, textStatus, thrownError) {
                alert(thrownError);
            })
            .always(function () {
                console.log(verifyToken);
            });
        }

        window.RazorpayAuthorize = {
            getUser: getUser,
            verifyToken: verifyToken,
            elements: elements
        };
    })();

    RazorpayAuthorize.getUser();
</script>
</body>
</html>