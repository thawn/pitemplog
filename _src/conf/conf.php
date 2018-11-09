---
layout: default
title: Database and Sensor Configuration
---
    <!-- Carousel
    ================================================== -->
    <div id="myCarousel" class="carousel" data-ride="carousel">
      <div class="carousel-inner" role="listbox">
        <div class="item active">
          
          <div class="container">
            <div class="carousel-caption">
              <img class="pull-left" src="{{ site.baseurl }}/assets/img/config_icon1.png" alt="Config">
              <h1>Database and Sensor Configuration</h1>
              <p>All available sensors and their IDs should be listed here. Just add the database and Sensor information and the data will show up in the graphs.</p>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /.carousel -->


    <!-- Wrap the rest of the page in another container to center all the content. -->
    <div class="container">
<?php
// define variables and set to empty values
$errors['extURLError'] = $errors['extURLNameError'] = '';
$model=array(
  'name' => '',
  'table' => '',
  'category' => 'MPI',
  'enabled' => 'true',
  'comment' => '',
  'tabletest' => '',
  'tableerrormsg' => '',
  'calibration' => '0'
);
$dbModel = array(
  'db' => 'temperatures',
  'user' => 'temp',
  'pw' => 'temp',
  'aggregateTables' => ['_5min', '_15min', '_60min'],
  'dbtest' => '',
  'dberrormsg' => ''
);
$messages='';
$input = $actions = $errors = $commands = [];
$errors['nameErr'] = $errors['tableErr'] = $errors['categoryErr'] = $errors['calibrationErr'] = [];
$errors['dbErr'] = $errors['userErr'] = $errors['extURLError'] = $errors['extURLNameError'] = '';
$debug=false;
if (isset($_GET['debug'])) {$debug = true;}
$parserdir=dirname(getcwd()).'/assets/parser/';
$parsers=getExternalParsers($parserdir);

function abortWithError($message) {
  global $conf;
  echo ('<div class="well"><span class="text-danger"><h4>'.$message.'</h4>');
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo '<h4>Error ocurred while parsing $_POST:</h4><pre>';
    print_r($_POST);
    echo '</pre>';
  }
  if (isset($conf)) {
    echo '<h4>Error ocurred while parsing $conf:</h4><pre>';
    print_r($conf);
    echo '</pre>';    
  }
  echo '</span></div>';
  die;
}

//handle form input
function test_input($data) {
   $data = trim($data);
   $data = stripslashes($data);
   $data = htmlspecialchars($data);
   return $data;
}

function parseDatabaseInput($field, $value) {
  global $actions, $errors;
  $parsed=[];
  switch ($field) {
    case 'db':
      if (empty($value)) {
        $errors['dbErr'] = 'Database name is required.';
      } else {
        $parsed['db'] = test_input($value);
        // check if name only contains letters
        if (!preg_match("/^[a-zA-Z_]{4,20}$/",$parsed['db'])) {
          $errors['dbErr'] = 'Only letters and underscore allowed. The database name must be 4-20 characters long.'; 
        }
      }
      break;
    case 'user':
      if (empty($value)) {
        $errors['userErr'] = 'Username for database is required';
      } else {
        $parsed['user'] = test_input($value);
        // check if name only contains letters
        if (!preg_match("/^[a-zA-Z]{4,20}$/",$parsed['user'])) {
          $errors['userErr'] = 'Only letters allowed. The user name must be 4-20 characters long.'; 
        }
      }
      break;
    case 'pw':
      if (empty($value)) {
        $parsed['pw'] = '';
      } else {
        $parsed['pw'] = test_input($value);
      }
      break;
    case 'aggregateTables':
      if (!is_array($value)) {
        $value=explode('/',$value);
      }
      foreach ($value as $suffix) {
        $tested=test_input($suffix);
        if (!preg_match("/^[a-zA-Z0-9_]{1,10}$/",$tested)) {
          $errors['dbErr'] = 'Only letters, numbers and underscore allowed. The database aggregateTable suffix must be 1-10 characters long. The offending input was: '.$suffix; 
        } else {
          $parsed['aggregateTables'][]=$tested;
        }
      }
      break;
    case 'dbtest':
      $parsed['dbtest']=$value;
      break;
    case 'dberrormsg':
      $parsed['dberrormsg']=$value;
      break;
    default:
      abortWithError('Error: unknown input field: "'.$field.'" for the database!');
  }
  return $parsed;
}

