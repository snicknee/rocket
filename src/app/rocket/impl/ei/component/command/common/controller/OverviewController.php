<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the n2n module ROCKET.
 *
 * ROCKET is free software: you can redistribute it and/or modify it under the terms of the
 * GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * ROCKET is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg...........:	Architect, Lead Developer, Concept
 * Bert Hofmänner.............: Idea, Frontend UI, Design, Marketing, Concept
 * Thomas Günther.............: Developer, Frontend UI, Rocket Capability for Hangar
 */
namespace rocket\impl\ei\component\command\common\controller;

use n2n\web\http\PageNotFoundException;
use rocket\impl\ei\component\command\common\model\OverviewModel;
use n2n\web\http\controller\ControllerAdapter;
use rocket\ei\manage\critmod\impl\model\CritmodSaveDao;
use rocket\ei\manage\critmod\impl\model\CritmodForm;
use rocket\ei\manage\critmod\quick\impl\form\QuickSearchForm;
use n2n\web\http\controller\impl\ScrRegistry;
use rocket\ei\manage\critmod\filter\impl\controller\FilterFieldController;
use n2n\web\http\controller\ParamQuery;
use n2n\l10n\DynamicTextCollection;
use rocket\impl\ei\component\command\common\model\DraftListModel;
use rocket\ei\util\model\EiuCtrl;

class OverviewController extends ControllerAdapter {
	private $listSize;
// 	private $manageState;
// 	private $rocketState;
	private $scrRegistry;
	
	private $eiuCtrl;
	
	public function __construct(int $listSize) {
		$this->listSize = $listSize;
	}
	
	public function prepare(ScrRegistry $scrRegistry, EiuCtrl $eiuCtrl) {
// 		$this->manageState = $manageState;
// 		$this->rocketState = $rocketState;
		$this->scrRegistry = $scrRegistry;
		$this->eiuCtrl = $eiuCtrl;
	}
	
	public function index(CritmodSaveDao $critmodSaveDao, $pageNo = null, ParamQuery $numPages = null, ParamQuery $stateKey = null) {
		$eiuFrame = $this->eiuCtrl->frame();
		$eiFrame = $eiuFrame->getEiFrame();
		if ($stateKey !== null) {
            $stateKey = $stateKey->__toString();
		} else {
            $stateKey = OverviewJhtmlController::genStateKey();
		}
		$critmodForm = CritmodForm::create($eiFrame, $critmodSaveDao, $stateKey);
		$quickSearchForm = QuickSearchForm::create($eiFrame, $critmodSaveDao, $stateKey);
		$listModel = new OverviewModel($eiuFrame, $this->listSize, $critmodForm, $quickSearchForm);
		
		if ($pageNo === null) {
			$pageNo = 1;
		} else if ($pageNo == 1) {
			throw new PageNotFoundException();
		}
		
		if (!$listModel->initialize((int) $pageNo, ($numPages === null ? 1 : $numPages->toIntOrReject()))) {
			throw new PageNotFoundException();
		}
		
		$overviewAjahHook = OverviewJhtmlController::buildAjahHook($this->getHttpContext()
				->getControllerContextPath($this->getControllerContext())->ext('ajah')->toUrl(), $stateKey);
		$filterAjahHook = FilterFieldController::buildFilterAjahHook($this->getHttpContext()
				->getControllerContextPath($this->getControllerContext())->ext('filter')->toUrl());
		
		$this->eiuCtrl->applyCommonBreadcrumbs();
		
		$this->eiuCtrl->forwardView(
				$this->createView('..\view\overview.html', array('listModel' => $listModel, 
						'critmodForm' => $critmodForm,
						'quickSearchForm' => $quickSearchForm, 'overviewAjahHook' => $overviewAjahHook, 
						'filterAjahHook' => $filterAjahHook)));
				
// 		$this->forward('..\view\overview.html', 
// 				array('listModel' => $listModel, 'critmodForm' => $critmodForm,
// 						'quickSearchForm' => $quickSearchForm, 'overviewAjahHook' => $overviewAjahHook, 
// 						'filterAjahHook' => $filterAjahHook, 'listView' => $listView));
	}
	
	public function doAjah(array $delegateCmds = array(), OverviewJhtmlController $ajahOverviewController, 
			ParamQuery $pageNo = null) {
		if ($pageNo !== null) {
			$pageNo = $pageNo->toNumericOrReject();
			$this->eiuCtrl->frame()->getEiFrame()->setCurrentUrlExt(
					$this->getControllerContext()->getCmdContextPath()->ext($pageNo > 1 ? $pageNo : null)->toUrl());
		}
				
		$ajahOverviewController->setListSize($this->listSize);
		$this->delegate($ajahOverviewController);
	}
	
	public function doFilter(array $delegateCmds = array(), FilterFieldController $filterFieldController) {
		$this->delegate($filterFieldController);
	}
	
	public function doDrafts($pageNo = null, DynamicTextCollection $dtc) {
		$eiFrame = $this->eiuCtrl->frame()->getEiFrame();
		$draftListModel = new DraftListModel($eiFrame, $this->listSize);
		
		if ($pageNo === null) {
			$pageNo = 1;
		} else if ($pageNo == 1) {
			throw new PageNotFoundException();
		}
		
		if (!$draftListModel->initialize($pageNo)) {
			throw new PageNotFoundException();
		}
		
		$listView = $eiFrame->getContextEiEngine()->getEiMask()->createListView($eiFrame, $draftListModel->getEntryGuis());
		
		$this->eiuCtrl->applyCommonBreadcrumbs(null, $dtc->translate('ei_impl_drafts_title'));
		
		$stateKey = OverviewDraftJhtmlController::genStateKey();
		$overviewDraftAjahHook = OverviewDraftJhtmlController::buildAjahHook($this->getHttpContext()->getControllerContextPath(
				$this->getControllerContext())->ext('draftAjah')->toUrl(), $stateKey);

		$this->forward('..\view\overviewDrafts.html', array('draftListModel' => $draftListModel, 
				'overviewDraftAjahHook' => $overviewDraftAjahHook, 'listView' => $listView));
	}

	public function doDraftAjah(array $delegateCmds = array(), OverviewDraftJhtmlController $overviewDraftJhtmlController,
			ParamQuery $pageNo = null) {
		if ($pageNo !== null) {
			$this->eiuCtrl->frame()->getEiFrame()->setCurrentUrlExt(
					$this->getControllerContext()->getCmdContextPath()->ext('drafts', $pageNo->toNumericOrReject())->toUrl());
		}

		$this->delegate($overviewDraftJhtmlController);
	}
	
	public function doDelete($pageNo = null) {
		$eiFrame = $this->manageState->peakEiFrame();
		
// 		$this->manageState->getDraftManager()->findRemoved();
	}
	
	
// 	private function createNavPoints(ListModel $listModel) {
// 		if ($listModel->getNumPages() < 2) return array();
		
// 		$request = $this->getRequest();
// 		$navPoints = array();
// 		for ($pageNo = 1; $pageNo <= $listModel->getNumPages(); $pageNo++) {
// 			$navPoints[$request->getControllerContextPath($this->getControllerContext(), ($pageNo > 1 ? $pageNo : null))] = $pageNo;
// 		}
// 		return $navPoints;
// 	}
}
