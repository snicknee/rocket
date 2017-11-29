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
namespace rocket\spec\ei\component\field\impl\adapter;

use n2n\util\ex\IllegalStateException;
use rocket\spec\ei\manage\gui\DisplayDefinition;
use rocket\spec\ei\manage\gui\GuiProp;
use n2n\l10n\N2nLocale;
use n2n\util\ex\UnsupportedOperationException;
use rocket\spec\ei\component\field\GuiEiProp;
use rocket\spec\ei\manage\EiObject;
use rocket\spec\ei\manage\util\model\Eiu;
use rocket\spec\ei\component\field\indepenent\EiPropConfigurator;

abstract class DisplayableEiPropAdapter extends IndependentEiPropAdapter implements StatelessDisplayable, GuiEiProp, GuiProp {
	protected $displayDefinition;
	
	public function __construct() {
		$this->displayDefinition = new DisplayDefinition();
	}
	
	public function getDisplayDefinition(): DisplayDefinition {
		return $this->displayDefinition;
	}
	
	public function getGroupType() {
		return null;
	}

	public function createEiPropConfigurator(): EiPropConfigurator {
		$eiPropConfigurator = parent::createEiPropConfigurator();
		IllegalStateException::assertTrue($eiPropConfigurator instanceof AdaptableEiPropConfigurator);
		$eiPropConfigurator->registerDisplayDefinition($this->displayDefinition);
		return $eiPropConfigurator;
	}
	
	
	public function getGuiProp() {
		return $this;
	}
	
	public function getGuiPropFork() {
		return null;
	}

	public function getDisplayLabel(): string {
		return $this->getLabelLstr();
	}
	
	public function buildGuiField(Eiu $eiu) {
		return new StatelessDisplayElement($this, $eiu);
	}
	
	public function getUiOutputLabel(Eiu $eiu) {
		return $this->getLabelLstr();
	}
	
	public function getOutputHtmlContainerAttrs(Eiu $eiu) {
		$eiMask = $this->eiEngine->getEiMask();
		return array('class' => 'rocket-ei-spec-' . $this->eiEngine->getEiType()->getId()
						. ($eiMask !== null ? ' rocket-ei-mask-' . $eiMask->getId() : '') 
						. ' rocket-ei-field-' . $this->getId(), 
				'title' => $this->displayDefinition->getHelpText());
	}
	
	public function isStringRepresentable(): bool {
		return false;
	}
	
	public function buildIdentityString(EiObject $eiObject, N2nLocale $n2nLocale) {
		throw new UnsupportedOperationException('EiProp ' . $this->id . ' not string representable.');
	}
}