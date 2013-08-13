<?php

/**
 * @file controllers/grid/users/stageParticipant/form/AddParticipantForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AddParticipantForm
 * @ingroup controllers_grid_users_stageParticipant_form
 *
 * @brief Form for adding a stage participant
 */

import('controllers.grid.users.stageParticipant.form.StageParticipantNotifyForm');

class AddParticipantForm extends StageParticipantNotifyForm {
	/** The submission associated with the submission contributor being edited **/
	var $_submission;

	/** The stage Id **/
	var $_stageId;

	/** UserGroups **/
	var $_userGroups;

	/**
	 * Constructor.
	 */
	function AddParticipantForm(&$submission, $stageId, &$userGroups) {
		parent::StageParticipantNotifyForm($submission->getId(), ASSOC_TYPE_SUBMISSION, 'controllers/grid/users/stageParticipant/addParticipantForm.tpl');
		$this->_submission =& $submission;
		$this->_stageId = $stageId;
		$this->_userGroups =& $userGroups;

		// add checks in addition to anything that the Notification form may apply.
		$this->addCheck(new FormValidator($this, 'userGroupId', 'required', 'editor.submission.addStageParticipant.form.userGroupRequired'));
		// FIXME: should use a custom validator to check that the user belongs to this group.
		// validating in validate method for now.
		$this->addCheck(new FormValidator($this, 'userId', 'required', 'editor.submission.addStageParticipant.form.userRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the Submission
	 * @return Submission
	 */
	function &getSubmission() {
		return $this->_submission;
	}

	/**
	 * Get the stage ID
	 * @return int
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get the user groups allowed for this grid
	 */
	function &getUserGroups() {
		return $this->_userGroups;
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$userGroups = $this->getUserGroups();

		$userGroupOptions = array();
		foreach ($userGroups as $userGroupId => $userGroup) {
			$userGroupOptions[$userGroupId] = $userGroup->getLocalizedName();
		}
		// assign the user groups options
		$templateMgr->assign('userGroupOptions', $userGroupOptions);
		// assigned the first element as selected
		$templateMgr->assign('selectedUserGroupId', array_shift(array_keys($userGroupOptions)));

		// assign the vars required for the request
		$submission = $this->getSubmission();
		$templateMgr->assign('submissionId', $submission->getId());
		$templateMgr->assign('stageId', $this->getStageId());

		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData($request) {
		$this->readUserVars(array(
			'userGroupId',
			'userId',
			'message',
			'template'
		));
	}

	/**
	 * @copydoc Form::validate()
	 */
	function validate() {
		$userGroupId = (int) $this->getData('userGroupId');
		$userId = (int) $this->getData('userId');
		$submission = $this->getSubmission();

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		return $userGroupDao->userInGroup($userId, $userGroupId) && $userGroupDao->getById($userGroupId, $submission->getContextId());
	}

	/**
	 * @copydoc Form::execute()
	 * @return array($userGroupId, $userId)
	 */
	function execute($request) {
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO'); /* @var $stageAssignmentDao StageAssignmentDAO */
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */

		$submission = $this->getSubmission();
		$userGroupId = (int) $this->getData('userGroupId');
		$userId = (int) $this->getData('userId');

		// sanity check
		if ($userGroupDao->userGroupAssignedToStage($userGroupId, $this->getStageId())) {
			// insert the assignment
			$stageAssignment = $stageAssignmentDao->build($submission->getId(), $userGroupId, $userId);
		}

		parent::execute($request);
		return array($userGroupId, $userId, $stageAssignment->getId());
	}

	/**
	 * whether or not to include the Notify Users listbuilder  true, by default.
	 * @return boolean
	 */
	function includeNotifyUsersListbuilder() {
		return false; // use whoever is assigned to the stage when the form is submitted instead.
	}
}

?>
