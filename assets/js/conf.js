"use strict";

/*
 * global variables
 */

var apiURL = '/conf/conf.php';

var elementCount = 0;

/*
 * general functions
 */

function getQueryVariable(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        if (pair[0] == variable) {
            return pair[1];
        }
    }
    return (false);
}

var displayMessages = function (message) {
    /**
     * add content to the message window and show it.
     * 
     * @param: message: html string containing the messages.
     */
    $('#messageWindow').append(message);
    $('#messageWindow').show();
};

var resetMessages = function () {
    $('#messageWindow').hide();
    $('#messageWindow').html('<hr><h2>Messages:</h2>');
};

var validateInput = function () {
    if (this.validity.valid) {
        $(this).parent('.form-group').removeClass('has-error').addClass(
                'has-success');
    } else {
        $(this).parent('.form-group').removeClass('has-success').addClass(
                'has-error');
    }
};

var displayFailMessage = function (jqXHR) {
    displayMessages('<h4 class="text-danger">API error:</h4><div class="well">'
            + jqXHR.responseText + '</div>');
};

/*
 * functions that handle getting data from the configuration file and putting it
 * into form fields
 */

var getData = function (getVars, callback) {
    /**
     * handles GET requests to the configuration api
     * 
     * @param: getVars: string containing the get vars and their respective
     *         assignmnets (e.g. temperatures=$sensorID)
     * @param: callback: function that is executed upon successful execution of
     *         the request receives the data as parameter
     */
    if (getQueryVariable('debug')) {
        getVars += '&debug=' + getQueryVariable('debug');
    }
    // resetMessages();
    $.getJSON(apiURL, getVars).done(function (data) {
        if (data.log) {
            displayMessages(data.log);
        }
        if (data.status === "success") {
            callback(data);
        } else {
            checkErrors(data);
        }
    }).fail(displayFailMessage);
};

var getTemperature = function ($id) {
    /**
     * get the current temperature of a sensor
     * 
     * @param: sensorID: string id of the sensor (e.g. "28-0000063774fb"
     */
    var sensor = $id.find('[name="sensor"]').val();
    var getVars = 'temperature=' + encodeURIComponent(sensor);
    $id.find('input.ext-sensor').each(function () {
        var name = this.getAttribute('name').substring(3);
        var value = encodeURIComponent(this.value);
        getVars += '&' + name + '=' + value;
    });
    getData(getVars, function (data) {
        $id.find('input[name="temperature"]').val(data.temperature);
    });
};

var insertSensor = function (sensor_data, error_data) {
    var parent_id;
    if (sensor_data['exturl']) {
        if ($('#' + url2ID(sensor_data['exturl'])).length < 1) {
            addExternalSensorGroup(sensor_data['extname'],
                    sensor_data['exturl']);
        }
        parent_id = url2ID(sensor_data['exturl']);
    } else {
        parent_id = 'local_sensors';
    }
    var $id = insertElement('#' + parent_id, sensor_data, error_data);
    var id = $id.attr('id');
    $id.find('button, input, .input-group-addon>.glyphicon-refresh').data(
            'item-id', id);
    $id.find('[data-toggle="tooltip"]').tooltip();
    $id.find('.sensor-form').submit(function (event) {
        saveSensorConfig($(this));
        event.preventDefault();
    });
    $id.find('button[name="disable-btn"]').click(toggleSensorDisableButton);
    $id.find('button[name="delete-btn"]').click(deleteSensor);
    $id.find('input').change(validateInput);
    if (!$id.find('[name="table"]').val()) {
        setButtonStatus($id.find('[name="status-btn"]'), 'unsaved', 'sensor');
    }
    if (sensor_data['push'] === 'true') {
        $id.find('[name="temperature"]').parents('.col-sm-2').first().hide();
        $('#' + url2ID(sensor_data['exturl'])).find('div.row').first()
                .children('.col-sm-2').first().hide();
    } else {
        $id.find('[name="temperature"]').val(getTemperature($id));
        $id.find('.input-group-addon>.glyphicon-refresh').click(function () {
            $id = $('#' + $(this).data('item-id'));
            $id.find('[name="temperature"]').val(getTemperature($id));
        });
    }
    $('#local_sensors').show();
    return $id;
};

