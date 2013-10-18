<?php
	global $wpdb;

	add_thickbox();

	//resolve main URL
	$baseurl = 'admin.php?page=hcf_contact';
	$url = $baseurl . '&amp;view_form_sub_id=' . $_GET['view_form_sub_id'];

	//re-set defaults
	if(!isset($_SESSION['hcf-subs-per-page'])) $_SESSION['hcf-subs-per-page'] = 25;
	if(!isset($_SESSION['hcf-sub-cur-page'])) $_SESSION['hcf-sub-cur-page'] = 1;


	//set items per page
	if(isset($_GET['itemsperpage']) && is_numeric($_GET['itemsperpage'])){
	    $_SESSION['hcf-subs-per-page'] = $_GET['itemsperpage'];
	    $_SESSION['hcf-sub-cur-page'] = 1;
	    hcf_redirect_backtobase($url);
	}

	//set page number
	if(isset($_GET['p']) && is_numeric($_GET['p'])){
	    $_SESSION['hcf-sub-cur-page'] = $_GET['p'];
	    hcf_redirect_backtobase($url);
	}

	//If we've selected a form show that istead of the first one...
	if(isset($_GET['view_form_sub_id']) && is_numeric($_GET['view_form_sub_id'])){
		$query = "SELECT * FROM " . $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME . " WHERE form_id = " . $_GET['view_form_sub_id'] . ' ORDER BY date_submitted desc ';
		$limit = "LIMIT " . $_SESSION['hcf-subs-per-page']*($_SESSION['hcf-sub-cur-page']-1) . ", {$_SESSION['hcf-subs-per-page']}";

        $currentFormSubmissions = $wpdb->get_results($query . $limit);
        $numRows = count($wpdb->get_results($query));

		$currentFormSettings = json_decode(stripslashes($wpdb->get_var('SELECT form_settings FROM ' . $wpdb->prefix . HCF_FORM_TABLE_NAME . ' WHERE id = ' . $_GET['view_form_sub_id'])));
		$currentFormName = $currentFormSettings->formName;
	}

	//Delete any boxes that are selected.
	if(isset($_POST['massdel'])){
	    //DELETE ORDERS (not really delete but set them as IsDelted)
	    $delQry = "DELETE FROM " . $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME . " WHERE ID IN ({$_POST['massdel']}) ";
	    $wpdb->query($delQry);
	    $wpdb->flush();

	    hcf_redirect_backtobase($url . "&amp;msg=deletedmultiple");
	}

	//Check for any single deletions
	if(isset($_GET['delitem'])){
		$delQry = "DELETE FROM " . $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME . " WHERE ID = " . $_GET['delitem'];
	    $wpdb->query($delQry);
	    $wpdb->flush();

	    hcf_redirect_backtobase($url . "&amp;msg=deleted");
	}

	//Check for any single deletions
	if(isset($_GET['delall'])){
		$delQry = "DELETE FROM " . $wpdb->prefix . HCF_SUBMISSION_TABLE_NAME;
	    $wpdb->query($delQry);
	    $wpdb->flush();

	    hcf_redirect_backtobase($url . "&amp;msg=deletedmultiple");
	}
?>

