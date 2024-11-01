<ul>
    <li class="field">
    	<h4><?php _e( 'User Information' ); ?></h4>
    	<ul>
    		<li><?php _e( 'Public IP Address'); ?>: <strong><a target="_blank" href="http://whois.domaintools.com/<?php echo $_SERVER['REMOTE_ADDR']; ?>"><?php echo $_SERVER['REMOTE_ADDR']; ?></a></strong></li>
    		<li><?php _e( 'User Agent'); ?>: <strong><?php echo filter_var( $_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING ); ?></strong></li>
    	</ul>
    </li>
    <li class="field">
		<h4><?php _e( 'File System Information'); ?></h4>
		<ul>
			<li><?php _e( 'Website Root Folder'); ?>: <strong><?php echo get_site_url(); ?></strong></li>
			<li><?php _e( 'Document Root Path'); ?>: <strong><?php echo filter_var( $_SERVER['DOCUMENT_ROOT'], FILTER_SANITIZE_STRING ); ?></strong></li>
			<?php 
				$htaccess = ABSPATH . '.htaccess';
				
				if ( $f = @fopen( $htaccess, 'a' ) ) { 
				
					@fclose( $f );
					$copen = '<font color="red">';
					$cclose = '</font>';
					$htaw = __( 'Yes'); 
					
				} else {
				
					$copen = '';
					$cclose = '';
					$htaw = __( 'No.'); 
					
				}
				
				if ( $bwpsoptions['st_fileperm'] == 1 ) {
					@chmod( $htaccess, 0444 ); //make sure the config file is no longer writable
				}
			?>
			<li><?php _e( '.htaccess File is Writable'); ?>: <strong><?php echo $copen . $htaw . $cclose; ?></strong></li>
			<?php 
				$conffile = ABSPATH . 'wp-config.php';
				
				if ( $f = @fopen( $conffile, 'a' ) ) { 
				
					@fclose( $f );
					$copen = '<font color="red">';
					$cclose = '</font>';
					$wconf = __( 'Yes'); 
					
				} else {
				
					$copen = '';
					$cclose = '';
					$wconf = __( 'No.'); 
					
				}
				
				if ( $bwpsoptions['st_fileperm'] == 1 ) {
					@chmod( $conffile, 0444 ); //make sure the config file is no longer writable
				}
			?>
			<li><?php _e( 'wp-config.php File is Writable'); ?>: <strong><?php echo $copen . $wconf . $cclose; ?></strong></li>
		</ul>
	</li>
    
	<li class="field">
		<h4><?php _e( 'Server Information'); ?></h4>
		<?php $server_addr = array_key_exists('SERVER_ADDR',$_SERVER) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR']; ?>
		<ul>
			<li><?php _e( 'Server / Website IP Address'); ?>: <strong><a target="_blank" title="<?php _e( 'Get more information on this address'); ?>" href="http://whois.domaintools.com/<?php echo $server_addr; ?>"><?php echo $server_addr; ?></a></strong></li>
				<li><?php _e( 'Server Type'); ?>: <strong><?php echo filter_var( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ), FILTER_SANITIZE_STRING ); ?></strong></li>
				<li><?php _e( 'Operating System'); ?>: <strong><?php echo PHP_OS; ?></strong></li>
				<li><?php _e( 'Browser Compression Supported'); ?>: <strong><?php echo filter_var( $_SERVER['HTTP_ACCEPT_ENCODING'], FILTER_SANITIZE_STRING ); ?></strong></li>
		</ul>
	</li>
    
    <li class="field">
		<h4><?php _e( 'PHP Information'); ?></h4>
		<ul>
			<li><?php _e( 'PHP Version'); ?>: <strong><?php echo PHP_VERSION; ?></strong></li>
			<li><?php _e( 'PHP Memory Usage'); ?>: <strong><?php echo round(memory_get_usage() / 1024 / 1024, 2) . __( ' MB'); ?></strong> </li>
			<?php 
				if ( ini_get( 'memory_limit' ) ) {
					$memory_limit = filter_var( ini_get( 'memory_limit' ), FILTER_SANITIZE_STRING ); 
				} else {
					$memory_limit = __( 'N/A'); 
				}
			?>
			<li><?php _e( 'PHP Memory Limit'); ?>: <strong><?php echo $memory_limit; ?></strong></li>
			<?php 
				if ( ini_get( 'upload_max_filesize' ) ) {
					$upload_max = filter_var( ini_get( 'upload_max_filesize' ), FILTER_SANITIZE_STRING );
				} else 	{
					$upload_max = __( 'N/A'); 
				}
			?>
			<li><?php _e( 'PHP Max Upload Size'); ?>: <strong><?php echo $upload_max; ?></strong></li>
			<?php 
				if ( ini_get( 'post_max_size' ) ) {
					$post_max = filter_var( ini_get( 'post_max_size' ), FILTER_SANITIZE_STRING );
				} else {
					$post_max = __( 'N/A'); 
				}
			?>
			<li><?php _e( 'PHP Max Post Size'); ?>: <strong><?php echo $post_max; ?></strong></li>
			<?php 
				if ( ini_get( 'safe_mode' ) ) {
					$safe_mode = __( 'On');
				} else {
					$safe_mode = __( 'Off'); 
				}
			?>
			<li><?php _e( 'PHP Safe Mode'); ?>: <strong><?php echo $safe_mode; ?></strong></li>
			<?php 
				if ( ini_get( 'allow_url_fopen' ) ) {
					$allow_url_fopen = __( 'On');
				} else {
					$allow_url_fopen = __( 'Off'); 
				}
			?>
			<li><?php _e( 'PHP Allow URL fopen'); ?>: <strong><?php echo $allow_url_fopen; ?></strong></li>
			<?php 
				if ( ini_get( 'allow_url_include' ) ) {
					$allow_url_include = __( 'On');
				} else {
					$allow_url_include = __( 'Off'); 
				}
			?>
			<li><?php _e( 'PHP Allow URL Include' ); ?>: <strong><?php echo $allow_url_include; ?></strong></li>
				<?php 
				if ( ini_get( 'display_errors' ) ) {
					$display_errors = __( 'On');
				} else {
					$display_errors = __( 'Off'); 
				}
			?>
			<li><?php _e( 'PHP Display Errors'); ?>: <strong><?php echo $display_errors; ?></strong></li>
			<?php 
				if ( ini_get( 'display_startup_errors' ) ) {
					$display_startup_errors = __( 'On');
				} else {
					$display_startup_errors = __( 'Off'); 
				}
			?>
			<li><?php _e( 'PHP Display Startup Errors'); ?>: <strong><?php echo $display_startup_errors; ?></strong></li>
			<?php 
				if ( ini_get( 'expose_php' ) ) {
					$expose_php = __( 'On');
				} else {
					$expose_php = __( 'Off'); 
				}
			?>
			<li><?php _e( 'PHP Expose PHP'); ?>: <strong><?php echo $expose_php; ?></strong></li>
			<?php 
				if ( ini_get( 'register_globals' ) ) {
					$register_globals = __( 'On');
				} else {
					$register_globals = __( 'Off'); 
				}
			?>
			<li><?php _e( 'PHP Register Globals'); ?>: <strong><?php echo $register_globals; ?></strong></li>
			<?php 
				if ( ini_get( 'max_execution_time' ) ) {
					$max_execute = ini_get( 'max_execution_time' );
				} else {
					$max_execute = __( 'N/A'); 
				}
			?>
			<li><?php _e( 'PHP Max Script Execution Time' ); ?>: <strong><?php echo $max_execute; ?> <?php _e( 'Seconds' ); ?></strong></li>
			<?php 
				if ( ini_get( 'magic_quotes_gpc' ) ) {
					$magic_quotes_gpc = __( 'On');
				} else {
					$magic_quotes_gpc = __( 'Off'); 
				}
			?>
			<li><?php _e( 'PHP Magic Quotes GPC'); ?>: <strong><?php echo $magic_quotes_gpc; ?></strong></li>
			<?php 
				if ( ini_get( 'open_basedir' ) ) {
					$open_basedir = __( 'On');
				} else {
					$open_basedir = __( 'Off'); 
				}
			?>
			<li><?php _e( 'PHP open_basedir'); ?>: <strong><?php echo $open_basedir; ?></strong></li>
			<?php 
				if ( is_callable( 'xml_parser_create' ) ) {
					$xml = __( 'Yes');
				} else {
					$xml = __( 'No'); 
				}
			?>
			<li><?php _e( 'PHP XML Support'); ?>: <strong><?php echo $xml; ?></strong></li>
			<?php 
				if ( is_callable( 'iptcparse' ) ) {
					$iptc = __( 'Yes');
				} else {
					$iptc = __( 'No'); 
				}
			?>
			<li><?php _e( 'PHP IPTC Support'); ?>: <strong><?php echo $iptc; ?></strong></li>
			<?php 
				if ( is_callable( 'exif_read_data' ) ) {
					$exif = __( 'Yes' ). " ( V" . substr(phpversion( 'exif' ),0,4) . ")" ;
				} else {
					$exif = __( 'No'); 
				}
			?>
			<li><?php _e( 'PHP Exif Support'); ?>: <strong><?php echo $exif; ?></strong></li>
		</ul>
	</li>
</ul>