<?php
/**
 * phpBB Post Comments Extension
 *
 * @copyright (c) 2026 Meis@M <info@phpbb-seo.ir>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbseo\postcomments\service;

use phpbb\db\driver\driver_interface;
use phpbb\config\config;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\language\language;
use phpbb\notification\manager as notification_manager;
use phpbb\user_loader;

class comment_manager
{
	/** @var driver_interface */
	protected $db;

	/** @var config */
	protected $config;

	/** @var user */
	protected $user;

	/** @var auth */
	protected $auth;

	/** @var language */
	protected $language;

	/** @var notification_manager */
	protected $notification_manager;

	/** @var user_loader */
	protected $user_loader;

	/** @var string */
	protected $comments_table;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	public function __construct(
		driver_interface $db,
		config $config,
		user $user,
		auth $auth,
		language $language,
		notification_manager $notification_manager,
		user_loader $user_loader,
		$table_prefix,
		$phpbb_root_path,
		$php_ext
	) {
		$this->db = $db;
		$this->config = $config;
		$this->user = $user;
		$this->auth = $auth;
		$this->language = $language;
		$this->notification_manager = $notification_manager;
		$this->user_loader = $user_loader;
		$this->comments_table = $table_prefix . 'post_comments';
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
	}

	public function is_enabled_for_forum($forum_id)
	{
		if (empty($this->config['postcomments_enable']))
		{
			return false;
		}

		$allowed_forums = trim($this->config['postcomments_forums']);
		if ($allowed_forums !== '')
		{
			$forums_array = array_map('intval', explode(',', $allowed_forums));
			if (!in_array((int) $forum_id, $forums_array, true))
			{
				return false;
			}
		}

		return true;
	}

	public function can_view_comments($forum_id)
	{
		if (!$this->is_enabled_for_forum($forum_id))
		{
			return false;
		}

		return (bool) ($this->auth->acl_get('f_comment_view', $forum_id) || $this->auth->acl_get('f_read', $forum_id));
	}

	protected function is_user_logged_in()
	{
		return !empty($this->user->data['user_id']) && (int) $this->user->data['user_id'] !== ANONYMOUS;
	}

	public function can_post_comment($forum_id, $topic_status = 0)
	{
		if (!$this->is_enabled_for_forum($forum_id))
		{
			return false;
		}

		if (!$this->is_user_logged_in())
		{
			return false;
		}

		// Founders and administrators always have permission
		if ((isset($this->user->data['user_type']) && (int) $this->user->data['user_type'] === USER_FOUNDER) || $this->auth->acl_get('a_'))
		{
			return true;
		}

		// Check if topic is locked (unless user is a moderator)
		if ((int) $topic_status === ITEM_LOCKED && !$this->auth->acl_get('m_', $forum_id))
		{
			return false;
		}

		return (bool) $this->auth->acl_get('f_comment_post', $forum_id);
	}

	public function can_edit_comment(array $comment, $forum_id)
	{
		if (!$this->is_user_logged_in())
		{
			return false;
		}

		// Founders and administrators always have permission
		if ((isset($this->user->data['user_type']) && (int) $this->user->data['user_type'] === USER_FOUNDER) || $this->auth->acl_get('a_'))
		{
			return true;
		}

		if ($this->auth->acl_get('m_comment_edit', $forum_id))
		{
			return true;
		}

		if ((int) $comment['poster_id'] === (int) $this->user->data['user_id'] && $this->auth->acl_get('f_comment_edit_own', $forum_id))
		{
			return true;
		}

		return false;
	}

	public function can_delete_comment(array $comment, $forum_id)
	{
		if (!$this->is_user_logged_in())
		{
			return false;
		}

		// Founders and administrators always have permission
		if ((isset($this->user->data['user_type']) && (int) $this->user->data['user_type'] === USER_FOUNDER) || $this->auth->acl_get('a_'))
		{
			return true;
		}

		if ($this->auth->acl_get('m_comment_delete', $forum_id))
		{
			return true;
		}

		if ((int) $comment['poster_id'] === (int) $this->user->data['user_id'] && $this->auth->acl_get('f_comment_delete_own', $forum_id))
		{
			return true;
		}

		return false;
	}

	public function get_comments_count_for_posts(array $post_ids)
	{
		if (empty($post_ids))
		{
			return [];
		}

		$post_ids = array_map('intval', $post_ids);
		$sql = 'SELECT post_id, COUNT(comment_id) AS total_comments
			FROM ' . $this->comments_table . '
			WHERE ' . $this->db->sql_in_set('post_id', $post_ids) . '
			GROUP BY post_id';
		$result = $this->db->sql_query($sql);

		$counts = array_fill_keys($post_ids, 0);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$counts[(int) $row['post_id']] = (int) $row['total_comments'];
		}
		$this->db->sql_freeresult($result);

		return $counts;
	}

	public function get_comments_for_post($post_id, $offset = 0, $limit = 0, $order = '')
	{
		if (empty($order))
		{
			$order = !empty($this->config['postcomments_order']) ? $this->config['postcomments_order'] : 'ASC';
		}
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

		$sql_array = [
			'SELECT'    => 'c.*, u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_rank',
			'FROM'      => [
				$this->comments_table => 'c',
			],
			'LEFT_JOIN' => [
				[
					'FROM' => [USERS_TABLE => 'u'],
					'ON'   => 'c.poster_id = u.user_id',
				],
			],
			'WHERE'     => 'c.post_id = ' . (int) $post_id,
			'ORDER_BY'  => 'c.comment_time ' . $order . ', c.comment_id ' . $order,
		];

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query_limit($sql, (int) $limit, (int) $offset);

		$comments = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$comments[] = $this->format_comment_row($row);
		}
		$this->db->sql_freeresult($result);

		return $comments;
	}

	public function get_comment($comment_id)
	{
		$sql_array = [
			'SELECT'    => 'c.*, u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_rank',
			'FROM'      => [
				$this->comments_table => 'c',
			],
			'LEFT_JOIN' => [
				[
					'FROM' => [USERS_TABLE => 'u'],
					'ON'   => 'c.poster_id = u.user_id',
				],
			],
			'WHERE'     => 'c.comment_id = ' . (int) $comment_id,
		];

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row ?: null;
	}

	public function format_comment_row(array $row)
	{
		if (!function_exists('generate_text_for_display'))
		{
			include_once($this->phpbb_root_path . 'includes/functions_content.' . $this->php_ext);
		}
		if (!function_exists('phpbb_get_user_avatar'))
		{
			include_once($this->phpbb_root_path . 'includes/functions.' . $this->php_ext);
		}

		$forum_id = (int) $row['forum_id'];

		$display_text = generate_text_for_display(
			$row['comment_text'],
			$row['bbcode_uid'],
			$row['bbcode_bitfield'],
			(int) $row['bbcode_options']
		);

		$edit_text = generate_text_for_edit(
			$row['comment_text'],
			$row['bbcode_uid'],
			(int) $row['bbcode_options']
		);

		$avatar_html = phpbb_get_user_avatar($row, 'USER_AVATAR');
		if (empty($avatar_html))
		{
			// Clean fallback avatar placeholder
			$avatar_html = '<div class="postcomment-default-avatar"><i class="icon fa fa-user fa-fw" aria-hidden="true"></i></div>';
		}

		$relative_time = $this->format_relative_time((int) $row['comment_time']);

		$formatted_time = $this->user->format_date((int) $row['comment_time']);

		$username_full = get_username_string('full', (int) $row['poster_id'], $row['username'], $row['user_colour']);

		return [
			'comment_id'         => (int) $row['comment_id'],
			'post_id'            => (int) $row['post_id'],
			'topic_id'           => (int) $row['topic_id'],
			'forum_id'           => $forum_id,
			'poster_id'          => (int) $row['poster_id'],
			'username'           => $row['username'],
			'user_colour'        => $row['user_colour'],
			'username_full'      => $username_full,
			'avatar'             => $avatar_html,
			'comment_text'       => $display_text,
			'comment_text_raw'   => $edit_text['text'],
			'comment_time'       => (int) $row['comment_time'],
			'formatted_time'     => $formatted_time,
			'relative_time'      => $relative_time,
			'comment_edit_time'  => (int) $row['comment_edit_time'],
			'comment_edit_count' => (int) $row['comment_edit_count'],
			'is_edited'          => (int) $row['comment_edit_count'] > 0,
			'can_edit'           => $this->can_edit_comment($row, $forum_id),
			'can_delete'         => $this->can_delete_comment($row, $forum_id),
		];
	}

	public function format_relative_time($timestamp)
	{
		$now = time();
		$diff = $now - (int) $timestamp;

		if ($diff < 60)
		{
			return $this->language->lang('POST_COMMENT_JUST_NOW');
		}
		if ($diff < 3600)
		{
			$mins = max(1, (int) round($diff / 60));
			return $this->language->lang('POST_COMMENT_TIME_MINS_AGO', $mins);
		}
		if ($diff < 86400)
		{
			$hours = (int) round($diff / 3600);
			return $this->language->lang('POST_COMMENT_TIME_HOURS_AGO', $hours);
		}

		return $this->user->format_date($timestamp, 'j M Y, H:i');
	}

	public function add_comment($post_id, $comment_text)
	{
		$post_id = (int) $post_id;
		$comment_text = trim($comment_text);

		if ($comment_text === '')
		{
			return ['success' => false, 'error' => $this->language->lang('POST_COMMENT_EMPTY_ERROR')];
		}

		$max_length = (int) $this->config['postcomments_max_length'];
		if ($max_length > 0 && utf8_strlen($comment_text) > $max_length)
		{
			return ['success' => false, 'error' => sprintf($this->language->lang('POST_COMMENT_TOO_LONG_ERROR'), $max_length)];
		}

		// Retrieve post and topic information
		$sql = 'SELECT p.post_id, p.topic_id, p.forum_id, p.poster_id AS post_author_id, t.topic_title, t.topic_status
			FROM ' . POSTS_TABLE . ' p
			JOIN ' . TOPICS_TABLE . ' t ON (p.topic_id = t.topic_id)
			WHERE p.post_id = ' . $post_id;
		$result = $this->db->sql_query($sql);
		$post_info = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$post_info)
		{
			return ['success' => false, 'error' => $this->language->lang('POST_COMMENT_NOT_FOUND')];
		}

		$forum_id = (int) $post_info['forum_id'];

		if (!$this->can_post_comment($forum_id, $post_info['topic_status']))
		{
			if ((int) $post_info['topic_status'] === ITEM_LOCKED && !$this->auth->acl_get('m_', $forum_id))
			{
				return ['success' => false, 'error' => $this->language->lang('POST_COMMENT_TOPIC_LOCKED')];
			}
			return ['success' => false, 'error' => $this->language->lang('POST_COMMENT_PERMISSION_DENIED')];
		}

		if (!function_exists('generate_text_for_storage'))
		{
			include_once($this->phpbb_root_path . 'includes/functions_content.' . $this->php_ext);
		}

		$uid = $bitfield = '';
		$flags = 0;
		$allow_bbcode  = !empty($this->config['postcomments_allow_bbcode']);
		$allow_smilies = !empty($this->config['postcomments_allow_smilies']);
		$allow_urls    = true;

		generate_text_for_storage($comment_text, $uid, $bitfield, $flags, $allow_bbcode, $allow_urls, $allow_smilies);

		$now = time();
		$sql_ary = [
			'post_id'            => $post_id,
			'topic_id'           => (int) $post_info['topic_id'],
			'forum_id'           => $forum_id,
			'poster_id'          => (int) $this->user->data['user_id'],
			'comment_text'       => $comment_text,
			'comment_time'       => $now,
			'comment_edit_time'  => 0,
			'comment_edit_count' => 0,
			'bbcode_uid'         => $uid,
			'bbcode_bitfield'    => $bitfield,
			'bbcode_options'     => $flags,
		];

		$this->db->sql_query('INSERT INTO ' . $this->comments_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
		$comment_id = (int) $this->db->sql_nextid();

		// Trigger notification if configured and not commenting on own post
		if (!empty($this->config['postcomments_notify_author']))
		{
			$notification_data = [
				'comment_id'         => $comment_id,
				'post_id'            => $post_id,
				'topic_id'           => (int) $post_info['topic_id'],
				'forum_id'           => $forum_id,
				'poster_id'          => (int) $this->user->data['user_id'],
				'poster_username'    => $this->user->data['username'],
				'post_author_id'     => (int) $post_info['post_author_id'],
				'topic_title'        => $post_info['topic_title'],
			];

			$this->notification_manager->add_notifications([
				'notification.type.phpbbseo_post_comment',
			], $notification_data);
		}

		// Retrieve the newly created comment with user data
		$comment_row = $this->get_comment($comment_id);
		$formatted = $this->format_comment_row($comment_row);

		// Get total updated comments count for this post
		$sql_count = 'SELECT COUNT(comment_id) AS total FROM ' . $this->comments_table . ' WHERE post_id = ' . $post_id;
		$res_count = $this->db->sql_query($sql_count);
		$total_count = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($res_count);

		return [
			'success'     => true,
			'comment'     => $formatted,
			'total_count' => $total_count,
		];
	}

	public function edit_comment($comment_id, $new_text)
	{
		$comment = $this->get_comment($comment_id);
		if (!$comment)
		{
			return ['success' => false, 'error' => $this->language->lang('POST_COMMENT_NOT_FOUND')];
		}

		$forum_id = (int) $comment['forum_id'];
		if (!$this->can_edit_comment($comment, $forum_id))
		{
			return ['success' => false, 'error' => $this->language->lang('POST_COMMENT_PERMISSION_DENIED')];
		}

		$new_text = trim($new_text);
		if ($new_text === '')
		{
			return ['success' => false, 'error' => $this->language->lang('POST_COMMENT_EMPTY_ERROR')];
		}

		$max_length = (int) $this->config['postcomments_max_length'];
		if ($max_length > 0 && utf8_strlen($new_text) > $max_length)
		{
			return ['success' => false, 'error' => sprintf($this->language->lang('POST_COMMENT_TOO_LONG_ERROR'), $max_length)];
		}

		if (!function_exists('generate_text_for_storage'))
		{
			include_once($this->phpbb_root_path . 'includes/functions_content.' . $this->php_ext);
		}

		$uid = $bitfield = '';
		$flags = 0;
		$allow_bbcode  = !empty($this->config['postcomments_allow_bbcode']);
		$allow_smilies = !empty($this->config['postcomments_allow_smilies']);
		$allow_urls    = true;

		generate_text_for_storage($new_text, $uid, $bitfield, $flags, $allow_bbcode, $allow_urls, $allow_smilies);

		$sql_ary = [
			'comment_text'       => $new_text,
			'comment_edit_time'  => time(),
			'comment_edit_count' => (int) $comment['comment_edit_count'] + 1,
			'bbcode_uid'         => $uid,
			'bbcode_bitfield'    => $bitfield,
			'bbcode_options'     => $flags,
		];

		$sql = 'UPDATE ' . $this->comments_table . '
			SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE comment_id = ' . (int) $comment_id;
		$this->db->sql_query($sql);

		$updated_comment = $this->get_comment($comment_id);
		$formatted = $this->format_comment_row($updated_comment);

		return [
			'success' => true,
			'comment' => $formatted,
		];
	}

	public function delete_comment($comment_id)
	{
		$comment = $this->get_comment($comment_id);
		if (!$comment)
		{
			return ['success' => false, 'error' => $this->language->lang('POST_COMMENT_NOT_FOUND')];
		}

		$forum_id = (int) $comment['forum_id'];
		if (!$this->can_delete_comment($comment, $forum_id))
		{
			return ['success' => false, 'error' => $this->language->lang('POST_COMMENT_PERMISSION_DENIED')];
		}

		$post_id = (int) $comment['post_id'];

		$sql = 'DELETE FROM ' . $this->comments_table . ' WHERE comment_id = ' . (int) $comment_id;
		$this->db->sql_query($sql);

		// Delete associated notification
		$this->notification_manager->delete_notifications(
			'notification.type.phpbbseo_post_comment',
			$comment_id
		);

		// Get remaining count
		$sql_count = 'SELECT COUNT(comment_id) AS total FROM ' . $this->comments_table . ' WHERE post_id = ' . $post_id;
		$res_count = $this->db->sql_query($sql_count);
		$total_count = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($res_count);

		return [
			'success'     => true,
			'message'     => $this->language->lang('POST_COMMENT_DELETED_SUCCESS'),
			'post_id'     => $post_id,
			'total_count' => $total_count,
		];
	}

	public function delete_comments_by_posts(array $post_ids)
	{
		if (empty($post_ids))
		{
			return;
		}

		$post_ids = array_map('intval', $post_ids);

		// Find comment IDs to prune notifications
		$sql = 'SELECT comment_id FROM ' . $this->comments_table . '
			WHERE ' . $this->db->sql_in_set('post_id', $post_ids);
		$result = $this->db->sql_query($sql);
		$comment_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$comment_ids[] = (int) $row['comment_id'];
		}
		$this->db->sql_freeresult($result);

		if (!empty($comment_ids))
		{
			$this->notification_manager->delete_notifications(
				'notification.type.phpbbseo_post_comment',
				$comment_ids
			);

			$sql = 'DELETE FROM ' . $this->comments_table . '
				WHERE ' . $this->db->sql_in_set('comment_id', $comment_ids);
			$this->db->sql_query($sql);
		}
	}
}
