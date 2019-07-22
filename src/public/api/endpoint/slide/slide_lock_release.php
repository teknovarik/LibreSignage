<?php
/*
*  ====>
*
*  Attempt to lock a slide.
*
*  The operation is authorized if the following conditions are met:
*
*    * The caller is in the 'admin' or 'editor' groups.
*    * The slide has previously been locked by the caller.
*
*  The HTTP status returned by this endpoint is
*
*    * '200 OK' on success.
*    * '423 Locked' if the slide is locked by another user.
*
*  **Request:** POST, application/json
*
*  Parameters
*    * id = The ID of the slide to lock.
*
*  <====
*/

namespace libresignage\api\endpoint\slide;

require_once($_SERVER['DOCUMENT_ROOT'].'/../common/php/Config.php');

use libresignage\api\APIEndpoint;
use libresignage\api\APIException;
use libresignage\api\HTTPStatus;
use libresignage\common\php\slide\Slide;
use libresignage\common\php\slide\SlideLockException;

APIEndpoint::POST(
	[
		'APIAuthModule' => [
			'cookie_auth' => FALSE
		],
		'APIRateLimitModule' => [],
		'APIJSONValidatorModule' => [
			'schema' => [
				'type' => 'object',
				'properties' => [
					'id' => [
						'type' => 'string'
					]
				],
				'required' => ['id']
			]
		]
	],
	function($req, $module_data) {
		$caller = $module_data['APIAuthModule']['user'];
		$session = $module_data['APIAuthModule']['session'];
		$params = $module_data['APIJSONValidatorModule'];

		if (!$caller->is_in_group('admin') && !$caller->is_in_group('editor')) {
			throw new APIException(
				'Not authorized because user is not admin or editor.',
				HTTPStatus::UNAUTHORIZED
			);
		}

		$slide = new Slide();
		$slide->load($params->id);
		try {
			$slide->lock_release($session);
		} catch (SlideLockException $e) {
			throw new APIException(
				"Can't release slide lock created from another session.",
				HTTPStatus::LOCKED,
				$e
			);
		}
		$slide->write();

		return [];
	}
);
