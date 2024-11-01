<?php
if (isset($_POST['access_token'])) {

    \wptio_drive_member::wptio_save_access_token($_POST);
}
if (isset($_POST['wptio_create_scedule'])) {
    if (isset($_POST['wptio_schedule_days'])) {
        \wptio_schedule::wptio_create_schedule_backup($_POST['wptio_schedule_days']);
    }
}
$schedule_backup_option = get_option("wptio_schedule_backup");
$provider = '';
if (get_option('wptio_connected_storage') != false) {
    $connect = get_option('wptio_connected_storage');
    $provider = $connect['provider'];
}
$client_info = get_option('wptio_client_info');
$last_backup_date = get_option('wptio_last_backup_time');
?>
<html lang="en">
    <head>


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
                box-shadow: -3px 4px 7px rgba(0,0,0,0.5);
            }
            .ui-tooltip {
                background: #212629;
                border: 1px solid white;
                position: relative;
                padding: 10px 20px;
                color: white;
                border-radius: 3px;
                font: bold 14px "Helvetica Neue", Sans-Serif;
                box-shadow: 0 0 7px #356aa0;
                max-width:350px;
            }
        </style>   

    </head>
    <body>

        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-xs-12">
                    <div class="wptio-header">
                        <h1 class="display-4">WPtools.io Cloud Backup & Restore Plugin <small>v<?php echo WPTIO_VERSION;?></small></h1>
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

                <?php
                die();
            endif;
            ?>
            <?php if (get_option('wptio_restore_patch') != false): ?>
                <div>
                    <div class="alert alert-danger text-center" role="alert">

                        Sorry! Their is a Restore process working Right Now . we could not start any process until this is finished.
                    </div>
                    </td>
                </div>

                <?php
                die();
            endif;
            ?>
            <div class="alert alert-warning text-center" id="congrats" role="alert" hidden>Congratulations ! you Activated WPtools.io Cloud Backup & Restore Plugin.</div>
            <div class="row">
                <div class="col-md-6 col-lg-6">
                    <?php if (get_option("wptio_activation_info") == false) : ?>
                        <div class="card text-white bg-info">
                            <div class="card-header">
                                Free 30 Days Trial
                            </div>
                            <div class="card-body">
                                <p class="card-text" id="massage-trial-box">Please type down your email to enjoy 30 days trial for this domain. All features are included!</p>
                                <div class="form-group">
                                    <input class="form-control input-sm" placeholder="mail@example.com" id="email" type="email" required>
                                </div>
                                <div  id="emailalert" role="alert" hidden></div>
                                <input type="hidden" name="bk-ajax-nonce"  id="bk-ajax-nonce" value="<?php echo wp_create_nonce('wptio_nonce_key') ?>">
                                <button type="submit" class="btn btn-light btn-sm" id= "emailfreebtn" onclick="wptioAjax.licence_free()" style="float: right">Submit</button> 
                                <p class="text-warning" id="wrongemail"></p>
                            </div>
                        </div>
                        <div class="card text-white bg-warning">
                            <div class="card-header">
                                Plugin Activation
                            </div>
                            <div class="card-body">
                                <p class="card-text">Please enter your activation key. Where can I find the Activation Key?!</p>
                                <div class="form-group">                                
                                    <input class="form-control input-sm" placeholder="Activation Key" id="Activate" type="text" required>
                                </div>
                                <div class="alert alert-info text-center" id="activealert" role="alert" hidden></div>
                                <input type="hidden" name="bk-ajax-nonce" id="bk-ajax-nonce" value="<?php echo wp_create_nonce('wptio_nonce_key') ?>">
                                <button type="submit" class="btn btn-light btn-sm" id= "activebtn" onclick="wptioAjax.licence()"style="float: right">Submit</button> 
                                <p class="text-danger" id="wrongact"></p>

                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (get_option('wptio_client_info') != false): $info = get_option('wptio_client_info') ?>   
                        <div class="card border-primary">
                            <div class="card-header">
                                License information
                            </div>
                            <div class="card-body">
                                <ul>
                                    <li><b>License Type:</b><?php echo " " . $info['license_type'] ?></li>
                                    <li><b>Expires On:</b><?php echo " " . ($info['expiry_date'] == "infinate" ? 'For Life' : $info['expiry_date']); ?></li>
                                    <li><b>Licensed To:</b> <?php echo " " . $info['licensed_to'] ?></li>

                                </ul>
                                <p class="text-center"><a class="btn btn-primary   btn-lg" <?php echo ($info['expiry_date'] == "infinate" ? 'hidden' : ''); ?> href="http://wptools.io/wptoolsio-cloud-backup-restore-plugin/">Buy Now</a></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (get_option("wptio_activation_info") != false) : ?>

                        <div class="card border-secondary mb-3">
                            <div class="card-header text-secondary">Cloud Storage</div>
                            <div class="card-body text-secondary">
                                <p class="card-text">Please select your drive to upload your website backup</p>
                                <div class="form-group">
                                    <form  action=" https://api.wptools.io/backup/connect/google<?php echo WPTIO_API_REQ_VERSION ?>" method="post">
                                        <label for="btn1"><i class="fa fa-google fa-fw"></i> Google Drive</label>
                                        <input type="hidden" value="<?php echo admin_url('admin.php?page=setting-website-proccess') ?>" name="return-url" >
                                        <input type="submit" <?php echo ($provider == 'google' ? 'disabled' : ''); ?> id="google" value="<?php echo ($provider == 'google' ? 'Connected' : 'Connect'); ?>"class="btn <?php echo ($provider == 'google' ? 'btn-warning' : 'btn-primary'); ?>  btn-sm connect-to-drive" style="float: right">

                                    </form> 
                                </div>
                                <div class="form-group">
                                    <form action="https://api.wptools.io/backup/connect/onedrive<?php echo WPTIO_API_REQ_VERSION ?>"method="post">
                                        <label for="btn2"><i class="fa fa-windows fa-fw"></i> One Drive</label>
                                        <input type="hidden" value="<?php echo admin_url('admin.php?page=setting-website-proccess') ?>" name="return-url" >
                                        <input type="submit" <?php echo ($provider == 'onedrive' ? 'disabled' : ''); ?> value="<?php echo ($provider == 'onedrive' ? 'Connected' : 'Connect'); ?>"class="btn <?php echo ($provider == 'onedrive' ? 'btn-warning' : 'btn-primary'); ?>  btn-sm connect-to-drive" style="float: right">


                                    </form>
                                </div>


                                <div class="form-group">
                                    <form action="https://api.wptools.io/backup/connect/dropbox<?php echo WPTIO_API_REQ_VERSION ?>" method="post">
                                        <label for="btn4"><i class="fa fa-dropbox fa-fw"></i> Dropbox</label>
                                        <input type="hidden" value="<?php echo admin_url('admin.php?page=setting-website-proccess') ?>" name="return-url" >
                                        <input type="submit"  <?php echo ($provider == 'dropbox' ? 'disabled' : ''); ?> value="<?php echo ($provider == 'dropbox' ? 'Connected' : 'Connect'); ?>"class="btn <?php echo ($provider == 'dropbox' ? 'btn-warning' : 'btn-primary'); ?>  btn-sm connect-to-drive" style="float: right">
                                    </form>
                                </div>
                            </div>

                        </div>
                        <div class="card border-warning mb-3">
                            <div class="card-header text-secondary">Backup Schedule</div>
                            <div class="card-body text-secondary">
                                <?php if ($connect == false) : ?>
                                    <p class="card-text text-danger" style="font-size:16px">Please configure your Cloud Storage first!</p>
                                <?php else : ?>
                                    <?php if (get_option("wptio_schedule_backup") == false): ?>
                                        <form method="post">
                                            <div class="form-group">
                                                <p class="card-text" style="font-size:16px">Configure automatic backups for the WordPress every:</p>
                                                <p style="font-size:16px"> 
                                                    <select name="wptio_schedule_days" style="width: 120px">
                                                        <?php for ($i = 1; $i <= 30; $i++) : ?>
                                                            <option value="<?php echo $i ?>"><?php echo $i ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                    &nbsp;days.
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <input type="submit"  name="wptio_create_scedule" value="Schedule" class="btn btn-success btn-sm" >
                                                &nbsp;&nbsp;
                                                <input type="submit"  value="Cancel" class="btn btn-link btn-sm" >
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <p class="card-text text-success" style="font-size:18px">
                                            WordPress is scheduled for backup every <u><?php echo ($schedule_backup_option != false ) ? $schedule_backup_option['every'] : ''; ?></u> days. 
                                            <?php if ($last_backup_date != false): ?>
                                                <br><u>Last Backup:</u> <?php echo $last_backup_date['date'] ?>.
                                            <?php endif; ?>
                                            <br><u>Next Backup:</u> <?php echo ($schedule_backup_option != false ) ? $schedule_backup_option['date'] : ''; ?>.
                                        </p>
                                        <p class="text-right">
                                            <input type="hidden" name="bk-ajax-nonce" id="bk-ajax-nonce" value="<?php echo wp_create_nonce('wptio_nonce_key') ?>">
                                            <button type="submit"  onclick="wptioAjax.delete_schedule()" class="btn btn-danger btn-sm">Delete Schedule</button>

                                        </p>

                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
                <div class="col-md-6 col-lg-6">
                    <h3><a href="http://wptools.io/wptoolsio-cloud-backup-restore-plugin/" style="color:black">Safeguard the future of your online business</a></h3>
                    <p>Obviously, creating backups for your online business is essential. It’s non-negotiable. But – how do you create those backups? Conventionally, the process is time-consuming and expensive. You don’t want to pay too much, but you also don’t want to compromise. With more hackers, crashing servers and shoddy updates emerging, it’s just not worth the risk. One hiccup could be disastrous.</p>
                    <p><strong>WPtool.io Cloud Backup & Restore </strong>is an innovative WordPress plugin, engineered to help WordPress owners like you create reliable, simple and speedy backups for your sites. </p>
                    <p><strong>Need a portal to store your backups?</strong> Done!
                        As your trusted friend, we’re just as dedicated to your security, safety and success as you are.</p>
                    <h5>Three solutions. One result: Peace of mind</h5>
                    <p>
                        This is our first release. Currently, we’re offering 3 x intuitive cloud storage solutions:</p>
                    <div class="row mt-5 mb-5">
                        <div class="col-lg-4 text-center"><i class="fa fa-dropbox fa-fw fa-3x" data-trigger="click" data-toggle="tooltip" data-placement="top" title="Please Activate The Plugin In Order To Connect To The Cloud Your Storage"></i><h6 class="mt-3" data-trigger="click" data-toggle="tooltip" data-placement="top" title="Please Activate The Plugin In Order To Connect To The Cloud Your Storage">Dropbox</h6></div>
                        <div class="col-lg-4 text-center"><i class="fa fa-windows fa-fw fa-3x" data-trigger="click" data-toggle="tooltip" data-placement="top" title="Please Activate The Plugin In Order To Connect To The Cloud Your Storage"></i><h6 class="mt-3" data-trigger="click" data-toggle="tooltip" data-placement="top" title="Please Activate The Plugin In Order To Connect To The Cloud Your Storage">OneDrive</h6> <p class="text-center"><a class="btn btn-primary   btn-lg mt-5" <?php echo ($client_info['expiry_date'] == "infinate" ? 'hidden' : ''); ?> href="http://wptools.io/wptoolsio-cloud-backup-restore-plugin/">Buy Now</a></p></div>
                        <div class="col-lg-4 text-center"><i class="fa fa-google fa-fw fa-3x" data-trigger="click" data-toggle="tooltip" data-placement="top" title="Please Activate The Plugin In Order To Connect To The Cloud Your Storage"></i><h6 class="mt-3" data-trigger="click" data-toggle="tooltip" data-placement="top" title="Please Activate The Plugin In Order To Connect To The Cloud Your Storage" >Google Drive</h6></div>
                    </div>



                    <p><strong>The WPtool.io Cloud Backup & Restore WordPress Plugin </strong>is a trusted, reliable solution when it comes to backing up your sites into your preferred cloud storage location. No matter the size of your website (or the amount of data) this plugin is simple to use and start implementing immediately. In fact, we’ve made it powerful enough to handle even the biggest of sites and file sizes – benchmarked on 20GB websites – something unheard of for plugins of this nature.</p>
                    
					<div class="row mt-5 mb-5">
                        <div class="col-lg-4 text-center"></div>
                        <div class="col-lg-4 text-center"><i class="fa fa-calendar fa-fw fa-2x"></i><h6 class="mt-3">Fully Automated</h6><p> Automate Your Backups through scheduling.</p></div>
                        <div class="col-lg-4 text-center"></div>
                    </div>
					<div class="row mt-5 mb-5">
                        <div class="col-lg-4 text-center"><i class="fa fa-arrow-right fa-fw fa-2x"></i><h6 class="mt-3">Simple yet robust</h6><p>Enjoy an easy-to-use interface that helps you perform one-click backups, upload everything to the cloud, or restore reliably.</p></div>
                        <div class="col-lg-4 text-center"><i class="fa fa-refresh fa-fw fa-2x"></i><h6 class="mt-3">Migration</h6><p> Use WPtool.io to migrate your website from one server to another server – quickly and easily.</p></div>
                        <div class="col-lg-4 text-center"><i class="fa fa-money fa-fw fa-2x"></i><h6 class="mt-3">Affordable</h6><p> Enjoy a high-quality premium plug-in at highly competitive prices. Our packages start from just $24.95 per domain – without compromising the outcome. </p></div>
                    </div>
                    <div class="row mt-5 mb-5">
                        <div class="col-lg-4 text-center"><i class="fa fa-undo fa-fw fa-2x"></i><h6 class="mt-3">Reliable</h6><p> What good is a backup if it can’t be easily and effectively restored? The WPtool.io Cloud Backup & Restore WordPress Plugin has undergone rigorous testing.</p></div>
                        <div class="col-lg-4 text-center"><i class="fa fa-cloud fa-fw fa-2x"></i><h6 class="mt-3">Safe in the Cloud</h6><p> Need to save it to the cloud? Just say the word. You can make sure your website backups is safe in the most secure cloud storage you prefer.</p></div>
                        <div class="col-lg-4 text-center"><i class="fa fa-tachometer fa-fw fa-2x"></i><h6 class="mt-3">Advanced</h6><p>Download the trial today to safeguard the future of not just your website, but your business as a whole!</p></div>
                    </div>
                    <div class="row">

                        <div class="col-lg-2 pt-4"><i class="fa fa-phone fa-fw fa-5x"></i></div>
                        <div class="col-lg-10"><h5>Get in touch</h5> <p>
                                What could be more important than securing your online business? WPtool.io Cloud Backup & Restore is the easiest, fastest and most trusted way to backup and restore your online business. Safeguard the integrity of your company by investing in not just a plugin, but a more secure future. Why not request your free 30-day trial license (per domain) now? Once you’ve tried it, you won’t know how you survived without it. 
                            </p></div> 

                    </div>   




                </div>


            </div>

        </div>

        <script type="text/javascript">


            jQuery(function () {
                jQuery('[data-toggle="tooltip"]').tooltip();



            });

        </script>
    </body>

</html>