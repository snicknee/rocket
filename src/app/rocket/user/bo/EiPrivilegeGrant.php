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
namespace rocket\user\bo;

use n2n\reflection\ObjectAdapter;
use n2n\reflection\annotation\AnnoInit;
use n2n\util\StringUtils;
use n2n\persistence\orm\annotation\AnnoTable;
use n2n\persistence\orm\annotation\AnnoManyToOne;
use rocket\ei\manage\critmod\filter\data\FilterGroupData;
use n2n\util\config\Attributes;
use n2n\util\config\AttributesException;
use n2n\reflection\ArgUtils;
use rocket\ei\EiCommandPath;

class EiPrivilegeGrant extends ObjectAdapter {
	private static function _annos(AnnoInit $ai) {
		$ai->c(new AnnoTable('rocket_user_privileges_grant'));
		$ai->p('eiGrant', new AnnoManyToOne(EiGrant::getClass()));
	}
	
	private $id;
	private $eiGrant;
	private $eiCommandPrivilegeJson = '[]';
	private $eiPropPrivilegeJson = '[]';
	private $restricted = false;
	private $restrictionGroupJson = '[]';
	
	public function getEiGrant() {
		return $this->eiGrant;
	}

	public function setEiGrant(EiGrant $eiGrant) {
		$this->eiGrant = $eiGrant;
	}

	public function getEiCommandPathStrs() {
		return StringUtils::jsonDecode($this->eiCommandPrivilegeJson, true);
	}
	
	public function setEiCommandPathStrs(array $commnadPathStrs) {
		ArgUtils::valArray($commnadPathStrs, 'string');
		$this->eiCommandPrivilegeJson = StringUtils::jsonEncode($commnadPathStrs);
	}
	
	public function getEiCommandPaths() {
		$eiCommandPaths = array();
		foreach ($this->getEiCommandPathStrs() as $eiCommandPathStr) {
			$eiCommandPaths[$eiCommandPathStr] = EiCommandPath::create($eiCommandPathStr);
		}
		return $eiCommandPaths;
	}
	
	/**
	 * @param EiCommandPath $eiCommandPath
	 * @return boolean
	 */
	public function acceptsEiCommandPath(EiCommandPath $eiCommandPath) {
		foreach ($this->getEiCommandPaths() as $privilegeCommandPath) {
			if ($privilegeCommandPath->startsWith($eiCommandPath)) return true;
		}
		return false;
	}

	public function readEiPropPrivilegeAttributes(): Attributes {
		return new Attributes(StringUtils::jsonDecode($this->eiPropPrivilegeJson, true));
	}
	
	public function writeEiPropPrivilegeAttributes(Attributes $accessAttributes) {
		$this->eiPropPrivilegeJson = StringUtils::jsonEncode($accessAttributes->toArray());
	}
	
	public function isRestricted(): bool {
		return (bool) $this->restricted;
	}
	
	public function setRestricted(bool $restricted) {
		$this->restricted = $restricted;
	}
	
	public function readRestrictionFilterGroupData(): FilterGroupData {
		try {
			return FilterGroupData::create(new Attributes(StringUtils::jsonDecode($this->restrictionGroupJson, true)));
		} catch (AttributesException $e) {
			return new FilterGroupData();
		}
	}
	
	public function writeRestrictionFilterData(FilterGroupData $restrictionFilterGroupData) {
		$this->restrictionGroupJson = StringUtils::jsonEncode($restrictionFilterGroupData->toAttrs());
	}
}
