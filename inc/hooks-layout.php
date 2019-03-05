<?php

namespace Waboot\components\woocommerce_standard;
use Waboot\Layout;

/**
 * Inject WooCommerce-specific conditions for displaying the page title in 'above_primary' context. The 'below-primary' context in managed in archive-product.php
 *
 * @hooked 'woocommerce_before_main_content'
 */
function alter_archive_title_when_shop_title_above_primary(){
	add_filter("waboot/singular/title", function($title,$current_title_context){
		if($current_title_context === 'top' && is_shop()){
			$title = woocommerce_page_title(false);
		}
		return $title;
	},10,2);
	add_filter("waboot/singular/title/display_flag",function($can_display_title,$current_title_context){
		if(is_shop()){
			if($current_title_context === 'top'){
				$wb_wc_title_position_opt = 'woocommerce_shop_title_position';
				$wb_wc_title_display_opt = 'woocommerce_shop_display_title';
				$can_display_title = apply_filters( 'woocommerce_show_page_title', \Waboot\functions\get_option($wb_wc_title_display_opt) ) && \Waboot\functions\get_option($wb_wc_title_position_opt) === "top";
			}
		}
		return $can_display_title;
	},10,2);
	remove_action("waboot/layout/archive/page_title/after",'Waboot\hooks\display_taxonomy_description',20);
}
//add_action('woocommerce_before_main_content', __NAMESPACE__.'\\alter_archive_title_when_shop_title_above_primary',9); //Disable the default display action for the page title

/**
 * Adds conditions by title displaying
 *
 * @param $can_display_title
 * @param $current_title_position
 *
 * @return bool
 */
function alter_entry_title_visibility($can_display_title, $current_title_position){
	switch($current_title_position){
		//Print entry header INSIDE the entries:
		case "bottom":
			//PLEASE NOTE: in reality, we need the "top" condition ONLY. The bottom condition is handled in our archive-product.php
            if(\is_product_category() || (is_tax() && \Woocommerce_Standard::is_woocommerce_taxonomy(get_queried_object()->taxonomy))){
				$can_display_title = \Waboot\functions\get_option("woocommerce_archives_title_position") === "bottom" && (bool) \Waboot\functions\get_option("woocommerce_archives_display_title");
			}elseif(\is_shop()){
				$can_display_title = \Waboot\functions\get_option("woocommerce_shop_title_position") === "bottom" && (bool) \Waboot\functions\get_option("woocommerce_shop_display_title");
			}
			break;
		//Print entry header OUTSIDE the single entry:
		case "top":
            if(\is_product_category() || (is_tax() && \Woocommerce_Standard::is_woocommerce_taxonomy(get_queried_object()->taxonomy))){
				$can_display_title = \Waboot\functions\get_option("woocommerce_archives_title_position") === "top" && (bool) \Waboot\functions\get_option("woocommerce_archives_display_title");
			}elseif(\is_shop()){
				$can_display_title = \Waboot\functions\get_option("woocommerce_shop_title_position") === "top" && (bool) \Waboot\functions\get_option("woocommerce_shop_display_title");
			}
			break;
	}
	return $can_display_title;
}
add_filter("waboot/singular/title/display_flag", __NAMESPACE__.'\\alter_entry_title_visibility',10,2);

/**
 * Alter body layout for WooCommerce part of the site
 *
 * @hooked "waboot/layout/body_layout"
 *
 * @param $layout
 *
 * @return string
 */
function alter_body_layout($layout){
	if(is_product_category()){
		$layout = \Waboot\functions\get_option('woocommerce_sidebar_layout');
	}elseif(is_shop()) {
		$layout = \Waboot\functions\get_option('woocommerce_shop_sidebar_layout');
	}
	return $layout;
}
add_filter("waboot/layout/body_layout", __NAMESPACE__.'\\alter_body_layout', 90);

/**
 * Alter col sizes for WooCommerce part of the site
 *
 * @hooked 'waboot/layout/get_cols_sizes'
 *
 * @param $sizes
 *
 * @return array
 */
function alter_col_sizes($sizes){
	if(!is_woocommerce()) return $sizes;

	global $post;
	$do_calc = false;
	if(is_shop()){
		$sizes = array("main"=>12);
		//Primary size
		$primary_sidebar_width = \Waboot\functions\get_option('woocommerce_shop_primary_sidebar_size');
		if(!$primary_sidebar_width){
			$primary_sidebar_width = 0;
		}
		//Secondary size
		$secondary_sidebar_width = \Waboot\functions\get_option('woocommerce_shop_secondary_sidebar_size');
		if(!$secondary_sidebar_width){
			$secondary_sidebar_width = 0;
		}
		$do_calc = true;
	}elseif(is_product_category()){
		$sizes = array("main"=>12);
		//Primary size
		$primary_sidebar_width = \Waboot\functions\get_option('woocommerce_primary_sidebar_size');
		if(!$primary_sidebar_width) $primary_sidebar_width = 0;
		//Secondary size
		$secondary_sidebar_width = \Waboot\functions\get_option('woocommerce_secondary_sidebar_size');
		if(!$secondary_sidebar_width){
			$secondary_sidebar_width = 0;
		}
		$do_calc = true;
	}

	if($do_calc){
		if (\Waboot\functions\body_layout_has_two_sidebars()) {
			//Main size
			$mainwrap_size = 12 - WabootLayout()->layout_width_to_int($primary_sidebar_width) - WabootLayout()->layout_width_to_int($secondary_sidebar_width);
			$sizes = array("main"=>$mainwrap_size,"primary"=> WabootLayout()->layout_width_to_int($primary_sidebar_width),"secondary"=> WabootLayout()->layout_width_to_int($secondary_sidebar_width));
		}else{
			if(\Waboot\functions\get_body_layout() !== "full-width"){
				$mainwrap_size = 12 - WabootLayout()->layout_width_to_int($primary_sidebar_width);
				$sizes = array("main"=>$mainwrap_size,"primary"=> WabootLayout()->layout_width_to_int($primary_sidebar_width));
			}
		}
	}

	return $sizes;
}
add_filter("waboot/layout/get_cols_sizes", __NAMESPACE__.'\\alter_col_sizes', 90);

/**
 * Hides prices (in catalog) and add-to-cart button
 *
 * @hooked 'init'
 */
function hidePriceAndCart(){
	if((bool) \Waboot\functions\get_option("woocommerce_hide_price")) {
		remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
		remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
	}
	if((bool) \Waboot\functions\get_option("woocommerce_catalog")) {
		remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
		remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
	}
}
add_action('init', __NAMESPACE__.'\\hidePriceAndCart', 20);

/**
 * Retrieves the correct behavior value for shop page and 'content-width' behavior
 *
 * @param array $classes
 *
 * @hooked "waboot/layout/container/classes"
 *
 * @return array
 */
function alter_container_classes_for_shop_page($classes){
	if(is_shop()){
		$page_shop_id = wc_get_page_id( 'shop' );
		foreach ($classes as $k => $class_name){
			if($class_name === WabootLayout()->get_grid_class(Layout::GRID_CLASS_CONTAINER) || $class_name === WabootLayout()->get_grid_class(Layout::GRID_CLASS_CONTAINER_FLUID)){
				//We REPLACE the grid class with the behavior
				$b = \WBF\modules\behaviors\get_behavior('content-width',$page_shop_id);
				$classes[$k] =  WabootLayout()->get_grid_class($b);
				break;
			}
		}
	}
	return $classes;
}
add_filter("waboot/layout/container/classes",__NAMESPACE__.'\\alter_container_classes_for_shop_page');