function parseSensorInput($sensor, $field, $value) {
  global $actions, $errors, $parsers, $parserdir;
  $parsed=[];
  switch ($field) {
    case 'sensor':
      if ($value!=$sensor) {
        if ($value!=substr($sensor, 3)) {
          abortWithError('Error: sensor prefix and value do not match. Something is wrong:<pre>'.(print_r([$sensor => $value], true)).'</pre>');
        }
      }
      break;
    case 'calibration':
      if (empty($value)) {
        $parsed['calibration'] = '0';
      } else {
        $parsed['calibration'] = test_input($value);
      }
      if (!is_numeric($parsed['calibration'])) {
        $errors['calibrationErr'][$sensor]='The calibration must be numeric';
      }
      break;
    case 'name':
      if (empty($value)) {
        $errors['nameErr'][$sensor] = 'Name is required';
      } else {
        $parsed['name'] = test_input($value);
        // check if name only contains letters and whitespace
        if (!preg_match("/^[a-zA-Z0-9_\- ]*$/",$parsed['name'])) {
          $errors['nameErr'][$sensor] = 'Only letters, numbers and space allowed'; 
        }
      }
      break;
    case 'table':
      if (empty($value)) {
        $errors['tableErr'][$sensor] = 'Database table name is required.';
      } else {
        $parsed['table'] = test_input($value);
        // check if name only contains letters
        if (!preg_match("/^[a-zA-Z0-9_]{4,20}$/",$parsed['table'])) {
          $errors['tableErr'][$sensor] = 'Only letters, numbers and underscore allowed. The database table name must be 4-20 characters long.'; 
        } elseif (preg_match("/^\d.*/",$parsed['table'])) {
          $errors['tableErr'][$sensor] = 'The table name may not start with a number.';
        }
      }
      break;
    case 'comment':
      if (empty($value)) {
        $parsed['comment'] = '';
      } else {
        $parsed['comment'] = test_input($value);
      }
      break;
    case 'category':
      if (empty($value)) {
        $errors['categoryErr'][$sensor] = 'Category is required.';
      } else {
        $parsed['category'] = test_input($value);
        // check if name only contains letters and whitespace
        if (!preg_match("/^[a-zA-Z0-9_\- ]*$/",$parsed['category'])) {
          $errors['categoryErr'][$sensor] = 'Only letters, numbers and space allowed'; 
        }
      }
      break;
    case 'action': //the user wants to create a database table
        switch (test_input($value)) {
          case 'none':
            break;
          case 'delete':
            $actions[$sensor]='delete';
            break;
          case 'create':
            $actions[$sensor]='create';
            break;
          case 'overwrite':
            $actions[$sensor]='overwrite';
            break;
          case 'disable':
            $parsed['enabled']='false';
            break;
          case 'enable':
            $parsed['enabled']='true';
            break;
        }
      break;
    case 'enabled':
      $testedVal=test_input($value);
      if ($testedVal=='false') {
        $parsed['enabled']='false';
      } else {
        $parsed['enabled']='true';
      }
      break;
    case 'exttable':
      $parsed['exttable'] = test_input($value);
      // check if name only contains letters
      if (empty($parsed['exttable'])) {
        unset($parsed['exttable']);
      } elseif (!preg_match("/^[a-zA-Z0-9_]{4,20}$/",$parsed['exttable'])) {
        abortWithError('There is an error in the external configuration. The external table name should only contain letters and numbers:<pre>'.(print_r($parsed,true)).'</pre>');
      } elseif (preg_match("/^\d.*/",$parsed['exttable'])) {
        abortWithError('There is an error in the external configuration. The external table name may not start with a number:<pre>'.(print_r($parsed,true)).'</pre>');
      }
      break;
    case 'url':
      if (empty($value)) {
        $errors['extURLError'] = 'URL is required.';
      } else {
        $parsed['url'] = test_input($value);
        // check if name only contains letters
        if (!filter_var($parsed['url'], FILTER_VALIDATE_URL)) {
          $errors['extURLError'] = 'Only letters, numbers and space allowed.';
        }
      }
      break;
    case 'urlname':
      if (empty($value)) {
        $errors['extURLNameError'] = 'URL Name is required.';
      } else {
        $parsed['urlname'] = test_input($value);
        // check if name only contains letters
        if (!preg_match("/^[a-zA-Z0-9_\- ]*$/",$parsed['urlname'])) {
          $errors['extURLNameError'] = 'Error: The URL Name may onl contain letters, numbers and spaces.';
        }
      }
      break;
    case 'urlusername':
      $parsed['urlusername']=test_input($value);
      break;
    case 'urlpw':
      $parsed['urlpw']=test_input($value);
      break;
    case 'urlparser':
      $parsed['urlparser']=test_input($value);
      if (!empty($parsed['urlparser']) && $parsed['urlparser']!='none' && array_search($parsed['urlparser'], $parsers)===false) {
        abortWithError('There is an error in your configuration: The file for the parser: '.$parsed['urlparser'].' does not exist in: '.$parserdir.'. These are the parsers that I found: <pre>'.(print_r($parsers,true)).'</pre>');
      }
      break;
    case 'tabletest':
      $parsed['tabletest']=$value;
      break;
    case 'tableerrormsg':
      $parsed['tableerrormsg']=$value;
      break;
    case 'firsttime':
      if (empty($value)) {
        $parsed['firsttime'] = '0';
      } else {
        $parsed['firsttime'] = test_input($value);
      }
      if (!is_numeric($parsed['firsttime'])) {
        abortWithError('There is an error in your config file: Firsttime must be numeric! Offending value for sensor: "'.$sensor.'" was: "'.$value.'".');
      }
      break;
    default:
      abortWithError('Error: unknown input field: "'.$field.'" for sensor: "'.$sensor.'!');
  }
  return $parsed;
}

//delete a sensor from the configuration
function deleteSensor($sensor, $conf) {
  global $sensors;
  unset($conf[$sensor]);
  unset($sensors[$sensor]);
  return $conf;
}

