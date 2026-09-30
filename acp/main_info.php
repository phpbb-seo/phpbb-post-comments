<?php
/**
 * phpBB Post Comments Extension
 *
 * @copyright (c) 2026 Meis@M <info@phpbb-seo.ir>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbseo\postcomments\acp;

class main_info
{
	public function module()
	{
		return [
			'filename' => '\phpbbseo\postcomments\acp\main_module',
			'title'    => 'ACP_POSTCOMMENTS_TITLE',
			'modes'    => [
				'settings' => [
					'title' => 'ACP_POSTCOMMENTS_SETTINGS',
					'auth'  => 'ext_phpbbseo/postcomments && acl_a_board',
					'cat'   => ['ACP_POSTCOMMENTS_TITLE'],
				],
			],
		];
	}
}
