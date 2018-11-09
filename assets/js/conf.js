//function for creating tables
function createTable(button, message, name, value) {
    if (confirm(message)) {
        button.value=value;
        button.name=name;
    }
}
$('form').submit(function() {
   $(window).unbind('beforeunload');
});
//stateful save button
$("#saveButton").click( function () {
  $('#saveButton').button('loading');
});

//functions for loading and parsing external configuration
var ext_conf = "";
$('#addExternal').click( function () {
  $('#newExternal').removeClass("hidden");
});
$('#getExternal').click( function () {
  var extURL = $('#newExtURL').val();
  if (!extURL) {
    $('#extURLError').html('URL is Required');
    return;
  }
  var extName = $('#newExtName').val();
  if (!extName) {
    $('#extURLNameError').html('URL Name is Required');
    return;
  } else if (!extName.match(/^[a-zA-Z0-9_\- ]*$/)) {
    $('#extURLNameError').html('URL Name may only contain letters, numbers and space.');
    return;
  }
  var extUsername = $('#newExtUsername').val();
  var extPassword = $('#newExtPassword').val();
  var extParser = $('#newExtParser').val();
  var btn=$('#getExternal').button('loading');
  setTimeout(function () {
    var error="";
    //try {
      ext_conf = AmCharts.loadJSON(extURL,extUsername,extPassword,extParser);
    //} catch(e) {
    //  ext_conf=false;
    //  error = e;
    //}
    btn.button('reset');
    if (ext_conf===false) {
      $( '#getExternal' ).removeClass('btn-primary').addClass('btn-danger');
      $( '#getExternal' ).attr({
        'data-toggle': 'tooltip',
        'data-placement':'top',
        'title':'The server did not return a valid configuration. The error message was: '+error
        });
      $( '#getExternal' ).tooltip();
    } else {
      html = '<h2>External Sensor Box Configuration</h2>\n\
    <h3>' + extName + '</h3>\n\
    <table class="table table-condensed">\n\
      <thead>\n\
        <tr>\n\
          <th class="col-md-2">URL</th>\n\
          <th class="col-md-1">Status</th>\n\
        </tr>\n\
      </thead>\n\
      <tbody>\n\
        <tr>\n\
          <td>\n\
            <div class="form-group">\n\
              <input type="text" id="newExtURL" class="form-control" name="new_external/url" placeholder="http://diez-templog-2" value="' + extURL + '" disabled>\n\
            </div>\n\
          </td>\n\
          <td>\n\
            <button type="button" id="getExternal" class="btn btn-success"><span class="glyphicon glyphicon-ok"></span></button>\n\
          </td>\n\
        </tr>\n\
      </tbody>\n\
    </table>\n\
    <table class="table table-condensed">\n\
      <caption>\n\
        <p>This is the configuration I got from the external box. Please check and adapt if necessary. Then press "save".</p>\n\
      </caption>\n\
      <thead>\n\
        <tr>\n\
          <th class="col-md-2">Sensor ID</th>\n\
          <th class="col-md-2">Sensor Name <span class="text-danger">*</span></th>\n\
          <th class="col-md-1">Database Table Name<span class="text-danger">*</span></th>\n\
          <th class="col-md-1">Category <span class="text-danger">*</span></th>\n\
          <th class="col-md-2">Comment</th>\n\
          <th class="col-md-1">Status</th>\n\
          <th class="col-md-1">Action</th>\n\
        </tr>\n\
      </thead>\n\
      <tbody>\n\
        ';
      for (sensor in ext_conf[0]) {
        var isAlsoLocal = false;
        $('.sensorID').each( function () {
            if (sensor==this.value || sensor.substr(3)==this.value) {
                isAlsoLocal = true;
            }
        });
        if (sensor!="database" && !("url" in ext_conf[0][sensor])) {
          html += '<tr>\n\
            <td>\n\
              <input class="form-control" type="text" name="ext' + sensor + '/sensor" value="' + sensor + '" disabled>' + (isAlsoLocal ? ' <span class="text-danger">This sensor already exists in the configuration. Ignoring...</span>' : "") + '\n\
              <input type="hidden" name="ext' + sensor + '/sensor" value="' + sensor + '"' + (isAlsoLocal ? " disabled" : "") + '>\n\
              <input type="hidden" name="ext' + sensor + '/url" value="' + extURL + '"' + (isAlsoLocal ? " disabled" : "") + '>\n\
              <input type="hidden" name="ext' + sensor + '/urlname" value="' + extName + '"' + (isAlsoLocal ? " disabled" : "") + '>\n\
              <input type="hidden" name="ext' + sensor + '/urlusername" value="' + extUsername + '"' + (isAlsoLocal ? " disabled" : "") + '>\n\
              <input type="hidden" name="ext' + sensor + '/urlpw" value="' + extPassword + '"' + (isAlsoLocal ? " disabled" : "") + '>\n\
              <input type="hidden" name="ext' + sensor + '/urlparser" value="' + extParser + '"' + (isAlsoLocal ? " disabled" : "") + '>\n\
              <input type="hidden" name="ext' + sensor + '/exttable" value="' + ext_conf[0][sensor]['table'] + '"' + (isAlsoLocal ? " disabled" : "") + '>\n\
            </td>\n\
            <td>\n\
              <div class="form-group">\n\
                <input type="text" class="form-control" name="ext' + sensor + '/name" placeholder="Enter Sensor Name" value="' + ext_conf[0][sensor]["name"] + '"' + (isAlsoLocal ? " disabled" : "") + '>\n\
              </div>\n\
            </td>\n\
            <td>\n\
              <div class="form-group">\n\
                <input type="text" class="form-control" name="ext' + sensor + '/table" placeholder="Enter Database Table Name" value="' + ext_conf[0][sensor]["table"] + '"' + (isAlsoLocal ? " disabled" : "") + '>\n\
              </div>\n\
            </td>\n\
            <td>\n\
              <div class="form-group">\n\
                <input type="text" class="form-control" name="ext' + sensor + '/category" placeholder="Enter Category" value="' + ext_conf[0][sensor]["category"] + '"' + (isAlsoLocal ? " disabled" : "") + '>\n\
              </div>\n\
            </td>\n\
            <td>\n\
              <div class="form-group">\n\
                <input type="text" class="form-control" name="ext' + sensor + '/comment" placeholder="Enter Comment" value="' + ext_conf[0][sensor]["comment"] + '"' + (isAlsoLocal ? " disabled" : "") + '>\n\
              </div>\n\
            </td>\n\
            <td>\n\
              <button type="submit" class="btn btn-warning" data-toggle="tooltip" data-placement="top" title="Enter a table name to test whether the table is o.k."' + (isAlsoLocal ? " disabled" : "") + '><span class="glyphicon glyphicon-question-sign"></span></button>\n\
            </td>\n\
            <td>\n\
              <div class="form-group">\n\
                <select class="' + (isAlsoLocal ? "bg-danger " : "bg-success ") + 'form-control" name="ext' + sensor + '/action" onchange="this.className=this.options[this.selectedIndex].className"' + (isAlsoLocal ? " disabled" : "") + '>\n\
                  <option value="create" class="bg-success form-control form-control-inline" selected>create table</option>\n\
                  <option value="delete" class="bg-danger form-control form-control-inline"' + (isAlsoLocal ? " selected" : "") + '>ignore sensor</option>\n\
                </select>\n\
              </div>\n\
            </td>\n\
        </tr>\n\
        ';
        }
      }
      html += '</tbody>\n\
    </table>';
      $('#newExternal').html(html);
      $("[data-toggle='tooltip']").tooltip();
    }
  }, 100);
});
