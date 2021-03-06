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
namespace rocket\impl\ei\component\prop\relation;

use n2n\l10n\N2nLocale;
use rocket\ei\manage\gui\GuiProp;
use rocket\ei\component\prop\DraftableEiProp;
use rocket\ei\manage\draft\DraftProperty;
use rocket\ei\manage\EiObject;
use rocket\ei\util\model\Eiu;
use rocket\impl\ei\component\prop\relation\model\ToManyEiField;
use rocket\ei\manage\gui\GuiPropFork;

abstract class ToManyEiPropAdapter extends SimpleRelationEiPropAdapter implements GuiProp, DraftableEiProp, 
		DraftProperty {
	protected $min;
	protected $max;
	
	public function getMin() {
		return $this->min;
	}
	
	public function setMin(int $min = null) {
		$this->min = $min;
		if ($min !== null && $min > 0) {
			$this->standardEditDefinition->setMandatory(true);
		}
	}
	
	public function getMax() {
		return $this->max;
	}
	
	public function setMax(int $max = null) {
		$this->max = $max;
	}
	
	public function getRealMin(): int {
		if ($this->min !== null && $this->min > 0) return $this->min;
		
		if ($this->standardEditDefinition->isMandatory()) return 1;
		
		return 0;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \rocket\ei\manage\gui\GuiProp::getDisplayLabel()
	 */
	public function getDisplayLabel(): string {
		return $this->getLabelLstr();
	}

	public function getGuiProp(): ?GuiProp {
		return $this;
	}
	
	public function getGuiPropFork(): ?GuiPropFork {
		return null;
	}
	
	/**
	 * @return boolean
	 */
	public function isStringRepresentable(): bool {
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \rocket\ei\manage\gui\GuiProp::buildIdentityString()
	 */
	public function buildIdentityString(EiObject $eiObject, N2nLocale $n2nLocale): string {
		$targetEiObjects = $this->read($eiObject);
		
		$numTargetEiObjects = count($targetEiObjects);
		if ($numTargetEiObjects == 1) {
			return $numTargetEiObjects . ' ' . $this->eiPropRelation->getTargetEiMask()->getLabelLstr();
		}
		
		return $numTargetEiObjects . ' ' . $this->eiPropRelation->getTargetEiMask()->getPluralLabelLstr();
	}

	public function buildEiField(Eiu $eiu) {
		$readOnly = $this->eiPropRelation->isReadOnly($eiu->entry()->getEiEntry(), $eiu->frame()->getEiFrame());
	
		return new ToManyEiField($eiu->entry()->getEiObject(), $this, $this,
				($readOnly ? null : $this));
	}
}
