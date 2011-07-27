<?php
/**
 * Discussion settings administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */

if ( ! current_user_can( 'manage_options' ) )
	wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );

$title = __('SimpleComments Options');
?>

<div class="wrap">
<?php screen_icon(); ?>
<h2><?php echo esc_html( $title ); ?></h2>
<?php
$formAction = WP_PLUGIN_URL . '/simplecomments/simp_comments_admin_update.php';
?>
<form method="post" action="<?php echo $formAction; ?>" name="options"> 

<table class="form-table">
<tr valign="top">
<?php //General Comment Settings ?>
<th scope="row"><?php _e('General Options') ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('General comment settings') ?></span></legend>
<?php //Enable Comments - Also on Discussion Page ?>
<label for="default_comment_status">
<input name="default_comment_status" type="checkbox" id="default_comment_status" value="open" <?php checked('open', get_option('default_comment_status')); ?> />
<?php _e('Enable comments*') ?></label><br />
<?php //Display Avatars?>
<label for="sm_display_avatars">
<input name="sm_display_avatars" type="checkbox" id="sm_display_avatars" value="1" <?php checked('1', get_option('sm_display_avatars')); ?> />
<?php _e('Display avatars') ?></label>
<br />
<?php //Enable Threaded Comments - Also on Discussion Page ?>
<label for="thread_comments">
<input name="thread_comments" type="checkbox" id="thread_comments" value="1" <?php checked('1', get_option('thread_comments')); ?> />
<?php

printf( __('Enable threaded (nested) comments*') );

?><br />
<?php //Enable Reporting ?>
<label for="sm_enable_report">
<input name="sm_enable_report" type="checkbox" id="sm_enable_report" value="1" <?php checked('1', get_option('sm_enable_report')); ?> />
<?php

printf( __('Allow users to report comments') );

?><br />
<?php //Order Comments - Also on Discussion Page ?>
<label for="comment_order"><?php

$comment_order = '<select name="comment_order" id="comment_order"><option value="asc"';
if ( 'asc' == get_option('comment_order') ) $comment_order .= ' selected="selected"';
$comment_order .= '>' . __('older') . '</option><option value="desc"';
if ( 'desc' == get_option('comment_order') ) $comment_order .= ' selected="selected"';
$comment_order .= '>' . __('newer') . '</option></select>';

printf( __('Comments should be displayed with the %s comments at the top of each page*'), $comment_order );

?></label>

</fieldset></td>
</tr>
<?php //END General comment settings ?>
<tr valign="top">
<th scope="row"><?php _e('Restrictions') ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('Restriction settings') ?></span></legend>
<label for="comment_moderation">
<input name="comment_moderation" type="checkbox" id="comment_moderation" value="1" <?php checked('1', get_option('comment_moderation')); ?> />
<?php _e('All comments have to be moderated*') ?> </label>
<br />
<small><em><?php echo '(' . __('These settings may be overridden for individual articles.') . ')'; ?></em></small><br />
<?php //Users must be logged in to comment - Also on Discussion Page ?>
<label for="comment_registration">
<input name="comment_registration" type="checkbox" id="comment_registration" value="1" <?php checked('1', get_option('comment_registration')); ?> />
<?php _e('Only regiestered users can comment*') ?>
<?php if ( !get_option( 'users_can_register' ) && is_multisite() ) echo ' ' . __( '(Signup has been disabled. Only members of this site can comment.)' ); ?>
</label>
<br />
<?php //Close comments on articles older than - Also on Discussion Page ?>
<label for="close_comments_for_old_posts">
<input name="close_comments_for_old_posts" type="checkbox" id="close_comments_for_old_posts" value="1" <?php checked('1', get_option('close_comments_for_old_posts')); ?> />
<?php printf( __('Automatically close comments on articles older than %s days*'), '</label><input name="close_comments_days_old" type="text" id="close_comments_days_old" value="' . esc_attr(get_option('close_comments_days_old')) . '" class="small-text" />') ?>
<br />
<label for="comment_whitelist"><input type="checkbox" name="comment_whitelist" id="comment_whitelist" value="1" <?php checked('1', get_option('comment_whitelist')); ?> /> <?php _e('Users require their first comment to be moderated*'); ?></label>
<br />
<?php //Comments with more than X hyperlinks should be held for moderation - Also on Discussion Page ?>
<label for="comment_max_links"><?php printf(__('Hold a comment in the queue if it contains %s or more links*'), '<input name="comment_max_links" type="text" id="comment_max_links" value="' . esc_attr(get_option('comment_max_links')) . '" class="small-text" />' ) ?><br /><small><?php _e('(A common characteristic of comment spam is a large number of hyperlinks.)'); ?></small></label>
</fieldset></td>
</tr>
<tr valign="top">
<?php //Users ?>
<th scope="row"><?php _e('Users') ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('Users') ?></span></legend>
<?php //Allow users to delete their comments ?>
<label for="sm_user_delete">
<input name="sm_user_delete" type="checkbox" id="sm_user_delete" value="1" <?php checked('1', get_option('sm_user_delete')); ?> />
<?php _e('Allow users to delete their comments') ?> </label>
<br />
<?php //Allow users to edit their comments ?>
<label for="sm_user_edit">
<input name="sm_user_edit" type="checkbox" id="sm_user_edit" value="1" <?php checked('1', get_option('sm_user_edit')); ?> />
<?php _e('Allow users to edit their comments') ?> </label>
</fieldset></td>
</tr>
<?php //END Users ?>
</table><p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
 <small><?php _e('Note: starred options can also be altered on the Discussion Settings page.'); ?></small>
</p>
</form>
<h2><?php _e('BETA Feedback'); ?></h2>
<small>Please take a moment to leave feeback about the plugin, any problems you may have encountered or any errors in the script. If you like the overall plugin please say so and say if you have anything that you would like to be improved. Once ready we will fix any errors and bring the plugin out of BETA stage. Your help would be greatly appreciated.</small>
<form method="post" action="<?php echo $formAction; ?>" name="feedback"> 

<table class="form-table">
<tr valign="top">
<th scope="row"><?php _e('Comments') ?></th>
<td><fieldset>
<label for="sm_beta_comment">
<textarea name="sm_beta_comment" id="sm_beta_comment" style="width:330px;height:120px;"></textarea></label>
</tr>
</table><p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Submit') ?>" />
</p>
</form>
</div>
