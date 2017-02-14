<HTML>
<HEAD>
<TITLE>Crunchify - Refresh Div without Reloading Page</TITLE>

<style type="text/css">
body {
    background-image:
        url('http://cdn.crunchify.com/wp-content/uploads/2013/03/Crunchify.bg_.300.png');
}
</style>
<script type="text/javascript"
    src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
    <?php
    $var = "Test1";
     ?>
<script>
    $(document).ready(
            function() {
                    $('#show').text('.<?php echo $var; ?>.');
            });
</script>

</HEAD>
<BODY>
    <br>
    <br>
    <div id="show" align="center"></div>

    <div align="center">
        <p>
            by <a href="http://crunchify.com">Crunchify.com</a>
        </p>
    </div>
</BODY>
</HTML>