var insertElement = function (target, data, error_data) {
    /**
     * inserts a new element into the page DOM
     * 
     * @param: target string that identifies the target DOM object where the
     *         element should be inserted
     * @param: data: object containing the data to be filled in for the element
     *         that was inserted
     */
    var id = 'el' + elementCount;

    var newElement = document.getElementById('element-template')
            .cloneNode(true);
    newElement.id = id;
    newElement.style.display = 'none';
    $(target).append(newElement);
    elementCount++;
    if (data.exturl) {
        addHiddenFields($('#' + id), $('#newExternalForm'));
    }
    var $id = fillInElement(id, data, error_data);
    return $id;
};

var fillInElement = function (id, data, error_data) {
    /**
     * fill the values of input fields with data
     * 
     * @param: $id: jquery object of the element representing the respective
     *         sensor.
     * @param: data: object containing the data.
     */
    var $id = $('#' + id);
    $id.find('h3.sensor-title').text('Sensor ' + data.sensor + ':');
    for ( var i in data) {
        $id.find('[name^="' + i + '"]').val(data[i]);
    }
    if (data.enabled == 'false') {
        $id.find('input').prop('disabled', true);
        $id.find('button[name="save-btn"]').hide();
        if ($id.find('input[name="exturl"]').val()) {
            $id.find('button[name="delete-btn"]').show();
        }
        $id.find('button[name="disable-btn"]').html(
                '<span class="glyphicon glyphicon-play"></span>').attr(
                'data-original-title', 'Enable this sensor.');
    } else {
        $id.find('input').prop('disabled', false);
        $id.find('input[name="temperature"]').prop('disabled', true);
        $id.find('button[name="delete-btn"]').hide();
        $id.find('button[name="save-btn"]').show();
        $id.find('button[name="disable-btn"]').html(
                '<span class="glyphicon glyphicon-pause"></span>').attr(
                'data-original-title', 'Disable this sensor.');
    }
    if ($id.find('input[name="table"]').val() || data.enabled == 'false') {
        $id.find('button[name="disable-btn"]').show();
    }
    $id.slideDown(100);
    checkSensorErrors($id, data, error_data);
    return $id;
};

var refreshSensorData = function (id, data, sensor) {
    if (!jQuery.isEmptyObject(data.local_sensors)) {
        fillInElement(id, data.local_sensors[sensor],
                data.local_sensor_error[sensor]);
    }
    if (!jQuery.isEmptyObject(data.remote_sensors)) {
        fillInElement(id, data.remote_sensors[sensor],
                data.remote_sensor_error[sensor]);
    }
};

var loopThroughSensors = function (sensors, errors, refresh) {
    for ( var sensor in sensors) {
        var error;
        if (errors) {
            error = errors[sensor];
        }
        if (refresh) {
            fillInElement($('[value="' + sensor + '"]').parents('form').first()
                    .parent().attr('id'), sensors[sensor], error);
        } else {
            insertSensor(sensors[sensor], error);
        }
    }
}

var fillAllFields = function (data, refresh) {
    if (!jQuery.isEmptyObject(data.db_config)) {
        checkDBErrors(data);
    }
    if (!jQuery.isEmptyObject(data.local_sensors)) {
        loopThroughSensors(data.local_sensors, data.local_sensor_error, refresh);
    }
    if (!jQuery.isEmptyObject(data.remote_sensors)) {
        loopThroughSensors(data.remote_sensors, data.remote_sensor_error,
                refresh);
    }
    if (!jQuery.isEmptyObject(data.push_servers)) {
        insertPushServers(data.push_servers, data.push_server_errors, refresh);
    }
    $('#saveEverythingButton').html('Save entire configuration');
    $('#saveEverythingButton').removeClass('btn-default').addClass(
            'btn-primary');
}

