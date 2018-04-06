<?php
/**
Component Name: WooCommerce Standard
Description: An initial customization for WooCommerce
Category: Utilities
Tags: Woocommerce
Version: 1.0
Author: WAGA Team <dev@waga.it>
Author URI: http://www.waga.it
 */

if(!class_exists("\\WBF\\modules\\components\\Component")) return;

class Woocommerce_Standard extends \WBF\modules\components\Component{
	public function setup(){
		global $woocommerce;
		parent::setup();
		if(!isset($woocommerce)) return;
		$this->declare_hooks();
	}

	private function declare_hooks(){
		if(\Waboot\functions\get_option('woocommerce_no_native_styles',true)){
			//Disable the default Woocommerce stylesheet
			add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
		}

		//Disabling actions
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

		//Enable the modification of WooCommerce query and loop
		add_filter("loop_shop_per_page", [$this,"alter_posts_per_page"], 20);
		add_filter("loop_shop_columns", [$this,"alter_loop_columns"]);
		add_filter("post_type_archive_title",[$this,"alter_archive_page_title"],10,2);

		//Layout altering:
		require_once $this->directory.'/inc/hooks-layout.php';

		//Templates
		if(\Waboot\functions\get_option('woocommerce_waboot_templates_hooks',true)){
			require_once $this->directory.'/inc/hooks-templates.php';
		}

		//Behaviors
		add_filter("wbf/modules/behaviors/get/primary-sidebar-size", [$this,"primary_sidebar_size_behavior"], 999);
		add_filter("wbf/modules/behaviors/get/secondary-sidebar-size", [$this,"secondary_sidebar_size_behavior"], 999);

		// Theme Options
		add_filter("wbf/theme_options/get/blog_primary_sidebar_size",[$this,"primary_sidebar_size_option"],999);
		add_filter("wbf/theme_options/get/blog_secondary_sidebar_size",[$this,"primary_sidebar_size_option"],999);
	}

	/**
	 * Enqueue styles
	 */
	public function styles(){
		if(\Waboot\functions\get_option('woocommerce_waboot_styles',true)){
			wp_enqueue_style("component-{$this->name}-style",$this->directory_uri . '/assets/dist/css/woocommerce-standard.min.css');
		}
	}

