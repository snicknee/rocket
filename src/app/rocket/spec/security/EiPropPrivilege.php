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
namespace rocket\spec\security;

use n2n\util\config\Attributes;
use n2n\web\dispatch\mag\Mag;

interface EiPropPrivilege {
	
	/**
	 * @param string $propertyName
	 * @param Attributes $attributes
	 * @return \n2n\web\dispatch\mag\Mag
	 */
	public function createMag(string $propertyName, Attributes $attributes): Mag;
	
	/**
	 * @param Mag $mag
	 * @return \n2n\util\config\Attributes
	 */
	public function buildAttributes(Mag $mag): Attributes;	
}