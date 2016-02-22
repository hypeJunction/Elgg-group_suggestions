Group Suggestions for Elgg
==========================
![Elgg 1.11](https://img.shields.io/badge/Elgg-1.11.x-orange.svg?style=flat-square)
![Elgg 1.12](https://img.shields.io/badge/Elgg-1.12.x-orange.svg?style=flat-square)
![Elgg 2.0](https://img.shields.io/badge/Elgg-2.0.x-orange.svg?style=flat-square)

## Features

 * Suggests groups to join based on shared tags and relationships

![Group Suggestions](https://raw.github.com/hypeJunction/Elgg-group_suggestions/master/screenshots/suggestions.png "Group Suggestions")

## Notes

Matching algorithm matches suggested groups based on:
1. Members in user's groups who have also joined other suggested groups
2. Tags that shared between the user and the suggested groups

Suggested groups are ordered by score that includes total number of shared members and tags.

If you want to list reasons for the suggestion, you can access this info through volatile data:

```php
// Total count of shared members and tags
$group->getVolatileData('select:score');

// Concatenated string of members in user's groups who have joined this suggested group
$group->getVolatileData('select:shared_members');

// Concated string of tag metadata value ids shared between the user and this suggested group
$group->getVolatileData('select:shared_tags');
```

You can constrain any set of groups to only include suggested groups ordered by score, by
filtering the options through:

```php
$options = group_suggestions_add_match_queries($options);
echo elgg_list_entities($options);
```