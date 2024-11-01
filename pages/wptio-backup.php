<?php
$backup_option = get_option('wptio_backup_patch');
$last_backup_option=get_option('wptio_connected_storage');
$provider='';
$date='';
if($last_backup_option!=false)
 {
$provider=$last_backup_option['provider'];

 }

?>

<html lang="en">
    <head>


        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

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

            .card{
                padding: 0px;
/*                box-shadow: -3px 4px 7px rgba(0,0,0,0.5);*/
            }
        </style>   
    </head>
    <body>

       
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-xs-12">
                    <div class="wptio-header">
                        <h1 class="display-4">WPtools.io Backup <small>v<?php echo WPTIO_VERSION;?></small></h1>
                    </div>
                </div>
            </div>
            <?php if (get_option('wptio_restore_patch') != false): ?>
                <div>
                    <div class="alert alert-danger text-center" role="alert">

                        Sorry! Their is a Restore process working Right Now . we could not start any process until this is finished.
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

            <div class="row" id="ini-plugin">
                <div class="col-md-4">
                    <div class="card border-primary mb-3" >
                        <div class="card-header text-primary">Full Backup</div>
                        <div class="card-body text-secondary">
                            <p class="card-text" style="font-size:15px">We would be glad to create a full backup for your business, all that you have to do is click on "Create Full Backup".</p>

                        </div>
                        <div class="card-footer bg-transparent border-secondary text-secondary text-right">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="full" onclick="wptioAjax.fullbackup()" >Create Full Backup</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-1"></div>
                <div class="col-md-7">
                    <div class="card border-success mb-3" style="max-width: 60rem;">
                        <div class="card-header text-success">Partial Backup</div>

                        <div class="card-body text-secondary">
                            <div class="row">
                                <div class="col-sm-5">
                                    <h5 class="card-title">What do you want to take backup for?</h5>


                                    <div class="input-group">
                                        <span >
                                            <input type="checkbox" id="plugins"aria-label="Checkbox " >Plugins
                                        </span>
                                    </div>
                                    <div class="input-group">
                                        <span >
                                            <input type="checkbox" id="themes" aria-label="Checkbox " >Themes
                                        </span>
                                    </div>
                                    <div class="input-group">
                                        <span >
                                            <input type="checkbox" id="uploadsFile"aria-label="Checkbox " >Upload Files
                                        </span>
                                    </div>
                                    <div class="input-group">
                                        <span >
                                            <input type="checkbox" id="dataBase"aria-label="Checkbox " >Database
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-7">
                                    <h5 class="card-title">Data Changed Within?</h5>
                                    <div class="input-group mb-3">
                                        <span >
                                            <input type="checkbox" aria-label="Checkbox " id="allDate" value="allDate" checked>All Time
                                        </span>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-addon" >From</span>
                                        <input type="date" class="form-control" disabled id="from" placeholder="" aria-label="Username" aria-describedby="sizing-addon2">
                                        <span class="input-group-addon" >To</span>
                                        <input type="date" class="form-control" id="to" disabled placeholder="" aria-label="Username" aria-describedby="sizing-addon2">
                                    </div>
                                </div>
                            </div>
                        </div>



                        <div class="card-footer bg-transparent border-secondary text-secondary text-right">
                            <button type="button" class="btn btn-outline-success btn-sm"  onclick="wptioAjax.partialbackup()">Create Partial Backup</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row" id="wptio-restore-log" hidden>
                <div class="col-lg-12">
                    <div>
                        <h4 class="display-5">Backup Log <small class="text-warning" id="file-name"> </small><i class="fa fa-spin fa-refresh fa ml-3" id="waiting_backup"></i></h4>
                       
                    </div>
                    <div class="alert alert-success text-center mt-4" id="wptio-success" hidden>Congratulations! We have successfully finished back upping your website to <span class="text-info initialism"><?php echo $provider; ?></span></div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="wptio-log-table">
                            <tbody><tr>
                                    <td>
                                        <label class="badge badge-success">Preparing</label>
                                    </td>
                                    <td>
                                        <strong>Prepare Backup Batch</strong>
                                    </td>
                                    <td>

                                    </td>

                                    <td></td>
                                </tr>

                            </tbody></table>
                    </div>
                </div>
            </div>

        </div>






        
        <?php if ($backup_option != false) : ?>

            <script type="text/javascript">
                jQuery("#ini-plugin").attr("hidden",true);
                jQuery("#wptio-restore-log").removeAttr("hidden");
                wptioAjax.check_backup_status();

            </script>
        <?php endif; ?>
            <input type="hidden" name="bk-ajax-nonce" id="bk-ajax-nonce" value="<?php echo wp_create_nonce('wptio_nonce_key') ?>">
    </body>
</html>