var checkErrors = function (data) {
    var $id;
    if (!jQuery.isEmptyObject(data.dbErrors)) {
        showDBErrors(data.dbErrors);
    }
    if (!jQuery.isEmptyObject(data.local_sensor_error)) {
        for ( var sensor in data.local_sensor_error) {
            $id = $('.sensorID[value="' + sensor + '"').parents('form').first()
                    .parent();
            showSensorErrors($id, data.local_sensor_error[sensor]);
        }
    }
    if (!jQuery.isEmptyObject(data.remote_sensor_error)) {
        for ( var sensor in data.remote_sensor_error) {
            $id = $('.sensorID[value="' + sensor + '"').parents('form').first()
                    .parent();
            showSensorErrors($id, data.remote_sensor_error[sensor]);
        }
    }
}

var showError = function ($id, message) {
    $id.parent('.form-group').removeClass('has-success').addClass('has-error');
    $id.attr('data-original-title', message).tooltip('show');
}

var showSensorErrors = function ($id, errors) {
    for ( var i in errors) {
        showError($id.find('[name="' + i + '"]'), errors[i]);
    }
    setButtonStatus($id.find('button[name="status-btn"]'), false, 'sensor');
};

var checkSensorErrors = function ($id, data, error_data) {
    if (error_data || (data.table && data.tabletest !== 'OK')) {
        showSensorErrors($id, error_data);
    } else {
        setButtonStatus($id.find('button[name="status-btn"]'), true, 'sensor');
        if (!data['exturl']) {
            $('#addPushServer').show();
        }
    }

};

var checkDBErrors = function (data) {
    if (data.db_config.dbtest != 'OK') {
        showDBErrors(data.dbErrors);
    } else {
        $('#db-status-btn').tooltip('hide');
        setButtonStatus($('#db-status-btn'), true, 'database');
    }
};

var showDBErrors = function (errors) {
    for ( var i in errors) {
        showError($('#db-status-btn'), errors[i]);
    }
    setButtonStatus($('#db-status-btn'), false, 'database');
};

var removeAllBtnClass = function (index, className) {
    return (className.match(/(^|\s)btn-\S+/g) || []).join(' ');
};

var setButtonStatus = function ($button, success, item) {
    if (success) {
        if (success == 'loading') {
            $button.removeClass(removeAllBtnClass).addClass('btn-default');
            $button.html('<span class="glyphicon glyphicon-refresh"></span>');
            $button.attr('data-original-title', 'Loading...');
        } else if (success == 'unsaved') {
            $button.removeClass(removeAllBtnClass).addClass('btn-warning');
            $button.html('<span class="glyphicon glyphicon-alert"></span>');
            $button
                    .attr(
                            'data-original-title',
                            'Caution: '
                                    + item
                                    + ' not saved yet. Please fill in all required fields and press save.');
        } else {
            $button.removeClass(removeAllBtnClass).addClass('btn-success');
            $button.html('<span class="glyphicon glyphicon-ok"></span>');
            $button.attr('data-original-title', 'The ' + item
                    + ' is configured properly.');
            $button.parents('form').first().find('.form-group').removeClass(
                    'has-success has-errors');
        }
    } else {
        $button.removeClass(removeAllBtnClass).addClass('btn-danger');
        $button.html('<span class="glyphicon glyphicon-warning-sign"></span>');
        $button.attr('data-original-title', 'Error: Cannot connect to the '
                + item + '.');
    }
};

var checkDBConnection = function () {
    setButtonStatus($('#db-status-btn'), 'loading', 'database');
    getData('db_config', checkDBErrors);
}

var loadPageData = function () {
    getData('db_config&local_sensors&remote_sensors&push_servers',
            fillAllFields);
};

/*
 * functions for submitting data:
 */

var postData = function (action, postData, callback) {
    /**
     * handles POST requests to the configuration api
     * 
     * @param: action: string defining which action should be performed
     * @param: postData: string containing the get vars and their respective
     *         assignmnets (e.g. temperatures=$sensorID)
     * @param: callback: function that is executed upon successful execution of
     *         the request receives the data as parameter
     */
    if (getQueryVariable('debug')) {
        action += '&debug=' + getQueryVariable('debug');
    }
    resetMessages();
    $.post(apiURL + '?action=' + action, postData).done(
            function cb(data, textStatus, jqXHR) {
                if (data.log) {
                    displayMessages(data.log);
                }
                if (data.confirm) {
                    confirmAction(data.confirm, cb);
                } else {
                    if (data.alert) {
                        alert(data.alert);
                    }
                    if (data.status === "success") {
                        callback(data);
                    } else {
                        checkErrors(data);
                    }
                }
            }).fail(displayFailMessage);
};

