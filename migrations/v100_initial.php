<?php
/**
 * phpBB Post Comments Extension
 *
 * @copyright (c) 2026 Meis@M <info@phpbb-seo.ir>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbseo\postcomments\migrations;

use phpbb\db\migration\migration;

class v100_initial extends migration
{
	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'post_comments' => [
					'COLUMNS' => [
						'comment_id'         => ['UINT', null, 'auto_increment'],
						'post_id'            => ['UINT', 0],
						'topic_id'           => ['UINT', 0],
						'forum_id'           => ['UINT', 0],
						'poster_id'          => ['UINT', 0],
						'comment_text'       => ['MTEXT_UNI', ''],
						'comment_time'       => ['TIMESTAMP', 0],
						'comment_edit_time'  => ['TIMESTAMP', 0],
						'comment_edit_count' => ['USINT', 0],
						'bbcode_uid'         => ['VCHAR:8', ''],
						'bbcode_bitfield'    => ['VCHAR:255', ''],
						'bbcode_options'     => ['UINT', 7],
					],
					'PRIMARY_KEY' => 'comment_id',
					'KEYS' => [
						'post_id'      => ['INDEX', 'post_id'],
						'topic_id'     => ['INDEX', 'topic_id'],
						'poster_id'    => ['INDEX', 'poster_id'],
						'comment_time' => ['INDEX', 'comment_time'],
					],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'post_comments',
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['postcomments_enable', 1]],
			['config.add', ['postcomments_limit', 3]],
			['config.add', ['postcomments_order', 'ASC']],
			['config.add', ['postcomments_max_length', 500]],
			['config.add', ['postcomments_allow_bbcode', 1]],
			['config.add', ['postcomments_allow_smilies', 1]],
			['config.add', ['postcomments_notify_author', 1]],
			['config.add', ['postcomments_forums', '']],

			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_POSTCOMMENTS_TITLE',
			]],
			['module.add', [
				'acp',
				'ACP_POSTCOMMENTS_TITLE',
				[
					'module_basename' => '\phpbbseo\postcomments\acp\main_module',
					'modes'           => ['settings'],
				],
			]],
		];
	}
}
