<?php
require_once('../../../wp-admin/admin.php');

if(isset($_POST['sm_beta_comment'])){
	$to = "sd.plugins@gmail.com";
	$message = $_POST['sm_beta_comment'];
	$subject = "SimpleComments Feedback";
	$headers = 'From: feedback@simplecomments' . "\r\n" .
    'Reply-To: feedback@simplecomments' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
	mail($to, $subject, $message, $headers);
}
else{
	//First update the wp buildin options
	update_option('default_comment_status', $_POST['default_comment_status']); //Enable comments
	update_option('comment_registration', $_POST['comment_registration']); //Users have to be logged in
	update_option('close_comments_for_old_posts', $_POST['close_comments_for_old_posts']); //Close comments on old posts
	update_option('close_comments_days_old', $_POST['close_comments_days_old']); //How old should the posts be
	update_option('thread_comments', $_POST['thread_comments']); //Enable threaded comments
	update_option('comment_order', $_POST['comment_order']); //Order, older or newer first
	update_option('comment_moderation', $_POST['comment_moderation']); //All comments to be moderated
	update_option('comment_whitelist', $_POST['comment_whitelist']); //User has to have first comment moderated
	update_option('comment_max_links', $_POST['comment_max_links']); //Comments with X hyperlinks...
	//Now for the simplecomments specific options
	update_option('sm_display_avatars', $_POST['sm_display_avatars']); //Display avatars
	update_option('sm_enable_report', $_POST['sm_enable_report']); //Enable reporting
	update_option('sm_user_delete', $_POST['sm_user_delete']); //Allow users to delete their comments
	update_option('sm_user_edit', $_POST['sm_user_edit']); //Allow user to edit their comments
}
header("Location:../../../wp-admin/edit-comments.php?page=simplecomments&settings-updated=true");
//get_option('sm_user_delete') */
?>