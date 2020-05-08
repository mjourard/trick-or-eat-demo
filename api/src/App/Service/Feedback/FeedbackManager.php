<?php
declare(strict_types=1);

namespace TOE\App\Service\Feedback;


use TOE\App\Service\BaseDBService;
use TOE\GlobalCode\Constants;

class FeedbackManager extends BaseDBService
{
	public const COMMENT_MAX_SIZE = 2000;

	/**
	 * Saves the passed in comment
	 *
	 * @param $userId
	 * @param $questionId
	 * @param $comment
	 *
	 * @return bool true on success, false if there was a failure to save the comment
	 */
	public function saveComment($userId, $questionId, $comment)
	{
		//TODO: Verify the passed in question_id is in the database
		//get the iteration #
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('iteration')
			->from('feedback')
			->where('user_id = :user_id')
			->andWhere('question_id = :question_id')
			->orderBy('iteration', 'desc')
			->setMaxResults(1)
			->setParameter(':user_id', $userId)
			->setParameter(':question_id', $questionId);

		$iteration = $qb->execute()->fetch();
		$iteration = empty($iteration) ? 1 : ((int)$iteration['iteration'] + 1);

		//shrink comment down to max size allowed
		$comment = substr($comment, 0, self::COMMENT_MAX_SIZE);

		$qb = $this->dbConn->createQueryBuilder();
		$qb->insert('feedback')
			->values([
				'user_id'     => ':user_id',
				'question_id' => ':question_id',
				'comment'     => ':comment',
				'iteration'   => ':iteration'
			])
			->setParameter(':user_id', $userId)
			->setParameter(':question_id', $questionId)
			->setParameter(':iteration', $iteration)
			->setParameter(':comment', $comment, Constants::SILEX_PARAM_STRING);
		return $qb->execute() > 0;
	}

	/**
	 * Gets the current comment saved to the user's account for the passed in question id
	 *
	 * @param $userId
	 * @param $questionId
	 *
	 * @return mixed
	 */
	public function getComment($userId, $questionId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'comment'
		)
			->from('feedback')
			->where('user_id = :user_id')
			->andWhere('question_id = :question_id')
			->orderBy('iteration', 'desc')
			->setMaxResults(1)
			->setParameter(':user_id', $userId)
			->setParameter(':question_id', $questionId);

		$comment = $qb->execute()->fetch();
		return empty($comment) ? '' : $comment['comment'];
	}

	/**
	 * Gets the maximum number of characters allowed in a comment
	 *
	 * @return int
	 * @throws FeedbackException
	 */
	public function getCharacterLimit()
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('character_maximum_length')
			->from('information_schema.columns')
			->where("table_schema = '" . Constants::DATABASE_NAME . "'")
			->andWhere("table_name = 'feedback'")
			->andWhere("column_name = 'comment'");

		$info = $qb->execute()->fetch();
		if (empty($info))
		{
			throw new FeedbackException("Unable to access the information_schema database tp get the table size of the 'comment' column");
		}

		return (int)$info['character_maximum_length'];
	}

	/**
	 * gets the questions saved in the database
	 *
	 * @return array
	 */
	public function getQuestions()
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'question_id',
			'question',
			'response_limit'
		)
			->from('question')
			->where("status = 'active'")
			->orderBy('question_id', 'ASC');
		$questions = $qb->execute()->fetchAll();
		foreach ($questions as &$question)
		{
			$question['response_limit'] = (int)$question['response_limit'];
		}
		return $questions;
	}
}