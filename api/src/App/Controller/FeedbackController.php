<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 10/28/2017
 * Time: 3:17 AM
 */

namespace TOE\App\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsHTTPCodes;
use TOE\GlobalCode\clsResponseJson;

class FeedbackController extends BaseController
{
	const COMMENT_MAX_SIZE = 2000;

	/**
	 * Saves the passed in comment
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Silex\Application                        $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function saveComment(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([clsConstants::ROLE_ALL]);

		//TODO: Verify the passed in question_id is in the database
		//get the iteration #
		$qb = $this->db->createQueryBuilder();
		$qb->select('iteration')
			->from('feedback')
			->where('user_id = :user_id')
			->andWhere('question_id = :question_id')
			->orderBy('iteration', 'desc')
			->setMaxResults(1)
			->setParameter(':user_id', $this->userInfo->getId())
			->setParameter(':question_id', $app[clsConstants::PARAMETER_KEY]['question_id']);

		$iteration = $qb->execute()->fetch();
		$iteration = empty($iteration) ? 1 : ((int)$iteration['iteration'] + 1);

		$qb = $this->db->createQueryBuilder();
		$qb->insert('feedback')
			->values([
				'user_id'     => ':user_id',
				'question_id' => ':question_id',
				'comment'     => ':comment',
				'iteration'   => $iteration
			])
			->setParameter(':user_id', $this->userInfo->getID())
			->setParameter(':question_id', $app[clsConstants::PARAMETER_KEY]['question_id'])
			->setParameter(':comment', substr($app[clsConstants::PARAMETER_KEY]['comment'], 0, self::COMMENT_MAX_SIZE), clsConstants::SILEX_PARAM_STRING);

		if (!$qb->execute())
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'There was a problem updating your comment.'), clsHTTPCodes::SERVER_GENERIC_ERROR);
		};

		return $app->json(clsResponseJson::GetJsonResponseArray(true, ""), clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
	}

	/**
	 * Gets the comments saved to the user's account.
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Silex\Application                        $app
	 *
	 * @param  int                                      $questionId
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getComment(Request $request, Application $app, $questionId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([clsConstants::ROLE_ALL]);
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'comment'

		)
			->from('feedback')
			->where('user_id = :user_id')
			->andWhere('question_id = :question_id')
			->orderBy('iteration', 'desc')
			->setMaxResults(1)
			->setParameter(':user_id', $this->userInfo->getID())
			->setParameter(':question_id', $questionId);

		$comment = $qb->execute()->fetch();
		$comment = empty($comment) ? '' : $comment['comment'];

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['comment' => $comment]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * Gets the maximum number of characters allowed in a comment
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Silex\Application                        $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getCharacterLimit(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([clsConstants::ROLE_ALL]);
		$qb = $this->db->createQueryBuilder();
		$qb->select('character_maximum_length')
			->from('information_schema.columns')
			->where("table_schema = '" . clsConstants::DATABASE_NAME . "'")
			->andWhere("table_name = 'feedback'")
			->andWhere("column_name = 'comment'");

		$info = $qb->execute()->fetch();
		if (empty($info))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "The feedback datastore doesn't exist"), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['limit' => (int)$info['character_maximum_length']]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	public function getQuestions(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([clsConstants::ROLE_ALL]);
		$qb = $this->db->createQueryBuilder();
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

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['questions' => $questions]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}
}