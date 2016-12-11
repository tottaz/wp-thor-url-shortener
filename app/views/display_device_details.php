<div class="thor-device-wrap">
		<h2 class=""><?php _e('FCM › Single Device','fcm'); ?></h2>
		<div id="poststuff">		
			<div id="post-body" class="metabox-holder columns-1"> 
			<!-- Content starts here -->
				<div id="post-body-content">
					<div class="postbox">
						<h3><?php _e('Information','fcm'); ?></h3>
						<div class="inside">
							<div class="row">
								<div class="col-md-6">
									<div class="row">
										<div class="col-md-6">
											<label><?php _e('Database ID','fcm'); ?></label>
										</div>
										<div class="col-md-6">
											<label><?php echo $id; ?></label>
										</div>
										<div class="col-md-6">
											<label><?php _e('FCM ID','fcm'); ?></label>
										</div>
										<div class="col-md-6">
											<label><?php echo $regId; ?></label>
										</div>
										<div class="col-md-6">
											<label><?php _e('Device OS','fcm'); ?></label>
										</div>
										<div class="col-md-6">
											<label><?php echo $os; ?></label>
										</div>
										<div class="col-md-6">
											<label><?php _e('Device Model','fcm'); ?></label>
										</div>
										<div class="col-md-6">
											<label><?php echo $model; ?></label>
										</div>
										<div class="col-md-6">
											<label><?php _e('Registration Date','fcm'); ?></label>
										</div>
										<div class="col-md-6">
											<label><?php echo $new_date; ?></label>
										</div>
									</div>
								</div>
							<div class="col-md-6">
								<canvas id="msgChart" width="250" height="250"></canvas>
							</div>
						</div> 
					</div>
				</div>
				
				<div id="postbox-container-1" class="postbox-container">
					<div class="postbox">
						<h3><?php _e('Write a Message to this Device','fcm'); ?></h3>
						<div class="inside">
							<form class="form-horizontal" method="post" action="#">
							  <div class="form-group">
							    <label class="col-sm-4 control-label"><?php _e('Enter your message','fcm'); ?></label>
							    <div class="col-sm-8">
									<textarea class="form-control" id="message" name="message" type="text" cols="50" rows="5" ></textarea>
									<p class="description"><?php _e('When using HTML, your App must also decode HTML','fcm'); ?></p>
							    </div>
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
					</div>
				</div>	
			</div> 
			<br class="clear">				
		</div>
	</div>
	<script type="text/javascript">
		var ctx = document.getElementById("msgChart").getContext("2d");
		var options = { animationEasing: "easeOutQuart" };
		var data = [
			{
				value: <?php echo $num ?>,
				color:"#2980b9",
				highlight: "#3498db",
				label: "<?php _e('Messages sent to this device','fcm'); ?>"
			},
			{
				value: <?php echo $total ?>,
				color: "#bdc3c7",
				highlight: "#ecf0f1",
				label: "<?php _e('Total Messages sent','fcm'); ?>"
			}];
		var msgChart = new Chart(ctx).Doughnut(data, options);			
	</script>