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
	'POST_COMMENTS'             => 'Comments',
	'POST_COMMENT'              => 'Comment',
	'POST_COMMENTS_COUNT'       => 'Comments (%d)',
	'POST_COMMENT_ADD'          => 'Add Comment',
	'POST_COMMENT_PLACEHOLDER'  => 'Write a comment...',
	'POST_COMMENT_SUBMIT'       => 'Post Comment',
	'POST_COMMENT_CANCEL'       => 'Cancel',
	'POST_COMMENT_SAVE'         => 'Save',
	'POST_COMMENT_EDIT'         => 'Edit',
	'POST_COMMENT_DELETE'       => 'Delete',
	'POST_COMMENT_LOAD_MORE'    => 'View earlier comments (%d more)',
	'POST_COMMENT_LOADING'      => 'Loading...',
	'POST_COMMENT_CHARS_LEFT'   => 'characters remaining',
	'POST_COMMENT_JUST_NOW'     => 'Just now',
	'POST_COMMENT_TIME_MINS_AGO'       => '%dm ago',
	'POST_COMMENT_TIME_HOURS_AGO'      => '%dh ago',
	'POST_COMMENT_EDITED'       => 'Edited',

	// Confirmation & Messages
	'POST_COMMENT_DELETE_CONFIRM'      => 'Are you sure you want to delete this comment? This action cannot be undone.',
	'POST_COMMENT_EMPTY_ERROR'         => 'Comment text cannot be empty.',
	'POST_COMMENT_TOO_LONG_ERROR'      => 'Your comment is too long. The maximum allowed length is %d characters.',
	'POST_COMMENT_PERMISSION_DENIED'   => 'You do not have permission to perform this action.',
	'POST_COMMENT_TOPIC_LOCKED'        => 'This topic is locked. Adding comments is not allowed.',
	'POST_COMMENT_NOT_FOUND'           => 'The requested comment could not be found.',
	'POST_COMMENT_DELETED_SUCCESS'     => 'Comment deleted successfully.',
	'POST_COMMENT_ERROR_SUBMIT'        => 'Error submitting comment.',
	'POST_COMMENT_ERROR_EDIT'          => 'Error updating comment.',
	'POST_COMMENT_ERROR_DELETE'        => 'Error deleting comment.',
	'POST_COMMENT_ERROR_NETWORK'       => 'A network error occurred. Please try again.',

	// Notifications
	'NOTIFICATION_POST_COMMENT'        => '<strong>%1$s</strong> commented on your post in <em>"%2$s"</em>',
	'NOTIFICATION_TYPE_POST_COMMENT'   => 'Someone comments on your post',
]);