//create the necessary tables for a sensor
function makeDbTable($sensor, $dbh) {
  global $conf;
  $commands=[];
  $result = $dbh->query('DROP TABLE IF EXISTS ' . $conf[$sensor]['table'] . '; CREATE TABLE ' . $conf[$sensor]['table'] . ' (time INT, temp FLOAT);');
  if ($result) {
    $conf[$sensor]['tabletest']='OK';
    $conf[$sensor]['tableerrormsg']='';
    $partition_database = escapeshellcmd('/usr/local/bin/partition_database.py');
    $partition_cmd = $partition_database . ' "" ' . $conf[$sensor]['table'];
    $commands[] = $partition_cmd;
    foreach($conf['database']['aggregateTables'] as $extension) {
      $result = null;
      $table_name = $conf[$sensor]['table'] . $extension;
      $result = $dbh->query('DROP TABLE IF EXISTS ' . $table_name . '; CREATE TABLE ' . $table_name . ' (time INT, temp FLOAT);');
      $partition_cmd = $partition_database . ' ' . $extension . ' ' . $conf[$sensor]['table'];
      $commands[] = $partition_cmd;
    }
  } else {
    $conf[$sensor]['tabletest']='NotOK';
    $conf[$sensor]['tableerrormsg']=$dbh->errorInfo()[2];
  }
  return $commands;

}

function createSensorSettingsRow($sensor, $errors, $config, $temperature, $buttonstring, $actionSelector, $islocal) {
  return '
              <tr>
                  <td>
                    <input class="form-control" type="text" value="' . $temperature . '" disabled>
                    <input type="hidden" class="sensorID" name="' . $sensor . '/sensor" value="' . ( $islocal ? $sensor : substr($sensor, 3) ) . '">'. ( $islocal? '' : '
                    <input type="hidden" name="' . $sensor . '/url" value="' . $config['url'] . '">
                    <input type="hidden" name="' . $sensor . '/urlname" value="' . $config['urlname'] . '">
                    <input type="hidden" name="' . $sensor . '/urlparser" value="' . $config['urlparser'] . '">
                    <input type="hidden" name="' . $sensor . '/urlusername" value="' . $config['urlusername'] . '">
                    <input type="hidden" name="' . $sensor . '/urlpw" value="' . $config['urlpw'] . '">
                    <input type="hidden" name="' . $sensor . '/exttable" value="' . (array_key_exists('exttable',$config) ? $config['exttable'] : '' ) . '">' ) . '
                  </td>
                  <td>
                    <div class="form-group' . (array_key_exists($sensor,$errors['calibrationErr']) ? ' has-error' : '') . '">
                      <input type="text" class="form-control" name="' . $sensor . '/calibration" value="' . $config['calibration'] . '"' . ($config['enabled']=='false' ? ' readonly' : '') . '>
                      ' . (array_key_exists($sensor,$errors['calibrationErr']) ? '<span class="text-danger">'.$errors['calibrationErr'][$sensor].'</span>' : "") . '
                    </div>
                  </td>
                  <td>
                    <div class="form-group' . (array_key_exists($sensor,$errors['nameErr']) ? ' has-error' : '') . '">
                      <input type="text" class="form-control" name="' . $sensor . '/name" placeholder="Enter Sensor Name" value="' . $config["name"] . '"' . ($config["enabled"]=="false" ? " readonly" : "") . '>
                      ' . (array_key_exists($sensor,$errors['nameErr']) ? '<span class="text-danger">'.$errors['nameErr'][$sensor].'</span>' : '') . '
                    </div>
                  </td>
                  <td>
                    <div class="form-group' . (array_key_exists($sensor,$errors['tableErr']) ? ' has-error' : '') . '">
                      <input type="text" class="form-control" name="' . $sensor . '/table" placeholder="Enter Database Table Name" value="' . $config["table"] . '"' . ($config["enabled"]=="false" ? " readonly" : "") . '>
                      <span class="text-danger">' . (array_key_exists($sensor,$errors['tableErr']) ? '<span class="text-danger">'.$errors['tableErr'][$sensor].'</span>' : '') . '
                    </div>
                  </td>
                  <td>
                    <div class="form-group' . (array_key_exists($sensor,$errors['categoryErr']) ? ' has-error' : '') . '">
                      <input type="text" class="form-control" name="' . $sensor . '/category" placeholder="Enter Category" value="' . $config["category"] . '"' . ($config["enabled"]=="false" ? " readonly" : "") . '>
                      <span class="text-danger">' . (array_key_exists($sensor,$errors['categoryErr']) ? '<span class="text-danger">'.$errors['categoryErr'][$sensor].'</span>' : "") . '
                    </div>
                  </td>
                  <td>
                    <div class="form-group">
                      <input type="text" class="form-control" name="' . $sensor . '/comment" placeholder="Enter Comment" value="' . $config['comment'] . '">
                    </div>
                  </td>
                  <td>
                    '. $buttonstring . '
                  </td>
                  <td>
                    <div class="form-group">
                      '. $actionSelector . '
                    </div>
                  </td>
              </tr>';
}

function createActionSelector($sensor, $selectorActions, $selected = 0, $selectorClass = [],$id='', $actionName='action' ) {
  $result = '
                <select '.(!empty($id) ? 'id='.$id.' ' : '').'class="'.(isset($selectorClass[$selected]) ? $selectorClass[$selected].' ' : '').'form-control form-control-inline" name="' . $sensor . '/'.$actionName.'" onchange="this.className=this.options[this.selectedIndex].className">';
  $option=1;
  foreach($selectorActions as $action => $text) {
    $result .= '
                  <option value="'.$action.'" class="'.(isset($selectorClass[$option]) ? $selectorClass[$option].' ' : '').'form-control form-control-inline"'.($selected==$option ? ' selected' : '' ).'>'.$text.'</option>';
    $option+=1;
  }
  $result .= '
                </select>';
  return $result;
}

