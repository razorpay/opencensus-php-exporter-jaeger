<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Razorpay - Authorize {{$data['application']['name']}}</title>
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
        button.btn-submit {
            background: #7268B7;
        }
        button.btn-default {
            border-color: #eee;
            border-width: 1px;
            color: #8497A0;
            margin-left: 14px;
        }
        .footer {
            margin-top: 28px;
            font-size: 10px;
        }
        .footer-content {
            display: inline-block;
        }
    </style>
    <script>
        function cors() {
          var req = new XMLHttpRequest();
          var currentUrl = window.location.href;
          var currentUrl = window.location.href;
          var token = '';

          if ('withCredentials' in req) {
            req.open('GET', '{{$data['dashboard_url']}}', true);
            req.withCredentials = true;
            req.onreadystatechange = function() {
              if (req.readyState === 4) {
                if (req.status >= 200 && req.status < 400) {
                  token = (JSON.parse(req.responseText)).data.token;
                  document.getElementById('token').value = token;
                  document.getElementById('token').onchange();
                } else {
                  currentUrl = encodeURIComponent(currentUrl);
                  window.location.href = 'http://dashboard.razorpay.dev/#/access/signin?next='+currentUrl;
                }
              }
            };

          req.send();
          }
        }
        window.onload = cors();

        function getData(token) {
          var xmlhttp = new XMLHttpRequest();

          xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == XMLHttpRequest.DONE ) {
              if (xmlhttp.status == 200) {
                console.log(xmlhttp.responseText);
                var data = JSON.parse(xmlhttp.responseText);
                if (data.success === false) {
                  alert('User data not found.');
                } else {
                  document.getElementById("user").innerHTML = data.data.user.id;
                  document.getElementById("merchant").innerHTML = data.data.user.merchant_id;
                  document.getElementById("agreement").style.display = "block";
                  document.getElementById("accept").style.display = "block";
                  document.getElementById("reject").style.display = "block";
                  document.getElementById("user_data").value = data.data.user;
                }
                document.getElementById("user").innerHTML = data.data.user.id;
                document.getElementById("merchant").innerHTML = data.data.user.merchant_id;
                document.getElementById("agreement").style.display = "block";
                document.getElementById("accept").style.display = "block";
                document.getElementById("reject").style.display = "block";
                document.getElementById("user_data").value = data.data.user;
              }
              else if (xmlhttp.status == 400) {
                alert('There was an error 400');
              }
              else {
                alert('something else other than 200 was returned');
              }
            }
          };

          xmlhttp.open("GET", "/"+token+"/token_data", true);
          xmlhttp.send();
        }

      function postAuthCode(input, user) {
          var xmlhttp = new XMLHttpRequest();

          var form_data = new FormData();

          for ( var key in input ) {
              form_data.append(key, input[key]);
          }
        user.authorize = true;
        form_data.append('user', JSON.stringify(user));

          xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == XMLHttpRequest.DONE ) {
              if (xmlhttp.status == 200) {
                var data = JSON.parse(xmlhttp.responseText);
                window.location.href = data;
              }
              else if (xmlhttp.status == 400) {
                alert('There was an error 400');
              }
              else {
                alert('something else other than 200 was returned');
              }
            }
          };

          xmlhttp.open("POST", "/authorize", true);
          xmlhttp.send(form_data);
        }

      function denyAuthCode(input, user) {
        var xmlhttp = new XMLHttpRequest();

        var form_data = new FormData();

        for ( var key in input ) {
          form_data.append(key, input[key]);
        }
        user.authorize = false;
        form_data.append('user', JSON.stringify(user));

          xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == XMLHttpRequest.DONE ) {
              if (xmlhttp.status == 200) {
                var data = JSON.parse(xmlhttp.responseText);
                window.location.href = data;
              }
              else if (xmlhttp.status == 400) {
                alert('There was an error 400');
              }
              else {
                alert('something else other than 200 was returned');
              }
            }
          };

          xmlhttp.open("POST", "/authorize", true);
          xmlhttp.send(form_data);
      }

     </script>
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
        <button class="btn-submit">Authorize</button>
        <button class="btn-default">Cancel</button>
    </div>

    <div class="footer">
        <div class="footer-content">Logged in as hari@nestaway.com. <a href="#">Not you?</a></div>
        <div class="footer-content bullet">•</div>
        <div class="footer-content"><a href="#">Manage Connected Apps on Dashboard.</a></div>
    </div>
</div>
</body>
</html>