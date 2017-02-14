<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta content="width=device-width,initial-scale=1.0,user-scalable=no,minimum-scale=1.0,maximum-scale=1.0" id="viewport" name="viewport">
  <link rel="stylesheet" type="text/css" href="/quant/css/bootstrap.css">
  <link rel="stylesheet" type="text/css" href="/quant/css/wynd.css">
  <script src="/quant/js/jquery-1.11.3.min.js"></script>
  <script src="/quant/js/migrate.js"></script>
  <script src="/quant/js/bootstrap.min.js"></script>
  <script src="/quant/js/main.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
  <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
  <link rel="shortcut icon" href="img/favicon.ico">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<title>AJAX Testing</title>
</head>
<body>
<div class="row">
          <nav role="navigation" class="nav">

            <a class="nav_link" href="table.php">
              <div class="nav_line"></div>Table Opener</a>

            <a class="nav_link" href="services.html">
              <div class="nav_line"></div>KONTAKTAI</a>

          </nav>
        </div>


        <script>
    $(document).ready(function(){
      //page
      $('.row').on("click",".nav_link",function(e){
        e.preventDefault(); // cancel click

        var page = $(this).attr('href');
        $('.row').load(page);
      });

      $('.nav_link').click(function(){
        var page = $(this).attr('href');
        $('.row').load(page);
        return false;
      });
    });
  </script>
