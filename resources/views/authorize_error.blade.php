<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Razorpay - Authorize</title>
    <style>
        body {
            margin: 0;
            line-height: 1.4;
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

    <div class="main-content">
        <p><strong>{{$error['message']}}</strong></p>
    </div>
</div>
</body>
</html>