<?php
//Photo Thumbnails
function kratos_photo_thumbnail(){  
    global $post;  
    if(has_post_thumbnail()){  
       the_post_thumbnail(array(750,),array('class'=>'img-responsive'));
    }else{ 
        $content = $post->post_content;  
        preg_match_all('/<img.*?(?: |\\t|\\r|\\n)?src=[\'"]?(.+?)[\'"]?(?:(?: |\\t|\\r|\\n)+.*?)?>/sim',$content,$strResult,PREG_PATTERN_ORDER);  
        $n = count($strResult[1]);  
        if($n>0){ 
            echo '<img src="'.$strResult[1][0].'" class="img-responsive" />';  
        }else {
            echo '<img src="'.get_bloginfo('template_url').'/images/thumb/thumb_1.jpg" class="img-responsive" />';  
        }  
    }  
}
//Post Thumbnails
if(function_exists('add_image_size')) add_image_size('kratos-thumb',750);
function kratos_blog_thumbnail(){
    global $post;
    $img_id = get_post_thumbnail_id();
    $img_url = wp_get_attachment_image_src($img_id,'kratos-entry-thumb');
    $img_url = $img_url[0];
    if(has_post_thumbnail()) echo '<a href="'.get_permalink().'"><img src="'.$img_url.'" /></a>';
}
add_filter('add_image_size',create_function('','return 1;'));
add_theme_support("post-thumbnails");
function kratos_blog_thumbnail_new(){
    global $post;
    $img_id = get_post_thumbnail_id();
    $img_url = wp_get_attachment_image_src($img_id,'kratos-entry-thumb');
    $img_url = $img_url[0];
    if(has_post_thumbnail()){
        echo '<a href="'.get_permalink().'"><img src="'.$img_url.'" /></a>';
    }else{
        $content = $post->post_content;
        $img_preg = "/<img(.*?)src=\"(.+?)\".*?>/";
        preg_match($img_preg,$content,$img_src);
        $img_count=count($img_src)-1;
        if(isset($img_src[$img_count]))
        $img_val = $img_src[$img_count];
        if(!empty($img_val)&&!post_password_required()){
            echo '<a href="'.get_permalink().'"><img src="'.$img_val.'" /></a>';
        }else if(!kratos_option('default_image')){
            $random = mt_rand(1,20);
            echo '<a href="'.get_permalink().'"><img src="'.get_bloginfo('template_url').'/images/thumb/thumb_'.$random.'.jpg" /></a>';
        }else echo '<a href="'.get_permalink().'"><img src="'.kratos_option('default_image').'" /></a>';
    }
}
//Share the thumbnail fetching
function share_post_image(){
    global $post;
    if(has_post_thumbnail($post->ID)){
        $post_thumbnail_id = get_post_thumbnail_id( $post_id );
        $img = wp_get_attachment_image_src($post_thumbnail_id,'full');
        $img = $img[0];
    }else{
        $content = $post->post_content;
        preg_match_all('/<img.*?(?: |\\t|\\r|\\n)?src=[\'"]?(.+?)[\'"]?(?:(?: |\\t|\\r|\\n)+.*?)?>/sim',$content,$strResult,PREG_PATTERN_ORDER);
        if(!empty($strResult[1])){
            $img = $strResult[1][0];
        }else{
            $img = '';
        }
    }
    return $img;
}
//Add media from url
add_action('admin_menu','add_submenu');
add_action('post-plupload-upload-ui','post_upload_ui');
add_action('post-html-upload-ui','post_upload_ui');
add_action('wp_ajax_add_external_media_without_import','wp_ajax_add_external_media_without_import');
add_action('admin_post_add_external_media_without_import','admin_post_add_external_media_without_import');
function add_submenu(){
	add_submenu_page(
		'upload.php',
		'从URL添加',
		'从URL添加',
		'manage_options',
		'add-from-url',
		'print_submenu_page'
	);
}
function post_upload_ui(){
    wp_enqueue_style('emwi',get_template_directory_uri().'/inc/theme-options/css/media.css');
    wp_enqueue_script('emwi',get_template_directory_uri().'/inc/theme-options/js/media.js');
	$media_library_mode = get_user_option('media_library_mode',get_current_user_id()); ?>
	<div id="emwi-in-upload-ui">
	  <div class="row1">或</div>
	  <div class="row2">
		<?php if('grid' === $media_library_mode): ?>
		  <button id="emwi-show" class="button button-large">从URL导入</button>
		  <?php print_media_new_panel( true ); ?>
		<?php else : ?>
		  <a class="button button-large" href="<?php echo esc_url(admin_url('/upload.php?page=add-from-url')); ?>">从URL导入</a>
		<?php endif; ?>
	  </div>
	</div><?php
}
function print_submenu_page(){ ?>
	<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
	  <?php print_media_new_panel(false); ?>
	</form><?php
}
function print_media_new_panel($is_in_upload_ui){
    wp_enqueue_style('emwi',get_template_directory_uri().'/inc/theme-options/css/media.css');
    wp_enqueue_script('emwi',get_template_directory_uri().'/inc/theme-options/js/media.js'); ?>
	<div id="emwi-media-new-panel" <?php if($is_in_upload_ui): ?>style="display:none"<?php endif; ?>>
	  <div class="url-row">
		<label>从URL添加媒体项目</label>
		<span id="emwi-url-input-wrapper">
		  <input id="emwi-url" name="url" type="url" required placeholder="Image URL" value="<?php echo esc_url($_GET['url']); ?>">
		</span>
	  </div>
	  <div id="emwi-hidden" <?php if($is_in_upload_ui||empty($_GET['error'])): ?>style="display: none"<?php endif; ?>>
		<div><span id="emwi-error"><?php echo esc_html($_GET['error']); ?></span>请手动指定图像大小与格式</div>
		<div id="emwi-properties">
		  <label>宽</label>
		  <input id="emwi-width" name="width" type="number" value="<?php echo esc_html($_GET['width']); ?>">
		  <label>高</label>
		  <input id="emwi-height" name="height" type="number" value="<?php echo esc_html($_GET['height']); ?>">
		  <label>MIME类型</label>
		  <input id="emwi-mime-type" name="mime-type" type="text" value="<?php echo esc_html($_GET['mime-type']); ?>">
		</div>
	  </div>
	  <div id="emwi-buttons-row">
		<input type="hidden" name="action" value="add_external_media_without_import">
		<span class="spinner"></span>
		<input type="button" id="emwi-clear" class="button" value="清除">
		<input type="submit" id="emwi-add" class="button button-primary" value="添加">
		<?php if($is_in_upload_ui): ?>
		  <input type="button" id="emwi-cancel" class="button" value="取消">
		<?php endif; ?>
	  </div>
	</div><?php
}
function wp_ajax_add_external_media_without_import(){
	$info = add_external_media_without_import();
	if(isset($info['id'])){
		if($attachment = wp_prepare_attachment_for_js($info['id'])){
			wp_send_json_success($attachment);
		}else{
			$info['error'] = '相关JS加载失败';
			wp_send_json_error($info);
		}
	}else{
		wp_send_json_error($info);
	}
}
function admin_post_add_external_media_without_import(){
	$info = add_external_media_without_import();
	$redirect_url = 'upload.php';
	if(!isset($info['id'])){
		$redirect_url = $redirect_url.'?page=add-from-url&url='.urlencode($info['url']);
		$redirect_url = $redirect_url.'&error='.urlencode($info['error']);
		$redirect_url = $redirect_url.'&width='.urlencode($info['width']);
		$redirect_url = $redirect_url.'&height='.urlencode($info['height']);
		$redirect_url = $redirect_url.'&mime-type='.urlencode( $info['mime-type']);
	}
	wp_redirect(admin_url($redirect_url));
	exit;
}
function sanitize_and_validate_input(){
	$input = array(
		'url' => esc_url_raw($_POST['url']),
		'width' => sanitize_text_field($_POST['width']),
		'height' => sanitize_text_field($_POST['height']),
		'mime-type' => sanitize_mime_type($_POST['mime-type'])
	);
	$width_str = $input['width'];
	$width_int = intval($width_str);
	if(!empty($width_str)&&$width_int<=0){
		$input['error'] = '图像大小不合法';
		return $input;
	}
	$height_str = $input['height'];
	$height_int = intval($height_str);
	if(!empty($height_str)&&$height_int<=0){
		$input['error'] = '图像大小不合法';
		return $input;
	}
	$input['width'] = $width_int;
	$input['height'] = $height_int;
	return $input;
}
function add_external_media_without_import(){
	$input = sanitize_and_validate_input();
	if(isset($input['error'])) return $input;
	$url = $input['url'];
	$width = $input['width'];
	$height = $input['height'];
	$mime_type = $input['mime-type'];
	if(empty($width)||empty($height)||empty($mime_type)){
		$image_size = @getimagesize($url);
		if(empty($image_size)){
			if(empty($mime_type)){
				$response = wp_remote_head($url);
				if(is_array($response)&&isset($response['headers']['content-type'])){
					$input['mime-type'] = $response['headers']['content-type'];
				}
			}
			$input['error'] = '无法获取图像大小';
			return $input;
		}
		if(empty($width)) $width = $image_size[0];
		if(empty($height)) $height = $image_size[1];
		if(empty($mime_type)) $mime_type = $image_size['mime'];
	}
	$filename = wp_basename($url);
	$attachment = array(
		'guid' => $url,
		'post_mime_type' => $mime_type,
		'post_title' => preg_replace('/\.[^.]+$/','',$filename),
	);
	$attachment_metadata = array('width'=>$width,'height'=>$height,'file'=>$filename);
	$attachment_metadata['sizes'] = array('full'=>$attachment_metadata);
	$attachment_id = wp_insert_attachment($attachment);
	wp_update_attachment_metadata($attachment_id,$attachment_metadata);
	$input['id'] = $attachment_id;
	return $input;
}