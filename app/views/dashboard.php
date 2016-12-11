<div class="thor-stats-wrap">
	<h2><?php _e('FCM › Dashboard','fcm'); ?></h2>
	<div id="poststuff">		
		<div id="post-body" class="metabox-holder columns-1"> 
		<!-- Content starts here -->
			<div id="post-body-content">
				<i><?php _e('Click on the Charts to view them in fullsize', 'fcm'); ?></i>
				<div class="postbox">
					<div class="inside">
						<p><b><?php _e('Total number of sent messages','fcm'); ?>:</b> &nbsp; &nbsp;
						<b id="totalChart" class="thor_txt_big"> </b></p>
						
						<br>
						
						<table class="table table-striped">
							<tr>
								<td>
									<b><?php _e('Device Models','fcm'); ?>:</b><br>
								</td>
								<td>
									<canvas id="modelChart" width="200" height="200" onclick="big('1')" ></canvas>
									<canvas id="modelChartBig" width="500" height="500" onclick="small('1')" ></canvas>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php _e('Device OS','fcm'); ?>:</b><br>
								</td>
								<td>
									<canvas id="osChart" width="200" height="200" onclick="big('2')" ></canvas>
									<canvas id="osChartBig" width="500" height="500" onclick="small('2')" ></canvas>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php _e('Messages success/fail','fcm'); ?>:</b><br>
								</td>
								<td>
									<canvas id="sfChart" width="200" height="200" onclick="big('3')" ></canvas>
									<canvas id="sfChartBig" width="500" height="500" onclick="small('3')" ></canvas>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</div> 
		<br class="clear">				
	</div>
</div>

<script type="text/javascript">
	var total = document.getElementById("totalChart");
	var model = document.getElementById("modelChart");
	var modelBig = document.getElementById("modelChartBig");
	
	var os = document.getElementById("osChart");
	var osBig = document.getElementById("osChartBig");
	
	var sf = document.getElementById("sfChart");
	var sfBig = document.getElementById("sfChartBig");
	
	//hide big ones
	modelBig.style.display = 'none';
	osBig.style.display = 'none';
	sfBig.style.display = 'none';
	
	
	// Charts
	var options = { animationEasing: "easeOutQuart" };
	
	var oData = [
		<?php
		$i = 0;
		foreach($resO as $row) {
			$i++;
			?>
			{
				value: <?php echo $row['COUNT(*)']; ?>,
				color: "<?php echo $color1[$i]; ?>",
				highlight: "<?php echo $color2[$i]; ?>",
				label: "<?php echo $row['os']; ?>"
			},
		<?php
		}			
	?>];
	
	var mData = [
		<?php
		$i = 0;
		foreach($resM as $row) {
			$i++;
			?>
			{
				value: <?php echo $row['COUNT(*)']; ?>,
				color: "<?php echo $color1[$i]; ?>",
				highlight: "<?php echo $color2[$i]; ?>",
				label: "<?php echo $row['model']; ?>"
			},
		<?php
		}			
	?>];
	
	var sfData = [
		{
			value: <?php echo get_option('thor_fcm_success_msg',0); ?>,
			color: "#27ae60",
			highlight: "#2ecc71",
			label: "<?php _e('sent successfully','thor_fcm'); ?>"
		},
		{
			value: <?php echo get_option('thor_fcm_fail_msg',0); ?>,
			color: "#c0392b",
			highlight: "#e74c3c",
			label: "<?php _e('sent unsuccessfully','thor_fcm'); ?>"
		},
	];
	
	var osChart = new Chart(os.getContext("2d")).Pie(oData, options);
	var osChartBig = new Chart(osBig.getContext("2d")).Pie(oData, options);
	
	var mChart = new Chart(model.getContext("2d")).Pie(mData, options);
	var mChartBig = new Chart(modelBig.getContext("2d")).Pie(mData, options);

	var sfChart = new Chart(sf.getContext("2d")).Pie(sfData, options);
	var sfChartBig = new Chart(sfBig.getContext("2d")).Pie(sfData, options);


	// Count Up
	var numAnim = new countUp(total, 0, <?php echo get_option('thor_fcm_total_msg', 0); ?>);
	numAnim.start();
	
	
	// bigger / smaller the charts
	function big(id) {
		if(id == 1) {
			model.style.display = 'none';
			modelBig.style.display = 'block';
		}else if(id == 2) {
			os.style.display = 'none';
			osBig.style.display = 'block';
		}else if(id == 3) {
			sf.style.display = 'none';
			sfBig.style.display = 'block';
		}
	}
	
	function small(id) {
		if(id == 1) {
			model.style.display = 'block';
			modelBig.style.display = 'none';
		}else if(id == 2) {
			os.style.display = 'block';
			osBig.style.display = 'none';
		}else if(id == 3) {
			sf.style.display = 'block';
			sfBig.style.display = 'none';
		}
	}
	
</script>