<?php
/**
 * phpBB Post Comments Extension
 *
 * @copyright (c) 2026 Meis@M <info@phpbb-seo.ir>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbseo\postcomments\notification\type;

use phpbb\notification\type\base;

class post_comment extends base
{
	/** @var \phpbb\user_loader */
	protected $user_loader;

	/** @var \phpbb\config\config */
	protected $config;

	public function set_user_loader(\phpbb\user_loader $user_loader)
	{
		$this->user_loader = $user_loader;
	}

	public function set_config(\phpbb\config\config $config)
	{
		$this->config = $config;
	}

	public function get_type()
	{
		return 'notification.type.phpbbseo_post_comment';
	}

	protected $language_key = 'NOTIFICATION_POST_COMMENT';

	public static $notification_option = [
		'lang'  => 'NOTIFICATION_TYPE_POST_COMMENT',
		'group' => 'NOTIFICATION_GROUP_POSTING',
	];

	public function is_available()
	{
		return !empty($this->config['postcomments_enable']) && !empty($this->config['postcomments_notify_author']);
	}

	public static function get_item_id($comment)
	{
		return (int) $comment['comment_id'];
	}

	public static function get_item_parent_id($comment)
	{
		return (int) $comment['post_id'];
	}

	public function find_users_for_notification($comment, $options = [])
	{
		$post_author_id = (int) $comment['post_author_id'];
		$commenter_id   = (int) $comment['poster_id'];

		if ($post_author_id <= 0 || $post_author_id === $commenter_id || $post_author_id === ANONYMOUS)
		{
			return [];
		}

		$users = [$post_author_id];
		return $this->get_authorised_recipients($users, (int) $comment['forum_id'], $options, true);
	}

	public function get_avatar()
	{
		if ($this->user_loader)
		{
			return $this->user_loader->get_avatar($this->get_data('commenter_id'), false, true);
		}
		return '';
	}

	public function get_title()
	{
		$username = $this->get_data('commenter_username');
		$topic_title = $this->get_data('topic_title');

		return $this->language->lang(
			$this->language_key,
			$username,
			censor_text($topic_title)
		);
	}

	public function get_url()
	{
		return append_sid($this->phpbb_root_path . 'viewtopic.' . $this->php_ext, 'p=' . (int) $this->get_data('post_id') . '#p' . (int) $this->get_data('post_id'));
	}

	public function create_insert_array($comment, $pre_create_data = [])
	{
		$data = [
			'comment_id'         => (int) $comment['comment_id'],
			'post_id'            => (int) $comment['post_id'],
			'topic_id'           => (int) $comment['topic_id'],
			'forum_id'           => (int) $comment['forum_id'],
			'commenter_id'       => (int) $comment['poster_id'],
			'commenter_username' => (string) $comment['poster_username'],
			'topic_title'        => (string) $comment['topic_title'],
		];

		$this->set_data('comment_id', $data['comment_id']);
		$this->set_data('post_id', $data['post_id']);
		$this->set_data('topic_id', $data['topic_id']);
		$this->set_data('forum_id', $data['forum_id']);
		$this->set_data('commenter_id', $data['commenter_id']);
		$this->set_data('commenter_username', $data['commenter_username']);
		$this->set_data('topic_title', $data['topic_title']);

		return parent::create_insert_array($comment, $pre_create_data);
	}

	public function users_to_query()
	{
		return [
			(int) $this->get_data('commenter_id'),
		];
	}

	public function get_email_template()
	{
		return false;
	}

	public function get_email_template_variables()
	{
		return [];
	}
}
