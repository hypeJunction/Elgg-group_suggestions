<?php

elgg_gatekeeper();

$guid = elgg_extract('guid', $vars);
if (!$guid) {
	$guid = elgg_get_logged_in_user_guid();
}
$entity = get_entity($guid);
if (!$entity instanceof ElggUser || !$entity->canEdit()) {
	forward('', '403');
}

elgg_set_page_owner_guid($guid);

$identifier = elgg_extract('identifier', $vars, 'groups');

// pushing context to make it easier to user 'menu:filter' hook
elgg_push_context("$identifier/suggested");

$segments = (array) elgg_extract('segments', $vars, array());

$title = elgg_echo("$identifier:list:suggested");

elgg_pop_breadcrumb();
elgg_push_breadcrumb(elgg_echo($identifier), "$identifier/all");
elgg_push_breadcrumb($entity->getDisplayName(), "$identifier/member/$entity->guid");
elgg_push_breadcrumb($title);

$params = array(
	'identifier' => $identifier,
	'filter_context' => 'suggested',
	'entity' => $entity,
);

$sidebar = elgg_view('groups/sidebar/featured', $params);

if (elgg_view_exists('filters/groups')) {
	$filter = elgg_view('filters/groups', $params);
} else {
	$params['selected'] = 'suggsted';
	$filter = elgg_view('groups/group_sort_menu', $params);
}

$content = elgg_view('lists/groups/suggested', $params);

$layout = elgg_view_layout('content', array(
	'title' => $title,
	'content' => $content,
	'filter' => $filter ? : '',
	'sidebar' => $sidebar,
		));

echo elgg_view_page($title, $layout);

