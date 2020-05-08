<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 10/29/2017
 * Time: 1:59 AM
 */

namespace TOETests\App\Controller;

use TOE\GlobalCode\HTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTesterCreds;

class FeedbackControllerTest extends BaseTestCase
{
	public const COMMENT_MAX_SIZE = 2000;
	public const TEST_QUESTION_ID = 1;

	/**
	 * @group Feedback
	 */
	public function testSaveComment()
	{
		$this->initializeTest(clsTesterCreds::NORMAL_USER_EMAIL);
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
			$this->basicResponseCheck(HTTPCodes::SUCCESS_RESOURCE_CREATED);
			$this->verifyCommentSaved($comment['expected'], self::TEST_QUESTION_ID);
		}
	}

	/**
	 * @group Feedback
	 */
	public function testGetCharacterLimit()
	{
		$this->initializeTest(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->client->request('GET', '/feedback/comment/maxCharacterCount');
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
		$content = json_decode($this->lastResponse->getContent());
		self::assertTrue(is_int($content->limit));
		self::assertEquals(self::COMMENT_MAX_SIZE, $content->limit, "Character limit does not match database column size");
	}

	private function verifyCommentSaved($expected, $questionId)
	{
		$this->client->request('GET', "/feedback/comment/$questionId");
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
		$content = json_decode($this->lastResponse->getContent(), true);
		self::assertEquals($expected, $content['comment'], "Comments were not the same");
	}

}
