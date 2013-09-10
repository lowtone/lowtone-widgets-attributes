<?php
/*
 * Plugin Name: Custom Attributes for Widgets
 * Plugin URI: http://wordpress.lowtone.nl/plugins/widgets-attributes/
 * Plugin Type: plugin
 * Description: Define custom ID and class values for widgets.
 * Version: 1.0
 * Author: Lowtone <info@lowtone.nl>
 * Author URI: http://lowtone.nl
 * License: http://wordpress.lowtone.nl/license
 */
/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2013, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\widgets\attributes
 */

namespace lowtone\widgets\attributes {

	// Add input to widget form

	add_action("in_widget_form", function($widget, $return, $instance) {
		$idBase = "widget-" . $widget->id_base;
	
		$id =  $idBase . "-" . $widget->number;
		$nameBase = $idBase . "[" . $widget->number . "]";

		$instance = array_merge(array(
				"lowtone_widgets_attributes" => array("id" => "", "class" => ""),
			), (array) $instance);

		echo '<fieldset class="lowtone" style="margin: 1em 0">' . 
			'<legend><span>' . __("Widget attributes", "lowtone_widgets_text_attributes") . '</span></legend>' . 
			'<p>' . 
			sprintf('<label for="%s-id" class="lowtone">', $id) . __("Id", "lowtone_widgets_text_attributes") . '</label>' . 
			sprintf('<input id="%s-id" type="text" name="%s[lowtone_widgets_attributes][id]" value="%s" class="lowtone text">', $id, $nameBase, esc_attr($instance["lowtone_widgets_attributes"]["id"])) . 
			'</p>' . 
			'<p>' . 
			sprintf('<label for="%s-class" class="lowtone">', $id) . __("Class", "lowtone_widgets_text_attributes") . '</label>' . 
			sprintf('<input id="%s-class" type="text" name="%s[lowtone_widgets_attributes][class]" value="%s" class="lowtone text">', $id, $nameBase, esc_attr($instance["lowtone_widgets_attributes"]["class"])) . 
			'</p>' . 
			'</fieldset>';
	}, 20, 3);

	// Add input to instance

	add_filter("widget_update_callback", function($instance, $new, $old, $widget) {
		if (!isset($_POST["widget-" . $widget->id_base][$widget->number]["lowtone_widgets_attributes"]))
			return $instance;

		$instance["lowtone_widgets_attributes"] = $_POST["widget-" . $widget->id_base][$widget->number]["lowtone_widgets_attributes"];

		return $instance;
	}, 10, 4);

	// Redefine before_widget

	add_filter("dynamic_sidebar_params", function($params) {
		global $wp_registered_sidebars, $wp_registered_widgets;

		$widgetId = $params[0]["widget_id"];
		$number = $params[1]["number"];

		if (!isset($wp_registered_widgets[$widgetId]))
			return $params;

		// Set widget

		$widget = $wp_registered_widgets[$widgetId];

		// Set sidebar

		if (!isset($wp_registered_sidebars[$params[0]["id"]]))
			return $params;

		$sidebar = $wp_registered_sidebars[$params[0]["id"]];

		// Default attributes

		$id = $widget["id"];

		$class = "widget " . implode("_", array_map(function($class) {return is_object($class) ? get_class($class) : (string) $class;}, (array) $widget["classname"]));

		// Fetch settings
		
		if (!(isset($widget["callback"][0]) && $widget["callback"][0] instanceof \WP_Widget))
			return $params;

		$settings = $widget["callback"][0]->get_settings();

		if (!isset($settings[$number])) 
			return $params;

		$settings = $settings[$number];

		// Check for attributes

		if (isset($settings["lowtone_widgets_attributes"])) {
			$attributes = (array) $settings["lowtone_widgets_attributes"];

			if (isset($attributes["id"]) && ($customId = trim($attributes["id"])))
				$id = sprintf($customId, $number);

			if (isset($attributes["class"]) && ($customClass = trim($attributes["class"])))
				$class .= " " . $customClass;

		}

		// Apply filters

		$id = apply_filters("lowtone_widgets_attributes_id", $id, $widget);
		$class = apply_filters("lowtone_widgets_attributes_class", $class, $widget);

		// Redifine before_widget

		$params[0]['before_widget'] = sprintf($sidebar['before_widget'], $id, $class);

		return $params;
	});

}