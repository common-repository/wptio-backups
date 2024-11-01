jQuery(document).ready(
        function () {
            jQuery('.connect-to-drive').on('click', function () {
                var theValue = jQuery(this).attr('value');
                ;
                wptioAjax.accessdrive(theValue);
            });
        });

var wptioAjax = {
    processData: function (theDomain, theDataSource, theDataSegment, theParams, success, repsonseType) {
        if (repsonseType == undefined) {
            repsonseType = 'json';
        }

        if (success == undefined) {
            success = function (data, textStatus, jqXHR) {
                console.log(['', data, textStatus, jqXHR]);
            }
        }

        theParams.security_key = jQuery('#bk-ajax-nonce').val();

        var requestData = {
            domain: theDomain
            , datasource: theDataSource
            , datasegment: theDataSegment
            , params: theParams
            , dataKey: 'edt-ajax'
            , repsonseType: repsonseType
        };
        //var requestDataJson = encodeURI(angular.toJson(requestData, false));
        jQuery.post(
                ajaxurl,
                {
                    action: 'wptio_api',
                    data: JSON.stringify(requestData),
                    dataType: 'json',

                },
                function (response) {

                    success(response);
                }
        );
    },
    fullbackup: function () {

        wptioAjax.start_backup_process({
            type: 'full'
        });
    },
    partialbackup: function () {
//        jQuery("#ini-plugin").attr("hidden", true);
//        jQuery("#ini-plugin").removeAttr("hidden");
        var params = {
            type: 'partial'
        };
        params.allDate = jQuery("#allDate").attr('checked') == 'checked' || jQuery("#allDate").prop('checked');
        params.fromDate = jQuery("#from").val();
        params.toDate = jQuery("#to").val();
        if (params.allDate == true)
        {
            params.fromDate = '';
            params.toDate = '';
        }
        if (Date.parse(params.fromDate) >= Date.parse(params.toDate))
        {
            alert("invalid Date");
            return;
        }
        if (params.allDate == false && params.fromDate == '' && params.toDate == '') {
            alert("Please select valid date range");
            return;
        }
        params.plugin = jQuery("#plugins").attr('checked') == 'checked' || jQuery("#plugins").prop('checked');
        params.theme = jQuery("#themes").attr('checked') == 'checked' || jQuery("#themes").prop('checked');
        params.uploadfile = jQuery("#uploadsFile").attr('checked') == 'checked' || jQuery("#uploadsFile").prop('checked');
        params.database = jQuery("#dataBase").attr('checked') == 'checked' || jQuery("#dataBase").prop('checked');
        if (params.plugin == false && params.theme == false && params.uploadfile == false && params.database == false) {
            alert("Please select components to backup");
            return;
        }
        wptioAjax.start_backup_process(params);
    },

    start_backup_process: function (params) {

        jQuery("#ini-plugin").attr("hidden", true);
        jQuery("#wptio-restore-log").removeAttr("hidden");
        wptioAjax.processData(
                "",
                "wptio_backup",
                "wptio_create_patch",
                params,
                function (response) {
                    if (response.theData.result == 'not-ok')
                    {
                        var process = ('<tr><td>' +
                                '<label class="badge badge-danger text-white">Erorr</label>' +
                                '</td>' +
                                '<td>' +
                                response.theData.details +
                                '</td>' +
                                '</tr>');
                        jQuery("#wptio-log-table").append(process);
                        return;
                    }
                    if (response.theData.url)
                    {
                        window.location.replace(response.theData.url);
                    }
                    wptioAjax.response_to_backup_process(response.theData);
                },
                );
    },
    response_to_backup_process: function (response) {
        var d = new Date();

        var month = d.getMonth() + 1;
        var day = d.getDate();
        var hour = d.getHours();
        var min = d.getMinutes();
        var sec = d.getSeconds();
        var output = d.getFullYear() + '-' +
                (month < 10 ? '0' : '') + month + '-' +
                (day < 10 ? '0' : '') + day + ' ' + hour + ":" + min + ":" + (sec < 10 ? '0' + sec : sec);
        if (response.backup_patch == false) {

            jQuery("#wptio-log-table").attr("hidden", true);
            jQuery("#wptio-success").removeAttr("hidden");
            jQuery("#waiting_backup").attr("hidden", true);

        } else if (response.backup_patch.status == 'patch_ready')
        {
            if (response.the_count == undefined)
            {
                response.the_count = response.backup_patch.total_expected_files_steps
            }
            if (response.backup_patch.total_byte_size == 0 && (response.the_count == "0"))
            {
                response.backup_patch.total_byte_size = 1025;
                response.the_count = 1;
            }
            var size = response.backup_patch.total_byte_size;
            var unit = "Bytes";
            if (size > 1024) {
                size = size / 1024;
                unit = "KB";
            }
            if (size > 1024) {
                size = size / 1024;
                unit = "MB";
            }
            if (size > 1024) {
                size = size / 1024;
                unit = "GB";
            }

            size = Math.round(size * 100) / 100;

            unit = unit;
            var start = ('<tr><td>' +
                    '<label class="badge badge-primary">Start Backuping</label>' +
                    '</td>' +
                    '<td>' +
                    'Backup Files ' + response.the_count + ' Files of size ' + size + ' ' + unit +
                    '</td>' +
                    '<td>' + output + '</td>' +
                    '</tr>');
            jQuery("#wptio-log-table").append(start);
            wptioAjax.do_backup_process();

        } else
        {
            if (response.the_count == undefined)
            {
                response.the_count = response.backup_patch.total_expected_files_steps
            }
            if (response.backup_patch.total_byte_size == 0 && response.the_count == "0")
            {
                response.backup_patch.total_byte_size = 1025;
                response.the_count = 1;
            }
            var size = response.backup_patch.total_byte_size;
            var unit = "Bytes";
            if (size > 1024) {
                size = size / 1024;
                unit = "KB";
            }
            if (size > 1024) {
                size = size / 1024;
                unit = "MB";
            }
            if (size > 1024) {
                size = size / 1024;
                unit = "GB";
            }

            size = Math.round(size * 100) / 100;
            ;
            unit = unit;
            var process = ('<tr><td>' +
                    '<label class="badge badge-warning text-white">Backuping</label>' +
                    '</td>' +
                    '<td>' +
                    'Backup Files ' + response.the_count + ' Files of size ' + size + ' ' + unit +
                    '</td>' +
                    '<td>' + output + '</td>' +
                    '</tr>');
            jQuery("#wptio-log-table").append(process);


        }

    },

    check_backup_status: function () {

        var myVar2 = setInterval(function () {

            wptioAjax.processData(
                    "",
                    "wptio_backup",
                    "wptio_check_backup_status",
                    {},
                    function (response) {


                        if (response.theData.backup_patch == false)
                        {


                            clearInterval(myVar2);
                        }
                        if (response.theData != false)
                        {
                            wptioAjax.response_to_backup_process(response.theData);
                        }


                    });
        }, 20 * 1000);

    },
    do_backup_process: function () {
        wptioAjax.processData(
                "",
                "wptio_backup",
                "wptio_do_backup_step",
                {},
                function (response) {

                    wptioAjax.response_to_backup_process(response.theData);
                    wptioAjax.check_backup_status();
                },
                );
    },
    accessdrive: function (theValue) {

        wptioAjax.processData(
                "",
                "wptio_settings",
                "wptio_access",
                {
                    drive: theValue
                },
                function (response) {

                });
    },
    licence: function ()
    {
        var params = jQuery("#Activate").val();
        if (params == '' || params == undefined)
        {
            jQuery("#wrongact").html('<i class="fa fa-exclamation" aria-hidden="true"></i> Please Fill Field');
            return;
        }
        jQuery("#wrongemail").attr('hidden', true);
        jQuery("#activebtn").attr('disabled', true);
        jQuery("#activebtn").html('<i class="fa fa-refresh fa-spin fa-1x fa-fw" ></i>');
        jQuery("#Activate").attr('disabeld', true);
        jQuery("#Activate").attr('hidden', true);
        wptioAjax.processData(
                "",
                "wptio_settings",
                "wptio_send_key",
                {

                    params: params
                },
                function (response) {


                    jQuery("#activebtn").html('sucess');
                    if (response.theData.result == 'ok')
                    {
                        location.reload();
                        jQuery("#congrats").removeAttr('hidden');
                    }
                    if (response.theData.error_reason)
                    {
                        jQuery("#activealert").removeAttr('hidden');
                        jQuery("#activealert").html(response.theData.error_reason);
                    }

                });
    },
    restore: function ()
    {

        jQuery('#wptio-btn-get-list').attr("hidden", true);
        jQuery('#wptio-btn-here').attr("disabled", true);
        jQuery('#wptio-refresh').removeAttr("hidden");
        jQuery('#wptio-btn-here-2').attr("disabled", true);
        wptioAjax.processData(
                "",
                "wptio_restore",
                "wptio_restore_list",
                {

                    params: ""
                },
                function (response) {

                    jQuery('#wptio-btn-get-list').attr("hidden", true);

                    if (response.theData == undefined)
                    {
                        jQuery('#wptio-refresh').attr("hidden", true);
                        jQuery('#wptio-process').attr("hidden", true);
                        var message = '<div class="alert alert-info text-center" role="alert">Not Found</div>';
                        jQuery('#wptio-result').html(message);
                        jQuery('#wptio-data-load').attr("hidden", true);
                        return;

                    }
                    if (response.theData.url)
                    {
                        window.location.replace(response.theData.url);

                    }
                    jQuery('#wptio-refresh').attr("hidden", true);
                    jQuery('#wptio-process').attr("hidden", true);

                    jQuery('#wptio-data-list').html('');

                    jQuery.each(response.theData.folder, function (key, value) {
                        var name_to_split = value.split("-", 6);
                        if (response.theData.details[key] == 'full')
                        {
                            var badrge = 'badge-success';
                        } else {
                            var badrge = 'badge-info';
                        }
                        var button = '<button type="button"  class="btn btn-warning btn-sm wptio-restore" data-restore-filename="' + value + '" data-restore-id="' + key + '" >Restore</button>';
                        var folder = ('<tr><td>'
                                + '<label class="badge ' + badrge + '">' + response.theData.details[key] + '</label>'
                                + '</td>'
                                + '<td>'
                                + '<strong>' + name_to_split[0] + '</strong>'
                                + '</td>'
                                + '<td>'
                                + name_to_split[2] + "-" + name_to_split[3] + "-" + name_to_split[4].slice(0, 2) + " " + name_to_split[4].slice(2, 4) + ':' + name_to_split[4].slice(4, 6) + ':' + name_to_split[4].slice(6, 8)
                                + '</td>'
                                + '<td class="text-right">'
                                + button
                                + '</td></tr>');

                        jQuery('#wptio-data-list').append(folder);


                    });

                    jQuery('#wptio-data-list').removeAttr("hidden");



                    jQuery('.wptio-restore').on('click', function () {
                        var restorefilename = jQuery(this).data('restoreFilename');
                        var restoreid = jQuery(this).data('restoreId');


                        wptioAjax.processData(
                                "",
                                "wptio_restore",
                                "wptio_restore_process",
                                {
                                    restorefilename: restorefilename,
                                    restoreid: restoreid
                                },
                                function (response) {



                                });

// timmer

                        wptioAjax.check_restore_step(restorefilename);
                    });
                });
    },
    check_restore_step: function (filename)
    {
        jQuery("#wptio-success").append(filename + "!");
        jQuery('#wptio-data-list').attr("hidden", true);
        jQuery('#wptio-restore-log').removeAttr('hidden');
        jQuery('#file-name').append(filename);


        var myVa = setInterval(function () {

            var d = new Date();

            var month = d.getMonth() + 1;
            var day = d.getDate();
            var hour = d.getHours();
            var min = d.getMinutes();
            var sec = d.getSeconds();
            var output = d.getFullYear() + '-' +
                    (month < 10 ? '0' : '') + month + '-' +
                    (day < 10 ? '0' : '') + day + ' ' + hour + ":" + min + ":" + (sec < 10 ? '0' + sec : sec);

            wptioAjax.processData(
                    "",
                    "wptio_restore",
                    "wptio_check_restore_status",
                    {

                        params: ""
                    }, function (response) {





                if (response.theData.restore_patch == false)
                {
                    //jQuery("#wptio-success").html('You have successfully restores' + " " + filename+ "!");
                    //jQuery('#file-name').html(filename);
                    jQuery("#wptio-success").removeAttr("hidden");
                    jQuery("#wptio-log-table").attr("hidden", true);
                    clearInterval(myVa);
                } else {
                    var pra = '';
                    if (response.theData.restore_patch.currentfilename != undefined)
                    {
                        pra = ('<tr>' +
                                '<td>' +
                                '<label class="badge badge-info">Restore File</label>' +
                                '</td>' +
                                '<td>' +
                                '<strong>' + response.theData.restore_patch.currentfilename + '</strong>' +
                                '</td>' +
                                '<td>' + output +
                                '</td>' +
                                '<td class="text-right">' +
                                '<label class="badge badge-warning text-white">' + response.theData.restore_patch.remaining_files + ' Files Remaining</label>' +
                                '</td>' +
                                '</tr>');
                    } else {
                        pra = ('<tr>' +
                                '<td>' +
                                '<label class="badge badge-warning">Connecting</label>' +
                                '</td>' +
                                '<td>' +
                                '<strong>Retriving Information from Cloud Storage</strong>' +
                                '</td>' +
                                '<td>' + output +
                                '</td>' +
                                '<td class="text-right">' +
                                '</td>' +
                                '</tr>');

                    }

                    jQuery("#wptio-log-table").append(pra);
                }




            });


        }, 20 * 1000);


    },
    licence_free: function ()
    {

        var params = jQuery("#email").val();
        var re = /[A-Z0-9._%+-]+@[A-Z0-9.-]+.[A-Z]{2,4}/igm;

        if (params == '' || params == undefined)
        {
            jQuery("#wrongemail").html('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Please fill the email address so we could help you.');
            return;
        }
        if (!re.test(params))
        {
            jQuery("#wrongemail").html('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> The email address is not correct.');
            return;
        }
        jQuery("#wrongemail").attr('hidden', true);
        jQuery("#emailfreebtn").attr('disabled', true);
        jQuery("#emailfreebtn").html('<i class="fa fa-refresh fa-spin fa-1x fa-fw" ></i>');
        jQuery("#massage-trial-box").html('This could take few moments, please wait!');
        jQuery("#email").attr('disabeld', true);
        jQuery("#email").attr('hidden', true);


        wptioAjax.processData(
                "",
                "wptio_settings",
                "wptio_send_email",
                {

                    params: params
                },
                function (response) {

                    jQuery("#emailalert").removeAttr('hidden');
                    jQuery("#emailfreebtn").html('sucess');

                    jQuery("#emailalert").html(response.theData.massege);


                });

    },
    delete_schedule: function ()
    {


        if (confirm("Are You Sure to delete this schedule?!")) {
            wptioAjax.processData(
                    "",
                    "wptio_schedule",
                    "wptio_delete_schedule_backup",
                    {

                        params: ""
                    },
                    function (response) {
                        if (response.result == "ok")
                        {
                            location.reload();
                        }



                    });





        }
    },

};
