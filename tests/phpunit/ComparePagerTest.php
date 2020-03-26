<?php

use MediaWiki\CheckUser\ComparePager;
use MediaWiki\CheckUser\CompareService;
use MediaWiki\CheckUser\TokenManager;
use MediaWiki\MediaWikiServices;
use Wikimedia\IPUtils;

/**
 * @group CheckUser
 * @group Database
 * @covers \MediaWiki\CheckUser\ComparePager
 */
class ComparePagerTest extends MediaWikiTestCase {

	/**
	 * @dataProvider provideDoQuery
	 */
	public function testDoQuery( $targets, $hideTargets, $expected ) {
		$services = MediaWikiServices::getInstance();

		$tokenManager = $this->getMockBuilder( TokenManager::class )
			->setConstructorArgs( [ 'secret' ] )
			->setMethods( [ 'getDataFromRequest' ] )
			->getMock();
		$tokenManager->method( 'getDataFromRequest' )
			->willReturn( [
				'targets' => $targets,
				'hide-targets' => $hideTargets,
			] );

		$compareService = $this->getMockBuilder( CompareService::class )
			->setConstructorArgs( [ $services->getDBLoadBalancer() ] )
			->setMethods( [ 'getUserId' ] )
			->getMock();
		$compareService->method( 'getUserId' )
			->will(
				$this->returnValueMap( [
					[ 'User1', 11111, ],
					[ 'User2', 22222, ],
					[ 'InvalidUser', 0 ],
					[ '', 0 ],
					[ '1.2.3.9/120', 0 ]
				] )
			);

		$pager = new ComparePager(
			RequestContext::getMain(),
			$services->get( 'LinkRenderer' ),
			$tokenManager,
			$compareService
		);
		$pager->doQuery();

		$this->assertSame( $expected, $pager->mResult->numRows() );
	}

	public function provideDoQuery() {
		return [
			[ [ 'User1' ], [], 2 ],
			[ [ 'User1', 'InvalidUser', '1.2.3.9/120' ], [], 2 ],
			[ [ 'User1', '' ], [], 2 ],
			[ [ 'User2' ], [], 1 ],
			[ [ 'User2' ], [ 'User2' ], 0 ],
			[ [ '1.2.3.4' ], [], 4 ],
			[ [ '1.2.3.0/24' ], [], 7 ],
		];
	}

	public function addDBData() {
		$testData = [
			[
				'cuc_user'       => 0,
				'cuc_user_text'  => '1.2.3.4',
				'cuc_type'       => RC_NEW,
				'cuc_ip'         => '1.2.3.4',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.4' ),
				'cuc_agent'      => 'foo user agent',
			], [
				'cuc_user'       => 0,
				'cuc_user_text'  => '1.2.3.4',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.4',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.4' ),
				'cuc_agent'      => 'foo user agent',
			], [
				'cuc_user'       => 0,
				'cuc_user_text'  => '1.2.3.4',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.4',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.4' ),
				'cuc_agent'      => 'bar user agent',
			], [
				'cuc_user'       => 0,
				'cuc_user_text'  => '1.2.3.5',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.5',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.5' ),
				'cuc_agent'      => 'bar user agent',
			], [
				'cuc_user'       => 0,
				'cuc_user_text'  => '1.2.3.5',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.5',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.5' ),
				'cuc_agent'      => 'foo user agent',
			], [
				'cuc_user'       => 11111,
				'cuc_user_text'  => 'User1',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.4',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.4' ),
				'cuc_agent'      => 'foo user agent',
			], [
				'cuc_user'       => 22222,
				'cuc_user_text'  => 'User2',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.4',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.4' ),
				'cuc_agent'      => 'foo user agent',
			], [
				'cuc_user'       => 11111,
				'cuc_user_text'  => 'User1',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.5',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.5' ),
				'cuc_agent'      => 'foo user agent',
			],
		];

		$commonData = [
			'cuc_namespace'  => NS_MAIN,
			'cuc_title'      => 'Foo_Page',
			'cuc_minor'      => 0,
			'cuc_page_id'    => 1,
			'cuc_timestamp'  => '',
			'cuc_xff'        => 0,
			'cuc_xff_hex'    => null,
			'cuc_actiontext' => '',
			'cuc_comment'    => '',
			'cuc_this_oldid' => 0,
			'cuc_last_oldid' => 0,
		];

		foreach ( $testData as $row ) {
			$this->db->insert( 'cu_changes', $row + $commonData );
		}

		$this->tablesUsed[] = 'cu_changes';
	}
}