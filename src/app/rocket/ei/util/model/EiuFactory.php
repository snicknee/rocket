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
namespace rocket\ei\util\model;

use rocket\ei\manage\EiFrame;
use n2n\core\container\N2nContext;
use rocket\ei\manage\ManageException;
use n2n\reflection\ArgUtils;
use rocket\ei\manage\EiObject;
use rocket\ei\manage\mapping\EiEntry;
use rocket\ei\manage\EiEntityObj;
use rocket\ei\manage\LiveEiObject;
use rocket\ei\manage\draft\Draft;
use rocket\ei\manage\DraftEiObject;
use rocket\ei\manage\ManageState;
use rocket\ei\manage\gui\EiEntryGui;
use rocket\ei\component\prop\EiProp;
use rocket\ei\EiPropPath;
use rocket\ei\EiType;
use rocket\ei\mask\EiMask;
use n2n\reflection\ReflectionUtils;
use rocket\ei\manage\gui\EiGui;
use rocket\ei\manage\gui\EiEntryGuiAssembler;
use rocket\ei\EiEngine;
use rocket\spec\Spec;
use rocket\ei\EiTypeExtension;
use rocket\ei\component\EiComponent;
use rocket\core\model\Rocket;

class EiuFactory {
	const EI_FRAME_TYPES = array(EiFrame::class, EiuFrame::class, N2nContext::class);
	const EI_ENTRY_TYPES = array(EiObject::class, EiEntry::class, EiEntityObj::class, Draft::class, 
			EiEntryGui::class, EiuEntry::class, EiuEntryGui::class);
	const EI_GUI_TYPES = array(EiGui::class, EiuGui::class, EiEntryGui::class, EiuEntryGui::class);
	const EI_ENTRY_GUI_TYPES = array(EiEntryGui::class, EiuEntryGui::class);
	const EI_TYPES = array(EiFrame::class, N2nContext::class, EiObject::class, EiEntry::class, EiEntityObj::class, 
			Draft::class, EiGui::class, EiuGui::class, EiEntryGui::class, EiEntryGui::class, EiProp::class, 
			EiPropPath::class, EiuFrame::class, EiuEntry::class, EiuEntryGui::class, EiuField::class, Eiu::class);
	const EI_FIELD_TYPES = array(EiProp::class, EiPropPath::class, EiuField::class);
	
	protected $n2nContext;
	protected $eiFrame;
	protected $eiObject;
	protected $eiEntry;
	protected $eiGui;
	protected $eiEntryGui;
	protected $eiEntryGuiAssembler;
	protected $eiPropPath;
	protected $eiEngine;
	protected $spec;
	protected $eiMask;
	
	protected $eiuContext;
	protected $eiuEngine;
	protected $eiuFrame;
	protected $eiuEntry;
	protected $eiuGui;
	protected $eiuEntryGui;
	protected $eiuEntryGuiAssembler;
	protected $eiuField;
	protected $eiuMask;
	protected $eiuProp;
	
