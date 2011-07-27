<?php
/*
Plugin Name: SimpleComments
Description: SimpleComments is a fully customisable, easy to use comment system for your wordpress site.
Version: 1.4
Author: George Sephton
License: Creative Commons Attribution-ShareAlike (http://creativecommons.org/licenses/by-sa/3.0/)

Please ensure you see the readme document for a full explanation on usage

	Copyright 2011  George Sephton
	
	When distributing or using this plugin, this notice must not be removed!
	
	Creative Commons Attribution-ShareAlike
	
    THE WORK (AS DEFINED BELOW) IS PROVIDED UNDER THE TERMS OF THIS
    CREATIVE COMMONS PUBLIC LICENSE ("CCPL" OR "LICENSE"). THE WORK
    IS PROTECTED BY COPYRIGHT AND/OR OTHER APPLICABLE LAW. ANY USE
    OF THE WORK OTHER THAN AS AUTHORIZED UNDER THIS LICENSE OR
    COPYRIGHT LAW IS PROHIBITED.

	BY EXERCISING ANY RIGHTS TO THE WORK PROVIDED HERE, YOU ACCEPT
	AND AGREE TO BE BOUND BY THE TERMS OF THIS LICENSE. TO THE EXTENT
	THIS LICENSE MAY BE CONSIDERED TO BE A CONTRACT, THE LICENSOR
	GRANTS YOU THE RIGHTS CONTAINED HERE IN CONSIDERATION OF YOUR
	ACCEPTANCE OF SUCH TERMS AND CONDITIONS.
	
	See more at http://creativecommons.org/licenses/by-sa/3.0/legalcode

*/
//Hooks
add_action('wp_head', 'smAddJQuery');
register_activation_hook( __FILE__, 'smInstall' ); //The function to run when the plugin is activated
register_deactivation_hook( __FILE__, 'smUninstall' ); //The function to run when the plugin is deactivated
add_action('wp_print_styles', 'smAddStyle'); //Include our stylesheet
add_action('admin_menu','smCommentMenu'); //Add the options panel to the admin area
add_action('wp_ajax_comment_manip', 'smCommentCallback'); //AJAX callback function for comment manipulation
add_action('wp_ajax_load_comments', 'smLoadCommentCallback'); //AJAX callback function for loading comments
add_action('wp_ajax_nopriv_load_comments', 'smLoadCommentCallback');
add_action('wp_ajax_nopriv_comment_manip', 'smCommentCallback');
add_action('wp_head', 'smAjaxHead'); //Add the correct AJAX to the document head
add_action('wp_head', 'smAjaxHead2'); //Add the correct AJAX to the document head
/*
Below are the functions, the first section I highly recommend you edit this,
it allows you to completely control the comment section. If you don't feel
confident enough with PHP to edit it then leave it and just edit the stylesheet.

The second seciton I recommend you leave alone, they are just stuff to make it all
work, unless you have a serious dislike to the way how it all works then edit it but
if not just leave it.
*/

