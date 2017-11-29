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
namespace rocket\spec\ei\component\field\impl\translation\model;

use rocket\spec\ei\manage\util\model\Eiu;
use rocket\spec\ei\component\field\impl\relation\model\ToManyEiField;

class TranslationEiField extends ToManyEiField {
	
	/**
	 * {@inheritDoc}
	 * @see \rocket\spec\ei\manage\mapping\EiField::copyEiField($eiObject)
	 */
	public function copyEiField(Eiu $eiu) {
		$copy = parent::copyEiField($eiu);
		
		if ($copy === null) return null;
		
		$value = $this->getValue();
		$valueCopy = $copy->getValue();

		foreach ($value as $key => $targetRelationEntry) {
			$valueCopy[$key] = $valueCopy[$key]->getEiObject()->getEiEntityObj()->getEntityObj()->setN2nLocale(
					$targetRelationEntry->getEiObject()->getEiEntityObj()->getEntityObj()->getN2nLocale());
		}
		
		return $copy;
	}

}