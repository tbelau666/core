<?php
/**
 * ownCloud - OCS API for server-to-server shares
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files\Share\API;

class Server2Server {

	/**
	 * create a new share
	 *
	 * @param array $params
	 * @return \OC_OCS_Result
	 */
	public static function createShare() {

		$remote = isset($_POST['remote']) ? $_POST['remote'] : null;
		$token = isset($_POST['token']) ? $_POST['token'] : null;
		$name = isset($_POST['name']) ? $_POST['name'] : null;
		$owner = isset($_POST['owner']) ? $_POST['owner'] : null;
		$shareWith = isset($_POST['shareWith']) ? $_POST['shareWith'] : null;
		$remoteId = isset($_POST['remote_id']) ? $_POST['remote_id'] : null;

		if ($remote && $token && $name && $owner && $remoteId && $shareWith) {

			if(!\OCP\Util::isValidFileName($name)) {
				return new \OC_OCS_Result(null, 400, 'The mountpoint name contains invalid characters.');
			}

			if (!\OCP\User::userExists($shareWith)) {
				return new \OC_OCS_Result(null, 400, 'User does not exists');
			}

			\OC_Util::setupFS($shareWith);

			$mountPoint = \OC\Files\Filesystem::normalizePath('/' . $name);
			$name = \OCP\Files::buildNotExistingFileName('/', $name);

			try {
				\OCA\Files_Sharing\Helper::addServer2ServerShare($remote, $token, $name, $mountPoint, $owner, $shareWith, '', $remoteId);

				\OC::$server->getActivityManager()->publishActivity(
						'files_sharing', \OCA\Files_Sharing\Activity::SUBJECT_REMOTE_SHARE_RECEIVED, array($owner), '', array(),
						'', '', $shareWith, \OCA\Files_Sharing\Activity::TYPE_REMOTE_SHARE, \OCA\Files_Sharing\Activity::PRIORITY_LOW);

				return new \OC_OCS_Result();
			} catch (\Exception $e) {
				return new \OC_OCS_Result(null, 500, 'server can not add remote share, ' . $e->getMessage());
			}
		}

		return new \OC_OCS_Result(null, 400, 'server can not add remote share, missing parameter');
	}

	/**
	 * accept server-to-server share
	 *
	 * @param array $params
	 * @return \OC_OCS_Result
	 */
	public static function acceptShare($params) {
		$id = $params['id'];
		$token = isset($_POST['token']) ? $_POST['token'] : null;
		$share = self::getShare($id, $token);

		list($file, $link) = self::getFile($share['uid_owner'], $share['file_source']);

		\OC::$server->getActivityManager()->publishActivity(
				'files_sharing', \OCA\Files_Sharing\Activity::SUBJECT_REMOTE_SHARE_ACCEPTED, array($share['share_with'], basename($file)), '', array(),
				$file, $link, $share['uid_owner'], \OCA\Files_Sharing\Activity::TYPE_REMOTE_SHARE, \OCA\Files_Sharing\Activity::PRIORITY_LOW);

		return new \OC_OCS_Result();
	}

	/**
	 * decline server-to-server share
	 *
	 * @param array $params
	 * @return \OC_OCS_Result
	 */
	public static function declineShare($params) {
		$id = $params['id'];
		$token = isset($_POST['token']) ? $_POST['token'] : null;

		$share = self::getShare($id, $token);

		$query = \OCP\DB::prepare('DELETE FROM `*PREFIX*share` WHERE id= ? AND token = ?');
		$query->execute(array($id, $token));

		list($file, $link) = self::getFile($share['uid_owner'], $share['file_source']);

		\OC::$server->getActivityManager()->publishActivity(
				'files_sharing', \OCA\Files_Sharing\Activity::SUBJECT_REMOTE_SHARE_DECLINED, array($share['share_with'], basename($file)), '', array(),
				$file, $link, $share['uid_owner'], \OCA\Files_Sharing\Activity::TYPE_REMOTE_SHARE, \OCA\Files_Sharing\Activity::PRIORITY_LOW);

		return new \OC_OCS_Result();
	}

	/**
	 * get share
	 *
	 * @param int $id
	 * @param string $token
	 * @return array
	 */
	private static function getShare($id, $token) {
		$query = \OCP\DB::prepare('SELECT * FROM `*PREFIX*share` WHERE id= ? AND token = ?');
		$query->execute(array($id, $token));
		$share = $query->fetchRow();

		return $share;
	}

	/**
	 * get file
	 *
	 * @param string $user
	 * @param int $fileSource
	 */
	private static function getFile($user, $fileSource) {
		\OC_Util::setupFS($user);

		$file = \OC\Files\Filesystem::getPath($fileSource);
		$args = \OC\Files\Filesystem::is_dir($file) ? array('dir' => $file) : array('dir' => dirname($file), 'scrollto' => $file);
		$link = \OCP\Util::linkToAbsolute('files', 'index.php', $args);

		return array($file, $link);

	}

	/**
	 * decline server-to-server share
	 *
	 * @param array $params
	 * @return \OC_OCS_Result
	 */
	public static function unshare($params) {
		$id = $params['id'];
		$owner = isset($_POST['owner']) ? $_POST['owner'] : null;
		$token = isset($_POST['token']) ? $_POST['token'] : null;
		$user = isset($_POST['user']) ? $_POST['user'] : null;
		// TODO send signal to activity app that server2server share was declined
		// TODO remove share from oc_share table
	}

}
