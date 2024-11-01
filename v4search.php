<?php
/**
 * @package v4Search
 * @version 0.0.2
 */
/*
Plugin Name: v4Search
Plugin URI: 
Description: V4 Search is a plugin that helps you understand what users are searching in your Wordpress Website.
Author: ItsValentin
Version: 0.0.2
Author URI: https://itsvalentin.com
*/


global $wpdb;


wp_enqueue_script( 'ajax-script', plugins_url('vlab_script.js', __FILE__), array(), '1.0.0', true );

wp_localize_script( 'ajax-script', 'ajax_object',
    array( 
    	'ajax_url' => admin_url( 'admin-ajax.php' ) 
    ) 
);


if (!function_exists('vlab_pluginInstall')) {
	function vlab_pluginInstall() {
	  global $wpdb;
		$ex = $wpdb->query("SHOW TABLES LIKE 'v4_search'");

		if(empty($ex)){

		$wpdb->query("CREATE TABLE `v4_search` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `params` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
		  `date` varchar(255) NOT NULL,
		  `count` int(11) NOT NULL,
		  `user_id` varchar(255) NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");


		}
	}
}

register_activation_hook( __FILE__, 'vlab_pluginInstall' );

$lang = array(
	'results' => 'Results',
	'delete_all' => 'Delete Everything',
	'clear' => 'Clear',
	'results_for' => 'Results for',
	'user_key' => 'User Key',
	'keyword' => 'Keyword',
	'date' => 'Date',
	'options' => 'Options',
	'delete' => 'Delete',
);

add_action( "wp_ajax_delete_single", "vlab_delete_single_function" );
add_action( "wp_ajax_nopriv_delete_single", "vlab_delete_single_function" );
add_action( "wp_ajax_delete_everything", "vlab_delete_everything_function" );
add_action( "wp_ajax_nopriv_delete_everything", "vlab_delete_everything_function" );


if (!function_exists('vlab_delete_everything_function')) {
	function vlab_delete_everything_function(){
		global $wpdb;
		$wpdb->query("TRUNCATE TABLE v4_search");

		wp_die();
	}
}


if (!function_exists('vlab_delete_single_function')) {
	function vlab_delete_single_function(){
		global $wpdb;
		if(empty($_POST['id'])){
			return;
		}
		
		$id = sanitize_text_field($_POST['id']);
		$id = (int)$id;

		$wpdb->query("DELETE FROM v4_search WHERE id = '$id'");

	  	wp_die(); 
	}
}



	
if(!empty($_GET['s'])){

	$s = sanitize_text_field($_GET['s']);
	
	if(empty($_COOKIE['v4_user'])){
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < 15; $i++) {
	        $randomS .= $characters[rand(0, $charactersLength - 1)];
	    }

		setcookie('v4_user', $randomS, 0, "/"); 
		$v4_user = $randomS;
	}else{
		$v4_user = sanitize_text_field($_COOKIE['v4_user']);
	}

	$v4_user = sanitize_text_field($v4_user);

	$today = date('d.m.Y');
	$ex_same_search = $wpdb->query("SELECT * FROM v4_search WHERE user_id = '$v4_user' AND params = '$s' AND `date` = '$today'");
	if(!empty($ex_same_search)){
		$wpdb->query("UPDATE v4_search SET count = count + 1 WHERE user_id = '$v4_user' AND params = '$s' AND `date` = '$today'");
	}else{
	
		$wpdb->query("INSERT INTO v4_search SET user_id = '$v4_user', params = '$s', `date` = '$today', count = 1");
	}
}

if (!function_exists('vlab_admin_page')) {

	function vlab_admin_page(){
		global $wpdb, $lang;
		$moreQ = '';
		$moreU = '';
		$moreT = '';
		$moreA = admin_url().'admin.php?page=v4search';

		if(!empty($_GET['params'])){
			$params = sanitize_text_field($_GET['params']);
			$moreQ .= " AND params = '".$params."'";
			$moreU .= '&params='.$params;
			$moreT = $lang['results_for'].' <u>'. $params.'</u> ('.$lang['keyword'].')';
		}

		if(!empty($_GET['user_id'])){
			$user_id = sanitize_text_field($_GET['user_id']);
			$moreQ .= " AND user_id = '".$user_id."'";
			$moreU .= '&user_id='.$user_id;
			$moreT = $lang['results_for'].' <u>'. $user_id.'</u> ('.$lang['user_key'].')'; 
		}	

		if(!empty($_GET['date'])){
			$date = sanitize_text_field($_GET['date']);
			$moreQ .= " AND date = '".$date."'";
			$moreU .= '&date='.$date;
			$moreT = $lang['results_for'].' <u>'. $date.'</u> ('.$lang['date'].')'; 
		}

		$html = '<center><h1>'.$lang['results'].'</h1><a class="vlab_clickable" onclick="deleteEverything()">'.$lang['delete_all'].'</a><br><br>';

		if(!empty($moreT)){
			$html .= '<h2>'.$moreT.' - <a href="'.$moreA.'">'.$lang['clear'].'</a></h2><br><br>';
		}

		$html .= '<table style="width:50%;" class="v4_table">';	
		$html .= '<tr>';
		$html .= '<th><b>'.$lang['user_key'].'</b></th>';
		$html .= '<th><b>'.$lang['keyword'].'</b></th>';
		$html .= '<th><b>'.$lang['date'].'</b></th>';
		$html .= '<th><b>'.$lang['options'].'</b></th>';
		$html .= '</tr>';

		$selectS = $wpdb->get_results("SELECT * FROM v4_search WHERE 1=1 $moreQ");

		foreach($selectS AS $s){
			$html .= '<tr id="v4_row_'.$s->id.'" class="v4_all_rows">';
			$html .= '<td><a href="'.$moreA.'&user_id='.$s->user_id.'">'.$s->user_id.'</a></td>';
			$html .= '<td><a href="'.$moreA.'&params='.$s->params.'">'.$s->params.'</a></td>';
			$html .= '<td><a href="'.$moreA.'&date='.$s->date.'">'.$s->date.'</a></td>';
			$html .= '<td><a class="vlab_clickable" onclick="deleteSingle('.$s->id.')">'.$lang['delete'].'</a></td>';
			$html .= '</tr>';	
		}

		$html .= '</table></center>';
		
		echo $html;
	}
}
	
if (!function_exists('vlab_search_menu_custom')) {
	add_action('admin_menu', 'vlab_search_menu_custom');
	function vlab_search_menu_custom(){
	    add_menu_page( 'V4 Search', 'V4 Search', 'manage_options', 'v4search', 'vlab_admin_page', 'dashicons-search');
	}
}

wp_enqueue_style( 'v4_style', plugins_url('css/vlab_style.css', __FILE__));