function createButtonString($buttonType, $buttonClass, $buttonTitle) {
  switch ($buttonClass) {
    case 'danger':
      $glyphicon = 'warning-sign';
      break;
    case 'warning':
      $glyphicon = 'question-sign';
      break;
    default:
      $glyphicon = 'ok';
      break;
  }
  return '<button type="'.$buttonType.'" class="btn btn-'.$buttonClass.'" data-toggle="tooltip" data-placement="top" title="'. $buttonTitle.'"><span class="glyphicon glyphicon-'.$glyphicon.'"></span></button>';
}


function getExternalParsers($parserdir) {
  $parsers=[];
  if (file_exists($parserdir)) {
    $files=scandir($parserdir);
    foreach ($files as $key => $file) {
      if (strpos($file, '.php')===false) {
        unset($files[$key]);
      }
    }
    $parsers=array_values($files);
  }
  return $parsers;
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if ($debug) {
    echo '<h4>Raw POST Data:</h4><pre>';
    print_r($_POST);
    echo '</pre>';
  }
  foreach($_POST as $key => $value) {
    //separate the sensor id from the variable name
    $splitkey=explode('/',$key);
    if ($splitkey[0]=='database') {
      if (!isset($input[$splitkey[0]])) {
        $input[$splitkey[0]]=$dbModel;
      }
      $input[$splitkey[0]]=array_merge($input[$splitkey[0]], parseDatabaseInput($splitkey[1],$value));
    } elseif ($splitkey[0]=='new_external') {
    } else {
      if (!isset($input[$splitkey[0]])) {
        $input[$splitkey[0]]=$model;
      }
      $input[$splitkey[0]]=array_merge($input[$splitkey[0]], parseSensorInput($splitkey[0],$splitkey[1],$value));
    }
    
  }
}
if ($debug) {
  echo '<h4>Parsed Input:</h4><pre>';
  print_r($input);
  echo '</pre>';
}
//look for available temperature sensors
$sensordir='/sys/bus/w1/devices/';
$haveLocalSensors=true;
if (file_exists($sensordir)) {
  $dirs=scandir($sensordir);
  foreach ($dirs as $key => $dir) {
    if ($dir == '.' || $dir == '..' || $dir == 'w1_bus_master1') {
      unset($dirs[$key]);
    }
  }
  $sensors=array_values($dirs);
} else {
  $sensors=[];
  $haveLocalSensors=false;
}

//load existing config file
$config_file='config.json';
if (file_exists($config_file)) {
  $conf=json_decode(file_get_contents($config_file),true);
} else { //generate an empty config
  $conf=array();
}
if ($debug) {
  echo '<h4>Raw configuration from the config file:</h4><pre>';
  print_r($conf);
  echo '</pre>';
}
if (empty($conf) || !array_key_exists('database', $conf)) {//there was no database info saved. we need to initialize the config for it
  $conf['database']=$dbModel;
}

//check whether the config is sane
foreach($conf as $sensor => $config) {
  if ($sensor=='database') {
    if (!isset($parsedConf[$sensor])) {
      $parsedConf[$sensor]=$dbModel;
    }
    foreach($config as $field => $value){
      $parsedConf[$sensor]=array_merge($parsedConf[$sensor], parseDatabaseInput($field,$value));
    }
  } elseif ($sensor=='new_external') {
  } else {
    if (!isset($parsedConf[$sensor])) {
      $parsedConf[$sensor]=$model;
    }
    foreach($config as $field => $value){
      $parsedConf[$sensor]=array_merge($parsedConf[$sensor], parseSensorInput($sensor,$field,$value));
    }
    if (!array_key_exists('url', $parsedConf[$sensor])) { //this is a local sensor
      //check whether that sensor is attached
      $sensorpath=$sensordir.$sensor.'/w1_slave';
      if (!file_exists($sensorpath)) { //clear that sensor from the configuration
        $parsedConf = deleteSensor($sensor, $parsedConf);
      }
    }
  }
}
if ($debug) {
  echo '<h4>Parsed configuration from the config file:</h4><pre>';
  print_r($parsedConf);
  echo '</pre>';
}

//update the config with the input
$conf=array_merge($parsedConf, $input);
if ($debug) {
  echo '<h4>Combined configuration from the config file and input:</h4><pre>';
  print_r($conf);
  echo '</pre>';
}

if ($debug) {
  echo '<h4>Local sensors found in: "'.$sensordir.'</h4><pre>';
  print_r($sensors);
  echo '</pre>';
}
//update the sensors array with the sensors from the config
$sensors=array_unique(array_merge($sensors, array_keys($conf)));
// delete the database entry from the sensors array
$dbkey=-1;
$dbkey=array_search('database', $sensors);
if ($dbkey>-1) {unset($sensors[$dbkey]);}

if ($debug) {
  echo '<h4>Combined sensors found in: "'.$sensordir.' and the configuration</h4><pre>';
  print_r($sensors);
  echo '</pre>';
}
//test the database connection
$success=true;
if (!empty($conf['database']['db']) && !empty($conf['database']['user'])) {
  try{
    $dbh = new PDO('mysql:host=localhost;dbname=' . $conf['database']['db'], $conf['database']['user'], $conf['database']['pw'], array(PDO::ATTR_PERSISTENT => true));
    // Close the connection
    //$dbh = null;
  } catch (PDOException $e) {
    $conf['database']['dbtest']='NotOK';
    $conf['database']['dberrormsg']=$e->getMessage();
    $success=false;
  }
  if ($success) {
    $conf['database']['dbtest']='OK';
    $conf['database']['dberrormsg']='';
  }
}