//EDITABLE FUNCTIONS\\
function smShow($id){
	//Show the comment area, any editing that needs to be done should be done here.
	if(get_option('default_comment_status') == 'open'){ //Check if commenting is enabled.
		global $current_user; 
		get_currentuserinfo(); //get info about our current user
		$nowUserID = $current_user->ID;
		$echo = '';
		$echo .= '<hr /><h3>Comments</h3>'; //This is the heading that will be displayed first
		$allow = 'n';
		if(get_option('comment_registration') != '1'){ //Check if users have to be registered to post comments
			$allow = 'y'; //If users don't need to be registered, let them comment
		}else{ //If not, they have to be logged in
			if(is_user_logged_in()){ //Check if they're logged in
				$allow = 'y'; //If the user is logged in then let them comment
			}
		}
		if(get_option('close_comments_for_old_posts') == '1'){
			$info = get_post($id);
			$datePosted = $info->post_date;
			$newDatePosted = strtotime($datePosted);
			$limit = (get_option('close_comments_days_old')*24*60*60);
			if(($newDatePosted+$limit) < time()){
				$allow = 'n';
			}
			
		}
		if($allow == 'y'){
			//This is the comment box, change any styling you want to here
			$echo .= '<a name="comment"></a>'; //This is our anchor, the page will scroll up to this box when a user hits reply
			$echo .= '<div id="commentboxcontainer">'."\n"; //A container div to hold it all in place.
			$echo .= '<div id="commentboxtext"></div>'."\n"; //This needs to remain here, it change to replying to ___ 
			if(get_option('sm_display_avatars') == '1'){ //Check if avatars are being displayed
				$echo .= '<div id="commentboxcontainerleft">'.get_avatar($nowUserID,"40").'</div>'."\n"; //Show the users avatar
			}
			$echo .= '<div id="commentboxcontainerright">'."\n";
			//Now show the comment box
			$echo .= '<textarea id="commentbox"></textarea>'."\n";
			$echo .= '</div>'."\n";
			$echo .= '<br style="clear:both" />'."\n"; //For good measure, clear the floats from above
			$echo .= "<a href=\"javascript:comment('new','','".$id."','')\">Submit</a>"; //Here's the submit button, edit it :)
			$echo .= '</div>'."\n"; //And we're done with the comment box
		}
		$echo .= smShowCommentTree($id,'0','0'); //This is where the comments will be displayed
		$echo .= '<input type="hidden" id="editinplacelock" />'."\n"; //Leave this! It is vital to the AJAX edit in place feature
		$echo .= '<input type="hidden" id="replydata" />'."\n"; //Leave this! It is vital when users click reply to a comment
		echo $echo; //Now show it all
	}
}
function smShowCommentTree($postid,$parentid,$level){
	/*
		This functions shows the comments, it is in a seperate function as it calls itself in order
		to deal with nested comments. It first takes the id of the post then displays each of the
		comments attached to it, as it loads each comment if checks if that comment has any nested
		comments, if so it calls itself but this time specifies a post. All the comments of that
		post are called and checked for nesting and we repeat. The functions continually calls
		itself until it reaches the bottom of the next and continues from where it left off on the 
		previous level until all comments are shown.
	*/
	$echo = '';
	if($level == '0'){ //First we check if we are dealing with nested comments or not
		//We're on the top level, no nesting
		$comments = get_comments('post_id='.$postid.'&parent='.$parentid.'&order='.get_option('comment_order'));
	}
	else{
		//Here are the nested comments.
		$comments = get_comments('parent='.$parentid.'&order='.get_option('comment_order'));
	}
	$noComments = count($comments); //Count how many comments are in the area
	if($noComments != 0){ //If the array has comments, parse them and show them, if not carry on.
		//Show comments
		foreach($comments as $comment) : //Parse each comment in the comments array
			$mylinks = '';
			$myApprovalLinks = '';
			$userIDcomm = $comment->user_id; //Get the comment user id
			$avatar_get = $userIDcomm; //Used to find their avatar
			$user = get_userdata($userIDcomm); //Get their data
			$username = $user->user_login; //Get their username
			$commentID = $comment->comment_ID; //Get the id of the comment
			$parent = $comment->comment_parent; //Get it's parent
			$spam = $comment->comment_approved; //Is it approved or not, its it spam?
			$time = $comment->comment_date; //get the time.
			$new = strtotime($time); //Change that time into unix timestamp so it's easier to deal with.
			if($username != ''){ //Check if the user who posted it had a name or if they were a guest
				//They were registered, add a link to their profile.
				$showUn = '<a href="'.get_bloginfo('url').'/index.php/author/'.$userIDcomm.'">'.$username."</a>";
				$imgB = '<a href="'.get_bloginfo('url').'/index.php/author/'.$userIDcomm.'">';
				$imgA = "</a>";
			}
			else{
				//They were a guest, they have no profile.
				$imgB = '';
				$imgA = '';
				$showUn = 'User';
			}
			$url = get_bloginfo('template_url');
			global $current_user;
			get_currentuserinfo(); //Get the current user's data
			$nowUserID = $current_user->ID; //Get the current user's id
			//If they are the same user and they are logged in, let them edit and delete the comment
			if(($userIDcomm == $nowUserID) and ($nowUserID != '0')){
				if(get_option('sm_user_delete') == '1'){
					$mylinks .= '<a href="javascript:comment(\'delete\',\''.$commentID.'\',\''.$postid.'\',\'\')"> | Delete</a>';
				}
				if(get_option('sm_user_edit') == '1'){
					$mylinks .= '<a href="javascript:comment(\'edit\',\''.$commentID.'\',\''.$postid.'\',\'\')"> | Edit</a>';
				}
				if(get_option('thread_comments') == '1'){ //if nesting is allowed, all the user to reply
					$allow = true;
					if(get_option('close_comments_for_old_posts') == '1'){
						$info = get_post($postid);
						$datePosted = $info->post_date;
						$newDatePosted = strtotime($datePosted);
						$limit = (get_option('close_comments_days_old')*24*60*60);
						if(($newDatePosted+$limit) < time()){
							$allow = false;
						}
						
					}
					if($allow){
						$mylinks .= '<a href="javascript:comment(\'reply\',\''.$commentID.'\',\''.$postid.'\',\''.$username.'\')"> | Reply</a>';
					}
				}
				$myApprovalLinks .= '<a href="javascript:comment(\'delete\',\''.$commentID.'\',\''.$postid.'\',\'\')">Delete</a>';
			}else{
				//If not they can just report it or reply to it
				if(get_option('sm_enable_report') == '1'){ //is reporting enabled??
					$mylinks .= '<a href="javascript:comment(\'report\',\''.$commentID.'\',\''.$postid.'\',\'\')"> | Report</a>';
				}
				if(get_option('thread_comments') == '1'){ //if nesting is allowed, all the user to reply
					$allow = true;
					if(get_option('close_comments_for_old_posts') == '1'){
						$info = get_post($postid);
						$datePosted = $info->post_date;
						$newDatePosted = strtotime($datePosted);
						$limit = (get_option('close_comments_days_old')*24*60*60);
						if(($newDatePosted+$limit) < time()){
							$allow = false;
						}
						
					}
					if($allow){
						$mylinks .= '<a href="javascript:comment(\'reply\',\''.$commentID.'\',\''.$postid.'\',\''.$username.'\')"> | Reply</a>';
					}
				}
			}
			if($spam == '1'){
				//If the comment is approved, show it
				$show = $comment->comment_content;
			}elseif($spam == '0'){
				//If not say it's awaiting approval
				$show = '<em>Comment is under approval.</em>';
			}//otherwise (ie, if it's spam) don't show it)
			//Check if nested comments are to be displayed, if not show them in a linear fashion
			if(get_option('thread_comments') == '1'){
				for($i=0; $i<$level; $i++){ //Now loop the number of levels in the nest it is
					$echo .= '<div id="vr">'."\n"; //For each level in, add a border and margin to the left
					//This show the readers than the comment is nested and for which comment it is nested.
				}
			}
			//The anchor so the window can focus on a new comment
			$echo .= '<a name="ace_'.$commentID.'" id="ace_'.$commentID.'"></a>'."\n";
			//Now begin to show the comment
			if(get_option('sm_display_avatars') == '1'){ //Check if avatars are being displayed
				$echo .= '<div id="commentLeft">'."\n"; //This is the left side, the user's picture
				$echo .= $imgB.get_avatar($avatar_get,"40").$imgA;
				$echo .= '</div>'."\n";
			}
			$echo .= '<div id="commentRight">'."\n";
			//Here's the user's data and the links for the comment
			$echo .= '<h4>'.$showUn.' | '.date('F j, Y',$new).' at '.date('g:i a',$new).$mylinks.'</h4>'."\n";
			$echo .= '<p id="commentID_'.$commentID.'">'.$show.'</p>'."\n"; //Show the comment
			//Leave the id of the comment div, this is part of the edit in place feature.
			$echo .= '</div>'."\n";
			$echo .= '<br style="clear:both" />'."\n"; //Clear the floats for good measure
			if(get_option('thread_comments') == '1'){ //Again check for nesting
				for($i=0; $i<$level; $i++){
					$echo .= '</div>'."\n";	//end the divs for the nested lines
				}
			}
			//Now check for nested comments
			$subComments = get_comments('post_id='.$postid.'&parent='.$commentID);
			$noSubComments = count($subComments);
			if($noSubComments != '0'){ //If there are comments
				$newLevel = $level+1;
				$echo .= smShowCommentTree($postid,$commentID,$newLevel); //Show these nested comments
			}
		endforeach; //Move onto the next comment
	}else{
		//Here there are no comments to show
		//We either have no comments at all or we're at the end of the nest
		if($level == '0'){ //If we're on the first level with no comments, there aren't any comments to show at all
			$echo = 'No one has commented.'; //Tell the users of this nonsense!
		}
	}
	//And we're done, return our data so it can be shown.
	return $echo;
}
//LEAVE THESE IN PEACE\\
function simpleComments($id){
	echo '<script type="text/javascript">loadComments(\''.$id.'\',\'\');</script>'."\n";
	echo '<div id="commentscontainer"></div>'."\n";
}
function smInstall(){
	//The code to run when the plugin is activated.
	global $wpdb;
	//First we want to create a new table that will hold all the comment reports
	$table_name = $wpdb->prefix . "smreports";
	$sql = "CREATE TABLE ".$table_name." (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, commentid INT NOT NULL, processed VARCHAR (255) NOT NULL);";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	//Add our custom options that will be displayed in the Admin panel
	add_option('sm_display_avatars','1');
	add_option('sm_enable_report','1');
	add_option('sm_user_delete','1');
	add_option('sm_user_edit','1');
}
function smUninstall(){
	//The code to run when the plugin is deactivated.
}
function smAddStyle(){
	if(get_option('default_comment_status') == 'open'){ //Check if commenting is enabled.
		//Add the plugin stylesheet to wordpress
		$myStyleUrl = WP_PLUGIN_URL . '/simplecomments/simp_comments_style.css';
    	$myStyleFile = WP_PLUGIN_DIR . '/simplecomments/simp_comments_style.css';
		if ( file_exists($myStyleFile) ) {
			wp_register_style('myStyleSheets', $myStyleUrl);
			wp_enqueue_style( 'myStyleSheets');
		}
	}
}
function smCommentMenu(){
	//This is the settings page in the administration area
	add_comments_page('SimpleComments Options', 'SimpleComments', 'manage_options', 'simplecomments', 'smAdminOptionPage');
	//This is the reported comments page in the administration area
	$c = smGetNoReports(); //The number of comments that are queuedd
	$menu_label = sprintf( __( 'Comment Reports %s' ), "<span class='update-plugins count-".$c."' title='Comments to view'><span class='update-count'>".$c."</span></span>" );
	$report = add_comments_page('Reported Comments', $menu_label, 'manage_options', 'simplecomments_report', 'smAdminReportPage');
	add_action( 'admin_head-'. $report, 'smReportHead' );
}
function smReportHead(){ echo "\n";
	?>
		<style type="text/css"> 
		#commentID{
			width: 55px;
		}
		#status{
			width: 200px;
		}
		</style> 
	<?php

}
function smAdminOptionPage(){
	//Load the document into the administration page.
	require('simp_comments_admin.php');
}
function smAdminReportPage(){
	//Load the document into the administration page.
	require('simp_comments_report.php');
}
function smGetNoReports(){
	//This returns the number of reported comments that haven't had action taken
	global $wpdb;
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME);
	$table_name = $wpdb->prefix . "smreports";
	$e = true;
	$result = mysql_query('SELECT * FROM '.$table_name.' WHERE processed = \'Queued\';', $link)
		or $e = false;
	if($e){
		$c = mysql_num_rows($result);
	}else{
		$c = 0;
	}
	return $c;
}
function smLoadCommentCallback(){
	if(get_option('default_comment_status') == 'open'){ //Check if commenting is enabled.
		//This is the AJAX callback function to load the comment area, nothing special really
		$id = $_GET['id'];
		smShow($id);
		die();
	}
}
function smCommentCallback(){
	/*
	This is thr AJAX call back function for the comment manipulation
	They decide what happens when the user click reply, edit, delete etc
	*/
	$allow = false;
	if(get_option('default_comment_status') == 'open'){ //Check if commenting is enabled.
		if((get_option('comment_registration') == '1') and (is_user_logged_in())){
			$allow = true;
		}
		if(get_option('comment_registration') != '1'){
			$allow = true;
		}
	}
	$postid = $_GET['postid']; //Which post are we dealing with
	$commentid = $_GET['commentid']; //Which comment are we dealing with
	$f = $_GET['f']; //Get the function
	if($f == "report"){
		//We are reporting a comment, it could be rude or insulting and whatnot
		if(get_option('sm_enable_report') == '1'){
			global $wpdb;
			$table_name = $wpdb->prefix . "smreports";
			$rows_affected = $wpdb->insert( $table_name, array( 'commentid' => $commentid, 'processed' => 'Queued'));
		}
	}
	if($allow){
		switch($f){
			case "edit":
				//Ok we are editing the comment
				//Do some error checking here to ensure the correct user can only edit their own posts.
				if(get_option('sm_user_edit') == '1'){
					$newValue = escapeData($_GET['newValue']); //Get the new value and secure it
					$commentarr = array('comment_ID'=>$commentid,'comment_content'=>$newValue);
					wp_update_comment($commentarr); //Update the comment
				}
			break;
			case "delete":
				//We are deleting the comment, at this point the user will already have confirmed that they want to
				if(get_option('sm_user_delete') == '1'){
					wp_delete_comment($commentid);
				}
			break;
			case "new":
				//We are creating a new comment
				/*
					NOTICE:
						Although I haven't included it, you may want to consider
						some sort of flood control, to stop users posting comments
						too quickly.
				*/
				$value = escapeData($_GET['value']); //Get the value of the new comment and secure it
				$time = current_time('mysql'); //Get the time
				global $current_user;
				get_currentuserinfo();
				$userid = $current_user->ID; //Add the correct user id to it
				$checkUserComments = get_comments(array('user_id' => $userid,'status' => 'approve'));
				$noApprovComms = count($checkUserComments); //This is the number of previously approved comments the user has
				$allow = 'n';
				if(get_option('comment_registration') != '1'){ //Check if users have to be registered to post comments
					$allow = 'y'; //If users don't need to be registered, let them comment
				}else{ //If not, they have to be logged in
					if(is_user_logged_in()){ //Check if they're logged in
						$allow = 'y'; //If the user is logged in then let them comment
					}
				}
				if(get_option('close_comments_for_old_posts') == '1'){
				//This checks how old the post is and checks if the user has disabled comments on old posts
					$info = get_post($postid);
					$datePosted = $info->post_date;
					$newDatePosted = strtotime($datePosted);
					$limit = (get_option('close_comments_days_old')*24*60*60);
					if(($newDatePosted+$limit) < time()){
						$allow = 'n';
					}
					
				}
				if($allow == 'y'){
					$dataMod = array('comment_post_ID' => $postid,'comment_content' => $value,'comment_parent' => 0,'user_id' => $userid,'comment_date' => $time,'comment_approved'=>'0'); //Data that needs moderation
					$dataNoMod = array('comment_post_ID' => $postid,'comment_content' => $value,'comment_parent' => 0,'user_id' => $userid,'comment_date' => $time,'comment_approved'=>'1'); //Data thats doesn't need moderation
					//We have check if user has to be logged in/is allowed to comment
					//Now check if they are unknown, in which case they automatically require moderation
					if($userid == '0'){ //if the user is unknown, they automatically require comments to approved
						wp_insert_comment($dataMod); //Add it 
					}
					else{
						//The user is known, check if every comment requires moderation
						if(get_option('comment_moderation') == '1'){ //If every comment requires moderation
							wp_insert_comment($dataMod); //Add it 
						}
						else{
							//Only certain posts require moderation
							if((get_option('comment_whitelist') == '1') and ($noApprovComms == '0')){
								//The user requires their first comment to be moderated
								wp_insert_comment($dataMod); //Add it 
							}
							elseif(smCheckforURLs($value, get_option('comment_max_links'))){
								//Check if the text contains too many urls
								wp_insert_comment($dataMod); //Add it 
							}
							else{
								//The user can happily add their data without moderation
								wp_insert_comment($dataNoMod); //Add it 
							}
						}
					}
				
				/*	We now take all the data we just inserted and query it to find the id of the comment we just added.
					The only way for their to be duplicate comments is if the same users sumbmits an identical comment 
					in the same second on the same post with the same parent, so unlikely, hence we can return the new
					comment's id.
				*/
				global $wpdb;
				$tb = $wpdb->prefix . "comments";
				$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
				mysql_select_db(DB_NAME);
				$sql = "SELECT comment_ID FROM ".$tb." WHERE comment_post_ID='".$postid."' AND comment_content='".$value."' AND comment_parent='0' AND user_id='".$userid."' AND comment_date='".$time."';";
				$result = mysql_query($sql, $link);
				$row = mysql_fetch_assoc($result);
				echo $row['comment_ID'];
				}
				break;
			case "reply":
				//We are replying to stuff
				$value = escapeData($_GET['value']); //Get the value of the new comment and secure it
				$time = current_time('mysql'); //Get the time
				global $current_user;
				get_currentuserinfo();
				$userid = $current_user->ID; //Add the correct user id to it
				$checkUserComments = get_comments(array('user_id' => $userid,'status' => 'approve'));
				$noApprovComms = count($checkUserComments); //This is the number of previously approved comments the user has
				$allow = 'n';
				if(get_option('comment_registration') != '1'){ //Check if users have to be registered to post comments
					$allow = 'y'; //If users don't need to be registered, let them comment
				}else{ //If not, they have to be logged in
					if(is_user_logged_in()){ //Check if they're logged in
						$allow = 'y'; //If the user is logged in then let them comment
					}
				}
				if(get_option('close_comments_for_old_posts') == '1'){
					//This checks how old the post is and checks if the user has disabled comments on old posts
					$info = get_post($postid);
					$datePosted = $info->post_date;
					$newDatePosted = strtotime($datePosted);
					$limit = (get_option('close_comments_days_old')*24*60*60);
					if(($newDatePosted+$limit) < time()){
						$allow = 'n';
					}
					
				}
				if($allow == 'y'){
					$dataMod = array('comment_post_ID' => $postid,'comment_content' => $value,'comment_parent' => $commentid, 'user_id' => $userid,'comment_date' => $time,'comment_approved'=>'0'); //Data that needs moderation
					$dataNoMod = array('comment_post_ID' => $postid,'comment_content' => $value,'comment_parent' => $commentid, 'user_id' => $userid,'comment_date' => $time,'comment_approved'=>'1'); //Data thats doesn't need moderation
					//We have check if user has to be logged in/is allowed to comment
					//Now check if they are unknown, in which case they automatically require moderation
					if($userid == '0'){ //if the user is unknown, they automatically require comments to approved
						wp_insert_comment($dataMod); //Add it 
					}
					else{
						//The user is known, check if every comment requires moderation
						if(get_option('comment_moderation') == '1'){ //If every comment requires moderation
							wp_insert_comment($dataMod); //Add it 
						}
						else{
							//Only certain posts require moderation
							if((get_option('comment_whitelist') == '1') and ($noApprovComms == '0')){
								//The user requires their first comment to be moderated
								wp_insert_comment($dataMod); //Add it 
							}
							elseif(smCheckforURLs($value, get_option('comment_max_links'))){
								//Check if the text contains too many urls
								wp_insert_comment($dataMod); //Add it 
							}
							else{
								//The user can happily add their data without moderation
								wp_insert_comment($dataNoMod); //Add it 
							}
						}
					}
					/*	We now take all the data we just inserted and query it to find the id of the comment we just added.
						The only way for their to be duplicate comments is if the same users sumbmits an identical comment 
						in the same second on the same post with the same parent, so unlikely, hence we can return the new
						comment's id.
					*/
					global $wpdb;
					$tb = $wpdb->prefix . "comments";
					$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
					mysql_select_db(DB_NAME);
					$sql = "SELECT comment_ID FROM ".$tb." WHERE comment_post_ID='".$postid."' AND comment_content='".$value."' AND comment_parent='".$commentid."' AND user_id='".$userid."' AND comment_date='".$time."';";
					$result = mysql_query($sql, $link);
					$row = mysql_fetch_assoc($result);
					echo $row['comment_ID'];
				}
			break;
		}
		
		
		die(); //Ensure no other weird data gets passed to the javascript
	}
}
function escapeData($data){
	global $wpdb;
	//This function is here to manipulate the data as you wish before it is inserted into the database
	$newData = $wpdb->escape($data);
	return $newData;
}
function smAddJQuery(){
	wp_enqueue_script("jquery");
}
function smAjaxHead() {
	//This is the javascript to deal with all the AJAX, it is added to the document head.
	//Comments are done in PHP so they aren't shoe to the users
	$Aallow = false;
	if(get_option('default_comment_status') == 'open'){ //Check if commenting is enabled.
		if((get_option('comment_registration') == '1') and (is_user_logged_in())){
			$Aallow = true;
		}
		if(get_option('comment_registration') != '1'){
			$Aallow = true;
		}
	}
	?>
<script type="text/javascript" >
function comment(func,commentid,postid,user){
	if(func == "report"){
		var ask = confirm("Are you sure you want to report this comment? \n\nOnly report a comment if you believe it to be spam or abusive not because you have a difference of opinion.");
		if(ask){
			jQuery(document).ready(function(){
				jQuery.ajax({
					url:"<?php echo get_bloginfo('url'); ?>/wp-admin/admin-ajax.php",
					type:'GET',
					data:'action=comment_manip&commentid='+commentid+'&postid='+postid+'&f=report',
					success:function(results){
						alert('Comment Reported.\n\nThank you.');
					}
				});
			})
		}
	}else{
	<?php
	if($Aallow){
	?>
		switch(func){
			<?php if(get_option('sm_user_delete') == '1'){ ?>
			case "delete":
				var ask = confirm("Are you sure you want to delete your comment?");
				if(ask){
					jQuery(document).ready(function(){
						jQuery.ajax({
							url:"<?php echo get_bloginfo('url'); ?>/wp-admin/admin-ajax.php",
							type:'GET',
							data:'action=comment_manip&postid='+postid+'&commentid='+commentid+'&f='+func,
							success:function(results){
								loadComments(postid,'');
							}
						});
					})
				}
			break;
			<?php }	if(get_option('sm_user_edit') == '1'){ ?>
			case "edit":
				var preedit = document.getElementById("commentID_"+commentid).innerHTML;
				var lock = document.getElementById('editinplacelock').value;
				if(lock != 'NOPE'){
					var editinplace = '<textarea id="eip">'+preedit+'</textarea><br />';
					editinplace += "<a href=\"javascript:comment('edit2','"+commentid+"','"+postid+"','')\">Save</a> | <a href=\"javascript:comment('canceledit','"+commentid+"','','')\">Cancel</a>";
					document.getElementById("commentID_"+commentid).innerHTML = editinplace;
				}
				document.getElementById('editinplacelock').value = 'NOPE';
			break;
			case "canceledit":
				document.getElementById('editinplacelock').value = '';
				loadComments(postid,'');
			break;
			case "edit2":
				var newValue = document.getElementById("eip").value;
				jQuery(document).ready(function(){
					jQuery.ajax({
						url:"<?php echo get_bloginfo('url'); ?>/wp-admin/admin-ajax.php",
						type:'GET',
						data:'action=comment_manip&postid='+postid+'&commentid='+commentid+'&f=edit&newValue='+newValue,
						success:function(results){
							loadComments(postid,'');
						}
					});
				})
				document.getElementById('editinplacelock').value = '';
			break;
			<?php
			}
			$allow = true;
			if(get_option('close_comments_for_old_posts') == '1'){
				$info = get_post($id);
				$datePosted = $info->post_date;
				$newDatePosted = strtotime($datePosted);
				$limit = (get_option('close_comments_days_old')*24*60*60);
				if(($newDatePosted+$limit) < time()){
					$allow = false;
				}
				
			}
			if($allow){
			?>
			case "new":
				var commentid = document.getElementById('replydata').value;
				var commentValue = document.getElementById("commentbox").value;
				if(commentValue != ''){
					if(commentid != ''){
						jQuery(document).ready(function(){
							jQuery.ajax({
								url:"<?php echo get_bloginfo('url'); ?>/wp-admin/admin-ajax.php",
								type:'GET',
								data:'action=comment_manip&commentid='+commentid+'&postid='+postid+'&f=reply&value='+commentValue,
								success:function(results){
									loadComments(postid,results);
								}
							});
						})
						document.getElementById('commentbox').innerHTML = '';
					}
					else{
						jQuery(document).ready(function(){
							jQuery.ajax({
								url:"<?php echo get_bloginfo('url'); ?>/wp-admin/admin-ajax.php",
								type:'GET',
								data:'action=comment_manip&postid='+postid+'&f=new&value='+commentValue,
								success:function(results){
									loadComments(postid,results);
								}
							});
						})
						document.getElementById('commentbox').innerHTML = '';
					}
				}
			break;
			case "cancelreply":
				document.getElementById('replydata').value = '';
				document.getElementById('commentboxtext').innerHTML = '';
			break;
			case "reply":
				window.location = "#comment";
				if(user == ''){
					var user = 'User';
				}
				document.getElementById('commentboxtext').innerHTML = '<h4>Reply to '+user+' | <a href="javascript:comment(\'cancelreply\',\'\',\'\',\'\')">Cancel</a></h4>';
				document.getElementById('replydata').value = commentid;
			break;
			<?php
				}
			?>
			default:
				alert("An error has occurred.");
			break;
		}<?php } ?>
	}
}
function gotoComment(id){
	var el = document.getElementById("ace_"+id);
	el.scrollIntoView(true);
}
</script>
<?php
} //And we're done with the first javascript bit
function smAjaxHead2() {
	//This is the javascript to deal with all the AJAX, it is added to the document head.
	//Comments are done in PHP so they aren't shoe to the users
	if(get_option('default_comment_status') == 'open'){ //Check if commenting is enabled.
	?>
<script type="text/javascript" >
function loadComments(id,gotoid){
	<?php
		//This is the ajax function to load the comments
		//it retrieves data from PHP for the correct post id then puts inside the comment container
	?>
	jQuery(document).ready(function(){
		jQuery.ajax({
			url:"<?php echo get_bloginfo('url'); ?>/wp-admin/admin-ajax.php",
			type:'GET',
			data:'action=load_comments&id='+id,
			success:function(results){
				document.getElementById('commentscontainer').innerHTML = results;
				if(gotoid != ''){
					gotoComment(gotoid);
				}
				return true;
			}
		});
	})
}
</script>
<?php
	}
} //And we're done with our javascript
function smCheckforURLs($text,$limit){
	/*
		There is a very clever algorithm that takes a string and validates if it exceeds
		the limit of urls allowed.
		
		First it takes the string and calculates how many urls there are for definite,
		using parse_url, however this relies on the user typing http://www.whatever.com
		
		Once calculated it then looks for strings that might be urls like google.com or
		tiny.cc and it counts these up, however these are uncertain so based on the length
		of the text entered, a certain amount of these urls are ignored.
		
		It then checks the number of confirmed urls + (possible urls - ignore some) against
		the limit and returns true if over the limit and false if not.
	
	*/
	$textlength = strlen($text); //Find the length of the text
	$splits = explode(" ",$text); //Explode the text by spaces
	$fullcount=0; //The initial number of confirmed urls
	$halfcount=0; //The initial number of possible urls
	$tlds = array( 'ac','ad','ae','aero','af','ag','ai','al','am','an','ao','aq','ar','arpa','as','asia','at','au','aw','ax','az','ba','bb','bd','be','bf','bg','bh','bi','biz','bj','bl','bm','bn','bo','br','bs','bt','bv','bw','by','bz','ca','cat','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co','com','coop','cr','cu','cv','cx','cy','cz','de','dj','dk','dm','do','dz','ec','edu','ee','eg','eh','er','es','et','eu','fi','fj','fk','fm','fo','fr','ga','gb','gd','ge','gf','gg','gh','gi','gl','gm','gn','gov','gp','gq','gr','gs','gt','gu','gw','gy','hk','hm','hn','hr','ht','hu','id','ie','il','im','in','info','int','io','iq','ir','is','it','je','jm','jo','jobs','jp','ke','kg','kh','ki','km','kn','kp','kr','kw','ky','kz','la','lb','lc','li','lk','lr','ls','lt','lu','lv','ly','ma','mc','md','me','mf','mg','mh','mil','mk','ml','mm','mn','mo','mobi','mp','mq','mr','ms','mt','mu','museum','mv','mw','mx','my','mz','na','name','nc','ne','net','nf','ng','ni','nl','no','np','nr','nu','nz','om','org','pa','pe','pf','pg','ph','pk','pl','pm','pn','pr','pro','ps','pt','pw','py','qa','re','ro','rs','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sj','sk','sl','sm','sn','so','sr','st','su','sv','sy','sz','tc','td','tel','tf','tg','th','tj','tk','tl','tm','tn','to','tp','tr','travel','tt','tv','tw','tz','ua','ug','uk','um','us','uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','yt','yu','za','zm','zw',
	);
	$j=count($splits);
	for($i=0; $i<$j; $i++){
		if(!strstr($splits[$i],".")){
			//Now remove any sections of our exploded string that don't contain a . as these obviously aren't urls
			unset($splits[$i]);
		}
	}
	foreach($splits as $split){
		//parse the each slice of the remaining split text
		$parseurl = parse_url($split); //php will easily find confirmed urls
		if($parseurl['host'] != ''){
			$fullcount = $fullcount+1; //add them to the confirmed url count
		}
		$splits2 = explode(".",$split); //next exploded each slice by . 
		$foundurl = false;
		foreach($splits2 as $split2){ //each each of those slices to see if they contain a tld
			foreach($tlds as $tld){
				if(strstr($split2,$tld)){
					$foundurl = true; //if found, set the found to true
				}
			}
		}
		if($foundurl){ //this ensure thats the possible url is only added to the count once.
			$halfcount = $halfcount+1; //add it to the possible url count
		}
	}
	//now round the number of characters and divide by 140 to get a single number
	$goodmeasure = round((floor($textlength / 10) * 10)/140);
	$halfcount = $halfcount - $goodmeasure; //remove this number from the possible url count for good measure
	$tot = $halfcount + $fullcount; //find the total
	if(($fullcount >= $limit) or (($tot-1) >= $limit)){
	/*	if the number of confirmed urls is greater than or equal to the limit
		or if the total number of links - 1  is greater than or equal to the limit... */
		return true; //return true
	}
	else{
		return false; //otherwise return false
	}
}
//END OF PLUGIN
?>