<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta charset="UTF-8"/>
    <title>Razorpay - Authorize {{$data['application']['name']}}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato&display=swap" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <style>
        body {
            -webkit-font-smoothing: antialiased;
            height: 100vh;
            margin: 0;
            /*line-height: 1.4;*/
            color: #8497a0;
            font-size: 12px;
            line-height: 1.8;
            font-family: 'Lato', sans-serif;
        }

        .header-logo {
          display: none;
        }

        @media (min-width: 1024px) {
          .header-logo {
            display: block;
            position: absolute;
            left: 58px;
            top: 42px;
          }
        }

        main {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 100vw;
          height: 100vh;
          background: transparent;
          background-image: url("https://easy.razorpay.com/build/browser/static/static/logos/dweb-botton-vector.svg");
          background-repeat: repeat-x;
          background-position: center bottom;
        }

        .header {
            background: #f7f8fa;
            height: 26px;
            padding: 14px 0;
        }

        .header-user-details {
            float: right;
            display: none;
        }

        .emphasis {
            color: #5a666f;
        }

        .content {
            max-width: 588px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .content-hero {
            overflow: auto;
            padding: 24px 0 0;
            border-bottom: 1px #eee solid;
        }

        .content-hero > div {
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

        .app-logo + .app-logo {
            margin-left: 8px;
        }

        ul {
            padding-left: 16px;
            margin: 8px 0 0 0;
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
            background: #fafafa;
            outline: none;
        }

        form {
            display: inline-block;
        }

        .error-container {
            display: none;
            border: 1px #faedd1 solid;
            background: #fcf8e3;
            color: #907545;
            padding: 12px;
            margin: 0px 24px 12px 24px;
            line-height: 1.4;
        }
        #loader {
            width: 40px;
            height: 40px;
            border: 3.5px solid #1a90ff;
            border-bottom-color: rgb(26, 144, 255);
            border-bottom-color: #eee;
            border-radius: 50%;
            display: inline-block;
            box-sizing: border-box;
            animation: rotation 600ms linear infinite;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
        }
            100% {
                transform: rotate(360deg);
        }
        } 
        .rzp-auth-header-footer {
            margin: 0px;
            padding: 0px;
        }

    </style>
</head>
<body>
    <div class="rzp-auth-header-footer">
        <img class="header-logo" src="https://easy.razorpay.com/federated-bundles/onboarding/build/browser/static/src/App/Onboarding/images/rzp-logo-dark.svg" />
    </div>
<main>
    <div id="loader"></div>
  @include('partials.card')
</main>
<div class="rzp-auth-header-footer">
    @include('partials.copyright')
</div>

<script type="text/javascript">
    (function () {
        var dashboardUrl = "{{$data['dashboard_url']}}";
        var queryParams = "{{$data['query_params']}}";
        var onboardingUrl = "{{$data['onboarding_url']}}";
        var isOnboardingExpEnabled = "{{$data['isOnboardingExpEnabled']}}";
        var pageUrl = window.location.href;
        var verifyToken = null;
        var elements = {
            user_email: $("#user_email"),
            user_details: $(".header-user-details"),
            buttons: $(".btn"),
            token: $(".verify_token"),
            error_pane: $(".error-container"),
            page_container: $(".inner-content"),
            merchant_logo: $(".merchant-logo"),
            merchant_name: $(".merchant-name"),
            merchant_id: $(".merchant-id"),
            application_logo: $(".application-logo"),
        };

        var errorHtml = {
            invalid_role:
                "<strong>You are not allowed to authorize this app.</strong> <br /> Contact one of your admins to add this app to your dashboard.",
            unknown_error:
                "<strong>An unknown error occurred.</strong> <br /> Please contact Razorpay support to report this issue.",
        };

        function showError(type) {
            var error = errorHtml[type];
            elements.buttons.prop("disabled", true);
            elements.error_pane.html(error);
            elements.error_pane.show();
            elements.page_container.addClass("fade");
        }

        function validateResponseData(data) {
            // TODO:
            // Validate if the response has all the fields we need
            // Error if not.
        }

        function enableButtonsAndShowEmail() {
            //elements.buttons.prop('disabled', false);
            $('.btn-default').prop("disabled", false);
            $('.btn-submit').prop("disabled", false);
            elements.user_details.show();
        }

        function showLoader(){
            document.querySelector('#loader').style.display = 'block';
            document.querySelector('.card').style.display = 'none';
            document.querySelector('.rzp-auth-header-footer').style.display = 'none';
        }

        function hideLoader(){
            document.querySelector('#loader').style.display = 'none';
            document.querySelector('.card').style.display = 'flex';
            document.querySelector('.rzp-auth-header-footer').style.display = 'block';
        }

        function handleUserSuccess(data) {
            validateResponseData(data);

            verifyToken = data.token;
            elements.user_email.text(data.email);
            elements.merchant_logo.attr("src", data.logo);
            elements.merchant_name.text(data.merchant_name);
            elements.token.attr("value", verifyToken);
            elements.merchant_id.attr("value", data.merchant_id);
            hideLoader();
            enableButtonsAndShowEmail();
        }

        function getUser() {
            var userUrl = dashboardUrl + "/user/session";
            $.get({
                url: userUrl,
                data: {
                  query: queryParams,
                  source: isOnboardingExpEnabled ? "oauth" : ""
                },
                dataType: "json",
                xhrFields: {
                    withCredentials: true,
                },
                headers: {"X-Requested-With": "XMLHttpRequest"},
            })
                .done(function (res, textStatus, xhr) {
                    var status = xhr.status;
                    if (status === 200) {
                        if (res.success === true) {
                            if (res.data.role === "owner") {
                              if (shouldRedirect(res)) {
                                pageUrl = window.location.href;
                                var pageUrl = encodeURIComponent(pageUrl);

                                window.location.href = getSignInUrl(pageUrl);
                              }
                                handleUserSuccess(res.data);
                            } else {
                                showError("invalid_role");
                            }
                        } else {
                            showError("unknown_error");
                        }
                    }
                })
                .fail(function (xhr, textStatus, thrownError) {
                    if (xhr.status === 401) {
                        pageUrl = window.location.href;
                        var pageUrl = encodeURIComponent(pageUrl);

                        window.location.href = getSignInUrl(pageUrl);
                    } else {
                        showError("unknown_error");
                    }
                });
        }

        function getSignInUrl(pageUrl) {
            if (isOnboardingExpEnabled) {
                let url = new URL(onboardingUrl);
                url.searchParams.append("next", pageUrl);
                return url;
            }
            return dashboardUrl + "?next=" + pageUrl;
        }

        function shouldRedirect(res) {
            return res.data.oauth_action === "REDIRECT"
        }

        function getAppLogoFullUrl(logoUrl) {
            if (logoUrl !== null && !/^http/.test(logoUrl)) {
              logoUrl = `https://cdn.razorpay.com${logoUrl.replace(
                  /\.([^\.]+$)/,
                      "_medium.$1"
                  )}`;
            }
            elements.application_logo.attr("src", logoUrl);
        }

        window.RazorpayAuthorize = {
            getUser: getUser,
            verifyToken: verifyToken,
            showError: showError,
            elements: elements,
            getAppLogoFullUrl: getAppLogoFullUrl,
            showLoader: showLoader
        };
    })();
    RazorpayAuthorize.showLoader();
    RazorpayAuthorize.getAppLogoFullUrl(`{{$data['application']['logo']}}`);
    RazorpayAuthorize.getUser();
</script>
</body>
</html>