//check for each sensor if we have a config entry for it
$dbTableCount=array();
foreach ($sensors as $sensor) {
  if (isset($actions[$sensor]) && $actions[$sensor]=='delete') {//need to add some code that deletes the corresponding html pages from the site
    $conf = deleteSensor($sensor, $conf);
    continue;
  }
  if (!array_key_exists($sensor, $conf)) {//sensor is new. we need to initialize the config for it
    $conf[$sensor]=$model;
    $sensorNo = array_search($sensor, array_keys($conf));
    $conf[$sensor]['name'] = $sensor;
    $conf[$sensor]['table'] = 'temp' . $sensorNo;
    $conf[$sensor]['comment'] = 'This sensor has the hardware id: "' . $sensor . '".';
  }
  if ($conf['database']['dbtest']=='OK' && !empty($conf[$sensor]['table'])) {
    //test whether the database tables exist and contain the correct fields
    $timeColumnExists=false;
    $tempColumnExists=false;
    try{
      //$dbh = new PDO('mysql:host=localhost;dbname=' . $conf['database']['db'], $conf['database']['user'], $conf['database']['pw'], array(PDO::ATTR_PERSISTENT => true));
      $result = $dbh->query('SHOW COLUMNS FROM '.$conf[$sensor]['table']);
      if ($result) {
        foreach($result as $row){
          if ($row['Field']=='time') {
            $timeColumnExists=true;
          } elseif ($row['Field']=='temp') {
            $tempColumnExists=true;
          }
        }
        if ($timeColumnExists && $tempColumnExists){
          $conf[$sensor]['tabletest']='OK';
          $conf[$sensor]['tableerrormsg']='';
        } else {
          $conf[$sensor]['tabletest']='NotOK';
          $conf[$sensor]['tableerrormsg']='table columns time and temp not found';
          if ($actions[$sensor]=='overwrite') {
            $commands = array_merge($commands, makeDbTable($sensor, $dbh));
          }
        }
      } else {
        $conf[$sensor]['tabletest']='NotOK';
        $conf[$sensor]['tableerrormsg']=$dbh->errorInfo()[2];
        if ($actions[$sensor]=='create' || $actions[$sensor]=='overwrite') {
          $commands = array_merge($commands, makeDbTable($sensor, $dbh));
        }
      }
      // Close the connection
      $result = null;
    } catch (PDOException $e) {
      $conf[$sensor]['tabletest']='NotOK';
      $conf[$sensor]['tableerrormsg']=$e->getMessage();
      continue;
    }
  }
  $dbTableCount[$sensor]=$conf[$sensor]['table'];
  $count=array_count_values($dbTableCount);
  if ($count[$conf[$sensor]['table']]>1 && !empty($conf[$sensor]['table'])) {
    $duplicates=array_keys($dbTableCount, $dbTableCount[$sensor]);
    foreach($duplicates as $duplicate) {
      $errors['tableErr'][$duplicate]='Each sensor must use a different table!';
      $conf[$duplicate]['tabletest']='NotOK';
      $conf[$duplicate]['tableerrormsg']='Table already in use by another sensor';
    }
  }
}

$buttonstring='';
$save=array();
//generate the sensor edit form
$localSensors='';
$externalSensors=array();

if ($debug) {
  echo '<h4>Configuration directly before sensor code is generated:</h4><pre>';
  print_r($conf);
  echo '</pre>';
}