var saveSensorConfig = function ($form) {
    /**
     * saves the database configuration
     * 
     * @param: $form: jQuery object representing the database configuration form
     */
    var $button = $form.find('button[name="status-btn"]');
    setButtonStatus($button, 'loading', 'sensor');
    postData('save_sensor', $form.serialize(), function (data) {
        refreshSensorData($button.data('item-id'), data, $form.find(
                'input[name="sensor"]').val());
    });
}

var confirmAction = function (data, callback) {
    if (data.data) {
        if (confirm(data.message)) {
            data.data.confirmed = 'true';
            var serialData = '';
            for ( var field in data.data) {
                serialData += field + '=' + data.data[field] + '&';
            }
            serialData = serialData.slice(0, -1);
            postData(data.action, serialData, callback);
        } else {
            location.reload();
        }
    }
}

var extractFormData = function ($form) {
    var config = {};
    $form.find('input').each(function () {
        var field = $(this).attr('name');
        if (field !== 'temperature') {
            config[field] = $(this).val();
        }
    });
    return config
}

var saveEverything = function () {
    var conf = {
        'all_sensors' : {},
        'push_servers' : {}
    };
    $('form').each(
            function () {
                if ($(this).find('[name="table"]').val()) {
                    var config = extractFormData($(this));
                    var sensor = config.sensor;
                    conf.all_sensors[sensor] = config;
                }
                if ($(this).find('[name="url"]').val()
                        && this.id !== 'newExternalForm'
                        && this.id !== 'newPushServerForm') {
                    var config = extractFormData($(this));
                    var url = config.url;
                    conf.push_servers[url] = config;
                }
            });
    postData('save_everything', 'conf=' + JSON.stringify(conf),
            function (data) {
                fillAllFields(data, true);
            });
};

var toggleSensorDisableButton = function (event) {
    var id = $(this).data('item-id');
    var $id = $('#' + id);
    var sensor = $id.find('input[name="sensor"]').val();
    var action;
    if ($id.find('input[name="table"]').prop('disabled')) {
        action = 'enable_sensor';
    } else {
        action = 'disable_sensor';
    }
    setButtonStatus($id.find('button[name="status-btn"]'), 'loading', 'sensor');
    postData(action, 'sensor=' + sensor, function (data) {
        refreshSensorData(id, data, sensor);
    });
    event.preventDefault();
};

var deleteSensor = function (event) {
    var $id = $(this).parents('form').parent();
    var answer = confirm('This will delete the sensor from the configuration file but will not remove any data from the database. Do you want to proceed?');
    if (answer == true) {
        setButtonStatus($id.find('button[name="status-btn"]'), 'loading',
                'sensor');
        postData('delete_sensor',
                'sensor=' + $id.find('[name="sensor"]').val(), function (data) {
                    if (data.sensor) {
                        $('[value="' + data.sensor + '"]').parents('form')
                                .parent().remove();
                    }
                });
    }
    event.preventDefault();
};

/*
 * pull config from external sensors
 */
var url2ID = function (url) {
    return url.replace(/[^a-zA-Z0-9]/g, "");
};

var addExternalSensorGroup = function (name, url) {
    var newElement = document.getElementById('externalSensorHeading-template')
            .cloneNode(true);
    newElement.id = url2ID(url);
    $(newElement).find('h3').html(name);
    $(newElement).find('p').html(url);
    $('#external_sensors').append(newElement);
    $('#external_sensors').slideDown(100);
    /**
     * @todo: make external sensor re-configurable
     */
};

var addHiddenFields = function ($id, $form) {
    $form.find('input').each(
            function () {
                $id.find('form').append(
                        '<input type="hidden" class="ext-sensor" name="ext'
                                + this.getAttribute('name') + '" value="'
                                + this.value + '">');
            });
    $id
            .find('form')
            .append(
                    '<input type="hidden" class="ext-sensor" name="exttable" value="'
                            + $id.find('input[name="table"]').val()
                            + '"><input type="hidden" class="ext-sensor" name="push" value="">');

};

