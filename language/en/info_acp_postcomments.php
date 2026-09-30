<?php
/**
 * phpBB Post Comments Extension
 *
 * @copyright (c) 2026 Meis@M <info@phpbb-seo.ir>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'ACP_POSTCOMMENTS_TITLE'            => 'Post Comments',
	'ACP_POSTCOMMENTS_SETTINGS'         => 'Settings',
	'ACP_POSTCOMMENTS_SETTINGS_EXPLAIN' => 'Configure options for post comments, including display counts, character limits, formatting, and author notifications.',

	'ACP_POSTCOMMENTS_ENABLE'           => 'Enable Post Comments',
	'ACP_POSTCOMMENTS_ENABLE_EXPLAIN'   => 'Globally enable or disable the post comments system across your board.',

	'ACP_POSTCOMMENTS_FORUMS'           => 'Enabled Forums',
	'ACP_POSTCOMMENTS_FORUMS_EXPLAIN'   => 'Select the forums where comments on posts should be enabled. If none are selected, comments will be available in all forums where user permissions permit.',

	'ACP_POSTCOMMENTS_LIMIT'            => 'Initial Comments Display Limit',
	'ACP_POSTCOMMENTS_LIMIT_EXPLAIN'    => 'The number of comments shown initially beneath each post. Any additional comments can be viewed via the "View earlier comments" AJAX button.',

	'ACP_POSTCOMMENTS_ORDER'            => 'Comment Sort Order',
	'ACP_POSTCOMMENTS_ORDER_EXPLAIN'    => 'Determines the ordering of comments under posts.',
	'ACP_POSTCOMMENTS_ORDER_ASC'        => 'Oldest first (Chronological)',
	'ACP_POSTCOMMENTS_ORDER_DESC'       => 'Newest first (Reverse chronological)',

	'ACP_POSTCOMMENTS_MAX_LENGTH'       => 'Maximum Comment Length',
	'ACP_POSTCOMMENTS_MAX_LENGTH_EXPLAIN' => 'Maximum allowed number of characters in a single comment. Set to 0 for unlimited.',

	'ACP_POSTCOMMENTS_ALLOW_BBCODE'     => 'Allow BBCode',
	'ACP_POSTCOMMENTS_ALLOW_BBCODE_EXPLAIN' => 'Allow standard BBCode formatting in comments.',

	'ACP_POSTCOMMENTS_ALLOW_SMILIES'    => 'Allow Smilies',
	'ACP_POSTCOMMENTS_ALLOW_SMILIES_EXPLAIN' => 'Allow smilies and emoji icons in comments.',

	'ACP_POSTCOMMENTS_NOTIFY_AUTHOR'    => 'Notify Post Author',
	'ACP_POSTCOMMENTS_NOTIFY_AUTHOR_EXPLAIN' => 'Send an on-site notification to the author of the post whenever a user comments on their post.',

	'ACP_POSTCOMMENTS_SAVED'            => 'Post Comments settings have been updated successfully.',
]);
