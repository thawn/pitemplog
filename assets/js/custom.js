//activate the bootstrap tooltips
$(function () {
  $("[data-toggle='tooltip']").tooltip();
});
//this function fetches the data from an external source such as a php script that outputs data in json format
AmCharts.loadJSON = function(url, username, password, parser) {
  // create the request
  if (window.XMLHttpRequest) {
    // IE7+, Firefox, Chrome, Opera, Safari
    var request = new XMLHttpRequest();
  } else {
    // code for IE6, IE5
    var request = new ActiveXObject('Microsoft.XMLHTTP');
  }
  // if a parser is given, we go through the parser which should convert our data to json
  console.log(parser);
  if (parser && parser!='none') {
    url=window.location.origin+'/assets/parser/'+parser+'?url='+url+'&user='+username+'&pw='+password;
    request.open('GET', url, false);
  } else if (username) {
    request.withCredentials=true;
    request.open('POST', url, false);
    request.setRequestHeader( 'Authorization', 'Basic ' + btoa( username + ':' + password ) );
  } else{
    //open the connection. N.b.: the last false makes sure the rest of our code waits for the data
    request.open('GET', url, false);
  }
  try {
    request.send();
  } catch(e) { //if javascript cannot fetch the data, try to run through proxy.php
    console.log(e)
    url=window.location.origin+'/conf/proxy.php?url='+url+'&user='+username+'$pw='+password;
  }

  // parse and return the output
  return eval(request.responseText);
};

function makeChart(theme, chartData, enableExport,pathToImg){
  var chart = {
        "type": "serial",
        "theme": theme,
        "pathToImages": pathToImg,
        "dataProvider": chartData,
        "valueAxes": [{
          "position": "left",
          "title": "temperature (\xB0C)",
          "minimum": -10,
          "maximum": 100,
        }],
        "graphs": [{
          "fillAlphas": 0.4,
          "valueField": "temp"
        }],
        "chartScrollbar": {},
        "valueScrollbar": {
        },
        "chartCursor": {
          "categoryBalloonDateFormat": "JJ:NN, DD MMMM",
          "cursorPosition": "mouse",
          "cursorAlpha": 0.2
        },
        "categoryField": "time",
        "categoryAxis": {
          "minPeriod": "mm",
          "parseDates": true,
          "title": "date"
        },
        "dataDateFormat": "YYYY-MM-DD HH:NN:SS"
      };
      if (enableExport) {
        chart.export={
          "enabled": true,
          "position": "top-left",
          "menu": [ {
            "class": "export-main",
            "menu": [ "PNG", "SVG" ]
          } ]
        }};
        return chart
}

function exportToCsv(filename, rows) {
  var processRow = function (row) {
    var results=[];
    for (key in row) {
      var innerValue = row[key] === null ? '' : row[key].toString();
      if (row[key] instanceof Date) {
        innerValue = row[key].toLocaleString();
      };
      var result = innerValue.replace(/"/g, '""');
      if (result.search(/("|,|\n)/g) >= 0) {
        result = '"' + result + '"';
      }
      results.push(result);
    }
    var finalVal=results.join(',');
    return finalVal + '\n';
  };

  var csvArray = [];
  for (key in rows) {
    csvArray.push(processRow(rows[key]));
  }
  var csvFile = csvArray.join('');

  var blob = new Blob([csvFile], { type: 'text/csv;charset=utf-8;' });
  if (navigator.msSaveBlob) { // IE 10+
    navigator.msSaveBlob(blob, filename);
  } else {
    var link = document.createElement("a");
    var url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    if (link.download !== undefined) { // feature detection
      // Browsers that support HTML5 download attribute
      link.setAttribute("download", filename);
    }
    link.style = "visibility:hidden";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
}

