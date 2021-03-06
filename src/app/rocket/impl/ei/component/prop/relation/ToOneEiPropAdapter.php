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
use n2n\core\container\N2nContext;
use n2n\impl\persistence\orm\property\ToOneEntityProperty;
use n2n\persistence\orm\property\EntityProperty;
use n2n\reflection\ArgUtils;
use rocket\impl\ei\component\prop\adapter\DisplaySettings;
use rocket\ei\util\model\Eiu;
use rocket\impl\ei\component\prop\relation\model\ToOneEiField;
use rocket\ei\manage\gui\GuiPropFork;

abstract class ToOneEiPropAdapter extends SimpleRelationEiPropAdapter implements GuiProp, DraftableEiProp, 
		DraftProperty {

	public function setEntityProperty(EntityProperty $entityProperty = null) {
		ArgUtils::assertTrue($entityProperty instanceof ToOneEntityProperty);
		parent::setEntityProperty($entityProperty);
	}

	public function buildEiField(Eiu $eiu) {
		$readOnly = $this->eiPropRelation->isReadOnly($eiu->entry()->getEiEntry(), $eiu->frame()->getEiFrame());
	
		return new ToOneEiField($eiu->entry()->getEiObject(), $this, $this,
				($readOnly ? null : $this));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \rocket\ei\manage\gui\GuiProp::getDisplayLabel()
	 */
	public function getDisplayLabel(): string {
		return $this->getLabelLstr();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \rocket\ei\manage\gui\GuiProp::getDisplaySettings()
	 */
	public function getDisplaySettings(): DisplaySettings {
		return $this->displaySettings;
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
		$targetEiObject = $this->read($eiObject);
		if ($targetEiObject === null) return '';
		
		return $this->eiPropRelation->getTargetEiMask()->createIdentityString($targetEiObject, $n2nLocale);
	}
			
	public function isEiEntryFilterable(): bool {
		return true;
	}
	
	public function buildEiEntryFilterField(N2nContext $n2nContext) {		
		return null;
// 		$targetEiMask = $this->eiPropRelation->getTargetEiMask();
		
// 		return new ToOneEiEntryFilterField($this->getLabelLstr(), $this->getEntityProperty(),
// 				new GlobalEiuFrame($this->getEiPropRelation()->getTargetEiMask(), $n2nContext),
// 				$this->createAdvTargetFilterDef($n2nContext));
	}
}
