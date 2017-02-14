<?php
$url = "https://www.bestlinknetware.com/Account/LogOn";
$cookie="cookie.txt";

$data = array(
  "UserName" => "ourValidUsername",
  "Password" => "ourValidPassword"
);

$postData = http_build_query($data);

$ch = curl_init();
curl_setopt ($ch, CURLOPT_URL, $url);
curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/4");
curl_setopt ($ch, CURLOPT_TIMEOUT, 10);
curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt ($ch, CURLOPT_REFERER, $url);

curl_setopt ($ch, CURLOPT_POST, 2);
curl_setopt ($ch, CURLOPT_POSTFIELDS, $postData);
$result = curl_exec ($ch);

if ($result === FALSE) {
    printf("cUrl error (#%d): %s<br>\n", curl_errno($ch),
           htmlspecialchars(curl_error($ch)));
}

rewind($verbose);
$verboseLog = stream_get_contents($verbose);

echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";


echo $result;
curl_close($ch);
?>
