<?php 

	function plugin_owner_fix_init(){
		
		if(get_context() == "admin" && stristr(get_input("page"), "plugins")){
			if($plugins = get_entities("object", "plugin", 0, "time_updated desc", 2)){
				$last_update = get_plugin_setting("last_update", "plugin_owner_fix");
				
				foreach($plugins as $plugin){
					if($plugin->title != "plugin_owner_fix"){
						if($plugin->time_updated > $last_update){
							plugin_owner_fix_update_all();
							
							set_plugin_setting("last_update", time(), "plugin_owner_fix");
						}
						
						break;
					}
				}
			}
		}
	}

	function plugin_owner_fix_object_event($event, $object_type, $object){
		global $CONFIG;
		global $plugin_owner_fix_update_flag;
		
		if($object->getSubtype() == "plugin" && empty($plugin_owner_fix_update_flag)){
			// set flag to prevent deadloop
			$plugin_owner_fix_update_flag = true;
			
			// change owner
			$save_needed = false;
			
			if($object->owner_guid != $CONFIG->site_guid){
				$object->owner_guid = $CONFIG->site_guid;
				$save_needed = true;
			}
			
			if($object->container_guid != $CONFIG->site_guid){
				$object->container_guid = $CONFIG->site_guid;
				$save_needed = true;
			}

			if($save_needed){
				$object->save();
			}
			
			// unset flag
			$plugin_owner_fix_update_flag = false;
		}
	}
	
	function plugin_owner_fix_update_all(){
		global $CONFIG;
		
		// fixing plugin ownership
		if($plugins = get_entities("object", "plugin", 0, null, 99999)){
			foreach($plugins as $plugin){
				$plugin->save();
			}
		}
		
		// fixing site metadata ownership
		$metadata_fields = array("enabled_plugins", "pluginorder");
		
		foreach($metadata_fields as $field){
			if($metadatas = get_metadata_byname($CONFIG->site_guid, $field)){
				if(!is_array($metadatas)){
					$metadatas = array($metadatas);
				}
				
				foreach($metadatas as $metadata){
					$metadata->owner_guid = $CONFIG->site_guid;
					$metadata->save();
				}
			}
		}
	}
	
	function plugin_owner_fix_metadata_event($event, $object_type, $metadata){
		global $CONFIG;
		global $plugin_owner_fix_metadata;
		
		// fixing site metadata ownership
		$metadata_fields = array("enabled_plugins", "pluginorder");
		
		if(!empty($metadata) && empty($plugin_owner_fix_metadata)){
			$name = $metadata->name;
			
			if(!empty($name) && in_array($name, $metadata_fields)){
				$plugin_owner_fix_metadata = true;
				
				$metadata->owner_guid = $CONFIG->site_guid;
				$metadata->save();
				
				$plugin_owner_fix_metadata = false;
			}
		}
	}
	
	// register default events
	register_elgg_event_handler("init", "system", "plugin_owner_fix_init");
	
	// register events
	register_elgg_event_handler("create", "object", "plugin_owner_fix_object_event");
	register_elgg_event_handler("update", "object", "plugin_owner_fix_object_event");
	register_elgg_event_handler("create", "metadata", "plugin_owner_fix_metadata_event");
	register_elgg_event_handler("update", "metadata", "plugin_owner_fix_metadata_event");

?>