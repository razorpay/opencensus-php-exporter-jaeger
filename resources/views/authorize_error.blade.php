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
        .app-logo + .app-logo {
            margin-left: 8px;
        }
        .body-section {
            font-size: 12px;
            line-height: 1.8;
        }
        p {
            margin: 14px 0 0 0;
        }
        .main-content {
            padding-top: 45px;
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