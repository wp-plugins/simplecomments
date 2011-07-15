<style type="text/css">
.tablenav{
	display: none;
}
</style>
<?php
if ( ! current_user_can( 'manage_options' ) )
wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
$title = __('Reported Comments');
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class reported_table extends WP_List_Table {
    function __construct(){
        global $status, $page;
        
        parent::__construct( array(
            'singular'  => 'report',
            'plural'    => 'reports',
            'ajax'      => true
        ) );
        
    }
    function get_columns(){
        $columns = array(
            'commentID'		=> 'ID',
            'comment'		=> 'Comment',
            'post'			=> 'Post',
            'status'		=> 'Status'
        );
        return $columns;
    }
    function get_sortable_columns() {
        $sortable_columns = array(
            'commentID'     => array('commentID',false),
            'post'			=> array('post',false),
            'status'		=> array('status',true),
            'comment'		=> array('status',false)
        );
        return $sortable_columns;
    }
    
    
    function column_commentID($item){
        return $item['commentID'];
	}
    function column_comment($item){
        $actions = array(
            'edit'      => sprintf('<a href="comment.php?action=editcomment&c=%s">Edit</a>',$item['commentID'])
        );
        return sprintf('%s%s',$item['comment'],$this->row_actions($actions));
	}
    function column_post($item){
    	$permalink = get_permalink($item['commentID']);
        $actions = array(
            'view'      => sprintf('<a href="'.$permalink.'" target="new">View</a>')
        );
        return sprintf('%s%s',$item['post'],$this->row_actions($actions));
	}
    function column_status($item){
    	$url = WP_PLUGIN_URL . '/simplecomments/simp_comments_report_update.php';
    	$spam = sprintf('<a href="%s?action=%s&id=%s">Mark as Spam</a>',$url,'spam',$item['commentID']);
    	$notspam = sprintf('<a href="%s?action=%s&id=%s">Not Spam</a>',$url,'notspam',$item['commentID']);
    	$approve = sprintf('<a href="%s?action=%s&id=%s">Approve</a>',$url,'approve',$item['commentID']);
    	$unapprove = sprintf('<a href="%s?action=%s&id=%s">Unapprove</a>',$url,'unapprove',$item['commentID']);
        switch($item['status']){
			case "Marked as Spam":
				$actions = array(
					'notspam'	=> $notspam
				);
			break;
			case "Queued":
				$actions = array(
					'allow'		=> $approve,
					'spam'		=> $spam
				);
			break;
			case "Approved":
				$actions = array(
					'disallow'	=> $unapprove,
					'spam'		=> $spam
				);
			break;
		}
        return sprintf('%s%s',$item['status'],$this->row_actions($actions));
	}
	function prepare_items() {
		$per_page = 10000000000;
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$data = $this->get_data();
		$total_items = count($data);
      	if($total_items != '0'){
    		$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
    	}
        $this->items = $data;
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ) );
    }
    function get_data(){
    	global $wpdb;
		$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_select_db(DB_NAME);
		$table_name = $wpdb->prefix . "smreports";
		$result = mysql_query('SELECT * FROM '.$table_name, $link);
		$numRows = mysql_num_rows($result);
		if($numRows != 0){
			$return = array();
			while ($row = mysql_fetch_assoc($result)) {
				$comment = $this->getComment('commentText',$row['commentid']);
				$post = $this->getComment('postTitle',$row['commentid']);
				$new = array(
					'commentID'	=> $row['commentid'],
					'comment'		=> $comment,
					'post'			=> $post,
					'status'		=> $row['processed']
				);
				array_push($return,$new);
			}   
			return $return;
			
		}
    }
    function getComment($type, $id){
    	$comment = get_comment($id);
    	switch($type){
    		case "commentText":
    			return $comment->comment_content;
    		break;
    		case "postTitle":
    			$postID = $comment->comment_post_ID;
    			$post = get_post($postID);
    			return $post->post_title;
    		break;
    	}
    }
}
$sm_reports = new reported_table();
$sm_reports->prepare_items();  
?>

<div class="wrap">
<?php screen_icon(); ?><h2><?php echo esc_html( $title ); ?></h2><p>
<?php $sm_reports->display() ?></p>
