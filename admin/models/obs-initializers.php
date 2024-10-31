<?php

include_once plugin_dir_path( __FILE__ ) . '../class-obs-admin-authentication.php';
                                                             
$models = glob(plugin_dir_path( __FILE__ ) . 'models/*.php');                                                                                                              
foreach($models as $model) {                                                                                                                                               
	if($model != __FILE__) {                                                                                                                                                 
		include_once $model;                                                                                                                                                   
    }                                                                                                                                                                        
}

?>
