<?php namespace JFusion\Plugins\joomla_int\Platform\Joomla;
/**
 * @category   Plugins
 * @package    JFusion\Plugins
 * @subpackage joomla_int
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

use JFusion\Factory;
use JFusion\Plugin\Platform\Joomla;
use JFusion\User\Userinfo;

use Joomla\Language\Text;

use \JRoute;

use \Exception;

/**
 * JFusion Platform Class for the internal Joomla database
 * For detailed descriptions on these functions please check Joomla
 *
 * @category   Plugins
 * @package    JFusion\Plugins
 * @subpackage joomla_int
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class Platform extends Joomla
{
	/**
	 * Returns the URL to a userprofile of the integrated software
	 *
	 * @param int|string $userid userid
	 *
	 * @return string URL
	 */
	function getProfileURL($userid)
	{
		$url = '';
		try {
			$db = Factory::getDatabase($this->getJname());
			if ($userid) {
				$query = $db->getQuery(true)
					->select('id')
					->from('#__menu')
					->where('type = ' . $db->quote('component'))
					->where('link LIKE ' . $db->quote('%com_comprofiler%'));
				$db->setQuery($query, 0, 1);
				$itemid = $db->loadResult();
				if ($itemid) {
					$url = JRoute::_('index.php?option=com_comprofiler&task=userProfile&Itemid=' . $itemid . '&user=' . $userid);
				} else {
					$query = $db->getQuery(true)
						->select('id')
						->from('#__menu')
						->where('type = ' . $db->quote('component'))
						->where('link LIKE ' . $db->quote('%com_community%'));
					$db->setQuery($query, 0, 1);
					$itemid = $db->loadResult();
					if ($itemid) {
						$url = JRoute::_('index.php?option=com_community&view=profile&Itemid=' . $itemid . '&userid=' . $userid);
					} else {
						$query = $db->getQuery(true)
							->select('id')
							->from('#__menu')
							->where('type = ' . $db->quote('component'))
							->where('link LIKE ' . $db->quote('%com_joomunity%'));
						$db->setQuery($query, 0, 1);
						$itemid = $db->loadResult();
						if ($itemid) {
							$url = JRoute::_('index.php?option=com_joomunity&Itemid=' . $itemid . '&cmd=Profile.View.' . $userid);
						}
					}
				}
			}
		} catch (Exception $e) {
			$url = '';
		}
		return $url;
	}

	/**
	 * Retrieves the source path to the user's avatar
	 *
	 * @param int|string $userid software user id
	 *
	 * @return string with source path to users avatar
	 */
	function getAvatar($userid)
	{
		try {
			$db = Factory::getDatabase($this->getJname());
			$source_url = $this->params->get('source_url', '/');
			try {
				$query = $db->getQuery(true)
					->select('avatar')
					->from('#__comprofiler')
					->where('user_id = ' . (int)$userid);

				$db->setQuery($query);
				$result = $db->loadResult();
				if (!empty($result)) {
					$avatar = $source_url . 'images/comprofiler/' . $result;
				} else {
					$avatar = $source_url . 'components/com_comprofiler/plugin/templates/default/images/avatar/nophoto_n.png';
				}
			} catch (Exception $e) {
				try {
					$query = $db->getQuery(true)
						->select('avatar')
						->from('#__community_users')
						->where('userid = ' . (int)$userid);

					$db->setQuery($query);
					$result = $db->loadResult();
					if (!empty($result)) {
						$avatar = $source_url . $result;
					} else {
						$avatar = $source_url . 'components/com_community/assets/default_thumb.jpg';
					}
				} catch (Exception $e) {
					$query = $db->getQuery(true)
						->select('user_picture')
						->from('#__joom_users')
						->where('user_id = ' . (int)$userid);

					$db->setQuery($query);
					$result = $db->loadResult();
					$avatar = $source_url . 'components/com_joomunity/files/avatars/' . $result;
				}
			}
		} catch (Exception $e) {
			$avatar = false;
		}
		return $avatar;
	}

	/**
	 * Returns a query to find online users
	 * Make sure columns are named as userid, username, username_clean (if applicable), name (of user), and email
	 *
	 * @param array $usergroups
	 *
	 * @return string online user query
	 */
	public function getOnlineUserQuery($usergroups = array())
	{
		$db = Factory::getDatabase($this->getJname());

		$query = $db->getQuery(true)
			->select('DISTINCT u.id AS userid, u.username, u.name, u.email')
			->from('#__users AS u')
			->innerJoin('#__session AS s ON u.id = s.userid');

		if (!empty($usergroups)) {
			$usergroups = implode(',', $usergroups);

			$query->innerJoin('#__user_usergroup_map AS g ON u.id = g.user_id')
				->where('g.group_id IN (' . $db->quote($usergroups) . ')');
		}

		$query->where('s.client_id = 0')
			->where('s.guest = 0');

		$query = (string)$query;
		return $query;
	}

	/**
	 * Returns number of guests
	 *
	 * @return int
	 */
	public function getNumberOnlineGuests()
	{
		$db = Factory::getDatabase($this->getJname());

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__session')
			->where('guest = 1')
			->where('client_id = 0');

		$db->setQuery($query);
		return $db->loadResult();
	}

	/**
	 * Returns number of logged in users
	 *
	 * @return int
	 */
	public function getNumberOnlineMembers()
	{
		$db = Factory::getDatabase($this->getJname());

		$query = $db->getQuery(true)
			->select('COUNT(DISTINCT userid) AS c')
			->from('#__session')
			->where('guest = 0')
			->where('client_id = 0');

		$db->setQuery($query);
		return $db->loadResult();
	}

	/**
	 * Update the language front end param in the account of the user if this one changes it
	 * NORMALLY THE LANGUAGE SELECTION AND CHANGEMENT FOR JOOMLA IS PROVIDED BY THIRD PARTY LIKE JOOMFISH
	 *
	 * @param Userinfo $userinfo userinfo
	 *
	 * @return array status
	 */
	public function setLanguageFrontEnd(Userinfo $userinfo = null)
	{
		$user = Factory::getUser($this->getJname());
		$existinguser = (isset($userinfo)) ? $user->getUser($userinfo) : null;
		// If the user is connected we change his account parameter in function of the language front end
		if ($userinfo instanceof Userinfo && $existinguser instanceof Userinfo) {
			$userinfo->language = Factory::getLanguage()->getTag();

			$user->updateUserLanguage($userinfo, $existinguser);
		} else {
			$this->debugger->addDebug(Text::_('NO_USER_DATA_FOUND'));
		}
	}
}
