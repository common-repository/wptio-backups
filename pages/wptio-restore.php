<?php
$last_backup = get_option('wptio_last_backup_time');
$restore_option = get_option('wptio_restore_patch');
?>

<html lang="en">
    <head>
        <style>
            .wptio{
                max-width: 100%;

            }

            .wptio-header{
                background-color: #fff;
                border-bottom: 1px solid #dddddd;
                padding: 10px 10px 10px;
                margin: 40px 0px 20px;
            }
			
			.wptio-header h1 small {
				font-size:30%;
			}
        </style>   

        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    </head>
    <body style="">





        <div class="container ">
            <div class="row">
                <div class="col-lg-12 col-xs-12">
                    <div class="wptio-header">
                        <h1 class="display-4">WPtools.io Restore <small>v<?php echo WPTIO_VERSION;?></small></h1>
                    </div>
                </div>
            </div>
            <?php if (get_option('wptio_backup_patch') != false): ?>
                <div>
                    <div class="alert alert-danger text-center" role="alert">

                        Sorry! There is a backup process working right now. We could not start any other process until this one is finished.
                    </div>
                    </td>
                </div>

                <?php die();
            endif; ?>
            <?php if (get_option('wptio_connected_storage') == false): ?>
                <div>
                    <div class="alert alert-danger text-center" role="alert">

                        Sorry! Their is no configure Cloud Storage.
                    </div>
                    </td>
                </div>

                <?php die();
            endif; ?>
            <div class="row mb-5">
                <div class="col-lg-12 col-xs-12">
                    <p>
                       Thank you for trusting us to backup your files. Please click <button type="button" class="btn btn-link btn-sm" onclick="wptioAjax.restore()" id="wptio-btn-here-2">here</button> to find out all the backups you have made to your cloud storage.
                    </p>
                    <?php if ($last_backup != false ): ?>
                        <p>
                            The last backup you made for this website was on <span class="text-warning"><?php echo $last_backup['date']; ?></span>. The backup is stored on <span class="text-warning initialism"><?php echo $last_backup['provider']; ?></span>.
                        </p>
                    <?php else: ?>
                       
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="table-responsive" >
                        <table  class="table"  id="wptio-process">
                            <tr id="wptio-btn-get-list">
                                <td>
                                    <div class="alert alert-info text-center" role="alert"  >
                                        Please click <button type="button" class="btn btn-primary btn-sm" id="wptio-btn-here" onclick="wptioAjax.restore()" >here</button> to find out all the backups
                                    </div>
                                </td>
                            </tr>
                            <tr id="wptio-refresh" hidden>
                                <td>
                                    <div class="alert alert-info text-center"  role="alert" >
                                        <i class="fa fa-spin fa-circle-o-notch fa-2x"></i>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="table-responsive" id="wptio-result">
                        <table  class="table table-hover" id="wptio-data-list" hidden>


                        </table>
                    </div>
                </div>
            </div>
            <div class="row" id="wptio-restore-log" hidden>
                <div class="col-lg-12">
                    <div >
                        <h4 class="display-5">Restore Log <small class="text-warning" id="file-name"></small></h4>
                    </div>
                    <div class="alert alert-success text-center" hidden id="wptio-success">You have successfully restored  </div>
                    <div class="table-responsive">
                        <table  class="table table-hover" id="wptio-log-table">
                            <tr>
                                <td>
                                    <label class="badge badge-success">Preparing</label>
                                </td>
                                <td>
                                    <strong>Prepare Restore Batch</strong>
                                </td>
                                <td>

                                </td>

                                <td></td>
                            </tr>

                        </table>
                    </div>
                </div>
            </div>


        </div>




        <?php if ($restore_option != false) : ?>

            <script type="text/javascript">
                wptioAjax.check_restore_step();
                jQuery("#wptio-process").attr("hidden", true);
                jQuery("#wptio-btn-here-2").attr("disabled", true);

            </script>
        <?php endif; ?>
        <input type="hidden" name="bk-ajax-nonce" id="bk-ajax-nonce" value="<?php echo wp_create_nonce('wptio_nonce_key') ?>">
    </body>
</html>



