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
namespace rocket\impl\ei\component\modificator\adapter;

use rocket\ei\component\modificator\EiModificator;
use rocket\impl\ei\component\IndependentEiComponentAdapter;
use rocket\ei\component\modificator\IndependentEiModificator;
use rocket\ei\manage\gui\GuiDefinition;
use rocket\ei\component\EiConfigurator;
use rocket\impl\ei\component\DefaultEiConfigurator;
use rocket\ei\util\model\Eiu;

abstract class IndependentEiModificatorAdapter extends IndependentEiComponentAdapter implements IndependentEiModificator {
	
	/**
	 * {@inheritDoc}
	 * @see \rocket\ei\component\IndependentEiComponent::createEiConfigurator()
	 */
	public function createEiConfigurator(): EiConfigurator {
		return new DefaultEiConfigurator($this);
	}
	
	public function equals($obj) {
		return $obj instanceof EiModificator && parent::equals($obj);
	}
	
	public function setupEiFrame(Eiu $eiu) { }
	
	public function setupEiEntry(Eiu $eiu) { }
	
	public function setupGuiDefinition(GuiDefinition $guiDefinition) { }
	
	/**
	 * {@inheritDoc}
	 * @see \rocket\ei\component\modificator\EiModificator::setupEiEntryGui()
	 */
	public function setupEiEntryGui(\rocket\ei\manage\gui\EiEntryGui $eiEntryGui) { }
	
	/**
	 * {@inheritDoc}
	 * @see \rocket\ei\component\modificator\EiModificator::setupDraftDefinition()
	 */
	public function setupDraftDefinition(\rocket\ei\manage\draft\DraftDefinition $draftDefinition) { }
}