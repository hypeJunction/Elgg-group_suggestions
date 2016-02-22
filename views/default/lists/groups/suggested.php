<?php

$entity = elgg_extract('entity', $vars);
$guid = (int) $entity->guid;

$identifier = elgg_extract('identifier', $vars, 'groups');

$base_url = elgg_normalize_url("$identifier/suggested") . '?' . parse_url(current_page_url(), PHP_URL_QUERY);

$list_class = (array) elgg_extract('list_class', $vars, array());
$list_class[] = 'elgg-list-groups';

$item_class = (array) elgg_extract('item_class', $vars, array());

$options = (array) elgg_extract('options', $vars, array());

$item_view = null;
if (elgg_is_active_plugin('group_list') && elgg_get_plugin_setting('use_membership_view', 'group_list')) {
	$item_view = 'group/format/membership';
}

$list_options = array(
	'full_view' => false,
	'limit' => elgg_extract('limit', $vars, elgg_get_config('default_limit')) ? : 10,
	'list_class' => implode(' ', $list_class),
	'item_class' => implode(' ', $item_class),
	'no_results' => elgg_echo("$identifier:suggested:none"),
	'pagination' => elgg_is_active_plugin('hypeLists') || !elgg_in_context('widgets'),
	'pagination_type' => 'default',
	'base_url' => $base_url,
	'list_id' => 'suggested-groups',
	'auto_refresh' => false,
	'user' => $entity,
	'item_view' => $item_view,
);

$getter_options = array(
	'types' => array('group'),
	'subtypes' => is_callable('group_subtypes_get_subtypes') ? group_subtypes_get_subtypes($identifier) : ELGG_ENTITIES_ANY_VALUE,
);

$options = array_merge_recursive($list_options, $options, $getter_options);

if (elgg_view_exists('lists/groups')) {
	$params = $vars;
	$params['rel'] = 'suggested';
	$params['show_rel'] = false;
	$params['show_sort'] = false;
	$params['options'] = $options;
	$params['callback'] = 'elgg_list_entities';
	echo elgg_view('lists/groups', $params);
} else {
	$options = group_suggestions_add_match_queries($options, $entity);
	echo elgg_list_entities($options);
}
