<?php

 include_once 'obs-initializers.php';

class OBS_User {
    public static function get_user_id() {
		$query_string = '{ users { id } }';
		$users_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);

		if(!is_wp_error($users_response)) {
			$users_json = json_decode($users_response['body'], true);
			$user = $users_json['data']['users'][0]['id'];
		}

		if(!empty($user)) { return $user; }
	}
}

?>
