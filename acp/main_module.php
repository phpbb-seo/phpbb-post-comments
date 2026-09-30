<?php
/**
 * phpBB Post Comments Extension
 *
 * @copyright (c) 2026 Meis@M <info@phpbb-seo.ir>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbseo\postcomments\acp;

class main_module
{
	public $u_action;
	public $tpl_name;
	public $page_title;

	public function main($id, $mode)
	{
		global $config, $request, $template, $user, $phpbb_root_path, $phpEx;

		if (!function_exists('make_forum_select'))
		{
			include_once($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
		}

		$user->add_lang_ext('phpbbseo/postcomments', 'info_acp_postcomments');

		$this->tpl_name = 'acp_postcomments';
		$this->page_title = $user->lang('ACP_POSTCOMMENTS_TITLE');

		add_form_key('acp_postcomments_settings');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('acp_postcomments_settings'))
			{
				trigger_error($user->lang('FORM_INVALID'), E_USER_WARNING);
			}

			$postcomments_enable        = $request->variable('postcomments_enable', 0);
			$postcomments_limit         = max(1, $request->variable('postcomments_limit', 3));
			$postcomments_order         = $request->variable('postcomments_order', 'ASC');
			$postcomments_max_length    = max(0, $request->variable('postcomments_max_length', 500));
			$postcomments_allow_bbcode  = $request->variable('postcomments_allow_bbcode', 0);
			$postcomments_allow_smilies = $request->variable('postcomments_allow_smilies', 0);
			$postcomments_notify_author = $request->variable('postcomments_notify_author', 0);

			$selected_forums_ary = $request->variable('postcomments_forums', [0]);
			$selected_forums = implode(',', array_filter(array_map('intval', $selected_forums_ary)));

			$config->set('postcomments_enable', $postcomments_enable);
			$config->set('postcomments_limit', $postcomments_limit);
			$config->set('postcomments_order', $postcomments_order === 'DESC' ? 'DESC' : 'ASC');
			$config->set('postcomments_max_length', $postcomments_max_length);
			$config->set('postcomments_allow_bbcode', $postcomments_allow_bbcode);
			$config->set('postcomments_allow_smilies', $postcomments_allow_smilies);
			$config->set('postcomments_notify_author', $postcomments_notify_author);
			$config->set('postcomments_forums', $selected_forums);

			trigger_error($user->lang('ACP_POSTCOMMENTS_SAVED') . adm_back_link($this->u_action));
		}

		$current_forums = !empty($config['postcomments_forums']) ? explode(',', $config['postcomments_forums']) : [];
		$forum_select_options = make_forum_select($current_forums, false, true, false, false);

		$template->assign_vars([
			'U_ACTION'                   => $this->u_action,
			'POSTCOMMENTS_ENABLE'        => (bool) $config['postcomments_enable'],
			'POSTCOMMENTS_LIMIT'         => (int) $config['postcomments_limit'],
			'POSTCOMMENTS_ORDER_ASC'     => ($config['postcomments_order'] !== 'DESC'),
			'POSTCOMMENTS_MAX_LENGTH'    => (int) $config['postcomments_max_length'],
			'POSTCOMMENTS_ALLOW_BBCODE'  => (bool) $config['postcomments_allow_bbcode'],
			'POSTCOMMENTS_ALLOW_SMILIES' => (bool) $config['postcomments_allow_smilies'],
			'POSTCOMMENTS_NOTIFY_AUTHOR' => (bool) $config['postcomments_notify_author'],
			'S_FORUM_OPTIONS'            => $forum_select_options,
		]);
	}
}
