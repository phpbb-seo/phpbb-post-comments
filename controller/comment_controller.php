<?php
/**
 * phpBB Post Comments Extension
 *
 * @copyright (c) 2026 Meis@M <info@phpbb-seo.ir>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbseo\postcomments\controller;

use phpbb\controller\helper;
use phpbb\request\request_interface;
use phpbb\user;
use phpbb\language\language;
use phpbbseo\postcomments\service\comment_manager;
use Symfony\Component\HttpFoundation\JsonResponse;

class comment_controller
{
	/** @var helper */
	protected $helper;

	/** @var request_interface */
	protected $request;

	/** @var user */
	protected $user;

	/** @var language */
	protected $language;

	/** @var comment_manager */
	protected $comment_manager;

	public function __construct(
		helper $helper,
		request_interface $request,
		user $user,
		language $language,
		comment_manager $comment_manager
	) {
		$this->helper = $helper;
		$this->request = $request;
		$this->user = $user;
		$this->language = $language;
		$this->comment_manager = $comment_manager;

		$this->language->add_lang('common', 'phpbbseo/postcomments');
	}

	protected function is_user_logged_in()
	{
		return !empty($this->user->data['user_id']) && (int) $this->user->data['user_id'] !== ANONYMOUS;
	}

	public function add()
	{
		if (!$this->is_user_logged_in())
		{
			return new JsonResponse([
				'success' => false,
				'error'   => $this->language->lang('POST_COMMENT_PERMISSION_DENIED'),
			], 401);
		}

		$post_id = $this->request->variable('post_id', 0);
		$comment_text = $this->request->variable('comment_text', '', true);

		if ($post_id <= 0)
		{
			return new JsonResponse([
				'success' => false,
				'error'   => $this->language->lang('POST_COMMENT_NOT_FOUND'),
			], 400);
		}

		$result = $this->comment_manager->add_comment($post_id, $comment_text);
		return new JsonResponse($result);
	}

	public function edit()
	{
		if (!$this->is_user_logged_in())
		{
			return new JsonResponse([
				'success' => false,
				'error'   => $this->language->lang('POST_COMMENT_PERMISSION_DENIED'),
			], 401);
		}

		$comment_id = $this->request->variable('comment_id', 0);
		$comment_text = $this->request->variable('comment_text', '', true);

		if ($comment_id <= 0)
		{
			return new JsonResponse([
				'success' => false,
				'error'   => $this->language->lang('POST_COMMENT_NOT_FOUND'),
			], 400);
		}

		$result = $this->comment_manager->edit_comment($comment_id, $comment_text);
		return new JsonResponse($result);
	}

	public function delete()
	{
		if (!$this->is_user_logged_in())
		{
			return new JsonResponse([
				'success' => false,
				'error'   => $this->language->lang('POST_COMMENT_PERMISSION_DENIED'),
			], 401);
		}

		$comment_id = $this->request->variable('comment_id', 0);

		if ($comment_id <= 0)
		{
			return new JsonResponse([
				'success' => false,
				'error'   => $this->language->lang('POST_COMMENT_NOT_FOUND'),
			], 400);
		}

		$result = $this->comment_manager->delete_comment($comment_id);
		return new JsonResponse($result);
	}

	public function load()
	{
		$post_id = $this->request->variable('post_id', 0);
		$offset  = $this->request->variable('offset', 0);
		$limit   = $this->request->variable('limit', 10);

		if ($post_id <= 0)
		{
			return new JsonResponse([
				'success' => false,
				'error'   => $this->language->lang('POST_COMMENT_NOT_FOUND'),
			], 400);
		}

		$comments = $this->comment_manager->get_comments_for_post($post_id, $offset, $limit);

		return new JsonResponse([
			'success'  => true,
			'comments' => $comments,
		]);
	}
}
