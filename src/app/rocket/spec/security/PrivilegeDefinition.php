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

use rocket\ei\EiCommandPath;
use rocket\ei\EiPropPath;
use n2n\util\config\Attributes;
use n2n\web\dispatch\mag\MagCollection;
use n2n\util\config\AttributesException;

class PrivilegeDefinition {
	private $eiCommandPrivileges = array();
	
	public function isEmpty(): bool {
		return $this->eiCommandPrivileges;
	}
	
	public function checkEiCommandPathForPrivileges(EiCommandPath $eiCommandPath) {
		foreach ($this->eiCommandPrivileges as $privilegeEiCommandPathStr => $eiCommandPrivilege) {
			$privilegeEiCommandPath = EiCommandPath::create($privilegeEiCommandPathStr);
			
			if ($privilegeEiCommandPath->startsWith($eiCommandPath) 
					|| $eiCommandPath->startsWith($privilegeEiCommandPath)) {
				return true;
			}
		}
		
		return false;
	}
	
	public function putEiCommandPrivilege(EiCommandPath $commandPath, EiCommandPrivilege $eiCommandPrivilege) {
		$this->eiCommandPrivileges[(string) $commandPath] = $eiCommandPrivilege;
	}
	
	public function getEiCommandPrivileges(): array {
		return $this->eiCommandPrivileges;
	}
	
	private $eiPropPrivileges = array();
	
	public function getEiPropPrivileges(): array {
		return $this->eiPropPrivileges;
	}
	
	public function putEiPropPrivilege(EiPropPath $eiPropPath, EiPropPrivilege $eiPropPrivilege) {
		$this->eiPropPrivileges[(string) $eiPropPath] = $eiPropPrivilege;
	}
	
// 	public function getEiPropPrivilegeByEiPropPath(EiPropPath $eiPropPath): EiPropPrivilege {
// 		$eiPropPathStr = (string) $eiPropPath;
// 		if (isset($this->eiPropPrivileges[$eiPropPath])) {
// 			return $this->eiPropPrivileges[$eiPropPath];
// 		}
	
// 		throw new UnknownEiPropPrivilegeException();
// 	}
	
	public function createEiPropPrivilegeMagCollection(Attributes $attributes): MagCollection {
		$magCollection = new MagCollection();
		foreach ($this->eiPropPrivileges as $eiPropPathStr => $eiPropPrivilege) {
			$itemAttributes = null;
			try {
				$itemAttributes = new Attributes($attributes->getArray($eiPropPathStr, false));
			} catch (AttributesException $e) {
				$itemAttributes = new Attributes();
			}
				
			$magCollection->addMag($eiPropPrivilege->createMag($eiPropPathStr, $itemAttributes));
		}
		return $magCollection;
	}
	
	public function buildEiPropPrivilegeAttributes(MagCollection $magCollection) {
		$attributes = new Attributes();
		
		foreach ($this->eiPropPrivileges as $eiPropPathStr => $eiPropPrivilege) {
			if (!$magCollection->containsPropertyName($eiPropPathStr)) continue;
			
			$attributes->set($eiPropPathStr, $eiPropPrivilege->buildAttributes(
					$magCollection->getMagByPropertyName($eiPropPathStr))->toArray());
		}

		return $attributes;
	}
	
	public static function extractAttributesOfEiPropPrivilege(EiPropPath $eiPropPath, 
			Attributes $eiPropPrivilegeAttributes) {
		
		$eiPropPathStr = (string) $eiPropPath;
				
		if (!$eiPropPrivilegeAttributes->contains($eiPropPathStr)) return null;
		
		$attrs = $eiPropPrivilegeAttributes->get($eiPropPathStr);
		if (is_array($attrs)) return new Attributes($attrs);
		
		return null;
	}
}
