<?php
/**
 * ownCloud - publish activities
 *
 * @copyright (c) 2014, ownCloud Inc.
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
 */

namespace OCA\Files_Sharing;

class Activity implements \OCP\Activity\IExtension {

	const TYPE_REMOTE_SHARE = 'remote_share';
	const SUBJECT_REMOTE_SHARE_RECEIVED = "remote_share_received";
	const SUBJECT_REMOTE_SHARE_ACCEPTED = "remote_share_accepted";
	const SUBJECT_REMOTE_SHARE_DECLINED = "remote_share_declined";

	/**
	 * The extension can return an array of additional notification types.
	 * If no additional types are to be added false is to be returned
	 *
	 * @param string $languageCode
	 * @return array|false
	 */
	public function getNotificationTypes($languageCode) {
		$l = \OC::$server->getL10N('files_sharing', $languageCode);
		return array(self::TYPE_REMOTE_SHARE => $l->t('A file or folder was shared from <strong>another server</strong>'));
	}

	/**
	 * The extension can filter the types based on the filter if required.
	 * In case no filter is to be applied false is to be returned unchanged.
	 *
	 * @param array $types
	 * @param string $filter
	 * @return array|false
	 */
	public function filterNotificationTypes($types, $filter) {
		return $types;
	}

	/**
	 * For a given method additional types to be displayed in the settings can be returned.
	 * In case no additional types are to be added false is to be returned.
	 *
	 * @param string $method
	 * @return array|false
	 */
	public function getDefaultTypes($method) {
		if ($method === 'stream') {
			return array(self::TYPE_REMOTE_SHARE);
		}

		return false;
	}

	/**
	 * The extension can translate a given message to the requested languages.
	 * If no translation is available false is to be returned.
	 *
	 * @param string $app
	 * @param string $text
	 * @param array $params
	 * @param boolean $stripPath
	 * @param boolean $highlightParams
	 * @param string $languageCode
	 * @return string|false
	 */
	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {

		$l = \OC::$server->getL10N('files_sharing', $languageCode);

		if (!$text) {
			return '';
		}

		if ($app === 'files_sharing') {
			switch ($text) {
				case self::SUBJECT_REMOTE_SHARE_RECEIVED:
					return $l->t('You received a new remote share from %s', $params)->__toString();
				case self::SUBJECT_REMOTE_SHARE_ACCEPTED:
					return $l->t('%1$s accepted remote share <strong>%2$s</strong>', $params)->__toString();
				case self::SUBJECT_REMOTE_SHARE_DECLINED:
					return $l->t('%1$s declined remote share <strong>%2$s</strong>', $params)->__toString();
			}
		}
	}

	/**
	 * A string naming the css class for the icon to be used can be returned.
	 * If no icon is known for the given type false is to be returned.
	 *
	 * @param string $type
	 * @return string|false
	 */
	public function getTypeIcon($type) {
		return 'icon-share';
	}

	/**
	 * The extension can define the parameter grouping by returning the index as integer.
	 * In case no grouping is required false is to be returned.
	 *
	 * @param array $activity
	 * @return integer|false
	 */
	public function getGroupParameter($activity) {
		return false;
	}

	/**
	 * The extension can define additional navigation entries. The array returned has to contain two keys 'top'
	 * and 'apps' which hold arrays with the relevant entries.
	 * If no further entries are to be added false is no be returned.
	 *
	 * @return array|false
	 */
	public function getNavigation() {
		return false;
	}

	/**
	 * The extension can check if a customer filter (given by a query string like filter=abc) is valid or not.
	 *
	 * @param string $filterValue
	 * @return boolean
	 */
	public function isFilterValid($filterValue) {
		return false;
	}

	/**
	 * For a given filter the extension can specify the sql query conditions including parameters for that query.
	 * In case the extension does not know the filter false is to be returned.
	 * The query condition and the parameters are to be returned as array with two elements.
	 * E.g. return array('`app` = ? and `message` like ?', array('mail', 'ownCloud%'));
	 *
	 * @param string $filter
	 * @return array|false
	 */
	public function getQueryForFilter($filter) {
		if ($filter === 'shares') {
			return array('`app` = ? and `type` = ?', array('files_sharing', self::TYPE_REMOTE_SHARE));
		}
		return false;
	}

}