	public function applyEiArgs(...$eiArgs) {
		$remainingEiArgs = array();
		
		foreach ($eiArgs as $key => $eiArg) {
			if ($eiArg instanceof N2nContext) {
				$this->n2nContext = $eiArg;
				continue;
			}
			
			if ($eiArg instanceof EiFrame) {
				$this->assignEiFrame($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiuFrame) {
				$this->assignEiuFrame($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiProp) {
				$this->eiPropPath = EiPropPath::from($eiArg);
				$this->assignEiMask($eiArg->getEiMask());
				continue;
			}
				
			if ($eiArg instanceof EiPropPath) {
				$this->eiPropPath = $eiArg;
				continue;
			}
			
			if ($eiArg instanceof EiEngine) {
				$this->assignEiEngine($eiArg);
				continue;
			}
			
			if ($eiArg instanceof Spec) {
				$this->spec = $eiArg;
				continue;
			}
			
			if ($eiArg instanceof EiGui) {
				$this->assignEiGui($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiEntryGui) {
				$this->assignEiEntryGui($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiEntryGuiAssembler) {
				$this->assignEiEntryGuiAssembler($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiMask) {
				$this->assignEiMask($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiComponent) {
				$this->assignEiMask($eiArg->getEiMask());
				continue;
			}
			
			if ($eiArg instanceof EiType) {
				$this->assignEiMask($eiArg->getEiMask());
				continue;
			}
			
			if ($eiArg instanceof EiTypeExtension) {
				$this->assignEiMask($eiArg->getEiMask());
				continue;
			}
			
			if ($eiArg instanceof EiObject) {
				$this->assignEiObject($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiEntry) {
				$this->assignEiEntry($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiEntityObj) {
				$this->assignEiObject(new LiveEiObject($eiObjectArg));
				continue;
			}
			
			if ($eiArg instanceof Draft) {
				$this->assignEiObject(new DraftEiObject($eiObjectArg));
				continue;
			}
			
			if ($eiArg instanceof EiuField) {
				$this->assignEiuField($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiuEntryGui) {
				$this->assignEiuEntryGui($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiuGui) {
				$this->assignEiuGui($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiuEntry) {
				$this->assignEiuEntry($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiuProp) {
				$this->assignEiuProp($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiuEngine) {
				$this->assignEiuEngine($eiArg);
				continue;
			}
			
			if ($eiArg instanceof EiuContext) {
				$this->assignEiuContext($eiuContext);
				continue;
			}
			
			if ($eiArg instanceof EiuCtrl) {
				$eiArg = $eiArg->eiu();
			}
			
			if ($eiArg instanceof Eiu) {
				$eiuFactory = $eiArg->getEiuFactory();
				
				if ($eiuFactory->n2nContext !== null) {
					$this->n2nContext = $eiuFactory->n2nContext;
				}
				if ($eiuFactory->eiFrame !== null) {
					$this->eiFrame = $eiuFactory->eiFrame;
				}
				if ($eiuFactory->eiObject !== null) {
					$this->eiObject = $eiuFactory->eiObject;
				}
				if ($eiuFactory->eiEntry !== null) {
					$this->eiEntry = $eiuFactory->eiEntry;
				}
				if ($eiuFactory->eiGui !== null) {
					$this->eiGui = $eiuFactory->eiGui;
				}
				if ($eiuFactory->eiEntryGui !== null) {
					$this->eiEntryGui = $eiuFactory->eiEntryGui;
				}
				if ($eiuFactory->eiEntryGuiAssembler !== null) {
					$this->eiEntryGuiAssembler = $eiuFactory->eiEntryGuiAssembler;
				}
				if ($eiuFactory->eiPropPath !== null) {
					$this->eiPropPath = $eiuFactory->eiPropPath;
				}
				if ($eiuFactory->eiEngine !== null) {
					$this->eiEngine = $eiuFactory->eiEngine;
				}
				if ($eiuFactory->spec !== null) {
					$this->spec = $eiuFactory->spec;
				}
				if ($eiuFactory->eiMask !== null) {
					$this->eiMask = $eiuFactory->eiMask;
				}
				
				
				if ($eiuFactory->eiuEngine !== null) {
					$this->eiuEngine = $eiuFactory->eiuEngine;
				}
				if ($eiuFactory->eiuFrame !== null) {
					$this->eiuFrame = $eiuFactory->eiuFrame;
				}
				if ($eiuFactory->eiuEntry !== null) {
					$this->eiuEntry = $eiuFactory->eiuEntry;
				}
				if ($eiuFactory->eiuGui !== null) {
					$this->eiuGui = $eiuFactory->eiuGui;
				}
				if ($eiuFactory->eiuEntryGui !== null) {
					$this->eiuEntryGui = $eiuFactory->eiuEntryGui;
				}
				if ($eiuFactory->eiuEntryGuiAssembler !== null) {
					$this->eiuEntryGuiAssembler = $eiuFactory->eiuEntryGuiAssembler;
				}
				if ($eiuFactory->eiuField !== null) {
					$this->eiuField = $eiuFactory->eiuField;
				}
				if ($eiuFactory->eiuProp !== null) {
					$this->eiuProp = $eiuFactory->eiuProp;
				}
				if ($eiuFactory->eiuContext !== null) {
					$this->eiuContext = $eiuFactory->eiuContext;
				}
				if ($eiuFactory->eiuMask !== null) {
					$this->eiuMask = $eiuFactory->eiuMask;
				}
				continue;
			}
			
			$remainingEiArgs[$key  + 1] = $eiArg;
		}
		
		if (empty($remainingEiArgs)) return;
		
		$eiType = null;
		$eiObjectTypes = self::EI_TYPES;
		if ($this->eiFrame !== null) {
			$eiType = $this->eiFrame->getContextEiEngine()->getEiMask()->getEiType();
			$eiObjectTypes[] = $eiType->getEntityModel()->getClass()->getName();
		}
		
		foreach ($remainingEiArgs as $argNo => $eiArg) {
			if ($eiType !== null) {
				try {
					$this->eiObject = LiveEiObject::create($eiType, $eiArg);
					continue;
				} catch (\InvalidArgumentException $e) {
					return null;
				}
			}
			
			ArgUtils::valType($eiArg, $eiObjectTypes, true, 'eiArg#' . $argNo);
		}	
	}
	
	/**
	 * @param EiuFrame $eiuFrame
	 */
	private function assignEiuFrame($eiuFrame) {
		if ($this->eiuFrame === $eiuFrame) {
			return;
		}
		
		$this->assignEiFrame($eiuFrame->getEiFrame());
		$this->eiuFrame = $eiuFrame;
	}
	
	/**
	 * @param EiFrame $eiFrame
	 */
	private function assignEiFrame($eiFrame) {
		if ($this->eiFrame === $eiFrame) {
			return;
		}
		
		$this->eiuFrame = null;
		$this->eiFrame = $eiFrame;
		$this->n2nContext = $eiFrame->getN2nContext();
		
		$this->assignEiEngine($eiFrame->getContextEiEngine());
	}
	
	/**
	 * @param EiuEngine $eiuEngine
	 */
	private function assignEiuEngine($eiuEngine) {
		if ($this->eiuEngine === $eiuEngine) {
			return;
		}
		
		$this->assignEiEngine($eiuEngine->getEiEngine());
		$this->eiuEngine = $eiuEngine;
	}
	
	/**
	 * @param EiEngine $eiEngine
	 */
	private function assignEiEngine($eiEngine) {
		if ($this->eiEngine === $eiEngine) {
			return;
		}
		
		$this->eiuEngine = null;
		$this->eiEngine = $eiEngine;
		
		$this->assignEiMask($eiEngine->getEiMask());
	}
	
	/**
	 * @param EiuProp $eiuProp
	 */
	private function assignEiuProp($eiuProp) {
		if ($this->eiuProp === $eiuProp) {
			return;
		}
		
		$this->assignEiuEngine($eiuProp->getEiuEngine());
		$this->eiuProp = $eiuProp;
	}
	
	
	/**
	 * @param EiuMask $eiuMask
	 */
	private function assignEiuMask($eiuMask) {
		if ($this->eiuMask === $eiuMask) {
			return;
		}
		
		$this->assignEiMask($eiuMask->getEiMask());
		$this->eiuMask = $eiuMask;
	}
	
	/**
	 * @param EiMask $eiMask
	 */
	private function assignEiMask($eiMask) {
		if ($this->eiMask === $eiMask) {
			return;
		}
		
		$this->eiuMask = null;
		$this->eiMask = $eiMask;
		
		if ($eiMask->hasEiEngine()) {
			$this->assignEiEngine($eiMask->getEiEngine());
		}
	}
	
	/**
	 * @param EiuGui $eiuGui
	 */
	private function assignEiuGui($eiuGui) {
		if ($this->eiuGui === $eiuGui) {
			return;
		}
		
		$this->assignEiGui($eiuGui->getEiGui());
		$this->assignEiuFrame($eiuGui->getEiuFrame());
		$this->eiuGui = $eiuGui;
	}
	
	/**
	 * @param EiGui $eiGui
	 */
	private function assignEiGui($eiGui) {
		if ($this->eiGui === $eiGui) {
			return;
		}
		
		$this->eiuGui = null;
		$this->eiGui = $eiGui;
		
		$this->assignEiFrame($eiGui->getEiFrame());
		
		$eiEntryGuis = $eiGui->getEiEntryGuis();
		if (count($eiEntryGuis) == 1) {
			$this->assignEiEntryGui(current($eiEntryGuis));
		}
	}
	
	/**
	 * @param EiuEntryGui $eiuEntryGui
	 */
	private function assignEiuEntryGui($eiuEntryGui) {
		if ($this->eiuEntryGui === $eiuEntryGui) {
			return;
		}
		
		$this->assignEiuGui($eiuEntryGui->getEiuGui());
		$this->assignEiEntryGui($eiuEntryGui->getEiEntryGui());
		$this->eiuEntryGui = $eiuEntryGui;
	}
	
	/**
	 * @param EiEntryGui $eiEntryGui
	 */
	private function assignEiEntryGui($eiEntryGui) {
		if ($this->eiEntryGui === $eiEntryGui) {
			return;
		}
		
		$this->eiuEntryGui = null;
		$this->eiEntryGui = $eiEntryGui;
		
		$this->assignEiGui($eiEntryGui->getEiGui());
		$this->assignEiEntry($eiEntryGui->getEiEntry());
	}
	
	/**
	 * @param EiuEntryGuiAssembler $eiuEntryGuiAssembler
	 */
	private function assignEiuGuiAssembler($eiuEntryGuiAssembler) {
		if ($this->eiuEntryGuiAssembler === $eiuEntryGuiAssembler) {
			return;
		}
		
		$this->assignEiEntryGuiAssembler($eiuEntryGuiAssembler->getEiEntryGuiAssembler());
// 		$this->assignEiuEntryGui($eiuEntryGuiAssembler->getEiuEntryGui());
		$this->eiuEntryGuiAssembler = $eiuEntryGuiAssembler;
	}
	
	/**
	 * @param EiEntryGuiAssembler $eiEntryGuiAssembler
	 */
	private function assignEiEntryGuiAssembler($eiEntryGuiAssembler) {
		if ($this->eiEntryGuiAssembler === $eiEntryGuiAssembler) {
			return;
		}
		
		$this->eiuEntryGuiAssembler = null;
		$this->eiEntryGuiAssembler = $eiEntryGuiAssembler;
		
		$this->assignEiEntryGui($eiEntryGuiAssembler->getEiEntryGui());
	}
	
	/**
	 * @param EiuField $eiuField
	 */
	private function assignEiuField($eiuField) {
		if ($this->eiuField === $eiuField) {
			return;
		}
		
		$this->assignEiuEntry($eiuField->getEiuEntry());
		
		$this->eiuField = $eiuField;
		$this->eiPropPath = $eiuField->getEiPropPath();
	}
	
	/**
	 * @param EiuEntry $eiuEntry
	 */
	private function assignEiuEntry($eiuEntry) {
		if ($this->eiuEntry === $eiuEntry) {
			return;
		}
		
		if (null !== ($eiEntry = $eiuEntry->getEiEntry(false))) {
			$this->assignEiEntry($eiEntry);
		} else {
			$this->assignEiObject($eiuEntry->getEiObject());
		}
		
		$this->eiuEntry = $eiuEntry;
	}
	
	/**
	 * @param EiEntry $eiObject
	 */
	private function assignEiEntry($eiEntry) {
		if ($this->eiEntry === $eiEntry) {
			return;
		}
		
		$this->eiuEntry = null;
		$this->eiEntry = $eiEntry;
		
		$this->assignEiObject($eiEntry->getEiObject());
	}
	
	/**
	 * @param EiObject $eiObject
	 */
	private function assignEiObject($eiObject) {
		if ($this->eiObject === $eiObject) {
			return;
		}
		
		$this->eiuEntry = null;
		$this->eiObject = $eiObject;
	}
	
// 	public function getEiFrame(bool $required) {
// 		if (!$required || $this->eiFrame !== null) {
// 			return $this->eiEntryGui;
// 		}
	
// 		throw new EiuPerimeterException(
// 				'Could not determine EiuFrame because non of the following types were provided as eiArgs: '
// 						. implode(', ', self::EI_FRAME_TYPES));
// 	}
	
	/**
	 * @return NULL|\rocket\ei\manage\mapping\EiEntry
	 */
	public function getEiEntry() {
		return $this->eiEntry;
	}
	
	/**
	 * 
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return \rocket\ei\manage\EiObject|NULL
	 */
	public function getEiObject(bool $required) {
		if (!$required || $this->eiObject !== null) {
			return $this->eiObject;
		}
	
		throw new EiuPerimeterException(
				'Could not determine EiObject because non of the following types were provided as eiArgs: '
						. implode(', ', self::EI_ENTRY_TYPES));
	}
	
	/**
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return \rocket\ei\manage\gui\EiGui
	 */
	public function getEiGui(bool $required) {
		if (!$required || $this->eiGui !== null) {
			return $this->eiGui;
		}
	
		throw new EiuPerimeterException(
				'Could not determine EiGui because non of the following types were provided as eiArgs: '
						. implode(', ', self::EI_GUI_TYPES));
	}
	
	/**
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return \rocket\ei\manage\gui\EiEntryGui
	 */
	public function getEiEntryGui(bool $required) {
		if (!$required || $this->eiEntryGui !== null) {
			return $this->eiEntryGui;
		}
		
		throw new EiuPerimeterException(
				'Could not determine EiEntryGui because non of the following types were provided as eiArgs: '
				. implode(', ', self::EI_ENTRY_GUI_TYPES));
	}
	
	/**
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return \rocket\ei\manage\gui\EiEntryGuiAssembler
	 */
	public function getEiEntryGuiAssembler(bool $required) {
		if (!$required || $this->eiEntryGuiAssembler !== null) {
			return $this->eiEntryGuiAssembler;
		}
		
		throw new EiuPerimeterException('Could not determine EiEntryGuiAssembler.');
	}
		
	public function getEiPropPath(bool $required) {
		if (!$required || $this->eiPropPath !== null) {
			return $this->eiPropPath;
		}
	
		throw new EiuPerimeterException(
				'Could not create EiuField because non of the following types were provided as eiArgs: '
						. implode(', ', self::EI_FIELD_TYPES));
	}
	
	/**
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return EiEngine
	 */
	public function getEiEngine(bool $required) {
		if (!$required || $this->eiEngine !== null) {
			return $this->eiEngine;
		}
		
		throw new EiuPerimeterException('Could not determine EiEngine.');
	}
	
	/**
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return Spec
	 */
	public function getSpec(bool $required) {
		if ($this->spec !== null) {
			return $this->spec;
		}
		
		if ($this->n2nContext !== null) {
			return $this->n2nContext->lookup(Rocket::class)->getSpec();
		}
		
		throw new EiuPerimeterException('Could not determine Spec.');
	}
	
	/**
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return N2nContext
	 */
	public function getN2nContext(bool $required) {
		if (!$required || $this->n2nContext !== null) {
			return $this->n2nContext;
		}
		
		throw new EiuPerimeterException('Could not determine N2nContext.');
	}
	
	public function getEiuContext(bool $required) {
		if ($this->eiuContext !== null) {
			return $this->eiuContext;
		}
		
		$spec = null;
		try {
			$spec = $this->getSpec($required);
		} catch (EiuPerimeterException $e) {
			throw new EiuPerimeterException('Could not determine EiuContext.', 0, $e);
		}
		
		if ($spec === null) return null;
		
		return $this->eiuContext = new EiuContext($spec, $this);
	}
	
	/**
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return EiuEngine
	 */
	public function getEiuEngine(bool $required) {
		if ($this->eiuEngine !== null) {
			return $this->eiuEngine;
		}
		
		if ($this->eiEngine === null && $this->eiMask !== null && ($required || $this->eiMask->hasEiEngine())) {
			$this->eiEngine = $this->eiMask->getEiEngine();
		}
		
		if ($this->eiEngine !== null) {
			return $this->eiuEngine = new EiuEngine($this->eiEngine, $this->eiuMask, $this);
		}
		
		if (!$required) return null;
		
		throw new EiuPerimeterException('Can not create EiuEngine.');
	}
	
	/**
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return EiuMask
	 */
	public function getEiuMask(bool $required) {
		if ($this->eiuMask !== null) {
			return $this->eiuMask;
		}
		
		if ($this->eiMask !== null) {
			return $this->eiuMask = new EiuMask($this->eiMask, $this->eiuEngine, $this);
		}
		
		if (!$required) return null;
		
		throw new EiuPerimeterException('EiuMask not avaialble');
	}
	/**
	 * @throws EiuPerimeterException
	 * @return \rocket\ei\util\model\EiuFrame
	 */
	public function getEiuFrame(bool $required) {
		if ($this->eiuFrame !== null) {
			return $this->eiuFrame;
		}
		
		if ($this->eiFrame !== null) {
			return $this->eiuFrame = new EiuFrame($this->eiFrame, $this);
		} 
		
		if ($this->eiuEntry !== null) {
			$this->eiuFrame = $this->eiuEntry->getEiuFrame(false);
			if ($this->eiuFrame !== null) {
				return $this->eiuFrame;
			}
		}
		
		if ($this->n2nContext !== null) {
			try {
				return new EiuFrame($this->n2nContext->lookup(ManageState::class)->peakEiFrame());
			} catch (ManageException $e) {
				if (!$required) return null;
				
				throw new EiuPerimeterException('Can not create EiuFrame in invalid context.', 0, $e);
			}
		}
		
		if (!$required) return null;
		
		throw new EiuPerimeterException(
				'Can not create EiuFrame because non of the following types were provided as eiArgs: ' 
						. implode(', ', self::EI_FRAME_TYPES));
	}
	
	public function getEiuEntry(bool $required) {
		if ($this->eiuEntry !== null) {
			return $this->eiuEntry;
		}
		
		$eiuFrame = $this->getEiuFrame(false);
		
		if ($eiuFrame !== null) {
			if ($this->eiEntry !== null) {
				return $this->eiuEntry = $eiuFrame->entry($this->eiEntry, true);
			}
			
			if ($this->eiObject !== null) {
				return $this->eiuEntry = $eiuFrame->entry($this->eiObject, true);
			}
		} else {
			if ($this->eiEntry !== null) {
				return $this->eiuEntry = new EiuEntry($this->eiObject, $this->eiEntry, $this->eiuFrame, $this);
			}
				
			if ($this->eiObject !== null) {
				return $this->eiuEntry = new EiuEntry($this->eiObject);
			}
		}
		
		if (!$required) return null;
		
		throw new EiuPerimeterException(
				'Can not create EiuEntry because non of the following types were provided as eiArgs: '
						. implode(', ', self::EI_ENTRY_TYPES));
	}
	

	/**
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return \rocket\ei\util\model\EiuEntryGui
	 */
	public function getEiuEntryGui(bool $required) {
		if ($this->eiuEntryGui !== null) {
			return $this->eiuEntryGui;
		}
		
		if ($this->eiEntryGui !== null) {
			return $this->eiuEntryGui = new EiuEntryGui($this->eiEntryGui, $this->getEiuGui(true));
		}
		
		if (!$required) return null;
		
		throw new EiuPerimeterException(
				'Can not create EiuEntryGui because non of the following types were provided as eiArgs: '
						. implode(', ', self::EI_ENTRY_GUI_TYPES));
	}
	
	/**
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return \rocket\ei\util\model\EiuGui
	 */
	public function getEiuGui(bool $required) {
		if ($this->eiuGui !== null) {
			return $this->eiuGui;
		}
	
		if ($this->eiGui !== null) {
			return $this->eiuGui = new EiuGui($this->eiGui, $this->getEiuFrame(true));
		}
	
		if (!$required) return null;
	
		throw new EiuPerimeterException(
				'Can not create EiuGui because non of the following types were provided as eiArgs: '
						. implode(', ', self::EI_GUI_TYPES));
	}
	
	public function getEiuField(bool $required) {
		if ($this->eiuField !== null) {
			return $this->eiuField;
		}
	
		$eiuEntry = $this->getEiuEntry(false);
		if ($eiuEntry !== null) {
			if ($this->eiPropPath !== null) {
				return $this->eiuField = $eiuEntry->field($this->eiPropPath);
			}
		} else {
			if ($this->eiPropPath !== null) {
				return $this->eiuField = new EiuField($this->eiPropPath);
			}
		}
	
		if (!$required) return null;
	
		throw new EiuPerimeterException(
				'Can not create EiuField because non of the following types were provided as eiArgs: '
						. implode(', ', self::EI_FIELD_TYPES));
	}
	
	public static function buildEiuFrameFormEiArg($eiArg, string $argName = null, bool $required = false) {
		if ($eiArg instanceof EiuFrame) {
			return $eiArg;
		}
		
		if ($eiArg === null && !$required) {
			return null;
		}
		
		if ($eiArg instanceof EiFrame) {
			return new EiuFrame($eiArg);
		}
		
		if ($eiArg instanceof N2nContext) {
			try {
				return new EiuFrame($eiArg->lookup(ManageState::class)->preakEiFrame());
			} catch (ManageException $e) {
				throw new EiuPerimeterException('Can not create EiuFrame in invalid context.', 0, $e);
			}
		}
		
		if ($eiArg instanceof EiuCtrl) {
			return $eiArg->frame();
		}
		
		if ($eiArg instanceof EiuEntry) {
			return $eiArg->getEiuFrame($required);
		}
		
		if ($eiArg instanceof Eiu) {
			return $eiArg->frame();
		}
		
		ArgUtils::valType($eiArg, self::EI_FRAME_TYPES, !$required, $argName);
	}
	
	/**
	 * @param mixed $eiArg
	 * @param EiuFrame $eiuFrame
	 * @param string $argName
	 * @param bool $required
	 * @return \rocket\ei\util\model\EiuEntry|NULL
	 */
	public static function buildEiuEntryFromEiArg($eiArg, EiuFrame $eiuFrame = null, string $argName = null, bool $required = false) {
		if ($eiArg instanceof EiuEntry) {
			return $eiArg;
		}
		
		if ($eiArg !== null) {
			$eiEntry = null;
			$eiObject = self::determineEiObject($eiArg, $eiEntry);
			return new EiuEntry($eiObject, $eiEntry, $eiuFrame);
		}
			
		if (!$required) {
			return null;
		}
		
		ArgUtils::valType($eiArg, self::EI_ENTRY_TYPES);
	}
	
	/**
	 * @param mixed $eiObjectObj
	 * @return \rocket\ei\manage\EiObject|null
	 */
	public static function determineEiObject($eiObjectArg, &$eiEntry = null, &$eiEntryGui = null) {
		if ($eiObjectArg instanceof EiObject) {
			return $eiObjectArg;
		} 
			
		if ($eiObjectArg instanceof EiEntry) {
			$eiEntry = $eiObjectArg;
			return $eiObjectArg->getEiObject();
		}
		
		if ($eiObjectArg instanceof EiEntityObj) {
			return new LiveEiObject($eiObjectArg);
		}
		
		if ($eiObjectArg instanceof Draft) {
			return new DraftEiObject($eiObjectArg);
		}
		
		if ($eiObjectArg instanceof EiuEntry) {
			$eiEntry = $eiObjectArg->getEiEntry(false);
			return $eiObjectArg->getEiObject();
		}
		
		if ($eiObjectArg instanceof EiuEntryGui && null !== ($eiuEntry = $eiObjectArg->getEiuEntry(false))) {
			$eiEntry = $eiuEntry->getEiEntry(false);
			$eiEntryGui = $eiObjectArg->getEiEntryGui();
			return $eiuEntry->getEiObject();
		}
		
		if ($eiObjectArg instanceof Eiu && null !== ($eiuEntry = $eiObjectArg->entry(false))) {
			return $eiuEntry->getEiObject();
		}
		
		return null;
// 		if (!$required) return null;
		
// 		throw new EiuPerimeterException('Can not determine EiObject of passed argument type ' 
// 				. ReflectionUtils::getTypeInfo($eiObjectArg) . '. Following types are allowed: '
// 				. implode(', ', array_merge(self::EI_FRAME_TYPES, self::EI_ENTRY_TYPES)));
	}
	
	/**
	 * 
	 * @param mixed $eiTypeObj
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return \rocket\ei\EiType|NULL
	 */
	public static function determineEiType($eiTypeArg, bool $required = false) {
		if (null !== ($eiObject = self::determineEiObject($eiTypeArg))) {
			return $eiObject->getEiEntityObj()->getEiType();
		}
		
		if ($eiTypeArg instanceof EiType) {
			return $eiTypeArg;
		}
		
		if ($eiTypeArg instanceof EiMask) {
			return $eiTypeArg->getEiEngine()->getEiMask()->getEiType();
		}
		
		if ($eiTypeArg instanceof EiFrame) {
			return $eiTypeArg->getEiEngine()->getEiMask()->getEiType();
		}
		
		if ($eiTypeArg instanceof Eiu && $eiuFrame = $eiTypeArg->frame(false)) {
			return $eiuFrame->getContextEiType();
		}
		
		if ($eiTypeArg instanceof EiuFrame) {
			return $eiTypeArg->getEiType();
		}
		
		if ($eiTypeArg instanceof EiuEntry && null !== ($eiuFrame = $eiTypeArg->getEiuFrame(false))) {
			return $eiuFrame->getContextEiType();
		}
		
		if (!$required) return null;
		
		throw new EiuPerimeterException('Can not determine EiType of passed argument type ' 
				. ReflectionUtils::getTypeInfo($eiTypeArg) . '. Following types are allowed: '
				. implode(', ', array_merge(self::EI_FRAME_TYPES, EI_ENTRY_TYPES)));
	}
	
	public static function buildEiTypeFromEiArg($eiTypeArg, string $argName = null, bool $required = true) {
		if ($eiTypeArg === null && !$required) {
			return null;
		}
		
		if (null !== ($eiType = self::determineEiType($eiTypeArg))) {
			return $eiType;
		}
		
		throw new EiuPerimeterException('Can not determine EiType of passed argument ' . $argName 
				. '. Following types are allowed: '
				. implode(', ', array_merge(self::EI_FRAME_TYPES, self::EI_ENTRY_TYPES)) . '; '
				. ReflectionUtils::getTypeInfo($eiTypeArg) . ' given.');
	}
	
	/**
	 * @param mixed $eiEntryArg
	 * @param string $argName
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return \rocket\ei\manage\mapping\EiEntry
	 */
	public static function buildEiEntryFromEiArg($eiEntryArg, string $argName = null, bool $required = true) {
		if ($eiEntryArg instanceof EiEntry) {
			return $eiEntryArg;
		}
		
		if ($eiEntryArg instanceof EiuEntry) {
			return $eiEntryArg->getEiEntry();
		}
		
		throw new EiuPerimeterException('Can not determine EiEntry of passed argument ' . $argName
				. '. Following types are allowed: '
				. implode(', ', array_merge(self::EI_ENTRY_TYPES)) . '; '
				. ReflectionUtils::getTypeInfo($eiEntryArg) . ' given.');
	}
	
	/**
	 * 
	 * @param mixed $eiEntryGuiArg
	 * @param string $argName
	 * @param bool $required
	 * @throws EiuPerimeterException
	 * @return \rocket\ei\manage\gui\EiEntryGui
	 */
	public static function buildEiEntryGuiFromEiArg($eiEntryGuiArg, string $argName = null, bool $required = true) {
		if ($eiEntryGuiArg instanceof EiEntryGui) {
			return $eiEntryGuiArg;
		}
		
		if ($eiEntryGuiArg instanceof EiuEntryGui) {
			return $eiEntryGuiArg->getEiEntryGui();
		}
		
		if ($eiEntryGuiArg instanceof EiuGui) {
			$eiEntryGuiArg = $eiEntryGuiArg->getEiGui();
		}
		
		if ($eiEntryGuiArg instanceof EiGui) {
			$eiEntryGuis = $eiEntryGuiArg->getEiEntryGuis();
			if (1 == count($eiEntryGuiArg)) {
				return current($eiEntryGuis);
			}
			
			throw new EiuPerimeterException('Can not determine EiEntryGui of passed EiGui ' . $argName);
		}
		
		throw new EiuPerimeterException('Can not determine EiEntryGui of passed argument ' . $argName
				. '. Following types are allowed: '
				. implode(', ', array_merge(self::EI_ENTRY_GUI_TYPES)) . '; '
				. ReflectionUtils::getTypeInfo($eiEntryGuiArg) . ' given.');
	}
	
	public static function buildEiGuiFromEiArg($eiGuiArg, string $argName = null, bool $required = true) {
		if ($eiGuiArg instanceof EiGui) {
			return $eiGuiArg;
		}
	
		if ($eiGuiArg instanceof EiuGui) {
			return $eiGuiArg->getEiGui();
		}
		
		if ($eiGuiArg instanceof EiEntryGui) {
			return $eiGuiArg->getEiGui();
		}
	
		if ($eiGuiArg instanceof EiuEntryGui) {
			return $eiGuiArg->getEiGui();
		}
		
		if ($eiGuiArg instanceof Eiu && null !== ($eiuGui = $eiGuiArg->gui(false))) {
			return $eiuGui->getEiGui();
		}
	
		throw new EiuPerimeterException('Can not determine EiGui of passed argument ' . $argName
				. '. Following types are allowed: '
				. implode(', ', array_merge(self::EI_GUI_TYPES)) . '; '
				. ReflectionUtils::getTypeInfo($eiGuiArg) . ' given.');
	}
	
	public static function buildEiObjectFromEiArg($eiObjectObj, string $argName = null, EiType $eiType = null, 
			bool $required = true, &$eiEntry = null, &$eiGuiArg = null) {
		if (!$required && $eiObjectObj === null) {
			return null;
		}
		
		$eiEntryGui = null;
		if (null !== ($eiObject = self::determineEiObject($eiObjectObj, $eiEntry, $eiEntryGui))) {
			return $eiObject;
		}
		
		$eiObjectTypes = self::EI_ENTRY_TYPES;
		
		if ($eiType !== null) {
			$eiObjectTypes[] = $eiType->getEntityModel()->getClass()->getName();
			try {
				return LiveEiObject::create($eiType, $eiObjectObj);
			} catch (\InvalidArgumentException $e) {
				return null;
			}
		}
		
		ArgUtils::valType($eiObjectObj, $eiObjectTypes, !$required, $argName);
	}
}
