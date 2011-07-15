<?php
require_once('../../../wp-admin/admin.php');
function mark_as_spam($id){
	global $wpdb;
	$table = $wpdb->prefix . "comments";
	$sql = "UPDATE  ".$table." SET  comment_approved =  'spam' WHERE  comment_ID =".$id.";";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	$table = $wpdb->prefix . "smreports";
	$sql = "UPDATE  ".$table." SET  processed =  'Marked as Spam' WHERE  commentid =".$id.";";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}
function not_spam($id){
	global $wpdb;
	$table = $wpdb->prefix . "comments";
	$sql = "UPDATE  ".$table." SET  comment_approved =  '1' WHERE  comment_ID =".$id.";";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	$table = $wpdb->prefix . "smreports";
	$sql = "UPDATE  ".$table." SET  processed =  'Queued' WHERE  commentid =".$id.";";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}
function approve($id){
	global $wpdb;
	$table = $wpdb->prefix . "smreports";
	$sql = "UPDATE  ".$table." SET  processed =  'Approved' WHERE  commentid =".$id.";";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}
function unapprove($id){
	global $wpdb;
	$table = $wpdb->prefix . "smreports";
	$sql = "UPDATE  ".$table." SET  processed =  'Queued' WHERE  commentid =".$id.";";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}
if(isset($_GET['action'])){
	switch($_GET['action']){
		case "spam":
			mark_as_spam($_GET['id']);
		break;	
		case "notspam":
			not_spam($_GET['id']);
		break;	
		case "approve":
			approve($_GET['id']);
		break;
		case "unapprove":
			unapprove($_GET['id']);
		break;		
	}
}
$admin = admin_url();
$url = $admin."edit-comments.php?page=simplecomments_report";
header("Location:".$url); exit();
?>