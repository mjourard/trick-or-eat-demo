<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 10/29/2017
 * Time: 1:59 AM
 */

namespace TOETests\App\Controller;

use TOE\GlobalCode\clsHTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTesterCreds;

class FeedbackControllerTest extends BaseTestCase
{
	const COMMENT_MAX_SIZE = 2000;
	const TEST_QUESTION_ID = 1;

	/**
	 * @group Feedback
	 */
	public function testSaveComment()
	{
		$this->InitializeTest(clsTesterCreds::NORMAL_USER_EMAIL);
		$fullComment = substr(str_repeat('abcdefghijklmnopqrstuvwxyz0123456789`-=[]\;\',./~!@#$%^&*()_+|}{":?>< ', 29), 0, self::COMMENT_MAX_SIZE);
		$sqlInjectAttempt = '); DROP TABLE user; --';
		$comments = [
			['test' => '', 'expected' => ''],
			['test' => $fullComment, 'expected' => $fullComment],
			['test' => $fullComment, 'expected' => $fullComment],
			['test' => $fullComment . 'X', 'expected' => substr($fullComment, 0, self::COMMENT_MAX_SIZE)],
			['test' => $sqlInjectAttempt, 'expected' => $sqlInjectAttempt]
		];

		foreach ($comments as $comment)
		{

			$this->client->request('POST', '/feedback/saveComment', [
				'comment' => $comment['test'],
				'question_id' => self::TEST_QUESTION_ID
			]);
			$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
			$this->verifyCommentSaved($comment['expected'], self::TEST_QUESTION_ID);
		}
	}

	/**
	 * @group Feedback
	 */
	public function testGetCharacterLimit()
	{
		$this->InitializeTest(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->client->request('GET', '/feedback/comment/maxCharacterCount');
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
		$content = json_decode($this->lastResponse->getContent());
		$this->assertTrue(is_int($content->limit));
		$this->assertEquals(self::COMMENT_MAX_SIZE, $content->limit, "Character limit does not match database column size");
	}

	private function verifyCommentSaved($expected, $questionId)
	{
		$this->client->request('GET', "/feedback/comment/$questionId");
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
		$content = json_decode($this->lastResponse->getContent(), true);
		$this->assertEquals($expected, $content['comment'], "Comments were not the same");
	}

}
