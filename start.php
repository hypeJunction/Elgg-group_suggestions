<?php

/**
 * Group Suggestions
 *
 * @author Ismayil Khayredinov <info@hypejunction.com>
 * @copyright Copyright (c) 2015, Ismayil Khayredinov
 */
require_once __DIR__ . '/autoloader.php';

elgg_register_event_handler('init', 'system', 'group_suggestions_init');

/**
 * Initialize the plugin
 * @return void
 */
function group_suggestions_init() {

	elgg_register_plugin_hook_handler('route', 'groups', 'group_suggestions_router');
	elgg_register_plugin_hook_handler('sort_relationships', 'group', 'group_suggestions_sort_relationships');
	elgg_register_plugin_hook_handler('rel_options', 'group', 'group_suggestions_rel_options');

	// Register a filter menu item from within a view so it only shows in groups context
	elgg_extend_view('groups/group_sort_menu', 'groups/suggested/filter', 1);
}

/**
 * Route suggested groups pages
 *
 * @param string $hook   "route"
 * @param string $type   "groups"
 * @param array  $return Identifier and segments
 * @param array  $params Hook params
 * @return array
 */
function group_suggestions_router($hook, $type, $return, $params) {

	if (!is_array($return)) {
		return;
	}

	// Initial page identifier might be different from /groups
	// i.e. subtype specific handler e.g. /schools
	$initial_identifier = elgg_extract('identifier', $params);
	$identifier = elgg_extract('identifier', $return);
	$segments = elgg_extract('segments', $return);

	if ($identifier !== 'groups') {
		return;
	}

	$page = array_shift($segments);
	if (!$page) {
		$page = 'all';
	}

	// we want to pass the original identifier to the resource view
	// doing this via route hook in order to keep the page handler intact
	$resource_params = array(
		'identifier' => $initial_identifier ? : 'groups',
		'segments' => $segments,
	);

	switch ($page) {
		case 'suggested':
			$guid = array_shift($segments);
			$resource_params['guid'] = $guid;
			echo elgg_view_resource('groups/suggested', $resource_params);
			return false;
	}
}

/**
 * Add group specific relationship field options
 * 
 * @param string $hook   "sort_relationships"
 * @param string $type   "group"
 * @param array  $return Fields
 * @param array  $params Hook params
 * @return array
 */
function group_suggestions_sort_relationships($hook, $type, $return, $params) {

	$return[] = 'suggested';
	return $return;
}

/**
 * Relationship options
 *
 * @param string $hook    "rel_options"
 * @param string $type    "user"
 * @param array  $options Options
 * @param array  $params  Hook params
 * @return array
 */
function group_suggestions_rel_options($hook, $type, $options, $params) {

	$page_owner = elgg_extract('user', $params);
	if (!isset($page_owner)) {
		$page_owner = elgg_get_page_owner_entity();
	}
	
	$rel = elgg_extract('rel', $params);
	if ($rel == 'suggested') {
		return group_suggestions_add_match_queries($options, $page_owner);
	}
}

/**
 * Add ege* queries to retrieve suggested groups
 * 
 * @param array    $options    ege* options
 * @param ElggUser $page_owner Page owner
 */
function group_suggestions_add_match_queries(array $options = array(), ElggUser $page_owner = null) {

	$dbprefix = elgg_get_config('dbprefix');

	if (!isset($page_owner)) {
		$page_owner = elgg_get_logged_in_user_entity();
	}

	$guid = (int) $page_owner->guid;

	// Not a member yet
	$options['wheres']['not_member'] = "NOT EXISTS (SELECT 1 FROM {$dbprefix}entity_relationships
		WHERE guid_one = $guid AND relationship = 'member' AND guid_two = e.guid)";

	$order_by = array();
	$group_by = array();
	$wheres = array();

	$user_groups_in = "SELECT guid_two FROM {$dbprefix}entity_relationships WHERE guid_one = $guid AND relationship = 'member'";

	// Match members of groups that user is a member of with
	// members of groups that user is not a member of
	$options['joins']['members'] = "JOIN {$dbprefix}entity_relationships members ON members.guid_two = e.guid AND members.relationship = 'member'";
	$wheres[] = "(members.guid_one IN (SELECT DISTINCT(guid_one) FROM {$dbprefix}entity_relationships WHERE relationship = 'member' AND guid_two IN ($user_groups_in)))";
	$options['selects']['score'] = "COUNT(DISTINCT(members.guid_one)) AS score";
	$options['selects']['shared_members'] = "GROUP_CONCAT(DISTINCT(members.guid_one)) as shared_members";
	
	// Match user tags against group tags
	$tag_names = elgg_get_registered_tag_metadata_names();
	if ($tag_names) {
		$tag_name_ids = array(0);
		foreach ($tag_names as $tag_name) {
			$tag_name_ids[] = (int) elgg_get_metastring_id($tag_name);
		}
		$tag_names_in = implode(',', array_unique($tag_name_ids));

		// Match tags by value id
		$options['joins']['tags'] = "JOIN {$dbprefix}metadata tags ON tags.entity_guid = e.guid AND tags.name_id IN ($tag_names_in)";
		$wheres[] = "(tags.value_id IN (SELECT value_id FROM {$dbprefix}metadata WHERE entity_guid = $guid AND name_id IN ($tag_names_in)))";
		$options['selects']['score'] = "COUNT(DISTINCT(members.guid_one)) + COUNT(DISTINCT(tags.value_id)) AS score";
		$options['selects']['shared_tags'] = "GROUP_CONCAT(DISTINCT(tags.value_id)) as shared_tags";
		$group_by[] = 'tags.entity_guid';
	}
	
	$options['order_by'] = 'score DESC';
	$options['group_by'] = 'e.guid';
	$options['wheres'][] = implode(' OR ', $wheres);
	
	return $options;
}
