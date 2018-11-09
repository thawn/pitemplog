<?php
$url='';
$user='';
$pw='';
$debug=false;
if ($_GET) {
  if (isset($_GET["debug"])) {
      $debug=true;
  }
  if ($debug) {
    echo '<h4>$_GET:</h4><pre>';
    print_r($_GET);
    echo '</pre>';
  }
  if (isset($_GET["url"])) {
      $url=$_GET["url"];
  } 
  if (isset($_GET["user"])) {
      $user=$_GET["user"];
  }
  if (isset($_GET["pw"])) {
      $pw=$_GET["pw"];
  }
  //use curl to fetch the data from url
  $process = curl_init($url);
  curl_setopt($process, CURLOPT_TIMEOUT, 60);
  curl_setopt($process, CURLOPT_RETURNTRANSFER,1);
  curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($process, CURLOPT_USERPWD, $user . ':' . $pw);
  if ($debug) {
    echo '<h4>CURL status:</h4>';
    $status_code = curl_getinfo($process, CURLINFO_HTTP_CODE);
    print_r($status_code);
  }
  $return = curl_exec($process);
  if ($debug) {
    echo '<h4>CURL returns:</h4><pre>'.$return.'</pre>';
  }
  curl_close($process);

  echo $return;
} else {
    echo "error no url specified.";
}
?>