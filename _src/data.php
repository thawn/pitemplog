<?php
header('content-type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
$starttime=0;
$endtime=0;
$db="temperatures";
$table="temperatures";
$user="temp";
$pw="temp";
$aggregate="_5min";
if ($_GET) {
    //print_r($_GET);
    if (isset($_GET["end"])) {
        $endtime=intval($_GET["end"]);
    } 
    if (isset($_GET["start"])) {
        $starttime=intval($_GET["start"]);
    }
    if (isset($_GET["db"])) {
        $db=$_GET["db"];
    }
    if (isset($_GET["table"])) {
        $table=$_GET["table"];
    }
    if (isset($_GET["user"])) {
        $user=$_GET["user"];
    }
    if (isset($_GET["pw"])) {
        $pw=$_GET["pw"];
    }
    if (isset($_GET["aggregate"])) {
        switch ($_GET["aggregate"]) {
            case "_5min":
                $aggregate="_5min";
                break;
            case "_15min":
                $aggregate="_15min";
                break;
            case "_60min":
                $aggregate="_60min";
                break;
            case "_1min":
                $aggregate="";
                break;
        }
    }
    if (isset($_GET["config"]) && $_GET["config"]=="get") {
        $config_file="conf/config.json";
        if (file_exists($config_file)) {
            echo "[".file_get_contents($config_file)."]";
            exit();
        }
    }
    if (isset($_GET["gettemp"])) {
        //record the current temperature
        $sensorpath="/sys/bus/w1/devices/".$_GET["gettemp"]."/w1_slave";
        if (file_exists($sensorpath)) {
            $sensordata=(substr(trim(file_get_contents($sensorpath)),-5))/1000;
            echo $sensordata;
            exit();
        } else { //generate an empty config
            echo ("Error: Sensor not found: ".$sensorpath);
            exit();
        }
    }
}
if ($endtime==0) {
    $endtime=time();
}
if ($starttime==0) {
    $starttime=$endtime - (1*24*60*60);//by default we get one weeks worth of data
}
$table=$table.$aggregate;

try{
    $dbh = new PDO('mysql:host=localhost;dbname='.$db, $user, $pw, array(PDO::ATTR_PERSISTENT => true));
    
    $prefix = '';
    echo "[\n";

    foreach ($dbh->query('SELECT FROM_UNIXTIME(time),temp FROM '.$table.' WHERE time BETWEEN ' . $starttime . ' AND '.$endtime . ' ORDER BY time ASC') as $row) { // Fetch the data
    echo $prefix . " {\n";
    //print_r($row);
    echo '  "time": "' . $row['FROM_UNIXTIME(time)'] . '",' . "\n";
    echo '  "temp": ' . $row['temp'] . ',' . "\n";
    echo " }";
    $prefix = ",\n";
    }
    echo "\n]";
    // Close the connection
    $dbh = null;
} catch (PDOException $e) {
   print "Error!: " . $e->getMessage() . "<br/>";
   die();
}
?>