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
	'ACL_F_COMMENT_VIEW'       => 'Can view post comments',
	'ACL_F_COMMENT_POST'       => 'Can post comments to posts',
	'ACL_F_COMMENT_EDIT_OWN'   => 'Can edit own post comments',
	'ACL_F_COMMENT_DELETE_OWN' => 'Can delete own post comments',
	'ACL_M_COMMENT_EDIT'       => 'Can edit any post comment',
	'ACL_M_COMMENT_DELETE'     => 'Can delete any post comment',
]);
