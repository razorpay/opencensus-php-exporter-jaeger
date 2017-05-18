<html>

<head>
  <script>
	function cors() {
	  var req = new XMLHttpRequest();
    var currentUrl = window.location.href;
    var token = '';
	  if ('withCredentials' in req) {
		req.open('GET', 'http://dashboard.razorpay.dev/user/logged_in', true);
		req.withCredentials = true;
		req.onreadystatechange = function() {
		  if (req.readyState === 4) {
		    if (req.status >= 200 && req.status < 400) {
		      token = (JSON.parse(req.responseText)).data.token;
		      document.getElementById('token').value = token;
		      document.getElementById('token').onchange();
		    } else {
          currentUrl = encodeURI(currentUrl);
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
          	var data = JSON.parse(xmlhttp.responseText);
            document.getElementById("user").innerHTML = data.data.user_id;
            document.getElementById("merchant").innerHTML = data.data.merchant_id;
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
    for ( var key in user ) {
      form_data.append(key, user[key]);
    }
    form_data.append('authorize', true);

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
    for ( var key in user ) {
      form_data.append(key, user[key]);
    }
    form_data.append('authorize', false);

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
  <input type="hidden" id="token" onchange="getData(this.value)"></input>
  <div>
  	<span id="agreement" style="display: none">You are giving permissions as the user <span id="user"></span> for merchant <span id="merchant"></span></span>
  </div>
  <div type="hidden" id="user_data"></div>
  <button id="accept" style="display: none" onclick="postAuthCode({{ json_encode($input) }}, document.getElementById('user_data').value)">
  	Accept
  </button>
  <button id="reject" style="display: none" onclick="denyAuthCode({{ json_encode($input) }}, document.getElementById('user_data').value)">
  	Reject
  </button>
</body>

</html>
