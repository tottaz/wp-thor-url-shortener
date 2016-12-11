<div class="thor-dashboard-wrap">
	<div class="thor-admin-header">
		<div class="thor-top-menu-section">
			<div class="thor-dashboard-logo">
				<div class="thor-dashboard-text text-center">
					<h2><?php echo "WP THOR <br> FCM"?></h2>
				</div>	
			</div>
			<div class="thor-dashboard-menu">
				<ul>
				<?php 
					foreach($tabs_arr as $k=>$v){
						$selected = '';
						if($tab==$k) $selected = 'selected';						
						?>
							<li class="<?php echo $selected;?>">
								<a href="<?php echo $url.'&tab='.$k;?>">
									<div class="thor-page-title">
										<i class="fa-thor fa-thor-menu fa-<?php echo $k;?>-thor"></i>
										<div><?php echo $v;?></div>								
									</div>						
								</a>
							</li>	
						<?php 	
					}
				?>		
				</ul>
			</div>
		</div>
	</div>
<?php 

$themes_dir = str_replace('plugins/wp-thor-fcm','themes', THORFCM_PLUGIN_PATH);
