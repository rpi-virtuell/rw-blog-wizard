<?php 

class RW_Blog_Wizard_Installer {
	
	protected $plugins = array();
	
	function __construct($plugins = array() ){
		$this->plugins = $plugins;	
		if(count(plugins)>0){
			$this->install_plugins();
		}
	}
	
	public function add_plugin( $slug, $zip_url = false , $exec = false ){
		
		if(!$zip_url){
			 $args = array(
				'slug' => $slug, 
				'fields' => array(
					'version' => true
				)
			);

			// Make request and extract plug-in object. Action is query_plugins
			$response = wp_remote_post(
				'http://api.wordpress.org/plugins/info/1.0/',
				array(
					'body' => array(
						'action' => 'plugin_information',
						'request' => serialize((object)$args)
					)
				)
			);
			//
			if ( !is_wp_error($response) ) {
				$returned_object = unserialize(wp_remote_retrieve_body($response));   

				if ($returned_object) {
					$download_link = $returned_object->download_link;
					//$name = $returned_object->name;
					//print_r($download_link);
					$zip_url = download_link;
				}
				else {
					// Response body does not contain an object/array
					echo "An error has occurred.";
				}
			}
			else {
				// Error object returned
				echo "An error has occurred";
			}
		}
		
		$exec = $exec ? $exec : $slug.'.php';
		
		$this->plugins[$slug] = array(
			'slug' => $slug,
			'url' => $zip_url,
			'install_path' => WP_PLUGIN_DIR.'/'.$slug.'.zip',
			'path' => WP_PLUGIN_DIR.'/'.$slug,
			'exec' => $slug.'/'.$exec
		);
		
		
	}
	
	protected function install_plugins()
	{
		
		$plugins = $this->plugins;
		$args = array(
				'path' => WP_PLUGIN_DIR.'/',
				'preserve_zip' => false
		);

		foreach($plugins as $plugin)
		{
				$plugin_path = realpath($plugin['path']);
				if($plugin_path !== false AND is_dir($plugin_path)){
					$this->mm_plugin_activate($plugin['exec']);
				}else{
					$this->mm_plugin_download($plugin['url'], $plugin['install_path']);
					$this->mm_plugin_unpack($args, $plugin['install_path'] );
					$this->mm_plugin_activate($plugin['exec']);	
				}
		}
	}
	
	protected function mm_plugin_download($url, $path) 
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$data = curl_exec($ch);
		curl_close($ch);
		if(file_put_contents($path, $data))
				return true;
		else
				return false;
	}
	
	protected function mm_plugin_unpack($args, $target)
	{
		if($zip = zip_open($target))
		{
				while($entry = zip_read($zip))
				{
						$is_file = substr(zip_entry_name($entry), -1) == '/' ? false : true;
						$file_path = $args['path'].zip_entry_name($entry);
						if($is_file)
						{
								if(zip_entry_open($zip,$entry,"r")) 
								{
										$fstream = zip_entry_read($entry, zip_entry_filesize($entry));
										file_put_contents($file_path, $fstream );
										chmod($file_path, 0777);
										//echo "save: ".$file_path."<br />";
								}
								zip_entry_close($entry);
						}
						else
						{
								if(zip_entry_name($entry))
								{
										mkdir($file_path);
										chmod($file_path, 0777);
										//echo "create: ".$file_path."<br />";
								}
						}
				}
				zip_close($zip);
		}
		if($args['preserve_zip'] === false)
		{
				unlink($target);
		}
	}
	
	protected function mm_plugin_activate($installer)
	{
		$current = get_option('active_plugins');
		$plugin = plugin_basename(trim($installer));

		if(!in_array($plugin, $current))
		{
				$current[] = $plugin;
				sort($current);
				do_action('activate_plugin', trim($plugin));
				update_option('active_plugins', $current);
				do_action('activate_'.trim($plugin));
				do_action('activated_plugin', trim($plugin));
				return true;
		}
		else
				return false;
	}
}