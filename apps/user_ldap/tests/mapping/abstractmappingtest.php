<?php
/**
* Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
* This file is licensed under the Affero General Public License version 3 or
* later.
* See the COPYING-README file.
*/

namespace OCA\user_ldap\tests\mapping;

use OCA\UserLDAP\Mapping\UserMapping;

abstract class AbstractMappingTest extends \PHPUnit_Framework_TestCase {
	abstract public function getMapper(\OCP\IDBConnection $dbMock);

	protected function getDBMock() {
		return $this->getMock('\OCP\IDBConnection');
	}

	/**
	 * returns a statement mock
	 * @param bool $success return value of execute()
	 * @param string $vIn the input parameter for execute()
	 * @param string $vOut optional, the result returned by fetchColumn
	 * @return \Doctrine\DBAL\Statement
	 */
	protected function getStatementMock($success, $vIn, $vOut = '') {
		$stmtMock = $this->getMockBuilder('\Doctrine\DBAL\Statement')
			->disableOriginalConstructor()
			->getMock();

		$stmtMock->expects($this->once())
			->method('execute')
			->with(array($vIn))
			->will($this->returnValue($success));

		if($success === true) {
			$stmtMock->expects($this->once())
				->method('fetchColumn')
				->will($this->returnValue($vOut));
		} else {
			$stmtMock->expects($this->never())
				->method('fetchColumn');
		}

		return $stmtMock;
	}

	protected function xByYTestSuccess($method, $input, $expected) {
		$stmtMock = $this->getStatementMock(true, $input, $expected);

		$dbMock = $this->getDBmock();
		$dbMock->expects($this->once())
			->method('prepare')
			->will($this->returnValue($stmtMock));

		$mapper = $this->getMapper($dbMock);

		$result = $mapper->$method($input);

		$this->assertSame($result, $expected);
	}

	protected function xByYTestNoSuccess($method, $input) {
		$stmtMock = $this->getStatementMock(false, $input);

		$dbMock = $this->getDBmock();
		$dbMock->expects($this->once())
			->method('prepare')
			->will($this->returnValue($stmtMock));

		$mapper = $this->getMapper($dbMock);

		$result = $mapper->$method($input);

		$this->assertFalse($result);
	}

	public function testGetDNByNameSuccess() {
		$this->xByYTestSuccess('getDNByName', 'alice', 'uid=alice,dc=example,dc=org');
	}

	public function testGetDNByNameNoSuccess() {
		$this->xByYTestNoSuccess('getDNByName', 'alice');
	}

	public function testGetNameByDNSuccess() {
		$this->xByYTestSuccess('getNameByDN', 'uid=alice,dc=example,dc=org', 'alice');
	}

	public function testGetNameByDNNoSuccess() {
		$this->xByYTestNoSuccess('getNameByDN', 'uid=alice,dc=example,dc=org');
	}

	public function testGetNameByUUIDSuccess() {
		$this->xByYTestSuccess('getNameByDN', '123-abc-4d5e6f-6666', 'alice');
	}

	public function testGetNameByUUIDNoSuccess() {
		$this->xByYTestNoSuccess('getNameByDN', '123-abc-4d5e6f-6666');
	}

}