<div class="wrap">
	<h2>Form Submissions for <em>&ldquo;<?php echo $currentFormName; ?>&rdquo;</em></h2>
	<?php if(isset($_GET['msg'])): ?>
		<?php if($_GET['msg'] = 'deletedmultiple' || $_GET['msg'] = 'deleted'): ?>
			<div class="updated">
				<p>Submission(s) Deleted</p>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php //Print out the submissions for the first form ?>
	<?php if(isset($currentFormSubmissions) && $currentFormSubmissions):?>
		<!-- EXPORT BUTTONS -->
        <div style="width:98px; float:left;padding-top: 20px; margin-bottom:20px;">
            <form target="_blank" id="exportall" name="exportall" action="<?php echo get_bloginfo('url') . HCF_CSV_EXPORT_URL; ?>" method="post">
            	<input type="hidden" id="massexport" name="massexport" value="all" />
            	<input type="hidden" id="export_fields" name="export_fields" value="" />
            	<input type="hidden" id="form_id" name="form_id" value="<?php echo $_GET['view_form_sub_id']; ?>" />
                <input type="button" class="button-primary" value="Export All" onclick="show_field_box();" /><br/><br/>
            </form>
        </div>

        <!-- DELETE BUTTONS -->
		<div style="width:130px; float:left;padding-top: 20px; margin-bottom:20px;">
            <form id="deleteall" name="deleteall" action="<?php echo $url; ?>" method="post">
                <input type="button" class="button-secondary" value="Delete all selected" onclick="DeleteAllSelected();" /><br/><br/>
                <input type="hidden" id="massdel" name="massdel" value="" />
            </form>
        </div>
        <div style="width:130px; float:left;padding-top: 20px; margin-bottom:20px;">
            <form id="deleteall" name="deleteall" action="<?php echo $url; ?>" method="post">
                <input type="button" class="button-secondary" value="Clear Submissions" onclick="DeleteAll();" /><br/><br/>
            </form>
        </div>
        <?php
            echo '<div style="float:right; padding-top: 20px; margin-bottom: 20px">
                    Page: <select id="pagenumbers" name="pagenumbers" onchange="window.location=\'' . $url .'&amp;p=\' + this.options[this.selectedIndex].value;">
                        '.hcf_paint_paging($numRows,$_SESSION['hcf-subs-per-page'],$_SESSION['hcf-sub-cur-page']).'
                    </select>&nbsp;&nbsp;
                    Items per Page: <select id="pagenumbers_items" name="pagenumbers_items" onchange="window.location=\'' . $url .'&amp;itemsperpage=\' + this.options[this.selectedIndex].value;">
                        '.hcf_paint_items_per_page($_SESSION['hcf-subs-per-page']) .'
                    </select>
                </div>';
        ?>
        <div style="clear:both;"><!-- EMPTY --></div>

        <!-- Pick the fields for the CSV -->
        <div id="field-box" style="display:none;">
        	<h3>Pick Fields</h3>
		<?php
			$fieldAr = hcf_get_form_submission_fields($_GET['view_form_sub_id']);
			foreach($fieldAr as $fa){
				echo '<label><input type="checkbox" name="fields" checked="checked" value="' . $fa . '"/> ' . $fa . '</label><br/>';
			}
			echo '<input type="button" id="fieldselect" class="button-primary" value="Download CSV" />';
		?>

		</div>
		<table class="table page widefat">
			<thead>
				<tr>
					<th><input type="checkbox" id="massdelete_all" name="massdelete_all"></th>
					<th>Date</th>
					<th>Title</th>
					<th>Submission</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($currentFormSubmissions as $submission): ?>
					<tr>
						<td><input type="checkbox" class="massdelete" name="selected[]" value="<?php echo $submission->id; ?>"></td>
						<td><?php echo date('d/m/Y H:i', strtotime($submission->date_submitted)); ?></td>
						<td><?php echo stripslashes(hcf_html_format_submission($submission->submission, $firstOnly = true)); ?>
						</td>
						<td><?php echo stripslashes(hcf_html_format_submission($submission->submission, $firstOnly = false)); ?></td>
						<td align="right">
							<a href="<?php echo $url; ?>&amp;sub_id=<?php echo $submission->id; ?>">View</a>&nbsp;&nbsp;|&nbsp;
						    <a href="Javascript:del_itm(<?php echo $submission->id; ?>)">Delete</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p class="description">...No submissions for this form</p>
	<?php endif; ?>
</div>



<script type="text/javascript">
    <!--
        jQuery(document).ready(function () {
            jQuery('.massdelete').click(function () {
                calccheckboxes();
            });

            jQuery('#massdelete_all').click(function () {
                if (jQuery(this).attr('checked')) {
                    jQuery('.massdelete').attr('checked', true);
                } else {
                    jQuery('.massdelete').attr('checked', false);
                }
            });

            jQuery('#fieldselect').click(function(){
            	pick_rows();
            })
        });

        function calccheckboxes() {
            var chk = false;
            jQuery('.massdelete').each(function () {
                if (jQuery(this).attr('checked')) {
                    chk = true;
                }
            });

            if (chk) {
                jQuery('#massdelete_all').attr('checked', true);
            } else {
                jQuery('#massdelete_all').attr('checked', false);
            }
            return chk;
        }

        function DeleteAllSelected() {
            if (calccheckboxes()) {
                if (confirm('Are you sure you want to delete all selected boxes?')) {
                    var boxes = '';
                    jQuery('.massdelete').each(function () {
                        if (jQuery(this).attr('checked')) {
                            if (boxes == '') {
                                boxes = jQuery(this).val();
                            } else {
                                boxes = boxes + ',' + jQuery(this).val();
                            }
                        }
                    });

                    jQuery('#massdel').val(boxes);
                    //alert(jQuery('#massdel').val());
                    jQuery('#deleteall').submit();
                }
            } else {
                alert('Please select boxes first.');
            }
        }

        function del_itm(val){
            if(confirm('Are you sure you want to delete this submission')){
                window.location="<?php echo str_ireplace('&amp;','&',$url); ?>&delitem=" + val;
            }
        }

        function DeleteAll(){
            if(confirm('This will delete all records!!  Are you sure?')){
                window.location="<?php echo str_ireplace('&amp;','&',$url); ?>&delall=1";
            }
        }

        function show_field_box(){
        	jQuery('#field-box').show();
        }

        function pick_rows(){
        	var fields = '';

        	jQuery('#field-box input[name="fields"]:checked').each(function(index, elem){
        		fields = fields + jQuery(elem).val() + ", ";
        	});

        	if(fields.length > 2) jQuery('#export_fields').val(fields.substr(0,fields.length-2));

        	if(fields == ''){
        		alert("Please select at least one field!");
        	}else{
        		jQuery('#exportall').submit();
        	}
        }

    //-->
</script>