//create a CURL handler for the connection to an external sensor box
$urls=array();
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, 0);  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//generate the page code for local and external sensors
foreach ($conf as $sensor => $config) {
  if ($sensor=='database') {
    if (!empty($config) && $config['dbtest']=='OK') { //if the database config works, save it to the config file
      $save['database']=$config;
    }
  } else {
    if ($config['enabled']=='false') {//clear all errors for disabled
      $disabled = true;
      if (!empty($errors['nameErr'])) {
        unset($errors['nameErr'][$sensor]);
      }
      if (!empty($errors['tableErr'])) {
        unset($errors['tableErr'][$sensor]);
      }
      if (!empty($errors['calibrationErr'])) {
        unset($errors['calibrationErr'][$sensor]);
      }
      if (!empty($errors['categoryErr'])) {
        unset($errors['categoryErr'][$sensor]);
      }
    } else {
      $disabled = false;
    }
    if ((empty($errors['nameErr']) || empty($errors['nameErr'][$sensor]))
        && (empty($errors['tableErr']) || empty($errors['tableErr'][$sensor]))
        && (empty($errors['calibrationErr']) || empty($errors['calibrationErr'][$sensor]))
        && (empty($errors['categoryErr']) || empty($errors['categoryErr'][$sensor]))) {
      $hasErrors=false;
    } else {
      $hasErrors=true;
    }
    if (!empty($config) && ($config['tabletest']=='OK' || $config['enabled']=='false') && !$hasErrors) { //if there are no errors in this entry, save it to the config file
      $save[$sensor]=$config;
      if (empty($save[$sensor]['firsttime'])) {
        $result = $dbh->query('SELECT * FROM ' . $config['table'] . ' ORDER BY time ASC LIMIT 1;');
        if ($result) {
          foreach($result as $row){
            $save[$sensor]['firsttime'] = $row['time'];
          }
          if ($result->rowCount()==0) {
            $save[$sensor]['firsttime'] = time();
          }
        }
        $result=null;
      }
    }

    $islocal=(!array_key_exists('url', $config) && $haveLocalSensors);
    //create the status button and the action selector
    $selectorAction=[];
    $buttonType='submit';
    $buttonTitle='';
    $selected=0; //0 means no option is marked as selected (default) 1 means the first option, 2 means the second option etc...
    if ($config['enabled']=='false') { //if the sensor is disabled, 
      $buttonClass='default active';
      $buttonTitle='This sensor is disabled. No data is being recorded. Enable it to restart recording.';
      $selectorAction['none']='none';
      $selectorAction['enable']='enable sensor';
      $selectorClass[2]='bg-success';
    } elseif ($config['tabletest']=='OK' && !$hasErrors) { //check for errors and adapt the buttons accordingly
      $buttonClass = 'success';
      $buttonTitle='All is well.';
      $selectorAction['none'] = 'none';
      $islocal ? $selectorAction['disable'] = 'disable sensor' : $selectorAction['delete'] = 'delete sensor';
      $selectorClass[2]= 'bg-danger';
    } elseif ($config['tabletest']=='NotOK') {
      if (preg_match("/Table.*?doesn't exist/", $config['tableerrormsg'])) {
        $tip = 'Click this button to attempt to fix this.';
        $selectorAction['none'] = 'try again';
        $selectorAction['create'] = 'create table';
        $selectorClass[2]= 'bg-success';
        $selected=2;
        $islocal ? $selectorAction['disable'] = 'disable sensor' : $selectorAction['delete'] = 'delete sensor';
        $selectorClass[3]= 'bg-danger';
      } elseif (preg_match("/Table already in use/", $config['tableerrormsg'])) {
        $tip = 'Change the database table name and try again.';
        $selectorAction['none'] = 'try again';
      } else {
        $tip = 'Click this button to attempt to fix this.';
        $selectorAction['none'] = 'try again';
        $selectorAction['overwrite'] = 'overwrite table';
        $selectorClass[2]= 'bg-danger';
        $selected=2;
        if ($islocal) {
          $selectorAction['disable'] = 'disable sensor';
          $selectorClass[3]= 'bg-warning';
        } else {
          $selectorAction['delete'] = 'delete sensor';
          $selectorClass[3]= 'bg-danger';
        }
      }
      $buttonClass='danger';
      $buttonTitle=$config["tableerrormsg"] . '. '.$tip;
    } elseif ($hasErrors) {
      $selectorAction['none'] = 'try again';
      $selectorAction['create'] = 'create table';
      $selectorClass[2]= 'bg-success';
      if ($islocal) {
        $selectorAction['disable'] = 'disable sensor';
        $selectorClass[3]= 'bg-warning';
      } else {
        $selectorAction['delete'] = 'delete sensor';
        $selectorClass[3]= 'bg-danger';
      }
      $buttonClass='danger';
      $buttonTitle='There is an error in your Configuration. Please check the red error messages for more info.';
    } else {
      $buttonClass='warning';
      $buttonTitle='Enter a table name to test whether the table is o.k.';
        $selectorAction['create'] = 'create table';
        $selectorClass[1]= 'bg-success';
        $selected=1;
        $islocal ? $selectorAction['disable'] = 'disable sensor' : $selectorAction['delete'] = 'delete sensor';
        $selectorClass[2]= 'bg-danger';
    }
    $buttonstring=createButtonString($buttonType, $buttonClass, $buttonTitle);
    $actionSelector=createActionSelector($sensor, $selectorAction, $selected, $selectorClass);
    if ($islocal) { //this is a local sensor
      //record the current temperature
      $sensorpath='/sys/bus/w1/devices/'.$sensor.'/w1_slave';
      if (file_exists($sensorpath)) {
        $sensordata=(substr(trim(file_get_contents($sensorpath)),-5))/1000;
        $sensordata+=$config['calibration'];
        $temperature=$sensordata.'&deg;C';
      } else { //clear that sensor from the configuration
        unset($conf[$sensor]);
        $temperature='Error: reading temperature failed for sensor: '.$sensor;
      }
      $localSensors .= createSensorSettingsRow($sensor, $errors, $config, $temperature, $buttonstring, $actionSelector, $islocal);
    } elseif (array_key_exists('url', $config) && empty($errors['extURLError']) && empty($errors['extURLNameError'])) { // the sensor is not local but external
      $urls[]=$config['url']; //record the url for distinguishing the sources later
      if (!array_key_exists($config['url'], $externalSensors)) {
        $externalSensors[$config['url']]['text'] = '';
      }
      $externalSensors[$config['url']]['urlname']=$config['urlname'];
      if (array_key_exists('urlparser',$config) && !empty($config['urlparser']) && $config['urlparser']!='none' ) {
        $tempurl=('http://'.$_SERVER['SERVER_NAME'].'/assets/parser/'.$config['urlparser'].'?url='.(urlencode($config['url'])).'&user='.(urlencode($config['urlusername'])).'&pw='.(urlencode($config['urlpw'])).'&gettemp='.(urlencode($config['exttable'])));
      } else {
        $tempurl=explode('?', $config['url']);
        $tempurl=($tempurl[0].'?gettemp='.urlencode(substr($sensor, 3)));
      }
      if ($debug) {
        echo '<h4>URL for getting the temperature:</h4>'.$tempurl;
      }
      curl_setopt($ch, CURLOPT_URL, $tempurl);
      $temperature=curl_exec($ch);
      if (is_numeric($temperature)) {
        $externalSensors[$config['url']]['buttonstring']=createButtonString('button', 'success', 'URL is sending correct data.');
      } else {
        $externalSensors[$config['url']]['buttonstring']=createButtonString('button', 'danger', 'Error: could not reach external server: '.$tempurl);
      }
      $temperature+=$config['calibration'];
      $temperature.='&deg;C';
      $externalSensors[$config['url']]['text'] .= createSensorSettingsRow($sensor, $errors, $config, $temperature, $buttonstring, $actionSelector, $islocal);
    }
  }
}
//close the connection handler for external sensors
curl_close($ch);

