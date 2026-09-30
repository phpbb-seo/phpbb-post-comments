<?php
/**
 * phpBB Post Comments Extension
 *
 * @copyright (c) 2026 Meis@M <info@phpbb-seo.ir>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbseo\postcomments\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class permission_listener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return [
			'core.permissions' => 'add_permissions',
			'core.user_setup'  => 'load_language_on_setup',
		];
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'phpbbseo/postcomments',
			'lang_set' => 'permissions_postcomments',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function add_permissions($event)
	{
		$permissions = $event['permissions'];

		// Forum permissions (under 'actions')
		$permissions['f_comment_view']       = ['lang' => 'ACL_F_COMMENT_VIEW', 'cat' => 'actions'];
		$permissions['f_comment_post']       = ['lang' => 'ACL_F_COMMENT_POST', 'cat' => 'actions'];
		$permissions['f_comment_edit_own']   = ['lang' => 'ACL_F_COMMENT_EDIT_OWN', 'cat' => 'actions'];
		$permissions['f_comment_delete_own'] = ['lang' => 'ACL_F_COMMENT_DELETE_OWN', 'cat' => 'actions'];

		// Moderator permissions (under 'post_actions')
		$permissions['m_comment_edit']       = ['lang' => 'ACL_M_COMMENT_EDIT', 'cat' => 'post_actions'];
		$permissions['m_comment_delete']     = ['lang' => 'ACL_M_COMMENT_DELETE', 'cat' => 'post_actions'];

		$event['permissions'] = $permissions;
	}
}
