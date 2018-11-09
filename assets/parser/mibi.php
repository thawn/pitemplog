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
  $model=array(
    'name' => '',
    'table' => '',
    'category' => 'B CUBE',
    'enabled' => 'true',
    'comment' => '',
    'tabletest' => '',
    'tableerrormsg' => '',
    'calibration' => '0'
  );
  //use curl to fetch the data from the mibi
  $process = curl_init($url);
  curl_setopt($process, CURLOPT_TIMEOUT, 5);
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
  $xml=simplexml_load_string($return);
  
  //for debugging purposes: read the xml from a local file
  //$file=dirname(dirname(dirname(getcwd()))).'/response.xml';
  //$xml=simplexml_load_file($file);
  $prefix=parse_url($url, PHP_URL_HOST);
  $prefix=preg_replace('/[\s\.]/','_', trim($prefix));
  if ($debug) {
    echo '<h4>Prefix:</h4>'.$prefix;
  }
  
  if (!isset($_GET["gettemp"])) {
    $conf=[];
    for ($i=1; $i<10; $i++) {
      $sensor='temp'.$i;
      if (!isset($xml->$sensor)) {break;}
      $fullsensor=$prefix.'^'.$sensor;
      //echo $sensor;
      $conf[$fullsensor]=$model;
      $conf[$fullsensor]['table']=$sensor;
    }
    if ($debug) {
      echo '<h4>$conf:</h4><pre>';
      print_r($conf);
      echo '</pre>';
    }
    $result='['.json_encode($conf,JSON_UNESCAPED_SLASHES).']';
  } else {
    if (isset($xml->$_GET["gettemp"])) {
      $result = $xml->$_GET["gettemp"];
    } else {
      $result = 'error: temperature for:'.$_GET["gettemp"].' not found in the xml data: <pre>'.$return.'</pre>';
    }
  }
  echo $result;
} else {
    echo "error no url specified.";
}
?>