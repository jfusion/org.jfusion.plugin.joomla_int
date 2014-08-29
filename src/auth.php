<?php namespace JFusion\Plugins\joomla_int;
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
use JFusion\Framework;
use JFusion\User\Userinfo;

use Hautelook\Phpass\PasswordHash;

/**
 * JFusion Auth Class for the internal Joomla database
 * For detailed descriptions on these functions please check Plugin_Auth
 *
 * @category   Plugins
 * @package    JFusion\Plugins
 * @subpackage joomla_int
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class Auth extends \JFusion\Plugin\Auth
{
	/**
	 * @var $helper Helper
	 */
	var $helper;

	/**
	 * Generates an encrypted password based on the userinfo passed to this function
	 *
	 * @param Userinfo $userinfo userdata object containing the userdata
	 *
	 * @return string Returns generated password
	 */
	public function generateEncryptedPassword(Userinfo $userinfo)
	{
		if ($this->helper->hasFile('libraries/phpass/PasswordHash.php')) {
			$testcrypt = $this->helper->hashPassword($userinfo->password_clear);
		} else {
			$testcrypt = $this->helper->getCryptedPassword($userinfo->password_clear, $userinfo->password_salt, 'md5-hex');
		}
		return $testcrypt;
	}

	/**
	 * used by framework to ensure a password test
	 *
	 * @param Userinfo $userinfo userdata object containing the userdata
	 *
	 * @return boolean
	 */
	function checkPassword(Userinfo $userinfo) {
		$rehash = false;
		$match = false;

		// If we are using phpass
		if (strpos($userinfo->password, '$P$') === 0) {
			// Use PHPass's portable hashes with a cost of 10.
			$phpass = new PasswordHash(10, true);

			$match = $phpass->CheckPassword($userinfo->password_clear, $userinfo->password);
		} elseif ($userinfo->password[0] == '$') {
			// JCrypt::hasStrongPasswordSupport() includes a fallback for us in the worst case
			$this->helper->hasStrongPasswordSupport();
			$match = password_verify($userinfo->password_clear, $userinfo->password);

			// Uncomment this line if we actually move to bcrypt.
			// $rehash = password_needs_rehash($hash, PASSWORD_DEFAULT);
			$rehash = true;
		} elseif (substr($userinfo->password, 0, 8) == '{SHA256}') {
			// Check the password
			$testcrypt = $this->helper->getCryptedPassword($userinfo->password_clear, $userinfo->password_salt, 'sha256', true);

			$match = $this->comparePassword($userinfo->password, $testcrypt);

			$rehash = true;
		} else {
			$rehash = true;

			$testcrypt = $this->helper->getCryptedPassword($userinfo->password_clear, $userinfo->password_salt, 'md5-hex', false);

			$match = $this->comparePassword($userinfo->password, $testcrypt);
		}

		// If we have a match and rehash = true, rehash the password with the current algorithm.
		if ($match && $rehash) {
			$user = Factory::getUser($this->getJname());
			$old = $user->getUser($userinfo);
			if ($old) {
				$user->updatePassword($userinfo, $old);
			}
		}
		return $match;
	}

	/**
	 * Hashes a password using the current encryption.
	 *
	 * @param   Userinfo  $userinfo  The plaintext password to encrypt.
	 *
	 * @return  string  The encrypted password.
	 *
	 * @since   3.2.1
	 */
	public function hashPassword(Userinfo $userinfo)
	{
		if ($this->helper->hasFile('libraries/phpass/PasswordHash.php')) {
			$password = $this->helper->hashPassword($userinfo->password_clear);
		} else {
			$salt = $this->genRandomPassword(32);
			$password = $this->helper->getCryptedPassword($userinfo->password_clear, $salt, 'md5-hex') . ':' . $salt;
		}
		return $password;
	}
}