	/**
	 * Register options
	 */
	public function register_options(){
		$orgzr = \WBF\modules\options\Organizer::getInstance();

		$layouts = \WBF\modules\options\of_add_default_key(\Waboot\hooks\options\_get_available_body_layouts());
		if(isset($layouts['values'][0]['thumb'])){
			$opt_type = "images";
			foreach($layouts['values'] as $k => $v){
				$final_layouts[$v['value']]['label'] = $v['name'];
				$final_layouts[$v['value']]['value'] = isset($v['thumb']) ? $v['thumb'] : "";
			}
		}else{
			$opt_type = "select";
			foreach($layouts['values'] as $k => $v){
				$final_layouts[$v['value']]['label'] = $v['name'];
			}
		}

		/*
		 * Standard group:
		 */

		$orgzr->set_group("components");

		$section_name = $this->name."_component";
		$additional_params = [
			'component' => true,
			'component_name' => $this->name
		];

		$orgzr->add_section($section_name,$this->name." Component",null,$additional_params);

		$orgzr->set_section($section_name);

		$orgzr->add([
			'type' => 'info',
			'name' => 'This component needs no administration options.',
			'desc' => 'Check <strong>theme options</strong> for additional settings'
		]);

		$orgzr->reset_group();
		$orgzr->reset_section();

		/*
		 * WOOCOMMERCE PAGE TAB
		 */

		$orgzr->add_section("woocommerce",__( 'WooCommerce', 'waboot' ));

		$orgzr->set_section("woocommerce");

		$orgzr->add(array(
			'name' => _x( 'General modifications', 'WooCommerce Standard component' , 'waboot' ),
			'desc' => '',
			'type' => 'info'
		));

		$orgzr->add(array(
			'name' => __( 'Disable WooCommerce native styles', 'waboot' ),
			'desc' => __( 'Check this box to enable WooCommerce native styles.', 'waboot' ),
			'id'   => 'woocommerce_no_native_styles',
			'std'  => '1',
			'type' => 'checkbox'
		));

		$orgzr->add(array(
			'name' => __( 'Enable Waboot WooCommerce styles', 'waboot' ),
			'desc' => __( 'Check this box to enable Waboot specific WooCommerce styles.', 'waboot' ),
			'id'   => 'woocommerce_waboot_styles',
			'std'  => '1',
			'type' => 'checkbox'
		));

		$orgzr->add(array(
			'name' => _x( 'Enable Waboot templates hooks', 'WooCommerce Standard component', 'waboot' ),
			'desc' => __( 'If checked, the theme will make use of special templates adjustment made for Waboot', 'waboot' ),
			'id'   => 'woocommerce_waboot_templates_hooks',
			'std'  => '1',
			'type' => 'checkbox'
		));

		$orgzr->add(array(
			'name' => __( 'WooCommerce Shop Page', 'waboot' ),
			'desc' => __( '', 'waboot' ),
			'type' => 'info'
		));

		$orgzr->add(array(
			'name' => __('WooCommerce Shop Layout', 'waboot'),
			'desc' => __('Select WooCommerce shop page layout', 'waboot'),
			'id' => 'woocommerce_shop_sidebar_layout',
			'std' => $layouts['default'],
			'type' => $opt_type,
			'options' => $final_layouts
		));

		$orgzr->add(array(
			'name' => __("Primary Sidebar width","waboot"),
			'desc' => __("Choose the primary sidebar width","waboot"),
			'id' => 'woocommerce_shop_primary_sidebar_size',
			'std' => '1/4',
			'type' => "select",
			'options' => array("1/2"=>"1/2","1/3"=>"1/3","1/4"=>"1/4","1/6"=>"1/6")
		));

		$orgzr->add(array(
			'name' => __("Secondary Sidebar width","waboot"),
			'desc' => __("Choose the secondary sidebar width","waboot"),
			'id' => 'woocommerce_shop_secondary_sidebar_size',
			'std' => '1/4',
			'type' => "select",
			'options' => array("1/2"=>"1/2","1/3"=>"1/3","1/4"=>"1/4","1/6"=>"1/6")
		));

		$orgzr->add(array(
			'name' => __( 'Display WooCommerce page title', 'waboot' ),
			'desc' => __( 'Check this box to show page title.', 'waboot' ),
			'id'   => 'woocommerce_shop_display_title',
			'std'  => '1',
			'type' => 'checkbox'
		));

		$orgzr->add(array(
			'name' => __('Title position', 'waboot'),
			'desc' => __('Select where to display page title', 'waboot'),
			'id' => 'woocommerce_shop_title_position',
			'std' => 'top',
			'type' => 'select',
			'options' => array('top' => __("Above primary","waboot"), 'bottom' => __("Below primary","waboot"))
		));

		$orgzr->add(array(
			'name' => __( 'WooCommerce Archives and Categories', 'waboot' ),
			'desc' => __( '', 'waboot' ),
			'type' => 'info'
		));

		$orgzr->add(array(
			'name' => __('WooCommerce Archive Layout', 'waboot'),
			'desc' => __('Select Woocommerce archive layout', 'waboot'),
			'id' => 'woocommerce_sidebar_layout',
			'std' => $layouts['default'],
			'type' => $opt_type,
			'options' => $final_layouts
		));

		$orgzr->add(array(
			'name' => __("Primary Sidebar width","waboot"),
			'desc' => __("Choose the primary sidebar width","waboot"),
			'id' => 'woocommerce_primary_sidebar_size',
			'std' => '1/4',
			'type' => "select",
			'options' => array("1/2"=>"1/2","1/3"=>"1/3","1/4"=>"1/4","1/6"=>"1/6")
		));

		$orgzr->add(array(
			'name' => __("Secondary Sidebar width","waboot"),
			'desc' => __("Choose the secondary sidebar width","waboot"),
			'id' => 'woocommerce_secondary_sidebar_size',
			'std' => '1/4',
			'type' => "select",
			'options' => array("1/2"=>"1/2","1/3"=>"1/3","1/4"=>"1/4","1/6"=>"1/6")
		));

		$orgzr->add(array(
			'name' => __( 'Display WooCommerce page title', 'waboot' ),
			'desc' => __( 'Check this box to show page title.', 'waboot' ),
			'id'   => 'woocommerce_archives_display_title',
			'std'  => '1',
			'type' => 'checkbox'
		));

		$orgzr->add(array(
			'name' => __('Title position', 'waboot'),
			'desc' => __('Select where to display page title', 'waboot'),
			'id' => 'woocommerce_archives_title_position',
			'std' => 'top',
			'type' => 'select',
			'options' => array('top' => __("Above primary","waboot"), 'bottom' => __("Below primary","waboot"))
		));

		$orgzr->add(array(
			'name' => __('Items for Row', 'waboot'),
			'desc' => __('How many items display for row', 'waboot'),
			'id' => 'woocommerce_cat_items',
			'std' => '3',
			'type' => 'select',
			'options' => array(
				'3' => '3',
				'4' => '4'
			)
		));

		$orgzr->add(array(
			'name' => __('Products per page', 'waboot'),
			'desc' => __('How many products display per page', 'waboot'),
			'id' => 'woocommerce_products_per_page',
			'std' => '10',
			'type' => 'text'
		));

		$orgzr->add(array(
			'name' => __( 'Catalog Mode', 'waboot' ),
			'desc' => __( 'Hide add to cart button', 'waboot' ),
			'id'   => 'woocommerce_catalog',
			'std'  => '0',
			'type' => 'checkbox'
		));

		$orgzr->add(array(
			'name' => __( 'Hide Price', 'waboot' ),
			'desc' => __( 'Hide price in catalog', 'waboot' ),
			'id'   => 'woocommerce_hide_price',
			'std'  => '0',
			'type' => 'checkbox'
		));

		$orgzr->reset_group();
		$orgzr->reset_section();
	}

