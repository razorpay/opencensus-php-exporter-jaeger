<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Razorpay - Authorize {{$data['application']['name']}}</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <style>
        body {
            margin: 0;
            /*line-height: 1.4;*/
            color: #8497A0;
            font-size: 12px;
            line-height: 1.8;
            font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen,Ubuntu,Cantarell,"Open Sans","Helvetica Neue",sans-serif;
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
            background-position-x: 20px;
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
        @media (max-width: 600px) {
            .hero-description {
                width: auto;
            }
        }
        .app-logos {
            float: right;
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
        @media (max-width: 600px) {
            .app-logos {
                float: none;
                text-align: left;
                margin-top: 20px;
            }
        }
        .app-logo + .app-logo {
            margin-left: 8px;
        }
        ul {
            padding-left: 16px;
            margin: 8px 0 0 0;
        }
        .body-section {
        }
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
            Allow <span class="emphasis">{{$data['application']['name']}}</span> to access your <span class="emphasis">Nestaway</span> account on Razorpay?
          </div>

          <div class="app-logos">
            <div class="logo-1 app-logo"></div>
            <div class="logo-2 app-logo"></div>
          </div>
        </div>

        <div class="main-content">
          <p class="emphasis"><strong>This will allow {{$data['application']['name']}} to:</strong></p>
          <ul>
            <li>Read all your live transaction data from dashboard</li>
            <li>Create live orders, transactions, refunds and all other entities</li>
          </ul>
          <p class="emphasis"><strong>The application will not be able to:</strong></p>
          <ul>
            <li>Access or change your API keys</li>
            <li>Access your organization's private details</li>
            <li>Update your account settings</li>
          </ul>
        </div>

        <div class="button-toolbar">
          <form method="POST" action="/authorize">
            <input type="hidden" name="token" class="verify_token" value="" />
            <button type="submit" class="btn btn-submit" disabled>Authorize</button>
          </form>
          <form method="DELETE" action="/authorize">
            <input type="hidden" name="token" class="verify_token" value="" />
            <button class="btn btn-default" disabled>Cancel</button>
          </form>
        </div>
    </div>
</div>
<script type="text/javascript">
    (function () {
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
            page_container: $('.inner-content')
        };

        var errorHtml = {
            invalid_role: '<strong>You are not allowed to authorize this app.</strong> <br /> Contact one of your admins to add this app to your dashboard.',
            unknown_error: '<strong>An unknown error occurred.</strong> <br /> Please contact Razorpay support to report this issue.'
        };

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
            elements.buttons.prop('disabled', false);
            elements.user_details.show();
        }

        function handleUserSuccess(data) {
            validateResponseData(data);

            verifyToken = data.token;
            elements.user_email.text(data.email);
            elements.token.attr('value', verifyToken);

            enableButtonsAndShowEmail();
        };

        function getUser() {
            var userUrl = dashboardUrl + '/user/session';
            $.get({
                url: userUrl,
                data: {query: queryParams},
                dataType: "json",
                xhrFields: {
                   withCredentials: true
                },
                headers: {'X-Requested-With': 'XMLHttpRequest'}
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
                    pageUrl = window.location.href;
                    var pageUrl = encodeURIComponent(pageUrl);
                    var signinUrl = dashboardUrl + '#/access/signin?next=' + pageUrl;

                    window.location.href = signinUrl;
                } else {
                    showError('unknown_error');
                }
            });
        }

        window.RazorpayAuthorize = {
            getUser: getUser,
            verifyToken: verifyToken,
            showError: showError,
            elements: elements
        };
    })();

    RazorpayAuthorize.getUser();
</script>
</body>
</html>
