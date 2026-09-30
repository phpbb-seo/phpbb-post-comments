<?php
/**
 * phpBB Post Comments Extension
 *
 * @copyright (c) 2026 Meis@M <info@phpbb-seo.ir>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbseo\postcomments\migrations;

use phpbb\db\migration\migration;

class v100_permissions extends migration
{
	public static function depends_on()
	{
		return ['\phpbbseo\postcomments\migrations\v100_initial'];
	}

	public function update_data()
	{
		return [
			['permission.add', ['f_comment_view', false]],
			['permission.add', ['f_comment_post', false]],
			['permission.add', ['f_comment_edit_own', false]],
			['permission.add', ['f_comment_delete_own', false]],
			['permission.add', ['m_comment_edit', false]],
			['permission.add', ['m_comment_delete', false]],

			// Set default permissions for forum roles
			['permission.permission_set', ['ROLE_FORUM_FULL', 'f_comment_view']],
			['permission.permission_set', ['ROLE_FORUM_FULL', 'f_comment_post']],
			['permission.permission_set', ['ROLE_FORUM_FULL', 'f_comment_edit_own']],
			['permission.permission_set', ['ROLE_FORUM_FULL', 'f_comment_delete_own']],

			['permission.permission_set', ['ROLE_FORUM_STANDARD', 'f_comment_view']],
			['permission.permission_set', ['ROLE_FORUM_STANDARD', 'f_comment_post']],
			['permission.permission_set', ['ROLE_FORUM_STANDARD', 'f_comment_edit_own']],
			['permission.permission_set', ['ROLE_FORUM_STANDARD', 'f_comment_delete_own']],

			['permission.permission_set', ['ROLE_FORUM_READONLY', 'f_comment_view']],
			['permission.permission_set', ['ROLE_FORUM_LIMITED', 'f_comment_view']],

			// Set default permissions for moderator roles
			['permission.permission_set', ['ROLE_MOD_FULL', 'm_comment_edit']],
			['permission.permission_set', ['ROLE_MOD_FULL', 'm_comment_delete']],
			['permission.permission_set', ['ROLE_MOD_STANDARD', 'm_comment_edit']],
			['permission.permission_set', ['ROLE_MOD_STANDARD', 'm_comment_delete']],
		];
	}
}
