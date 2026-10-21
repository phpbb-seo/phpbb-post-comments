<?php
/**
 * phpBB Post Comments Extension
 *
 * @copyright (c) 2026 Meis@M <info@phpbb-seo.ir>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbseo\postcomments;

use phpbb\extension\base;

class ext extends base
{
	/**
	 * Single enable step that installs included migrations and enables notifications
	 *
	 * @param mixed $old_state State returned by previous call of this method
	 * @return mixed Returns false after last step, otherwise temporary state
	 */
	public function enable_step($old_state)
	{
		switch ($old_state)
		{
			case '':
				if ($this->container->has('notification_manager'))
				{
					$notification_manager = $this->container->get('notification_manager');
					$notification_manager->enable_notifications('notification.type.phpbbseo_post_comment');
				}
				return 'notification';
			break;

			default:
				return parent::enable_step($old_state);
			break;
		}
	}

	/**
	 * Single disable step that disables notifications
	 *
	 * @param mixed $old_state State returned by previous call of this method
	 * @return mixed Returns false after last step, otherwise temporary state
	 */
	public function disable_step($old_state)
	{
		switch ($old_state)
		{
			case '':
				if ($this->container->has('notification_manager'))
				{
					$notification_manager = $this->container->get('notification_manager');
					$notification_manager->disable_notifications('notification.type.phpbbseo_post_comment');
				}
				return 'notification';
			break;

			default:
				return parent::disable_step($old_state);
			break;
		}
	}

	/**
	 * Single purge step that purges notifications and reverts migrations
	 *
	 * @param mixed $old_state State returned by previous call of this method
	 * @return mixed Returns false after last step, otherwise temporary state
	 */
	public function purge_step($old_state)
	{
		switch ($old_state)
		{
			case '':
				if ($this->container->has('notification_manager'))
				{
					$notification_manager = $this->container->get('notification_manager');
					$notification_manager->purge_notifications('notification.type.phpbbseo_post_comment');
				}

				if ($this->container->has('dbal.conn'))
				{
					$db = $this->container->get('dbal.conn');
					$user_notifications_table = $this->container->getParameter('tables.user_notifications');
					$sql = 'DELETE FROM ' . $user_notifications_table . "
						WHERE item_type = 'notification.type.phpbbseo_post_comment'";
					$db->sql_query($sql);
				}

				return 'notification';
			break;

			default:
				return parent::purge_step($old_state);
			break;
		}
	}
}