if ($debug) {
  echo '<h4>Data to be saved:</h4><pre>';
  print_r($save);
  echo '</pre>';
}

if (!empty($save)) { //if there is data to save, do so now
  if (is_writable($config_file)) {
    file_put_contents($config_file, json_encode($save,JSON_UNESCAPED_SLASHES), LOCK_EX);
    $local_conf = escapeshellarg($config_file);
    $build_conf = escapeshellarg('/usr/local/share/templog/_data/config.json');
    //$build_conf = escapeshellarg('/Users/korten/Apps/temperatures/build/_data/config.json');
    $diff = escapeshellcmd('/usr/bin/diff -q '.$build_conf.' '.$local_conf);
    $diff_output = shell_exec($diff);
    if (!empty($diff_output)) {
      $messages .= '<div class="well"><span class="text-success">Configuration saved successfully.</span></div>';
      $messages .= '<div class="well"><span class="text-info">Configuration changed: </span><pre>'.$diff_output.'</pre>';
      $create_pages = escapeshellcmd('/usr/local/share/templog/_data/create_pages.py');
      //$create_pages = escapeshellcmd('/Users/korten/Apps/temperatures/build/_data/create_pages.py');
      $create_pages .= ' 2>&1';
      $create_output = shell_exec($create_pages);
      $messages .= '<span class="text-info">Created pages: </span><pre>'.$create_output.'</pre>';
      $jekyll=escapeshellcmd('jekyll build');
      $cd=escapeshellcmd('cd /usr/local/share/templog/');
      $jekyllcmd=$cd.'&&'.$jekyll.' 2>&1';
      $jekyll_ouptut=shell_exec($jekyllcmd);
      $messages .= '<span class="text-info">Updated Site: </span><pre>'.$jekyll_ouptut.'</pre></div>';
    }
    //execute table partitioning commands
    if (!empty($commands)) {
      $messages .= '<div class="well">';
      foreach ($commands as $command) {
        $output = shell_exec($command);
        $messages .= '<span class="text-info">Executing: ' . $command . '</span><pre>' . $output . '</pre>';
      }
      $messages .= '</div>';
    }
  } else {
    $messages .= '<div class="well"><span class="text-danger">ERROR: Permission denied. Cannot write to config file: "'. (getcwd()) . '/' . $config_file . '". Can not save changes!</span></div>';
  }
}
// Close the database connection
$dbh = null;

// create the page
//==================================================

//database configuration:
echo '
        <form role="form" method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . ($debug ? '?debug' : ''). '">
        <h2>Database configuration</h2>
        <table class="table table-condensed">
          <caption>
            <p>Enter the database name, and the name and password of a user that has full access to that database</p>
            <p><span class="text-danger">* required field.</span></p>
          </caption>
          <thead>
            <tr>
              <th class="col-md-2">Database Name <span class="text-danger">*</span></th>
              <th class="col-md-1">Database User <span class="text-danger">*</span></th>
              <th class="col-md-1">Database Password</th>
              <th class="col-md-1">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <div class="form-group">
                  <input type="text" class="form-control" name="database/db" placeholder="Enter Database Name" value="' . $conf["database"]["db"] . '">
                  <span class="text-danger">' . $errors["dbErr"] . '</span>
                </div>
              </td>
              <td>
                <div class="form-group ">
                  <input type="text" class="form-control" name="database/user" placeholder="Database User" value="' . $conf["database"]["user"] . '">
                  <span class="text-danger">' . $errors["userErr"] . '</span>
                </div>
              </td>
              <td>
                <div class="form-group">
                  <input type="text" class="form-control" name="database/pw" placeholder="Database Password" value="' . $conf["database"]["pw"] . '">
                </div>
              </td>
              <td>
                ';
//create status buttons for the database
$buttonType = 'submit';
if ($conf['database']['dbtest']=='OK') {
  $buttonClass = 'success';
  $buttonTitle = 'All is well.';
} elseif ($conf['database']['dbtest']=='NotOK') {
  $buttonClass = 'danger';
  $buttonTitle = $conf["database"]["dberrormsg"];
} else {
  $buttonClass = 'warning';
  $buttonTitle = 'Enter the name of an existing database and a corresponding user to test if the database works.';
}
echo createButtonString($buttonType, $buttonClass, $buttonTitle);
echo '
              </td>
            </tr>
          </tbody>
        </table>';
// end database configuration

//local sensor configuration
echo '
        <h2>Local Sensor configuration</h2>
        <table class="table table-condensed">
          <caption>
            <p>';