	/**
	 * @hooked 'post_type_archive_title'
	 *
	 * @param $title
	 * @param $post_type
	 *
	 * @return mixed
	 */
	public function alter_archive_page_title($title,$post_type){
		if($post_type !== 'product') return $title;
		return \Waboot\woocommerce\get_shop_page_title();
	}

	/**
	 * @param \WBF\modules\behaviors\Behavior $b
	 *
	 * @return \WBF\modules\behaviors\Behavior
	 */
	public function primary_sidebar_size_behavior(\WBF\modules\behaviors\Behavior $b){
		if(!is_woocommerce()) return $b;

		if(is_shop()){
			$primary_sidebar_width = \Waboot\functions\get_option('woocommerce_shop_primary_sidebar_size');
			if(!$primary_sidebar_width){
				$primary_sidebar_width = 0;
			}
			$b->value = $primary_sidebar_width;
		}elseif(is_product_category()){
			$primary_sidebar_width = \Waboot\functions\get_option('woocommerce_primary_sidebar_size');
			if(!$primary_sidebar_width){
				$primary_sidebar_width = 0;
			}
			$b->value = $primary_sidebar_width;
		}

		return $b;
	}

	/**
	 * @param $value
	 *
	 * @return bool|int|mixed
	 */
	public function primary_sidebar_size_option($value){
		if(!is_woocommerce()) return $value;

		if(is_shop()){
			$primary_sidebar_width = \Waboot\functions\get_option('woocommerce_shop_primary_sidebar_size');
			if(!$primary_sidebar_width){
				$primary_sidebar_width = 0;
			}
			$value = $primary_sidebar_width;
		}elseif(is_product_category()){
			$primary_sidebar_width = \Waboot\functions\get_option('woocommerce_primary_sidebar_size');
			if(!$primary_sidebar_width){
				$primary_sidebar_width = 0;
			}
			$value = $primary_sidebar_width;
		}

		return $value;
	}

	/**
	 * @param \WBF\modules\behaviors\Behavior $b
	 *
	 * @return \WBF\modules\behaviors\Behavior
	 */
	public function secondary_sidebar_size_behavior(\WBF\modules\behaviors\Behavior $b){
		if(!is_woocommerce()) return $b;

		if(is_shop()){
			$secondary_sidebar_width = \Waboot\functions\get_option('woocommerce_shop_secondary_sidebar_size');
			if(!$secondary_sidebar_width){
				$secondary_sidebar_width = 0;
			}
			$b->value = $secondary_sidebar_width;
		}elseif(is_product_category()){
			$secondary_sidebar_width = \Waboot\functions\get_option('woocommerce_secondary_sidebar_size');
			if(!$secondary_sidebar_width){
				$secondary_sidebar_width = 0;
			}
			$b->value = $secondary_sidebar_width;
		}

		return $b;
	}

	/**
	 * @param $value
	 *
	 * @return bool|int|mixed
	 */
	public function primary_secondary_size_option($value){
		if(!is_woocommerce()) return $value;

		if(is_shop()){
			$primary_sidebar_width = \Waboot\functions\get_option('woocommerce_shop_secondary_sidebar_size');
			if(!$primary_sidebar_width){
				$primary_sidebar_width = 0;
			}
			$value = $primary_sidebar_width;
		}elseif(is_product_category()){
			$primary_sidebar_width = \Waboot\functions\get_option('woocommerce_secondary_sidebar_size');
			if(!$primary_sidebar_width){
				$primary_sidebar_width = 0;
			}
			$value = $primary_sidebar_width;
		}

		return $value;
	}

	/**
	 * Alter product per page
	 *
	 * @hooked 'loop_shop_per_page'
	 *
	 * @param string|int $posts_per_page
	 *
	 * @return int
	 */
	public function alter_posts_per_page($posts_per_page){
		$n = intval(\Waboot\functions\get_option('woocommerce_products_per_page'));
		if(is_integer($n)){
			$posts_per_page = $n;
		}
		return $posts_per_page;
	}

	/**
	 * Alter loop colums
	 */
	public function alter_loop_columns() {
		$cat_items = of_get_option('woocommerce_cat_items','3');
		return $cat_items;
	}
}