var getExternalConfig = function ($form) {
    /**
     * get external sensor configuration
     * 
     * @param: $form: jQuery object representing the database configuration form
     */
    /**
     * @todo: get parsers and put them into the "parser" dropdown list
     */
    var $button = $('#getExternal');
    var name = $form.find('input[name="name"]').val();
    var url = $form.find('input[name="url"]').val()
    setButtonStatus($button, 'loading', 'sensor');
    postData(
            'get_external',
            $form.serialize(),
            function (data) {
                addExternalSensorGroup(name, url);
                var conf = {
                    'remote_sensors' : data.external_config
                };
                for ( var sensor in conf.remote_sensors) {
                    var $id = insertSensor(conf.remote_sensors[sensor], {});
                    $id.find('[name="temperature"]').val(getTemperature($id));
                    setButtonStatus($id.find('[name="status-btn"]'), 'unsaved',
                            'sensor');
                    $id.find('[name="table_old"]').val('');
                    $id.find('button[name="disable-btn"]').hide();

                }
                $button
                        .addClass('btn-primary')
                        .html(
                                '<span class="glyphicon glyphicon-cloud-download"></span> Download external configuration');
                $('#newExternal').hide();
            });
}

/*
 * Push configuration to external server
 */
var insertPushServers = function (push_servers, push_server_errors, refresh) {
    for ( var url in push_servers) {
        var error;
        if (push_server_errors) {
            error = push_server_errors[url];
        }
        if (refresh) {
            var $id = fillInElement($('[value="' + url + '"]').parents('form')
                    .first().parent().attr('id'), push_servers[url], error);
        } else {
            var id = 'el' + elementCount;
            var newElement = document.getElementById('pushServerTemplate')
                    .cloneNode(true);
            newElement.id = id;
            newElement.style.display = 'none';
            $('#push_servers').append(newElement);
            elementCount++;
            var $id = fillInElement(id, push_servers[url], error);
            $id.find('.push-server-form').submit(function (event) {
                pushConfig2Server($(this), true);
                event.preventDefault();
            });
        }
        $id.find('h3.sensor-title').html(
                'External Server: ' + push_servers[url]['name']);
        $id.find('button[name="push-external-btn"]').removeClass('btn-default')
                .addClass('btn-primary').html(
                        '<span class="glyphicon glyphicon-refresh"></span>'
                                + ' Update configuration on central server');
    }
    $('#push_servers').slideDown(100);
};

var pushConfig2Server = function ($form, refresh) {
    postData('push_config', $form.serialize(), function (data) {
        insertPushServers(data.push_servers, data.push_server_errors, refresh);
        $('#newPushServer').hide();
    });
};

var url2Name = function ($form, input) {
    var result = '';
    var match
    if (!$form.find('input[name="name"]').val()) {
        if (match = input.value
                .match(/^(?:https?:\/\/)?(?:[^@\n]+@)?(?:www\.)?([^:\/\n\?\=]+)/im)) {
            result = match[1];
            result = result.charAt(0).toUpperCase() + result.slice(1);
        }
        $form.find('input[name="name"]').val(result);
    }
};

/*
 * code that is executed on page load:
 */
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    loadPageData();
    $('#db-check-btn').click(checkDBConnection);
    $('#saveEverythingButton').click(function (event) {
        setButtonStatus($(this), 'loading');
        $(this).addClass('btn-lg')
        saveEverything();
        event.preventDefault();
    });
    $('input').change(validateInput);
    $('#addExternal').click(function () {
        $('#newExternal').show();
    });
    $('#newExternalForm').submit(function (event) {
        getExternalConfig($(this));
        event.preventDefault();
    });
    $('#newExternalForm').find('input[name="url"]').change(function () {
        url2Name($('#newExternalForm'), this);
    });
    $('#addPushServer').click(function () {
        $('#newPushServer').show();
    });
    $('#newPushServerForm').submit(function (event) {
        setButtonStatus($(this), 'loading');
        pushConfig2Server($(this), false);
        event.preventDefault();
    });
    $('#newPushServerForm').find('input[name="url"]').change(function () {
        url2Name($('#newPushServerForm'), this);
    });
});
