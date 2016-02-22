<?php

$identifier = elgg_extract('identifier', $vars, 'groups');

elgg_register_menu_item('filter', array(
	'name' => 'suggested',
	'text' => elgg_echo("$identifier:suggested"),
	'href' => "$identifier/suggested",
	'priority' => 800,
));