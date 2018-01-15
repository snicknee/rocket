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
namespace rocket\impl\ei\component\prop\adapter;

use n2n\core\container\N2nContext;
use n2n\util\ex\IllegalStateException;
use rocket\spec\ei\component\prop\PrivilegedEiProp;
use rocket\spec\ei\EiPropPath;
use rocket\spec\ei\manage\gui\GuiProp;
use n2n\l10n\N2nLocale;
use n2n\util\ex\UnsupportedOperationException;
use rocket\spec\ei\component\prop\GuiEiProp;
use rocket\spec\ei\manage\EiObject;
use rocket\spec\ei\manage\util\model\Eiu;
use rocket\spec\ei\component\prop\indepenent\EiPropConfigurator;
use rocket\spec\ei\manage\gui\DisplayDefinition;
use n2n\reflection\ArgUtils;
use rocket\spec\ei\manage\gui\ui\DisplayItem;
use rocket\spec\ei\manage\gui\ViewMode;
use rocket\spec\security\EiPropPrivilege;

abstract class EditableEiPropAdapter extends DisplayableEiPropAdapter implements StatelessEditable, PrivilegedEiProp {
	protected $standardEditDefinition;

	/**
	 * @return \rocket\impl\ei\component\prop\adapter\StandardEditDefinition
	 */
	public function getStandardEditDefinition() {
		if ($this->standardEditDefinition === null) {
			$this->standardEditDefinition = new StandardEditDefinition();
		}

		return $this->standardEditDefinition;
	}

	public function createEiPropConfigurator(): EiPropConfigurator {
		$eiPropConfigurator = parent::createEiPropConfigurator();
		IllegalStateException::assertTrue($eiPropConfigurator instanceof AdaptableEiPropConfigurator);
		$eiPropConfigurator->registerStandardEditDefinition($this->getStandardEditDefinition());
		return $eiPropConfigurator;
	}

	public function getGuiProp() {
		return $this;
	}

	public function getGuiPropFork() {
		return null;
	}

	public function buildGuiField(Eiu $eiu) {
		return new StatelessEditElement($this, $eiu);
	}

	/**
	 * @return bool
	 */
	public function isReadOnly(Eiu $eiu): bool {
		if (!WritableEiPropPrivilege::checkForWriteAccess($eiu->frame()->getEiFrame()->getEiExecution()
				->createEiPropAccess(EiPropPath::from($this)))) {
			return true;
		}

		if ($eiu->entry()->isDraft() || (!$eiu->entry()->isNew()
				&& $this->standardEditDefinition->isConstant())) {
			return true;
		}

		return $this->standardEditDefinition->isReadOnly();
	}

	public function isMandatory(Eiu $eiu): bool {
		return $this->standardEditDefinition->isMandatory();
	}

	public function createEiPropPrivilege(N2nContext $n2nContext): EiPropPrivilege {
		return new WritableEiPropPrivilege();
	}
}