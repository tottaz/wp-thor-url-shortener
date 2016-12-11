<div class="thor-message-wrap">
	<h2><?php _e('FCM › New Message','fcm'); ?></h2>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-1"> 
			<!-- main content -->
			<div id="post-body-content">
				<div class="inside">
					<form class="form-horizontal" method="post" action="#">
						  <div class="form-group">
						    <label class="col-sm-4 control-label"><?php _e('Enter your message','fcm'); ?></label>
						    <div class="col-sm-8">
								<textarea class="form-control" id="message" name="message" type="text" cols="50" rows="5" ></textarea>
								<p class="description"><?php _e('When using HTML, your App must also decode HTML','fcm'); ?></p>
						    </div>
						  </div>
						  <div class="form-group">
						    <label class="col-sm-4 control-label"><?php _e('Priority (10 = highest)', 'fcm'); ?></label>
						    <div class="col-sm-8">
								<select class="form-control" name="priority">
									<option>0</option>
									<option>1</option>
									<option>2</option>
									<option>3</option>
									<option>4</option>
									<option>5</option>
									<option>6</option>
									<option>7</option>
									<option>8</option>
									<option>9</option>
									<option>10</option>
								</select>
						    </div>
						  </div>

						  <div class="form-group">
						    <label class="col-sm-4 control-label"><?php _e('Time to live until self destruction (in seconds)', 'fcm'); ?></label>
						    <div class="col-sm-8">
								<input type="number" id="time" name="time" size="50" value="2419200" />
								<p class="description"><?php _e('Default 4 weeks', 'fcm'); ?></p>
						    </div>
						  </div>

						<?php
								$options = get_option('thor_fcm_settings');
								if(isset($options['thor_fcm_field_api_key'])) {
									submit_button(__('Send','thor_fcm'));
								} else {
									echo '<p style="color: red;"><b>'+__('First setup the settings', 'fcm')+ '</b></p>';
								}
								?>
					 </form>
				</div>
				<p><b><?php _e('Info','thor_fcm'); ?> &nbsp;&nbsp;</b> <?php echo $info ?></p>
			</div>
		</div> 
		<br class="clear">
	</div>
</div> 
<?