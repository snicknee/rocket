namespace Rocket.Display {

	export class StructureElement {
		private jqElem: JQuery;
		private onShowCallbacks: Array<(se: StructureElement) => any> = [];
		private onHideCallbacks: Array<(se: StructureElement) => any> = [];
		private toolbar: Toolbar = null;
		
		constructor(jqElem: JQuery) {
			this.jqElem = jqElem;
			
//			jqElem.addClass("rocket-structure-element");
			jqElem.data("rocketStructureElement", this);
			
			this.valClasses();
		}
		
		private valClasses() {
			if (this.isItem() || this.isGroup()) {
				this.jqElem.removeClass("rocket-structure-element");
			} else {
				this.jqElem.addClass("rocket-structure-element");
			}
		}
		
		get jQuery(): JQuery {
			return this.jqElem;
		}
		
		public setGroup(group: boolean) {
			if (!group) {
				this.jqElem.removeClass("rocket-group");
			} else {
				this.jqElem.addClass("rocket-group");
			}
			
			this.valClasses();
		}
		
		public isGroup(): boolean {
			return this.jqElem.hasClass("rocket-group");
		}
		
		public setPanel(panel: boolean) {
			if (!panel) {
				this.jqElem.removeClass("rocket-panel");
			} else {
				this.jqElem.addClass("rocket-panel");
			}
			
			this.valClasses();
		}
		
		public isPanel(): boolean {
			return this.jqElem.hasClass("rocket-panel");
		}
		
		public setItem(field: boolean) {
			if (!field) {
				this.jqElem.removeClass("rocket-item");
			} else {
				this.jqElem.addClass("rocket-item");
			}
			
			this.valClasses();
		}
		
		public isItem(): boolean {
			return this.jqElem.hasClass("rocket-field");
		}
		
		public getToolbar(): Toolbar {
			if (this.toolbar !== null) {
				return this.toolbar;
			}
			
//			if (!this.isGroup()) {
//				return null;
//			}
			
			let toolbarJq = this.jqElem.find(".rocket-group-toolbar:first")
					.filter((index, elem) => {
						return this === StructureElement.of($(elem));
					});
			if (toolbarJq.length == 0) {
				toolbarJq = $("<div />", { "class": "rocket-group-toolbar" });
				this.jqElem.prepend(toolbarJq);
			}
			
			return this.toolbar = new Toolbar(toolbarJq);
		}
		
		public getTitle() {
			return this.jqElem.children("label:first").text();
		}
		
		public getParent(): StructureElement {
			return StructureElement.of(this.jqElem.parent());
		}
		
		public isVisible() {
			return this.jqElem.is(":visible");
		}
		
		public show(includeParents: boolean = false) {
			for (var i in this.onShowCallbacks) {
				this.onShowCallbacks[i](this);
			}
			
			this.jqElem.show();
			
			var parent;
			if (includeParents && null !== (parent = this.getParent())) {
				parent.show(true)
			}
		}
		
		public hide() {
			for (var i in this.onHideCallbacks) {
				this.onHideCallbacks[i](this);
			}
			
			this.jqElem.hide();
		}
		
//		public addChild(structureElement: StructureElement) {
//			var that = this;
//			structureElement.onShow(function () {
//				that.show();
//			});
//		}
		
		public onShow(callback: (group: StructureElement) => any) {
			this.onShowCallbacks.push(callback);
		}
		
		public onHide(callback: (group: StructureElement) => any) {
			this.onHideCallbacks.push(callback);
		}
		
		public scrollTo() {
			var top = this.jqElem.offset().top;
			var maxOffset = top - 50;
			
			var height = this.jqElem.outerHeight();
			var margin = $(window).height() - height;
			
			var offset = top - (margin / 2);
			
			if (maxOffset < offset) {
				offset = maxOffset;
			}
			
			$("html, body").animate({
		    	"scrollTop": offset
		    }, 250);
		}
		
		private highlightedParent: StructureElement = null;
		
		public highlight(findVisibleParent: boolean = false) {
			this.jqElem.addClass("rocket-highlighted");
			this.jqElem.removeClass("rocket-highlight-remember");
			
			if (!findVisibleParent || this.isVisible()) return;
				
			this.highlightedParent = this;
			while (null !== (this.highlightedParent = this.highlightedParent.getParent())) {
				if (!this.highlightedParent.isVisible()) continue;
				
				this.highlightedParent.highlight();
				return;
			}
		}
		
		public unhighlight(slow: boolean = false) {
			this.jqElem.removeClass("rocket-highlighted");
			
			if (slow) {
				this.jqElem.addClass("rocket-highlight-remember");	
			} else {
				this.jqElem.removeClass("rocket-highlight-remember");
			}
			
			if (this.highlightedParent !== null) {
				this.highlightedParent.unhighlight();
				this.highlightedParent = null;
			}
		}

		public static from(jqElem: JQuery, create: boolean = false): StructureElement {
			var structureElement = jqElem.data("rocketStructureElement");
			if (structureElement instanceof StructureElement) return structureElement;
		
			if (!create) return null;
			
			structureElement = new StructureElement(jqElem);
			jqElem.data("rocketStructureElement", structureElement);
			return structureElement;
		}
		
		public static of(jqElem: JQuery): StructureElement {
			jqElem = jqElem.closest(".rocket-structure-element, .rocket-group, .rocket-field");
			
			if (jqElem.length == 0) return null;
			
			var structureElement = jqElem.data("rocketStructureElement");
			if (structureElement instanceof StructureElement) {
				return structureElement;
			}
			
			structureElement = StructureElement.from(jqElem, true);
			jqElem.data("rocketStructureElement", structureElement);
			return structureElement;
		}
	}
	
	export class Toolbar {
		private jqToolbar: JQuery;
		private jqControls: JQuery;
		private commandList: CommandList;
		
		public constructor(jqToolbar: JQuery) {
			this.jqToolbar = jqToolbar;
			
			this.jqControls = jqToolbar.children(".rocket-group-controls");
			if (this.jqControls.length == 0) {
				this.jqControls = $("<div />", { "class": "rocket-group-controls"});
				this.jqToolbar.append(this.jqControls);
				this.jqControls.hide();
			} else if (this.jqControls.is(':empty')) {
				this.jqControls.hide();
			}
			
			var jqCommands = jqToolbar.children(".rocket-simple-commands");
			if (jqCommands.length == 0) {
				jqCommands = $("<div />", { "class": "rocket-simple-commands"});
				jqToolbar.append(jqCommands);
			}
			this.commandList = new CommandList(jqCommands, true);
		}
		
		get jQuery(): JQuery {
			return this.jqToolbar;
		}
		
		public getJqControls(): JQuery {
			return this.jqControls;	
		}
		
		public getCommandList(): CommandList {
			return this.commandList;
		}
	}
	
	export class CommandList {
		private jqCommandList: JQuery;
		
		public constructor(jqCommandList: JQuery, private simple: boolean = false) {
			this.jqCommandList = jqCommandList;
			
			if (simple) {
				jqCommandList.addClass("rocket-simple-commands");
			}
		}
		
		get jQuery(): JQuery {
			return this.jqCommandList;
		}
		
		public createJqCommandButton(buttonConfig: ButtonConfig/*, iconType: string, label: string, severity: Severity = Severity.SECONDARY, tooltip: string = null*/, prepend: boolean = false): JQuery {
			this.jqCommandList.show();
			
			if (buttonConfig.iconType === undefined) {
				buttonConfig.iconType = "fa fa-circle-o";
			}
			
			if (buttonConfig.severity === undefined) {
				buttonConfig.severity = Severity.SECONDARY;
			}
			
			var jqButton = $("<button />", { 
				"class": "btn btn-" + buttonConfig.severity 
						+ (buttonConfig.important ? " rocket-important" : "")
						+ (buttonConfig.iconImportant ? " rocket-icon-important" : "")
						+ (buttonConfig.labelImportant ? " rocket-label-important" : ""),
				"title": buttonConfig.tooltip,
				"type": "button"
			});
		
			if (this.simple) {
				jqButton.append($("<span />", {
					"text": buttonConfig.label
				})).append($("<i />", {
					"class": buttonConfig.iconType
				}));
			} else {
				jqButton.append($("<i />", {
					"class": buttonConfig.iconType
				})).append($("<span />", {
					"text": buttonConfig.label
				}));
			}
			
			if (prepend) {
				this.jqCommandList.prepend(jqButton);
			} else {
				this.jqCommandList.append(jqButton);
			}
			
			return jqButton;
		}
		
		static create(simple: boolean = false) {
			return new CommandList($("<div />"), simple);
		}
	}
	
	export interface ButtonConfig {
		iconType?: string;
		label: string;
		severity?: Severity;
		tooltip?: string;
		important?: boolean;
		iconImportant?: boolean;
		labelImportant?: boolean;
	}
}