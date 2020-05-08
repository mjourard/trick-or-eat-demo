<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 10/28/2017
 * Time: 3:17 AM
 */

namespace TOE\App\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use TOE\App\Service\Feedback\FeedbackException;
use TOE\App\Service\Feedback\FeedbackManager;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class FeedbackController extends BaseController
{
	/**
	 * Saves the passed in comment
	 *
	 * @param Request     $request
	 * @param Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function saveComment(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ALL]);

		/** @var FeedbackManager $feedbackManager */
		$feedbackManager = $app['feedback'];
		if(!$feedbackManager->saveComment($this->userInfo->getID(), $app[Constants::PARAMETER_KEY]['question_id'], $app[Constants::PARAMETER_KEY]['comment']))
		{
			$this->logger->err("Unable to save comment", [
				'user_id'     => $this->userInfo->getID(),
				'question_id' => $app[Constants::PARAMETER_KEY]['question_id']
			]);
			return $app->json(ResponseJson::GetJsonResponseArray(false, 'There was a problem saving your comment.'), HTTPCodes::SERVER_SERVICE_UNAVAILABLE);
		}

		return $app->json(ResponseJson::GetJsonResponseArray(true, ""), HTTPCodes::SUCCESS_RESOURCE_CREATED);
	}

	/**
	 * Gets the comments saved to the user's account.
	 *
	 * @param Request     $request
	 * @param Application $app
	 *
	 * @param int         $questionId
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getComment(Request $request, Application $app, $questionId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ALL]);
		/** @var FeedbackManager $feedbackManager */
		$feedbackManager = $app['feedback'];

		return $app->json(ResponseJson::GetJsonResponseArray(true, "", ['comment' => $feedbackManager->getComment($this->userInfo->getID(), $questionId)]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * Gets the maximum number of characters allowed in a comment
	 *
	 * @param Request     $request
	 * @param Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getCharacterLimit(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ALL]);
		/** @var FeedbackManager $feedbackManager */
		$feedbackManager = $app['feedback'];
		try
		{
			return $app->json(ResponseJson::GetJsonResponseArray(true, "", ['limit' => $feedbackManager->getCharacterLimit()]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
		}
		catch(FeedbackException $ex)
		{
			$this->logger->err("Couldn't get character limit", [
				'err' => $ex->getMessage(),
				'type' => get_class($ex)
			]);
			return $app->json(ResponseJson::GetJsonResponseArray(false, "The feedback datastore doesn't exist"), HTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}
	}

	/**
	 * Gets all questions saved in the database
	 *
	 * @param Request     $request
	 * @param Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getQuestions(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ALL]);
		/** @var FeedbackManager $feedbackManager */
		$feedbackManager = $app['feedback'];

		return $app->json(ResponseJson::GetJsonResponseArray(true, "", ['questions' => $feedbackManager->getQuestions()]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}
}