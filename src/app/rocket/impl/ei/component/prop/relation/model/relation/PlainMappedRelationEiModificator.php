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
namespace rocket\impl\ei\component\prop\relation\model\relation;

use rocket\spec\ei\manage\EiFrame;
use n2n\reflection\property\AccessProxy;
use rocket\impl\ei\component\modificator\adapter\EiModificatorAdapter;
use rocket\spec\ei\manage\mapping\WrittenMappingListener;
use rocket\spec\ei\manage\util\model\Eiu;

class PlainMappedRelationEiModificator extends EiModificatorAdapter {
	private $targetEiFrame;
	private $entityObj;
	private $targetAccessProxy;
	private $sourceMany;

	public function __construct(EiFrame $targetEiFrame, $entityObj, AccessProxy $targetAccessProxy, bool $sourceMany) {
		$this->targetEiFrame = $targetEiFrame;
		$this->entityObj = $entityObj;
		$this->targetAccessProxy = $targetAccessProxy;
		$this->sourceMany = $sourceMany;
	}

	public function setupEiEntry(Eiu $eiu) {
		$eiFrame = $eiu->frame()->getEiFrame();
		$eiEntry = $eiu->entry()->getEiEntry();
		
		if ($this->targetEiFrame !== $eiFrame
				|| !$eiEntry->getEiObject()->isNew()) return;

		$that = $this;
		$targetEntityObj = $eiEntry->getEiObject()->getLiveObject();
		$eiEntry->registerListener(new WrittenMappingListener(function () use ($that, $targetEntityObj) {
			$this->write($targetEntityObj);
		}));
	}
	
	protected function write($targetEntityObj) {
		if (!$this->sourceMany) {
			$this->targetAccessProxy->setValue($targetEntityObj, $this->entityObj);
			return;
		}
		
		$value = $this->targetAccessProxy->getValue($this->entityObj);
		if ($value === null) {
			$value = new \ArrayObject();
		}
		$value[] = $this->entityObj;
		$this->targetAccessProxy->setValue($targetEntityObj, $value);
	}
}