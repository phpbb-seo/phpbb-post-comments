<?php
/**
 * phpBB Post Comments Extension
 *
 * @copyright (c) 2026 Meis@M <info@phpbb-seo.ir>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbseo\postcomments\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\config\config;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\template\template;
use phpbb\controller\helper;
use phpbbseo\postcomments\service\comment_manager;

class main_listener implements EventSubscriberInterface
{
	/** @var config */
	protected $config;

	/** @var user */
	protected $user;

	/** @var auth */
	protected $auth;

	/** @var template */
	protected $template;

	/** @var helper */
	protected $helper;

	/** @var comment_manager */
	protected $comment_manager;

	/** @var array Cached comment counts by post_id */
	protected $cached_counts = [];

	/** @var array Cached comments list by post_id */
	protected $cached_comments = [];

	public function __construct(
		config $config,
		user $user,
		auth $auth,
		template $template,
		helper $helper,
		comment_manager $comment_manager
	) {
		$this->config = $config;
		$this->user = $user;
		$this->auth = $auth;
		$this->template = $template;
		$this->helper = $helper;
		$this->comment_manager = $comment_manager;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'                  => 'user_setup',
			'core.page_header'                 => 'page_header',
			'core.viewtopic_modify_post_data'  => 'viewtopic_modify_post_data',
			'core.viewtopic_modify_post_row'   => 'viewtopic_modify_post_row',
			'core.viewtopic_post_row_after'    => 'viewtopic_post_row_after',
			'core.delete_posts_after'          => 'delete_posts_after',
		];
	}

	public function user_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'phpbbseo/postcomments',
			'lang_set' => 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function page_header($event)
	{
		if (!function_exists('phpbb_get_user_avatar'))
		{
			include_once($this->user->page['root_script_path'] . 'includes/functions.' . $this->user->page['php_ext']);
		}

		$current_user_avatar = '';
		if ($this->user->data['is_registered'])
		{
			$current_user_avatar = phpbb_get_user_avatar($this->user->data, 'USER_AVATAR');
		}

		$this->template->assign_vars([
			'S_POSTCOMMENTS_ENABLED'     => !empty($this->config['postcomments_enable']),
			'POSTCOMMENTS_MAX_LENGTH'    => (int) $this->config['postcomments_max_length'],
			'POSTCOMMENTS_INITIAL_LIMIT' => (int) $this->config['postcomments_limit'],
			'CURRENT_USER_AVATAR'        => $current_user_avatar,
			'U_POSTCOMMENTS_ADD'         => $this->helper->route('phpbbseo_postcomments_add'),
			'U_POSTCOMMENTS_EDIT'        => $this->helper->route('phpbbseo_postcomments_edit'),
			'U_POSTCOMMENTS_DELETE'      => $this->helper->route('phpbbseo_postcomments_delete'),
			'U_POSTCOMMENTS_LOAD'        => $this->helper->route('phpbbseo_postcomments_load'),
		]);
	}

	public function viewtopic_modify_post_data($event)
	{
		$forum_id = (int) $event['forum_id'];

		if (!$this->comment_manager->can_view_comments($forum_id))
		{
			return;
		}

		$post_list = $event['post_list'];
		if (empty($post_list))
		{
			return;
		}

		// Batch query total comments counts for all posts on the page
		$this->cached_counts = $this->comment_manager->get_comments_count_for_posts($post_list);

		// Batch query initial comments for posts that actually have comments
		$posts_with_comments = array_filter($this->cached_counts, function ($cnt) {
			return $cnt > 0;
		});

		$initial_limit = max(1, (int) $this->config['postcomments_limit']);
		$order = !empty($this->config['postcomments_order']) ? $this->config['postcomments_order'] : 'ASC';

		$this->cached_comments = [];
		foreach (array_keys($posts_with_comments) as $p_id)
		{
			$this->cached_comments[$p_id] = $this->comment_manager->get_comments_for_post(
				$p_id,
				0,
				$initial_limit,
				$order
			);
		}
	}

	public function viewtopic_modify_post_row($event)
	{
		$row = $event['row'];
		$post_id = (int) $row['post_id'];
		$forum_id = (int) $row['forum_id'];
		$topic_status = (int) $event['topic_data']['topic_status'];

		$can_view = $this->comment_manager->can_view_comments($forum_id);
		$can_post = $this->comment_manager->can_post_comment($forum_id, $topic_status);

		$total_count = isset($this->cached_counts[$post_id]) ? $this->cached_counts[$post_id] : 0;
		$comments = isset($this->cached_comments[$post_id]) ? $this->cached_comments[$post_id] : [];
		$loaded_count = count($comments);

		$post_row = $event['post_row'];
		$post_row['S_POSTCOMMENTS_CAN_VIEW']   = $can_view;
		$post_row['S_POSTCOMMENTS_CAN_POST']   = $can_post;
		$post_row['POSTCOMMENTS_COUNT']        = $total_count;
		$post_row['POSTCOMMENTS_LOADED_COUNT'] = $loaded_count;
		$post_row['POSTCOMMENTS_HAS_MORE']     = ($total_count > $loaded_count);
		$post_row['POSTCOMMENTS_REMAINING']    = max(0, $total_count - $loaded_count);

		$event['post_row'] = $post_row;
	}

	public function viewtopic_post_row_after($event)
	{
		$post_id = (int) $event['row']['post_id'];

		if (!empty($this->cached_comments[$post_id]))
		{
			foreach ($this->cached_comments[$post_id] as $comment)
			{
				$this->template->assign_block_vars('postrow.comment', [
					'ID'             => (int) $comment['comment_id'],
					'POST_ID'        => (int) $comment['post_id'],
					'POSTER_ID'      => (int) $comment['poster_id'],
					'USERNAME'       => $comment['username'],
					'USERNAME_FULL'  => $comment['username_full'],
					'AVATAR'         => $comment['avatar'],
					'TEXT'           => $comment['comment_text'],
					'TEXT_RAW'       => $comment['comment_text_raw'],
					'TIME'           => (int) $comment['comment_time'],
					'FORMATTED_TIME' => $comment['formatted_time'],
					'RELATIVE_TIME'  => $comment['relative_time'],
					'S_EDITED'       => (bool) $comment['is_edited'],
					'EDIT_COUNT'     => (int) $comment['comment_edit_count'],
					'S_CAN_EDIT'     => (bool) $comment['can_edit'],
					'S_CAN_DELETE'   => (bool) $comment['can_delete'],
				]);
			}
		}
	}

	public function delete_posts_after($event)
	{
		$post_ids = (array) $event['post_ids'];
		if (!empty($post_ids))
		{
			$this->comment_manager->delete_comments_by_posts($post_ids);
		}
	}
}
