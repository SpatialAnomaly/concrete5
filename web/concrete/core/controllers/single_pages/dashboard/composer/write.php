<?
defined('C5_EXECUTE') or die("Access Denied.");

class Concrete5_Controller_Dashboard_Composer_Write extends DashboardBaseController {

	public function view($type = false, $id = false) {
		switch($type) {
			case 'composer':
				$this->composer = Composer::getByID($id);
				$saveURL = View::url('/dashboard/composer/write', 'save', 'composer', $id);
				$discardURL = View::url('/dashboard/composer/drafts');
				$publishURL = View::url('/dashboard/composer/write', 'save', 'composer', $id, 'publish');
				break;
			case 'draft':
				$this->draft = ComposerDraft::getByID($id);
				if (is_object($this->draft)) {
					$this->composer = $this->draft->getComposerObject();
				}
				$saveURL = View::url('/dashboard/composer/write', 'save', 'draft', $id);
				$discardURL = View::url('/dashboard/composer/write', 'discard', $id, Loader::helper('validation/token')->generate('discard_draft'));
				$publishURL = View::url('/dashboard/composer/write', 'save', 'draft', $id, 'publish');
				break;
		}

		$this->addFooterItem(Loader::helper('html')->javascript('ccm.composer.js'));
		$js =<<<EOL
<script type="text/javascript">$(function() { $('form[data-form=composer]').ccmcomposer({saveURL: '{$saveURL}', discardURL: '{$discardURL}', publishURL: '{$publishURL}'})});</script>
EOL;
		$this->addFooterItem($js);

		if (!is_object($this->composer)) {
			$composers = Composer::getList();
			if (count($composers) == 1) {
				$cmp = $composers[0];
				$this->redirect('/dashboard/composer/write', 'composer', $cmp->getComposerID());
			} else {
				$this->set('composers', $composers);
			}
		} else {
			$this->set('composer', $this->composer);
			$this->set('fieldsets', ComposerFormLayoutSet::getList($this->composer));
			$this->set('draft', $this->draft);
			$this->setupAssets();
		}
	}

	protected function setupAssets() {
		$sets = $this->get('fieldsets');
		foreach($sets as $s) {
			$controls = ComposerFormLayoutSetControl::getList($s);
			foreach($controls as $cn) {
				$basecontrol = $cn->getComposerControlObject();
				$basecontrol->onComposerControlRender();
			}
		}
	}

	protected function publish(ComposerDraft $d, $outputControls) {

		if (!$d->getComposerDraftTargetParentPageID()) {
			$this->error->add(t('You must choose a page to publish this page beneath.'));
		}

		foreach($outputControls as $oc) {
			if ($oc->isComposerFormControlRequiredOnThisRequest()) {
				$data = $oc->getRequestValue();
				$oc->validate($data, $this->error);
			}
		}

		if (!$this->error->has()) {
			$d->publish();
			$c = $d->getComposerDraftCollectionObject();
			header('Location: ' . BASE_URL . DIR_REL . '/' . DISPATCHER_FILENAME . '?cID=' . $c->getCollectionID());
		}
	}

	public function discard($cmpDraftID = false, $token = false) {
		if (Loader::helper('validation/token')->validate('discard_draft', $token)) {
			$draft = ComposerDraft::getByID($cmpDraftID);
			$draft->discard();
			$this->redirect('/dashboard/composer/drafts');
		}
	}

	public function save($type = 'composer', $id = false, $action = 'return_json') {
		Cache::disableCache();
		Cache::disableLocalCache();
		session_write_close();

		$this->view($type, $id);
		$ct = CollectionType::getByID($this->post('cmpPageTypeID'));
		$availablePageTypes = $this->composer->getComposerPageTypeObjects();

		if (!is_object($ct) && count($availablePageTypes) == 1) {
			$ct = $availablePageTypes[0];
		}

		$this->error = $this->composer->validateCreateDraftRequest($ct);

		if (!$this->error->has()) {
			// create the page
			if (!is_object($this->draft)) {
				$d = $this->composer->createDraft($ct);
			} else {
				$d = $this->draft;
				$d->createNewCollectionVersion();
			}

			$controls = ComposerControl::getList($this->composer);
			$outputControls = array();
			foreach($controls as $cn) {
				$data = $cn->getRequestValue();
				$cn->publishToPage($d, $data, $controls);
				$outputControls[] = $cn;
			}
			$d->setPageNameFromComposerControls($outputControls);
			$configuredTarget = $this->composer->getComposerTargetObject();
			$targetPageID = $configuredTarget->getComposerConfiguredTargetParentPageID();
			if (!$targetPageID) {
				$targetPageID = $this->post('cParentID');
			}
			$d->setComposerDraftTargetParentPageID($targetPageID);
			$d->finishSave();
			if ($action == 'return_json') {
				$ax = Loader::helper('ajax');
				$r = new stdClass;
				$r->time = date('F d, Y g:i A');
				$r->cmpDraftID = $d->getComposerDraftID();
				// we make sure to send back the save url which we need because it changes
				// between making a draft and saving new copies of drafts.
				$r->saveurl = View::url('/dashboard/composer/write', 'save', 'draft', $d->getComposerDraftID());
				$r->discardurl = View::url('/dashboard/composer/write', 'discard', $d->getComposerDraftID(), Loader::helper('validation/token')->generate('discard_draft'));
				$r->publishurl = View::url('/dashboard/composer/write', 'save', $d->getComposerDraftID(), 'publish');
				$ax->sendResult($r);
			} else if ($action == 'publish') {
				$this->publish($d, $outputControls);
			}
		}
	}

}