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
namespace rocket\spec\ei\component\field\impl\relation\model;

use rocket\spec\ei\manage\EiFrame;
use rocket\spec\ei\manage\gui\Editable;
use n2n\util\ex\IllegalStateException;
use n2n\impl\web\ui\view\html\HtmlView;
use rocket\spec\ei\manage\gui\GuiField;
use rocket\core\model\Rocket;
use rocket\spec\ei\component\field\EiProp;
use rocket\spec\ei\manage\util\model\Eiu;
use rocket\spec\ei\manage\util\model\EiuFrame;

class ToManySelectGuiField implements GuiField {
	private $eiProp;
	private $eiu;
	private $targetEiFrame;
	private $editable;
	private $toOneMag;
	
	public function __construct(EiProp $eiProp, Eiu $eiu, EiFrame $targetEiFrame, 
			Editable $editable = null) {
		$this->eiProp = $eiProp;
		$this->eiu = $eiu;
		$this->targetEiFrame = $targetEiFrame;
		$this->editable = $editable;
	}
	
	public function isReadOnly(): bool {
		return $this->editable === null;
	}
	
	public function getGroupType() {
		return null;
	}
	
	/**
	 * @return string
	 */
	public function getUiOutputLabel(): string {
		return $this->eiProp->getLabelLstr();
	}
	
	/**
	 * @return array
	 */
	public function getOutputHtmlContainerAttrs(): array {
// 		if ($this->eiu->entryGui()->isBulky()) {
// 			return array('class' => 'rocket-block');
// 		}
		
		return array();
	}
	
	public function createOutputUiComponent(HtmlView $view) {
		if ($this->eiu->entry()->getEiEntry()->isNew()) {
			return null;
		}
		
		$criteria = $this->targetEiFrame->createCriteria('e');
		$criteria->select('COUNT(e)');
		$num = $criteria->toQuery()->fetchSingle();

		$targetEiUtils = new EiuFrame($this->targetEiFrame);
		if ($num == 1) {
			$label = $num . ' ' . $targetEiUtils->getGenericLabel();
		} else {
			$label = $num . ' ' . $targetEiUtils->getGenericPluralLabel();
		}

		if (null !== ($relation = $this->eiu->frame()->getEiFrame()
				->getEiRelation($this->eiProp->getId()))) {
			return $this->createUiLink($relation->getEiFrame(), $label, $view);
		}

		return $this->createUiLink($this->targetEiFrame, $label, $view);
	}

	private function createUiLink(EiFrame $targetEiFrame, $label, HtmlView $view) {
		$html = $view->getHtmlBuilder();

		if (!$targetEiFrame->isOverviewUrlAvailable()) return $html->getEsc($label);

		return $html->getLink($targetEiFrame->getOverviewUrl($view->getHttpContext()), $label, array('data-jhtml' => 'true'));
	}
	
	
	/**
	 * {@inheritDoc}
	 * @see \rocket\spec\ei\manage\gui\GuiField::createEditable()
	 */
	public function getEditable(): Editable {
		if ($this->editable !== null) {
			return $this->editable;
		}
		
		throw new IllegalStateException('GuiField read only.');
	}
}