if (!$haveLocalSensors) {
  echo '<span class="text-danger">No local sensors attached!.</span></p>
          </caption>';
} else {
  echo 'Enter Names for the sensors and names of the corresponding database tables to be used.</p>
          </caption>
          <thead>
            <tr>
              <!--Column headers-->
              <th class="col-md-1">Current Temperature</th>
              <th class="col-md-1">Calibration</th>
              <th class="col-md-2">Sensor Name <span class="text-danger">*</span></th>
              <th class="col-md-1">Database Table Name<span class="text-danger">*</span></th>
              <th class="col-md-1">Category <span class="text-danger">*</span></th>
              <th class="col-md-2">Comment</th>
              <th class="col-md-1">Status</th>
              <th class="col-md-1">Action</th>
            </tr>
          </thead>
          <tbody>
' . $localSensors .'
          </tbody>';
}
echo'
        </table>';
// end local sensors

// external sensors
if (!empty($externalSensors) && empty($errors['extURLError']) && empty($errors['extURLNameError'])) {
  $urls=array_unique($urls);
  if ($debug) {
    echo '<h4>URLs to external sensor boxes:</h4><pre>';
    print_r($urls);
    echo '</pre>';
  }
  echo '
          <h2>External Sensor Configuration</h2>';
  foreach ($urls as $url) {
    echo '
          <h3>' . $externalSensors[$url]['urlname'] . '</h3>
          <table class="table table-condensed">
            <thead>
              <tr>
                <th class="col-md-2">URL</th>
                <th class="col-md-1">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div class="form-group">
                    <input type="text" class="form-control extURL" name="new_external/url" value="'.$url.'" disabled>
                  </div>
                </td>
                <td>
                  '.$externalSensors[$url]['buttonstring'].'
                </td>
              </tr>
            </tbody>
          </table>
          <table class="table table-condensed">
          <caption>
            <p>Enter Names for the sensors and names of the corresponding database tables to be used.</p>
          </caption>
          <thead>
            <tr>
              <!--Column headers-->
              <th class="col-md-1">Current Temperature</th>
              <th class="col-md-1">Calibration</th>
              <th class="col-md-2">Sensor Name <span class="text-danger">*</span></th>
              <th class="col-md-1">Database Table Name<span class="text-danger">*</span></th>
              <th class="col-md-1">Category <span class="text-danger">*</span></th>
              <th class="col-md-2">Comment</th>
              <th class="col-md-1">Status</th>
              <th class="col-md-1">Action</th>
            </tr>
          </thead>
          <tbody>
' . $externalSensors[$url]['text'] . '
          </tbody>
        </table>';
  }
}
//end external sensors

// hidden div for adding new external sensors
//look for external parsers
//$parsers is assigned at the top of the script
$selectorAction=[];
$selectorAction['none'] = 'no parser';
$parsers=array_combine($parsers, $parsers);
$selectorAction=array_merge($selectorAction, $parsers);
$parserSelector=createActionSelector('new_external', $selectorAction, 0, [] ,'newExtParser','parser' );

echo '
        <div id="newExternal"' . ((empty($errors['extURLNameError']) && empty($errors['extURLError'])) ? ' class="hidden"' : ''). '>
          <h2>New External Sensor Configuration</h2>
          <table class="table table-condensed">
            <caption>
              <p>Enter the URL and a name for the external sensor box. If the external box requires a username and password, you can enter those as well.</p>
            </caption>
            <thead>
              <tr>
                <th class="col-md-2">URL <span class="text-danger">*</span></th>
                <th class="col-md-2">Name<span class="text-danger">*</span></th>
                <th class="col-md-1">Username</th>
                <th class="col-md-1">Password</th>
                <th class="col-md-1">Type</th>
                <th class="col-md-1"></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div class="form-group">
                    <input type="text" id="newExtURL" class="form-control" name="new_external/url" placeholder="http://diez-templog-1/data.php?config=get" value="http://diez-templog-1/data.php?config=get">
                    <span id="extURLError" class="text-danger">' . $errors['extURLError'] . '</span>
                  </div>
                </td>
                <td>
                  <div class="form-group">
                    <input type="text" id="newExtName" class="form-control" name="new_external/name" placeholder="Enter a name for the box." value="">
                    <span id="extURLNameError" class="text-danger">' . $errors['extURLNameError'] . '</span>
                  </div>
                </td>
                <td>
                  <div class="form-group">
                    <input type="text" id="newExtUsername" class="form-control" name="new_external/username" placeholder="username" value="">
                  </div>
                </td>
                <td>
                  <div class="form-group">
                    <input type="text" id="newExtPassword" class="form-control" name="new_external/password" placeholder="password" value="">
                  </div>
                </td>
                <td>
                  '.$parserSelector.'
                </td>
                <td>
                  <button type="button" id="getExternal" class="btn btn-primary" data-loading-text="Loading..." autocomplete="off"><span class="glyphicon glyphicon-cloud-download"></span> Download external configuration</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <button type="button" id="addExternal" class="btn btn-info" data-loading-text="Loading..." autocomplete="off"><span class="glyphicon glyphicon-plus"></span> Add external source</button>';
// end hidden div for new external configuration

// save button and end of form
echo '
        <div class="text-center">
        <button type="submit" id="saveButton" class="btn btn-lg btn-primary" data-loading-text="Saving..." autocomplete="off" value="">Save</button>
        </div>
        </form>
        <hr>';
// echo any messages that might have come up (for example during saving the configuration
if (!empty($messages)) {
  echo '
  <h2>Messages:</h2>';
  echo $messages;
}
?>
      </div>
{% include js.html %}
    <!-- build:js {{site.baseurl}}/assets/js/conf.min.js -->
    <script src="{{ site.baseurl }}/assets/js/conf.js" type="text/javascript"></script>
    <!-- /build -->