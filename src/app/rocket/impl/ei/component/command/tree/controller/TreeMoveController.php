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
namespace rocket\impl\ei\component\command\tree\controller;

use rocket\ei\manage\ManageState;
use n2n\web\http\controller\ControllerAdapter;
use n2n\web\http\controller\ParamGet;
use rocket\ei\util\model\UnknownEntryException;
use n2n\persistence\orm\util\NestedSetUtils;
use rocket\ei\util\model\EiuCtrl;
use rocket\ajah\JhtmlEvent;

class TreeMoveController extends ControllerAdapter {
	private $eiCtrl;

	public function prepare(ManageState $manageState, EiuCtrl $eiuCtrl) {
		$this->eiCtrl = $eiuCtrl;
	}

	public function doChild($targetPid, ParamGet $pids, ParamGet $refPath) {
		$refUrl = $this->eiCtrl->parseRefUrl($refPath);
		
		foreach ($pids->toStringArrayOrReject() as $pid) {
			$this->move($pid, $targetPid);
		}
		
		$this->eiCtrl->redirectToReferer($refUrl, JhtmlEvent::ei()->noAutoEvents());
	}
	
	public function doBefore($targetPid, ParamGet $pids, ParamGet $refPath) {
		$refUrl = $this->eiCtrl->parseRefUrl($refPath);

		foreach ($pids->toStringArrayOrReject() as $pid) {
			$this->move($pid, $targetPid, true);
		}
		
		$this->eiCtrl->redirectToReferer($refUrl, JhtmlEvent::ei()->noAutoEvents());
	}

	public function doAfter($targetPid, ParamGet $pids, ParamGet $refPath) {
		$refUrl = $this->eiCtrl->parseRefUrl($refPath);

		foreach (array_reverse($pids->toStringArrayOrReject()) as $pid) {
			$this->move($pid, $targetPid, false);
		}

		$this->eiCtrl->redirectToReferer($refUrl, JhtmlEvent::ei()->noAutoEvents());
	}

	private function move(string $pid, string $targetPid, bool $before = null) {
		if ($pid === $targetPid) return;

		$eiUtils = $this->eiCtrl->frame();
		
		$nestedSetStrategy = $eiUtils->getNestedSetStrategy();
		if ($nestedSetStrategy === null) return;
		
		$eiEntityObj = null;
		$targetEiEntityObj = null;

		try {
			$eiEntityObj = $eiUtils->lookupEntry($eiUtils->pidToId($pid))->getEiEntityObj();
			$targetEiEntityObj = $eiUtils->lookupEntry($eiUtils->pidToId($targetPid))->getEiEntityObj();
		} catch (UnknownEntryException $e) {
			return;
		} catch (\InvalidArgumentException $e) {
			return;
		}

		$nsu = new NestedSetUtils($eiUtils->em(), $eiUtils->getContextClass());
		
		try {
			if ($before === true) {
				$nsu->moveBefore($eiEntityObj->getEntityObj(), $targetEiEntityObj->getEntityObj());
			} else if ($before === false) {
				$nsu->moveAfter($eiEntityObj->getEntityObj(), $targetEiEntityObj->getEntityObj());
			} else {
				$nsu->move($eiEntityObj->getEntityObj(), $targetEiEntityObj->getEntityObj());
			}
		} catch (\n2n\util\ex\IllegalStateException $e) {
		}
	}
}
