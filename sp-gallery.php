<?php
/*
Plugin Name: Simple Wordpress Gallery PRO
Plugin URI: http://atomixstar.com
Description: Adds a gallery shortcode with way more options than the standard WordPress gallery shortcode.
Author: Atomixstar and Gyurka Mircea
Version: 1.1
Author URI: http://atomixstar.com

*/

if( !class_exists( 'SP_Gallery' ) ) {
	class SP_Gallery {

		private $url;

		private $large_width = 589; //Default value
		private $large_height = 430; //Default value
		private $small_width = 48; //Default value
		private $small_height = 48; //Default value
		private $folder; //Default value
		private $options = array(); //
		private $pluginDomain = 'simple_gallery'; //
		private $supportUrl = 'http://support.makedesignnotwar.com/categories/simple-wordpress-gallery';

		private static $_meta_inGallery = '_sp_gallery_image';

		public function __construct() {
			$this->addActions();
			$this->addFilters();
			$this->addImageSize();
			// customize this if not in the template's plugins/gallery dir
			$this->url = WP_CONTENT_URL . '/plugins/'.end(explode('/',dirname(__file__)));

			$this->options = get_option('sp_gallery');

			if($this->options['large_height']) //Custom values have been set. Overide.
				$this->large_height = $this->options['large_height'];

			if($this->options['large_width']) //Custom values have been set. Overide.
				$this->large_width = $this->options['large_width'];

			if($this->options['small_height']) //Custom values have been set. Overide.
				$this->small_height = $this->options['small_height'];

			if($this->options['small_width']) //Custom values have been set. Overide.
				$this->small_width = $this->options['small_width'];
		}

		private function addImageSize() {
			add_image_size('sp-gallery-thumb', $this->small_width, $this->small_height, true);
			add_image_size('sp-gallery-large', $this->large_width, $this->large_height );
		}

		private function addActions() {
			add_action('admin_init', array($this,'enqueueAdminCss'));
			add_action('wp_head', array($this, 'enqueueIECss'));
			add_action('template_redirect', array($this, 'enqueueFrontEnd'));
			add_action('admin_menu', array($this,'admin_setup'));
		}

		private function addFilters() {
			add_filter('attachment_fields_to_edit', array($this,'galleryImageFormFields'), 100, 2);
			add_filter('attachment_fields_to_save', array($this,'galleryImageFormSave'), 10, 2 );
			add_filter('post_gallery', array($this,'gallery'),10,2);
		}

		public function enqueueFrontEnd() {
			if (is_singular() || $this->archive_has_gallery()){
				wp_register_script('jquery-cycle', $this->url.'/resources/jquery.cycle.min.js', array('jquery'), '2.86', true );
				wp_enqueue_script('sp-gallery', $this->url.'/resources/sp-gallery.js', array('jquery-cycle'), '', true );
				wp_enqueue_style('sp-gallery', $this->url.'/resources/sp-gallery.css');
			}
		}

		public function enqueueAdminCss() {
			wp_enqueue_style('sp-gallery-admin', $this->url.'/resources/sp-gallery-admin.css');
		}

		public function enqueueIECss() {
			if (is_singular() || $this->archive_has_gallery()) {
				echo '<!--[if lt IE 8]><link rel="stylesheet" href="'.$this->url.'/resources/ie7.css" type="text/css" media="screen"><![endif]-->'."\n";
				echo '<!--[if IE 8]><link rel="stylesheet" href="'.$this->url.'/resources/ie8.css" type="text/css" media="screen"><![endif]-->'."\n";
			}
		}

		private function archive_has_gallery(){
				
				global $wp_query;
				foreach($wp_query->posts as $post)
					if(strpos($post->post_content,'[gallery') !== false)
						return true;

		}
		public function galleryImageFormFields($form_fields, $post) {
			if ( substr($post->post_mime_type, 0, 5) == 'image' ) {
				$inGallery = get_post_meta($post->ID, self::$_meta_inGallery, true);
				if( '' === $inGallery ) {
					$inGallery = 1;
				} else {
					$inGallery = ('yes' == $inGallery) ? 1 : 0;
				}

				ob_start();
				include('views/image-options.php');
				$html = ob_get_clean();
				$form_fields['include-in-gallery'] = array('label'=>__('Include in Gallery'),'input'=>'html','html'=>$html);
			}
			return $form_fields;
		}

		public function galleryImageFormSave($post, $attachment) {
			if( isset( $attachment['in-gallery'] ) ) {
				$value = intval( $attachment['in-gallery']);
				$value = (1 === $value) ? 'yes' : 'no';
				update_post_meta($post['ID'],self::$_meta_inGallery,$value);
			}
			return $post;
		}

		public function gallery($content = '', $attr) {
			global $post;

// 			if(!is_singular())
// 				return;

			// define width and height in case we want to change/override later
			$width = $this->large_width;
			$height = $this->large_height;
			$thumb_width = $this->small_width;


			// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
			if ( isset( $attr['orderby'] ) ) {
				$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
				if ( !$attr['orderby'] )
					unset( $attr['orderby'] );
			}

			extract(shortcode_atts(array(
				'order'      => 'DESC',
				'orderby'    => 'menu_order ID',
				'id'         => $post->ID,
				'include'    => '',
				'exclude'    => ''
			), $attr));

			if ( 'RAND' == $order )
				$orderby = 'none';

			if($order != 'ASC' && $order != 'DESC')
				$order = 'ASC';

			$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

			$excludedAttachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby, 'meta_key' => self::$_meta_inGallery, 'meta_value' => 'no') );

			$excludedIds = array();

			if($exclude != '')
				$excludedIds = explode(',', $exclude);

			foreach($excludedAttachments as $excludedGalleryItem) {
				$excludedIds[] = $excludedGalleryItem->ID;
			}

			foreach($attachments as $key => $galleryItem) {
				if( in_array($galleryItem->ID,$excludedIds) ) {
					unset($attachments[$key]);
				}
			}


			if ( empty($attachments) ) {
				return '';
			}

			// grab info from first photo for initial state below
			$first_gal = current($attachments);
			$gal_title = $first_gal->post_title;
			$gal_caption = $first_gal->post_excerpt;
			$gal_description = $first_gal->post_content;

			if($gal_caption)
				$hide_title = 'style="display:none;"';

			foreach ( $attachments as $id => $attachment ) {

				// get full item src
				$item_src = wp_get_attachment_image_src($id, 'sp-gallery-large', false);
				$thumb_src = wp_get_attachment_image_src($id, 'sp-gallery-thumb', false);
				$thumb_src[0] = add_query_arg('w', $thumb_width, $thumb_src[0]);

				// doing some height/width ratio checks
				if ($item_src[1] > $item_src[2]) { // width greater than height
					$img_dimension = 'width="'.$width.'" style="display:inline;"';
				}
				else {
					$img_dimension = 'height="'.$height.'" style="display:block;"';
				}

				// setting up JS array
				$js_array = array('title'=>$attachment->post_title, 'caption'=>$attachment->post_excerpt,'description'=>$attachment->post_content, 'thumbnail'=>$thumb_src[0]);
				$js[] = json_encode($js_array);
				
				// do we have a caption?
				if( !empty($attachment->post_excerpt) ) {
					$caption = '<div class="caption">'.$attachment->post_excerpt.'</div>';
				}
				else {
					$caption = '';
				}

				$output .= '<li style="line-height:'.$this->large_height.'px;"><img '.$img_dimension.' src="'.$item_src[0].'" alt="" />'.$caption.'</li>';
			}
			// set up javascript
			$js = implode( ', ', $js );
			$js = '<script type="text/javascript">
					jQuery(function(){
						var spGalleryData'.$post->ID.' = ['.$js.'];
						jQuery("#sp-gallery'.$post->ID.'").data("images",spGalleryData'.$post->ID.');
						jQuery("#sp-gallery'.$post->ID.'").data("id",'.$post->ID.');
					});
				   </script>';

			// loading animation.
			$loading = '<div class="sp-gallery-loading"><span>Loading</span></div>';

			// set up gallery

			$output = "<div id='sp-gallery{$post->ID}' class='sp-gallery'><ol>{$output}</ol><div class='sp-gallery-nav-outer'>{$loading}<span class='sp-gallery-next nav' title='Next Image'>Next</span><span class='sp-gallery-prev nav' title='Previous Image'>Previous</span><div class='sp-gallery-nav-inner'><div class='sp-gallery-nav'></div></div></div></div>";
			$output .= 	'<div id="sp-gallery-meta'.$post->ID.'" class="sp-gallery-meta">'.
						'<div class="count">Picture <span class="sp-gallery-count">1</span> of '.count($attachments).' </div>'.
						'<h5 class="sp-gallery-title" '.$hide_title.'>'.$gal_title.'</h5>'.
						'<p class="sp-gallery-caption">'.$gal_caption.'</p>'.
						'<p class="sp-gallery-description">'.$gal_description.'</p>'.
						'</div>';
			$output .= '<style type="text/css">.sp-gallery,.sp-gallery li{width:'. $width .'px;height:'. $height .'px;}.sp-gallery-meta{width:'. ($width - 30) .'px;}</style>';

			return $js . $output;
		}

	function admin_setup() {
		add_submenu_page('options-general.php', 'Simple Wordpress Gallery', 'Simple Wordpress Gallery', 8, basename(__FILE__), array($this,'admin'));
	}

	function admin() {
		$options = get_option("sp_gallery");

		if ( isset($_POST['sp_gallerysubmit']) ) {
			$options = $_POST['sp_gallery'];
			$this->large_width = $options['large_width'];
			$this->large_height = $options['large_height'];
			$this->small_width = $options['small_width'];
			$this->small_height = $options['small_height'];
			update_option('sp_gallery', $options);

		}
	?>
	<div class="wrap">
			<h2><?=__('Simple Wordpress Gallery',$this->pluginDomain);?></h2>
		<div class="form">
			<h3><?php _e('Need a hand?',$this->pluginDomain); ?></h3>
			<p><?php printf( __( 'If you’re stuck on these options, please <a href="%s">check out the documentation</a>. Or, go to the <a href="%s">support forum</a>.', $this->pluginDomain ), trailingslashit($this->url) . 'docs/Main_Documentation.html', $this->supportUrl ); ?></p>

			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">

			<h3><?php _e('Settings', $this->pluginDomain); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row"><?php _e('Gallery Image Size',$this->pluginDomain); ?></th>
				<td>
					<fieldset>
					<span style="margin-left:20px;" >
						<input type="text" name="sp_gallery[large_width]" value="<?php echo $this->large_width ?>" size=4> <?php _e('wide',$this->pluginDomain); ?><?php _e(' by ', $this->pluginDomain); ?> 
						<input type="text" name="sp_gallery[large_height]" value="<?php echo $this->large_height ?>" size=4> <?php _e('tall',$this->pluginDomain); ?> <?php _e('(number)', $this->pluginDomain); ?> 
					</span>
			<br />
					</fieldset>
				</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Gallery Thumb Size',$this->pluginDomain); ?></th>
				<td>
					<fieldset>
					<span style="margin-left:20px;" >
						<input type="text" name="sp_gallery[small_width]" value="<?php echo $this->small_width ?>" size=4> <?php _e('wide',$this->pluginDomain); ?><?php _e(' by ', $this->pluginDomain); ?> 
						<input type="text" name="sp_gallery[small_height]" value="<?php echo $this->small_height ?>" size=4> <?php _e('tall',$this->pluginDomain); ?> <?php _e('(number)', $this->pluginDomain); ?> 
					</span>
			<br />
					</fieldset>
				</td>
				</tr>
				<tr>
				<td>
					<input id="sp_gallerysubmit" class="button-primary" type="submit" name="sp_gallerysubmit" value="<?php _e('Save Changes', $this->pluginDomain); ?>" />
				</td>
			</tr>
		</table>

		</form>

	</div>
	<?php
	}

	}

	global $sp_gallery;
	$sp_gallery = new SP_Gallery;
	include('lib/template-tags.php');
}
