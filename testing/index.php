<html>
<head>
<script>
function showUser(str) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                document.getElementById("txtHint").innerHTML = xmlhttp.responseText;
            }
        };
        xmlhttp.open("GET","getuser.php?q="+str,true);
        xmlhttp.send();
    }
</script>
</head>
<body>

<form>
<select name="users" onchange="showUser(this.value)">
  <option value="">Time Period:</option>
  <option value="1">Yesterday</option>
  <option value="7">Week</option>
  <option value="30">Month</option>
  </select>
</form>
<br>
<div id="txtHint"><b>Person info will be listed here...</b></div>

</body>
</html>
