var ReportBuilder = Class.create({
	initialize: function(mainBuilderDiv, editorDiv, maxWidth, possibleFieldOptions) {
		this.mainBuilderDiv = mainBuilderDiv;
		this.editorDiv = editorDiv;
		this.fieldSets = new Array();
		this.maxWidth = maxWidth;
		this.editor = new ComponentEditor(editorDiv);
		this.possibleFieldOptions = possibleFieldOptions;
	},
	
	getFieldSetPosition: function(fieldSet) {
		for(var position=0; position<this.getNumberOfFieldSets(); position++) {
			if (this.fieldSets[position] == fieldSet)
				return position;
		}
		return 0;
	},
	
	getNumberOfFieldSets: function() {
		return this.fieldSets.length;
	},
	
	moveUp: function(fieldSet) {
		var fieldSetPosition = this.getFieldSetPosition(fieldSet);
		if (fieldSetPosition >= 0) {
			this.mainBuilderDiv.removeChild(fieldSet.getBaseElement());
			this.mainBuilderDiv.insertBefore(fieldSet.getBaseElement(), this.fieldSets[fieldSetPosition-1].getBaseElement());
			this.fieldSets.splice(fieldSetPosition, 1);
			this.fieldSets.splice(fieldSetPosition-1, 0, fieldSet);
		}
		fieldSet.showEditingFields();
	},
	
	moveDown: function(fieldSet) {
		var fieldSetPosition = this.getFieldSetPosition(fieldSet);
		if (fieldSetPosition < this.getNumberOfFieldSets()) {
			this.mainBuilderDiv.removeChild(fieldSet.getBaseElement());
			if (fieldSetPosition+2 != this.getNumberOfFieldSets()) {
				this.mainBuilderDiv.insertBefore(fieldSet.getBaseElement(), this.fieldSets[fieldSetPosition+2].getBaseElement());
			} else {
				this.mainBuilderDiv.appendChild(fieldSet.getBaseElement());
			}
			this.fieldSets.splice(fieldSetPosition, 1);
			this.fieldSets.splice(fieldSetPosition+1, 0, fieldSet);
		}
		fieldSet.showEditingFields();
	},
	
	remove: function(fieldSet) {
		var fieldSetPosition = this.getFieldSetPosition(fieldSet);
		this.mainBuilderDiv.removeChild(fieldSet.getBaseElement());
		this.fieldSets.splice(fieldSetPosition, 1);
		this.hideEditingFields();
	},
	
	addFieldSet: function(type, data, dataType, sortBy, sortByDirection, fontSize, fontFamily) {
		var newFieldSet;
		if (type == "form")
			newFieldSet = new FormFieldSet(this.editor, this.possibleFieldOptions.form);
		else if (type == "grid")
			newFieldSet = new GridFieldSet(this.editor, this.possibleFieldOptions.grid);
		else if (type == "space")
			newFieldSet = new SpaceFieldSet(this.editor, data);
		else
			newFieldSet = new PageBreakFieldSet(this.editor);
			
		this.mainBuilderDiv.appendChild(newFieldSet.getBaseElement());
		newFieldSet.setParentController(this);
		if (newFieldSet.activateVisualComponents != null)
			newFieldSet.activateVisualComponents();
		//newFieldSet.setEditorController(this.editor);
		this.fieldSets.push(newFieldSet);
		newFieldSet.showEditingFields();
		
		if (data != null && data != "" && newFieldSet.loadFromJSON != null) {
			if (sortBy != null)
				newFieldSet.loadFromJSON(data, dataType, sortBy, sortByDirection, fontSize, fontFamily);
			else
				newFieldSet.loadFromJSON(data, dataType);
		}
			
	},
	
	hideEditingFields: function() {
		this.editorDiv.innerHTML = "";	
	},
	
	clearHighlights: function() {
		for(var position=0; position<this.getNumberOfFieldSets(); position++) {
			this.fieldSets[position].clearHighlight();
		}
	},
	
	loadFromJSON: function(jsonData) {
		jsonData = jsonData.evalJSON();
		for(var i=0; i < jsonData.length; i++) {
			// if sortBy is not an array make it the first element
			if (jsonData[i].sortBy == null)
				jsonData[i].sortBy = new Array(null, null, null);
			if (jsonData[i].sortByDirection == null)
				jsonData[i].sortByDirection = new Array(null, null, null);
			if (jsonData[i].fontSize == null)
				jsonData[i].fontSize = null;
			if (jsonData[i].fontFamily == null)
				jsonData[i].fontFamily = null;
			this.addFieldSet(jsonData[i].type, jsonData[i].data, jsonData[i].dataType, jsonData[i].sortBy, jsonData[i].sortByDirection, jsonData[i].fontSize, jsonData[i].fontFamily);
		}
		this.hideEditingFields();
	},
	
	toJSON: function() {
		var jsonData = [];
		for (var i=0; i < this.fieldSets.length; i++) {
			jsonData.push(this.fieldSets[i].toJSON());
		}
		return Object.toJSON(jsonData);
	}
});

/*var ReportBuilder = Class.create({
	initialize: function(element) {
		this.element = element;
		this.currentDataObject = null;
	}
});*/

var ComponentEditor =  Class.create({
	initialize: function(element) {
		this.element = element;
		this.currentDataObject = null;
	},
	
	showEditingFields: function(dataObject) {
		if (this.currentDataObject != null && this.currentDataObject.clearHighlight != null)
			this.currentDataObject.clearHighlight();
		this.currentDataObject = dataObject;
		var editingHTML = dataObject.createEditingFields();
		this.element.innerHTML = "";
		
		var hrDiv = new Element("div");
		hrDiv.addClassName("hr");
		editingHTML.appendChild(hrDiv);
		
		this.element.appendChild(editingHTML);
		if (dataObject.highlight != null)
			dataObject.highlight();
	},
	
	createCommonFields: function(mainForm, editingTitle, showMovingButtons, showFormRowMovingButtons) {
		if (showMovingButtons == null) {
			showMovingButtons = false;
		}
		
		if (showFormRowMovingButtons == null)
			showFormRowMovingButtons = false;
		var deleteButtonBox = new Element("div");
		deleteButtonBox.addClassName("deleteElementButton");
		
		var deleteButton = new Element("div");
		deleteButton.addClassName("redButton");
		deleteButton.addClassName("outerButton");
		
		var buttonLeft = new Element("div");
		buttonLeft.addClassName("buttonLeft");
		deleteButton.appendChild(buttonLeft);
		
		var buttonText = new Element("div");
		buttonText.addClassName("buttonText");
		buttonLeft.appendChild(buttonText);
		
		// delete element button
		var deleteLink = new Element("a");
		deleteLink.href = "#";
		deleteLink.innerHTML = "Delete";
		//deleteLink.addClassName("deleteElementButton");
		deleteLink.observe("click", this.currentDataObject.remove.bind(this.currentDataObject));
		buttonText.appendChild(deleteLink);
		
		var buttonRight = new Element("div");
		buttonRight.addClassName("buttonRight");
		deleteButton.appendChild(buttonRight);
		
		deleteButtonBox.appendChild(deleteButton);
		
		mainForm.appendChild(deleteButtonBox);
		
		var container = new Element("div");
		container.setStyle({float: 'left', width: '120px', height: '50px'});
		
		// title
		var title = new Element("div");
		title.addClassName("editingTitle");
		title.innerHTML = editingTitle;
		container.appendChild(title);
		
		if (showMovingButtons || showFormRowMovingButtons) {
			var fieldSetPosition;
			if (showMovingButtons) {
				fieldSetPosition = this.currentDataObject.parentController.getFieldSetPosition(this.currentDataObject);
			} else {
				fieldSetPosition = this.currentDataObject.parentController.getFormRowPosition(this.currentDataObject);
			}
			// move up button
			var moveUpLink = new Element("a");
			moveUpLink.href = "#";
			var moveUpButton = new Element("div");
			moveUpLink.addClassName("moveUpButton");
			if (fieldSetPosition != 0) {
				moveUpLink.observe("click", this.currentDataObject.moveUp.bind(this.currentDataObject));
			} else { 
				//moveUpButton.disabled = true;
			}
			moveUpLink.appendChild(moveUpButton);
			container.appendChild(moveUpLink);
			
			// move down button
			var totalFields;
			if (showMovingButtons) {
				totalFields = this.currentDataObject.parentController.getNumberOfFieldSets();
			} else {
				totalFields = this.currentDataObject.parentController.getNumberOfFormRows();
			}
			var moveDownLink = new Element("a");
			moveDownLink.href = "#";
			var moveDownButton = new Element("div");
			moveDownLink.addClassName("moveDownButton");
			if (fieldSetPosition != totalFields-1) {
				moveDownLink.observe("click", this.currentDataObject.moveDown.bind(this.currentDataObject));
			} else {
				//moveDownButton.disabled = true;
			}
			moveDownLink.appendChild(moveDownButton);
			container.appendChild(moveDownLink);
		}
		mainForm.appendChild(container);
	}
});

var FieldSet =  Class.create({
	initialize: function(editController) {
		this.baseElement = new Element("div");
		this.baseElement.addClassName("reportFieldSetBox");
		
		this.innerBox = new Element("div");
		this.innerBox.addClassName("reportFieldSet");
		this.baseElement.appendChild(this.innerBox);
		//this.baseElement.innerHTML = "&nbsp;";
		this.editController = editController;
	},
	
	getBaseElement: function() {
		return this.baseElement;
	},
	
	setParentController: function(parentController) {
		this.parentController = parentController;
	},
	
	showEditingFields: function() {
		this.editController.showEditingFields(this);
		if (this.activateEditorComponents != null)
			this.activateEditorComponents();
	},
	
	clearHighlight: function() {
		this.baseElement.setStyle({ border: "1px solid white"});
	},
	
	highlight: function() {
		this.baseElement.setStyle({ border: "1px solid "+this.highlightColor});
	},
	
	moveUp: function() {
		this.parentController.moveUp(this);
	},
	
	moveDown: function() {
		this.parentController.moveDown(this);
	},
	
	remove: function() {
		this.parentController.remove(this);
	}
});

var FormFieldSet = Class.create(FieldSet, {
	initialize: function($super, editController, possibleFieldOptions) {
		$super(editController);
		this.baseElement.setStyle({background: '#E8F0F7'});
		this.highlightColor = "#355A68";
		this.possibleFieldOptions = $H(possibleFieldOptions);
		this.selectedDataType = this.possibleFieldOptions.keys()[0];
		
		var editElement = new EditElement("data");
		editElement.setClickHandler(this.showEditingFields.bind(this));
		
		this.innerBox.appendChild(editElement.getBaseElement());
		
		var hrDiv = new Element("div");
		hrDiv.addClassName("hr");
		this.innerBox.appendChild(hrDiv);
		this.fieldContainmentElements = new Array();
		this.dataRows = new Array();
		
		//this.addRow();
	},
	
	getFieldContainmentElements: function() {
		return this.fieldContainmentElements;
	},
	
	getNumberOfFormRows: function() {
		return this.dataRows.length;
	},
	
	getFormRowPosition: function(formRow) {
		for(var position=0; position < this.getNumberOfFormRows(); position++) {
			if (this.dataRows[position] == formRow)
				return position;
		}
		return 0;
	},
	
	addSortableElement: function(sortableElementID) {
		// check to see if the element doesn't exist
		for (var i=0; i<this.fieldContainmentElements.length; i++) {
			if (this.fieldContainmentElements[i] == sortableElementID)
				return;
		}
		this.fieldContainmentElements.push(sortableElementID);
	},
	
	createEditingFields: function() {
		var mainEditor = new Element("div");
		mainEditor.addClassName("editFields");
		var mainForm = new Element("form");
		this.editController.createCommonFields(mainForm, "Edit Form", true);
		
		// Form data type selection
		var formTypeLabel = new Element("span");
		formTypeLabel.innerHTML = "Type: ";
		mainForm.appendChild(formTypeLabel);
		
		var selectedDataType = this.selectedDataType;
		var formTypeSelection = new Element("select");
		formTypeSelection.name = "FormTypeSelection";
		this.possibleFieldOptions.each(function(data) {
			var formTypeOption = new Element("option");
			if (data.key == selectedDataType) {
				formTypeOption.selected = "selected";
			}
			formTypeOption.value = data.key;
			formTypeOption.innerHTML = data.key;
			formTypeSelection.appendChild(formTypeOption);
		});
		this.formTypeSelection = formTypeSelection;
		this.formTypeSelection.observe("change", this.changeDataType.bind(this));
		mainForm.appendChild(this.formTypeSelection);
		
		// add row button
		var addRowButton = new Element("input");
		addRowButton.type = "button";
		addRowButton.writeAttribute("value", "Add Row");
		addRowButton.observe("click", this.addRow.bind(this));
		mainForm.appendChild(addRowButton);
		
		mainEditor.appendChild(mainForm);
		return mainEditor;
	},
	
	moveUpRow: function(row) {
		var rowPosition = this.getFormRowPosition(row);
		if (rowPosition >= 0) {
			this.innerBox.removeChild(row.getBaseElement());
			this.innerBox.insertBefore(row.getBaseElement(), this.dataRows[rowPosition-1].getBaseElement());
			this.dataRows.splice(rowPosition, 1);
			this.dataRows.splice(rowPosition-1, 0, row);
		}
		row.showEditingFields();
	},
	
	moveDownRow: function(row) {
		var rowPosition = this.getFormRowPosition(row);
		if (rowPosition < this.getNumberOfFormRows()) {
			this.innerBox.removeChild(row.getBaseElement());
			if (rowPosition+2 != this.getNumberOfFormRows()) {
				this.innerBox.insertBefore(row.getBaseElement(), this.dataRows[rowPosition+2].getBaseElement());
			} else {
				this.innerBox.appendChild(row.getBaseElement());
			}
			this.dataRows.splice(rowPosition, 1);
			this.dataRows.splice(rowPosition+1, 0, row);
		}
		row.showEditingFields();
	},
	
	loadFromJSON: function(data, dataType) {
		this.selectedDataType = dataType;
		for(var currentRow=0; currentRow < data.length; currentRow++) {
			this.addRow(data[currentRow].data);
		}
		//this.editController.showEditingFields(this);
	},
	
	changeDataType: function() {
		var selectedOption = this.formTypeSelection.options[this.formTypeSelection.selectedIndex];
		if (selectedOption.value != this.selectedDataType) {
			if (confirm("Are you sure you want to change this element's data type? Changing it will remove all the components within the element")) {
				this.selectedDataType = selectedOption.value;
				this.removeAllData();
			} else { // revert the selection box back to the originally selected option
				for (var i=0; i < this.formTypeSelection.options.length; i++) {
					if (this.formTypeSelection.options[i].value == this.selectedDataType) {
						this.formTypeSelection.selectedIndex = i;
					}
				}
			}
		}
	},
	
	removeAllData: function () {
		while(this.dataRows.length > 0) {
			// remove the first html element
			this.innerBox.removeChild(this.dataRows[0].getBaseElement());
			
			// remove it from the main row array
			this.dataRows.splice(0, 1);
		}
		
		this.addRow();
		
		this.editController.showEditingFields(this);
	},
	
	removeColumn: function(elementID) {
		for (var i=0; i < this.dataRows.length; i++) {
			var removedColumn = this.dataRows[i].removeColumn(elementID);
			if (removedColumn != null)
				return removedColumn;
		}
		return null;
	},
	
	removeRow: function(row) {
		for (var i=0; i < this.dataRows.length; i++) {
			if (this.dataRows[i] == row) {
				// find the HTML element, and remove it
				this.innerBox.removeChild(row.getBaseElement());
				
				// remove it from the main row array
				this.dataRows.splice(i, 1);
				
				this.editController.showEditingFields(this);
			}
		}
	},
	
	addRow: function(data) {
		var dataRow = new FormRow(this, this.editController, this.possibleFieldOptions.get(this.selectedDataType));
		this.dataRows.push(dataRow);
		var childNodes = this.innerBox.childNodes;
		if (childNodes.length == 2)
			this.innerBox.insertBefore(dataRow.getBaseElement(), childNodes[0]);
		else
			this.innerBox.insertBefore(dataRow.getBaseElement(), childNodes[childNodes.length-1]);
		if (data != null) {
			dataRow.loadFromJSON(data);
		}
		dataRow.showEditingFields();
	},
	
	updateSortableLists: function() {
		for (var i=0; i<this.dataRows.length; i++) {
			this.dataRows[i].updateSortableList();
		}
	},
	
	toJSON: function() {
		var rowsJSON = [];
		for (var i=0; i < this.dataRows.length; i++) {
			var rowJSON = this.dataRows[i].toJSON();
			//alert("data: "+rowJSON.toSource());
			rowsJSON.push(rowJSON);
		}
		return {
			type: 'form',
			dataType: this.selectedDataType,
			data: rowsJSON
		}
	}
});

var FormRow = Class.create({
	initialize: function(parentController, editController, possibleFieldOptions) {
		this.baseElement = new Element("div");
		this.baseElement.id = generateUniqueID("dataRowBox");
		this.baseElement.addClassName("dataRow");
		this.clearHighlightColor = "#CCD3D6";
		this.highlightColor = "#8EA6AF";
		this.possibleFieldOptions = possibleFieldOptions;
		
		//this.baseElement.innerHTML = "row";
		this.parentController = parentController;
		this.editController = editController;
		this.rowID = generateUniqueID("dataRow");
		this.elementIDCount = 1;
		this.columns = new Array();
		
		this.dataRowElementsBox = new Element("div");
		this.dataRowElementsBox.addClassName("dataRowElementsBox");
		this.baseElement.appendChild(this.dataRowElementsBox);
		
		var editRowElement = new Element("div");
		editRowElement.addClassName("editRowElement");
		
		var editElement = new EditElement("dataRow");
		editRowElement.appendChild(editElement.getBaseElement());
		
		this.dataRowElementsBox.appendChild(editRowElement);
		
		this.dataRowElements = new Element("div");
		this.dataRowElements.addClassName("dataRowElements");
		this.dataRowElements.id = this.rowID;
		//this.dataRowElements.innerHTML = "&nbsp;";
		this.dataRowElementsBox.appendChild(this.dataRowElements);
		
		editElement.setClickHandler(this.editController.showEditingFields.bind(this.editController, this));
		
		this.parentController.updateSortableLists();
	},
	
	remove: function () {
		this.parentController.removeRow(this);
	},
	
	loadFromJSON: function(data) {
		for(var currentColumn=0; currentColumn < data.length; currentColumn++) {
			var columnData = data[currentColumn];
			this.addColumn(columnData.type, columnData);
		}
		//this.editController.showEditingFields(this);
	},
	
	moveUp: function() {
		this.parentController.moveUpRow(this);
	},
	
	moveDown: function() {
		this.parentController.moveDownRow(this);
	},
	
	highlight: function() {
		this.dataRowElementsBox.setStyle({ border: "1px solid "+this.highlightColor});
	},
	
	clearHighlight: function() {
		this.dataRowElementsBox.setStyle({ border: "1px solid "+this.clearHighlightColor});
	},
	
	showEditingFields: function() {
		this.editController.showEditingFields(this);
	},
	
	updateSortableList: function() {
		// create the sortable list
		this.parentController.addSortableElement(this.dataRowElements.id);
		Sortable.create(this.dataRowElements, {
			tag: 'div',
			overlap: 'horizontal',
			constraint: false,
			/*containment: this.parentController.getFieldContainmentElements(),*/
			dropOnEmpty: true,
			handle: 'handle',
			onUpdate: this.changeSortingList.bind(this)
		});
	},
	
	changeSortingList: function(divElement) {
		// look through the list of elements in this row and see what's changed
		var elements = this.dataRowElements.childNodes
		if (this.columns.length > elements.length) { // something has been removed
			// the adding function of the other row will take care of changing the column structure, so do nothing
			
			// update the elementIDs
			
			// update the sortable list
			
		} else if (this.columns.length < elements.length) { // something has been added
			// find the added column
			var addedComponentIndex = null
			for (var j=0; j < elements.length; j++) {
				if (this.columns[j] == null || elements[j].id != this.columns[j].getBaseElement().id) { // changed
					addedComponentIndex = j;
					break;
				}
			}
			
			if (addedComponentIndex != null) {
				// remove the column from its previous row
				var removedColumn = this.parentController.removeColumn(elements[addedComponentIndex].id);
				
				// add it into our columns array
				this.columns.splice(addedComponentIndex, 0, removedColumn);
			}
		} else { // something has been moved.
			//alert("moving");
			// find the components that have changed and swap them
			/*var firstComponentIndex = null;
			var secondComponentIndex = null;
			for (var j=0; j < elements.length; j++) {
				if (elements[j].id != this.columns[j].id) { // changed
					if (firstComponentIndex == null)
						firstComponentIndex = j;
					else {
						secondComponentIndex = j;
						break;
					}
				}
			}
			
			// swap the components in the column
			if (firstComponentIndex != null && secondComponentIndex != null) {
				alert("swapping: "+firstComponentIndex+" and "+secondComponentIndex);
				var firstComponent = this.columns[firstComponentIndex];
				var secondComponent = this.columns[secondComponentIndex];
				this.columns.splice(firstComponentIndex, 1, secondComponent);
				this.columns.splice(secondComponentIndex, 1, firstComponent);
			}*/
			
			// re-order the columns, based on the new order of the elements
			var newColumns = [];
			for (var j=0; j < elements.length; j++) {
				// find the corresponding column for this element
				for (var k=0; k < this.columns.length; k++) {
					//alert(elements[j].id+" == "+this.columns[k].getBaseElement().id);
					if (elements[j].id == this.columns[k].getBaseElement().id) {
						newColumns.push(this.columns[k]);
						break;
					}
				}
			}
			this.columns = newColumns;
		}
		
	},
	
	removeColumn: function(columnElementID) {
		for (var i=0; i < this.columns.length; i++) {
			if (this.columns[i].getBaseElement().id == columnElementID) {
				// remove the actual element, if it hasn't been removed already
				if (i < this.dataRowElements.childNodes.length && this.dataRowElements.childNodes[i].id == columnElementID)
					this.dataRowElements.removeChild(this.columns[i].getBaseElement());
				
				// remove it from the current array
				var removedColumn = this.columns[i];
				this.columns.splice(i, 1);
				return removedColumn;
			}
		}
		return null;
	},
	
	getBaseElement: function() {
		return this.baseElement;
	},
	
	createEditingFields: function() {
		var mainEditor = new Element("div");
		mainEditor.addClassName("editFields");
		var mainForm = new Element("form");
		
		this.editController.createCommonFields(mainForm, "Edit Row", false, true);
		
		// data field button
		var addDataFieldButton = new Element("input");
		addDataFieldButton.type = "button";
		addDataFieldButton.writeAttribute("value", "Add Data");
		addDataFieldButton.observe("click", this.addColumn.bind(this, "field"));
		mainForm.appendChild(addDataFieldButton);
		
		// data field button
		var addDataTextButton = new Element("input");
		addDataTextButton.type = "button";
		addDataTextButton.writeAttribute("value", "Add Text");
		addDataTextButton.observe("click", this.addColumn.bind(this, "text"));
		mainForm.appendChild(addDataTextButton);
		
		mainEditor.appendChild(mainForm);
		return mainEditor;
	},
	
	addColumn: function(type, data) {
		var newColumnComponent;
		if (type == "text") {
			newColumnComponent = new DataRowText(this.editController, this, this.rowID+"_"+this.elementIDCount);
		} else {
			newColumnComponent = new DataRowField(this.editController, this, this.rowID+"_"+this.elementIDCount, this.possibleFieldOptions);
		}
		this.dataRowElements.appendChild(newColumnComponent.getBaseElement());
		
		if (data != null)
			newColumnComponent.loadFromJSON(data);
		
		this.columns.push(newColumnComponent);
		newColumnComponent.showEditingFields();
		this.elementIDCount++;
		this.parentController.updateSortableLists();
	},
	
	toJSON: function() {
		var columnsJSON = [];
		for (var i=0; i < this.columns.length; i++) {
			columnsJSON.push(this.columns[i].toJSON());
		}
		return {
			type: 'row',
			data: columnsJSON
		};
	}
});

var DataRowComponent = Class.create({
	initialize: function(editController, parentController) {
		this.editController = editController;
		this.parentController = parentController;
		this.wordWrap = false;
		this.minWidth = 18;
		this.maxWidth = 610;
		this.width = 60;
		this.variableWidth = false;
		this.fontFamilies = ["Helvetica", "Times", "Courier", "Palatino"];
		this.fontSizes = [ "8pt", "9pt", "10pt", "11pt", "12pt", "14pt", "16pt", "18pt", "20pt" ];
		this.fontSizeHeights = {
			'8pt': '17px',
			'9pt': '19px',
			'10pt': '20px',
			'11pt': '22px',
			'12pt': '24px',
			'14pt': '26px',
			'16pt': '28px',
			'18pt': '30px',
			'20pt': '34px'
		};
		this.bold = false;
		this.italic = false;
		this.fontSize = "9pt";
		this.fontFamily = "Helvetica";
	},
	
	getBaseElement: function() {
		return this.baseElement;
	},
	
	showEditingFields: function() {
		this.editController.showEditingFields(this);
		this.activateEditorComponents();
	},
	
	remove: function() {
		this.parentController.removeColumn(this.getBaseElement().id);
		this.parentController.editController.showEditingFields(this.parentController);
	},
	
	highlight: function() {
		this.baseElement.setStyle({ border: "1px solid "+this.highlightColor});
	},
	
	clearHighlight: function() {
		this.baseElement.setStyle({ border: "1px solid "+this.clearHighlightColor});
	},
	
	activateEditorComponents: function() {
		var debugWidth = this.debugWidth;
		this.widthSlider = new Control.Slider(this.widthHandle.id, this.widthTrack.id, {
			sliderValue: (this.width-this.minWidth)/(this.maxWidth-this.minWidth),
			onSlide: this.changeWidth.bind(this),
			onChange: this.changeWidth.bind(this)
		});
		this.debugWidth.value = this.width;
	},
	
	updateRowComponent: function() {
		if (this.bold) {
			this.columnElement.setStyle({fontWeight: 'bold'});
		} else {
			this.columnElement.setStyle({fontWeight: 'normal'});
		}
		
		if (this.italic) {
			this.columnElement.setStyle({fontStyle: 'italic'});
		} else {
			this.columnElement.setStyle({fontStyle: 'normal'});
		}
		
		this.columnElement.setStyle({fontFamily: this.fontFamily, fontSize: this.fontSize, lineHeight: this.fontSizeHeights[this.fontSize]});
		
		if (this.variableWidth) {
			this.columnElement.setStyle({overflow: 'visible', width: 'auto', height: 'auto'});
			if (this.wordwrapCheckbox != null)
				this.wordwrapCheckbox.disabled = true;
		} else {
			if (this.wordwrapCheckbox != null)
				this.wordwrapCheckbox.disabled = false;
			if (this.wordWrap) {
				this.columnElement.setStyle({overflow: 'visible', height: 'auto', width: this.width+"px"});
			} else {
				this.columnElement.setStyle({overflow: 'hidden', height: this.fontSizeHeights[this.fontSize], width: this.width+"px"});
			}
		}
		
		if (this.wordWrap) {
			this.columnElement.setStyle({overflow: 'visible', height: 'auto'});
		} else {
			this.columnElement.setStyle({overflow: 'hidden', height: this.fontSizeHeights[this.fontSize]});
		}
	},
	
	toggleVariableWidth: function() {
		this.variableWidth = this.variableWidthCheckbox.checked;
		this.updateRowComponent();
	},
	
	toggleWordwrap: function() {
		this.wordWrap = this.wordwrapCheckbox.checked;
		this.updateRowComponent();
	},
	
	toggleBold: function() {
		this.bold = this.boldCheckbox.checked;
		this.updateRowComponent();
	},
	
	toggleItalic: function() {
		this.italic = this.italicCheckbox.checked;
		this.updateRowComponent();
	},
	
	changeFontFamily: function() {
		var selectedOption = this.fontFamilySelection.options[this.fontFamilySelection.selectedIndex];
		this.fontFamily = selectedOption.value;
		this.updateRowComponent();
	},
	
	changeFontSize: function() {
		var selectedOption = this.fontSizeSelection.options[this.fontSizeSelection.selectedIndex];
		this.fontSize = selectedOption.value;
		this.updateRowComponent();
	},
	
	changeWidth: function(widthPercentage) {
		this.width = Math.floor((widthPercentage*(this.maxWidth-this.minWidth))+this.minWidth);
		if (this.width > this.maxWidth)
			this.width = this.maxWidth;
		this.debugWidth.value = this.width;
		this.columnElement.setStyle({width: this.width + "px"});
	},
	
	manuallyChangeWidth: function() {
		this.width = parseInt(this.debugWidth.value);
		if (this.width > this.maxWidth)
			this.width = this.maxWidth;
			
		var widthPercentage = (this.width-this.minWidth)/(this.maxWidth-this.minWidth);
			
		this.debugWidth.value = this.width;
		this.columnElement.setStyle({width: this.width + "px"});
		this.widthSlider.setValue(widthPercentage);
	},
	
	createEditingFields: function(mainForm) {
		// font label
		var labelText = new Element("span");
		labelText.innerHTML = "<br>Font:&nbsp;";
		mainForm.appendChild(labelText);
		
		var fontFamilySelection = new Element("select");
		fontFamilySelection.name = this.baseElement.id+"FontFamily";
		var fontFamily = this.fontFamily;
		this.fontFamilies.each(function(data) {
			var fontFamilyOption = new Element("option");
			fontFamilyOption.value = data;
			fontFamilyOption.setStyle({fontFamily: data});
			fontFamilyOption.innerHTML = data;
			if (data == fontFamily) {
				fontFamilyOption.selected = "selected";
			}
			fontFamilySelection.appendChild(fontFamilyOption);
		});
		this.fontFamilySelection = fontFamilySelection;
		this.fontFamilySelection.observe("change", this.changeFontFamily.bind(this));
		mainForm.appendChild(fontFamilySelection);
		
		// font size label
		var labelText = new Element("span");
		labelText.innerHTML = "&nbsp;Size:&nbsp;";
		mainForm.appendChild(labelText);
		
		var fontSizeSelection = new Element("select");
		fontSizeSelection.name = this.baseElement.id+"FontSize";
		fontSizeSelection.setStyle({height: "20px"});
		var fontSize = this.fontSize;
		this.fontSizes.each(function(data) {
			var fontSizeOption = new Element("option");
			fontSizeOption.value = data;
			fontSizeOption.setStyle({fontSize: data});
			fontSizeOption.innerHTML = data;
			if (data == fontSize) {
				fontSizeOption.selected = "selected";
			}
			fontSizeSelection.appendChild(fontSizeOption);
		});
		this.fontSizeSelection = fontSizeSelection;
		this.fontSizeSelection.observe("change", this.changeFontSize.bind(this));
		mainForm.appendChild(fontSizeSelection);
		
		// bold checkbox
		labelText = new Element("span");
		labelText.innerHTML = "&nbsp;&nbsp;Bold: ";
		mainForm.appendChild(labelText);
		
		this.boldCheckbox = new Element("input");
		this.boldCheckbox.type = "checkbox";
		this.boldCheckbox.checked = this.bold;
		mainForm.appendChild(this.boldCheckbox);
		this.boldCheckbox.observe("click", this.toggleBold.bind(this));
		
		// italic checkbox
		labelText = new Element("span");
		labelText.innerHTML = "&nbsp;&nbsp;Italic: ";
		mainForm.appendChild(labelText);
		
		this.italicCheckbox = new Element("input");
		this.italicCheckbox.type = "checkbox";
		this.italicCheckbox.checked = this.italic;
		mainForm.appendChild(this.italicCheckbox);
		this.italicCheckbox.observe("click", this.toggleItalic.bind(this));
		
		// wordwrap checkbox
		labelText = new Element("span");
		labelText.innerHTML = "&nbsp;&nbsp;Wordwrap: ";
		mainForm.appendChild(labelText);
		
		this.wordwrapCheckbox = new Element("input");
		this.wordwrapCheckbox.type = "checkbox";
		this.wordwrapCheckbox.checked = this.wordWrap;
		if (this.variableWidth)
			this.wordwrapCheckbox.disabled = true;
		mainForm.appendChild(this.wordwrapCheckbox);
		this.wordwrapCheckbox.observe("click", this.toggleWordwrap.bind(this));
		
		// variable width checkbox
		labelText = new Element("span");
		labelText.innerHTML = "&nbsp;&nbsp;Variable Width: ";
		mainForm.appendChild(labelText);
		
		this.variableWidthCheckbox = new Element("input");
		this.variableWidthCheckbox.type = "checkbox";
		this.variableWidthCheckbox.checked = this.variableWidth;
		mainForm.appendChild(this.variableWidthCheckbox);
		this.variableWidthCheckbox.observe("click", this.toggleVariableWidth.bind(this));
	},
	
	createWidthSlider: function() {
		// width slider
		this.totalWidthTrack = new Element("div");
		this.totalWidthTrack.addClassName("totalTrack");
		
		var labelText = new Element("div");
		labelText.addClassName("fieldLabel");
		labelText.innerHTML = "&nbsp;Width:&nbsp;";
		this.totalWidthTrack.appendChild(labelText);
		
		this.widthTrack = new Element("div");
		this.widthTrack.addClassName("track");
		this.widthTrack.id = generateUniqueID("widthTrack");
		this.totalWidthTrack.appendChild(this.widthTrack);
		
		this.widthHandle = new Element("div");
		this.widthHandle.addClassName("trackHandle");
		this.widthHandle.id = generateUniqueID("widthHandle");
		this.widthTrack.appendChild(this.widthHandle);
		
		this.debugWidth = new Element("input");
		this.debugWidth.addClassName("widthText");
		this.debugWidth.observe("blur", this.manuallyChangeWidth.bind(this));
		//this.debugHeight.id = "debug1";
		this.totalWidthTrack.appendChild(this.debugWidth);
		return this.totalWidthTrack;
	}
});

var DataRowText = Class.create(DataRowComponent, {
	initialize: function($super, editController, parentController, elementID) {
		$super(editController, parentController);
		this.type = "text";
		this.highlightColor = "#77809F";
		this.clearHighlightColor = "#B6B8BF";
		
		this.baseElement = new Element("div");
		this.baseElement.addClassName("dataRowTextBox");
		this.baseElement.id = elementID;
		this.baseElement.observe("click", this.showEditingFields.bind(this));

		this.columnElement = new Element("div");
		this.columnElement.addClassName("dataRowText");
		this.columnElement.unselectable = "on";
		this.columnElement.innerHTML = " ";
		this.columnElement.setStyle({width: this.width+'px', height: '19px', overflow: 'hidden'});
		
		this.baseElement.appendChild(this.columnElement);
	},
	
	createEditingFields: function($super) {
		var mainEditor = new Element("div");
		mainEditor.addClassName("editFields");
		var mainForm = new Element("form");
		
		this.editController.createCommonFields(mainForm, "Edit Text", false);
		
		var editRow = new Element("div");
		editRow.addClassName("editRow");
		
		// text label
		var labelText = new Element("div");
		labelText.addClassName("fieldLabel");
		labelText.innerHTML = "Text:&nbsp;&nbsp;";
		editRow.appendChild(labelText);
		
		this.textInput = new Element("input");
		this.textInput.addClassName("fieldLabel");
		this.textInput.type = "text";
		this.textInput.value = this.columnElement.innerHTML;
		this.textInput.observe("keyup", this.changeValue.bind(this));
		editRow.appendChild(this.textInput);
		
		editRow.appendChild(this.createWidthSlider());
		
		mainForm.appendChild(editRow);
		
		$super(mainForm);
		
		mainEditor.appendChild(mainForm);
		return mainEditor;
	},
	
	loadFromJSON: function(data) {
		if (data.value != null) {
			this.columnElement.innerHTML = data.value;
			this.width = data.width;
			this.variableWidth = data.variableWidth;
			this.wordWrap = data.wordWrap;
			this.bold = data.bold;
			this.italic = data.italic;
			this.fontSize = data.fontSize;
			// make sure the saved font is in the font list
			var foundFont = false;
			for (var i=0; i < this.fontFamilies; i++) {
				if (this.fontFamilies[i] == data.fontFamily) {
					this.fontFamily = data.fontFamily;
					foundFont = true;
				}
			}
			if (!foundFont)
				this.fontFamily = this.fontFamilies[0];
			
			this.updateRowComponent();
		}
	},
	
	changeValue: function() {
		this.columnElement.innerHTML = this.textInput.value;
	},
	
	toJSON: function() {
		return {
			type: 'text',
			value: this.columnElement.innerHTML,
			wordWrap: false,
			width: this.width,
			variableWidth: this.variableWidth,
			bold: this.bold,
			italic: this.italic,
			fontSize: this.fontSize,
			fontFamily: this.fontFamily
		}
	}
});

var DataRowField = Class.create(DataRowComponent, {
	initialize: function($super, editController, parentController, elementID, possibleFieldOptions) {
		$super(editController, parentController);
		this.type = "field";
		this.highlightColor = "#6F6F6F";
		this.clearHighlightColor = "#ABADB3";
		this.possibleFieldOptions = $H(possibleFieldOptions);
		this.selectedDataType = this.possibleFieldOptions.keys()[0];
		
		this.baseElement = new Element("div");
		this.baseElement.addClassName("dataRowFieldBox");
		this.baseElement.id = elementID;
		this.baseElement.observe("click", this.showEditingFields.bind(this));

		this.columnElement = new Element("div");
		this.columnElement.addClassName("dataRowField");
		this.columnElement.unselectable = "on";
		this.columnElement.innerHTML = this.possibleFieldOptions.get(this.selectedDataType);
		this.columnElement.setStyle({width: '60px', height: '19px', overflow: 'hidden'});
		
		this.baseElement.appendChild(this.columnElement);
	},
	
	loadFromJSON: function(data) {
		if (data.value != null) {
			this.selectedDataType = data.value;
			this.columnElement.innerHTML = this.possibleFieldOptions.get(data.value);
			this.width = data.width;
			this.variableWidth = data.variableWidth;
			this.wordWrap = data.wordWrap;
			this.bold = data.bold;
			this.italic = data.italic;
			this.fontSize = data.fontSize;
			// make sure the saved font is in the font list
			var foundFont = false;
			for (var i=0; i < this.fontFamilies; i++) {
				if (this.fontFamilies[i] == data.fontFamily) {
					this.fontFamily = data.fontFamily;
					foundFont = true;
				}
			}
			if (!foundFont) {
				this.fontFamily = this.fontFamilies[0];
			}
			
			this.updateRowComponent();
		}
	},
	
	createEditingFields: function($super) {
		var mainEditor = new Element("div");
		mainEditor.addClassName("editFields");
		var mainForm = new Element("form");
		//this.appendMovingButtons(mainForm);
		
		this.editController.createCommonFields(mainForm, "Edit Data", false);
		
		var editRow = new Element("div");
		editRow.addClassName("editRow");
		
		// text label
		var labelText = new Element("div");
		labelText.addClassName("fieldLabel");
		labelText.innerHTML = "Field: ";
		editRow.appendChild(labelText);
		
		var fieldTypeSelection = new Element("select");
		fieldTypeSelection.addClassName("fieldLabel");
		fieldTypeSelection.name = this.baseElement.id+"DataType";
		var selectedDataType = this.selectedDataType;
		this.possibleFieldOptions.each(function(data) {
			var fieldTypeOption = new Element("option");
			fieldTypeOption.value = data.key;
			fieldTypeOption.innerHTML = data.value;
			if (data.key == selectedDataType) {
				fieldTypeOption.selected = "selected";
			}
			fieldTypeSelection.appendChild(fieldTypeOption);
		});
		this.fieldTypeSelection = fieldTypeSelection;
		this.fieldTypeSelection.observe("change", this.changeValue.bind(this));
		editRow.appendChild(fieldTypeSelection);
		
		editRow.appendChild(this.createWidthSlider());
		
		mainForm.appendChild(editRow);
		
		$super(mainForm);
		
		mainEditor.appendChild(mainForm);
		return mainEditor;
	},
	
	changeValue: function() {
		var selectedOption = this.fieldTypeSelection.options[this.fieldTypeSelection.selectedIndex];
		this.selectedDataType = selectedOption.value;
		this.columnElement.innerHTML = selectedOption.innerHTML;
	},
	
	toJSON: function() {
		var jsonData = {
			type: 'field',
			value: this.selectedDataType,
			wordWrap: this.wordWrap,
			width: this.width,
			variableWidth: this.variableWidth,
			bold: this.bold,
			italic: this.italic,
			fontSize: this.fontSize,
			fontFamily: this.fontFamily
		};
		return jsonData;
	}
});

var GridFieldSet = Class.create(FieldSet, {
	initialize: function($super, editController, possibleFieldOptions) {
		$super(editController);
		this.baseElement.setStyle({background: '#F4ECDF'});
		this.highlightColor = "#7F520F";
		this.possibleFieldOptions = $H(possibleFieldOptions);
		var fieldOptions = this.possibleFieldOptions.keys();
		
		this.selectedDataType = fieldOptions[0];
		
		this.grid = null;
		this.numColumns = 0;
		this.columns = new Array();
		
		this.sortBy = new Array(0, 0, 0);
		this.sortByDirection = new Array("ASC", "ASC", "ASC");
		
		this.fontFamilies = ["Helvetica", "Times", "Courier", "Palatino"];
		this.fontSizes = [ "8pt", "9pt", "10pt", "11pt", "12pt", "14pt", "16pt", "18pt", "20pt" ];
		this.fontSizeHeights = {
			'8pt': 94,
			'9pt': 100,
			'10pt': 105,
			'11pt': 112,
			'12pt': 117,
			'14pt': 134,
			'16pt': 140,
			'18pt': 156,
			'20pt': 175
		};
		this.fontSize = "9pt";
		this.fontFamily = "Helvetica";
		
		this.gridElement = new Element("div");
		this.gridElement.id = generateUniqueID("gridElement");
		this.gridElement.setStyle({width: '97%', float: 'left'});
		this.innerBox.appendChild(this.gridElement);
		
		var editElement = new EditElement("grid");
		this.innerBox.appendChild(editElement.getBaseElement());
		editElement.setClickHandler(this.showEditingFields.bind(this));
		
		var hrDiv = new Element("div");
		hrDiv.addClassName("hr");
		this.innerBox.appendChild(hrDiv);
	},
	
	createEditingFields: function() {
		var mainEditor = new Element("div");
		mainEditor.addClassName("editFields");
		var mainForm = new Element("form");
		this.editController.createCommonFields(mainForm, "Edit Grid", true);
		
		if (this.columns.length > 0) {
			
			// Make sure the sortby is an array
			if(this.sortBy.length != 3)
				this.sortBy = new Array(this.sortBy, 0, 0);
			
			if(this.sortByDirection.length != 3 || this.sortByDirection == 'ASC')
				this.sortByDirection = new Array(this.sortByDirection, 0, 0);
			
			var sortingDiv = new Element("div");
			sortingDiv.style.paddingRight = "10px";
			sortingDiv.style.cssFloat = "left";
			
			mainForm.appendChild(sortingDiv);
				
			this.gridSortBySelection = new Array();
			this.gridSortBySelectionDirection = new Array();
			
			// For three different sorts
			for(var j=0; j<this.sortBy.length; j++) {

				// Form data type selection
				var gridSortLabel = new Element("span");
				gridSortLabel.innerHTML = "&nbsp;"+(j+1)+" Sort By: ";
				sortingDiv.appendChild(gridSortLabel);
				
				// populate the select box 
				this.gridSortBySelection[j] = new Element("select");
				this.gridSortBySelection[j].name = "GridTypeSelection"+j;
				this.gridSortBySelection[j].id = "GridTypeSelection"+j;
				for (var i=0; i < this.columns.length; i++) {
					var gridSortByOption = new Element("option");
					gridSortByOption.value = i;
					gridSortByOption.innerHTML = this.columns[i].name;
					// if previously selected, select it
					if (i == this.sortBy[j])
						gridSortByOption.selected = true;
					this.gridSortBySelection[j].appendChild(gridSortByOption);
				}
				this.gridSortBySelection[j].observe("change", this.changeSortBy.bind(this, j));
				sortingDiv.appendChild(this.gridSortBySelection[j]);
				
				var gridSortLabel = new Element("span");
				gridSortLabel.innerHTML = "&nbsp;";
				sortingDiv.appendChild(gridSortLabel);
				
				// now add the ascending/descending select box
				this.gridSortBySelectionDirection[j] = new Element("select");
				this.gridSortBySelectionDirection[j].name = "GridTypeSelectionDirection"+j;
				this.gridSortBySelectionDirection[j].id = "GridTypeSelectionDirection"+j;
				
				var gridSortByOption = new Element("option");
				gridSortByOption.value = "ASC";
				gridSortByOption.innerHTML = "Ascending";
				if (this.sortByDirection == "ASC")
					gridSortByOption.selected = true;
				this.gridSortBySelectionDirection[j].appendChild(gridSortByOption);
				
				gridSortByOption = new Element("option");
				gridSortByOption.value = "DESC";
				gridSortByOption.innerHTML = "Descending";
				
				// if previously selected, select it
				if (this.sortByDirection[j] == "DESC")
					gridSortByOption.selected = true;
				this.gridSortBySelectionDirection[j].appendChild(gridSortByOption);
				
				this.gridSortBySelectionDirection[j].observe("change", this.changeSortByDirection.bind(this, j));
				sortingDiv.appendChild(this.gridSortBySelectionDirection[j]);
				sortingDiv.appendChild(new Element("br"));
			}
		}
		
		var nonSortingDiv = new Element("div");
		nonSortingDiv.style.width = "450px";
		nonSortingDiv.style.cssFloat = "left";
		mainForm.appendChild(nonSortingDiv);
			
		// font label
		var labelText = new Element("span");
		labelText.innerHTML = "<br>Font:&nbsp;";
		nonSortingDiv.appendChild(labelText);
		
		var fontFamilySelection = new Element("select");
		fontFamilySelection.name = this.baseElement.id+"FontFamily";
		var fontFamily = this.fontFamily;
		this.fontFamilies.each(function(data) {
			var fontFamilyOption = new Element("option");
			fontFamilyOption.value = data;
			fontFamilyOption.setStyle({fontFamily: data});
			fontFamilyOption.innerHTML = data;
			if (data == fontFamily) {
				fontFamilyOption.selected = "selected";
			}
			fontFamilySelection.appendChild(fontFamilyOption);
		});
		this.fontFamilySelection = fontFamilySelection;
		this.fontFamilySelection.observe("change", this.changeFontFamily.bind(this));
		nonSortingDiv.appendChild(fontFamilySelection);
		
		// font size label
		var labelText = new Element("span");
		labelText.innerHTML = "&nbsp;Size:&nbsp;";
		nonSortingDiv.appendChild(labelText);
		
		var fontSizeSelection = new Element("select");
		fontSizeSelection.name = this.baseElement.id+"FontSize";
		fontSizeSelection.setStyle({height: "20px"});
		var fontSize = this.fontSize;
		this.fontSizes.each(function(data) {
			var fontSizeOption = new Element("option");
			fontSizeOption.value = data;
			fontSizeOption.setStyle({fontSize: data});
			fontSizeOption.innerHTML = data;
			if (data == fontSize) {
				fontSizeOption.selected = "selected";
			}
			fontSizeSelection.appendChild(fontSizeOption);
		});
		this.fontSizeSelection = fontSizeSelection;
		this.fontSizeSelection.observe("change", this.changeFontSize.bind(this));
		nonSortingDiv.appendChild(fontSizeSelection);
		
		// Form data type selection
		var gridTypeLabel = new Element("span");
		gridTypeLabel.innerHTML = "&nbsp;Type: ";
		nonSortingDiv.appendChild(gridTypeLabel);
		
		var gridTypeSelection = new Element("select");
		gridTypeSelection.name = "GridTypeSelection";
		this.possibleFieldOptions.each(function(data) {
			var gridTypeOption = new Element("option");
			gridTypeOption.innerHTML = data.key;
			gridTypeSelection.appendChild(gridTypeOption);
		});
		nonSortingDiv.appendChild(gridTypeSelection);
		
		var gridSortLabel = new Element("span");
		gridSortLabel.innerHTML = "&nbsp;";
		nonSortingDiv.appendChild(gridSortLabel);
		
		var addColumnButton = new Element("input");
		addColumnButton.type = "button";
		addColumnButton.writeAttribute("value", "Add Column");
		addColumnButton.observe("click", this.addColumn.bind(this));
		nonSortingDiv.appendChild(addColumnButton);
				
		mainEditor.appendChild(mainForm);
		return mainEditor;
	},
	
	changeSortBy: function(j) {
		
		var selectedOption = this.gridSortBySelection[j].options[this.gridSortBySelection[j].selectedIndex];
		this.sortBy[j] = selectedOption.value;
		//this.updateRowComponent();
	},
	
	changeSortByDirection: function(j) {
		var selectedOption = this.gridSortBySelectionDirection[j].options[this.gridSortBySelectionDirection[j].selectedIndex];
		this.sortByDirection[j] = selectedOption.value;
		//this.updateRowComponent();
	},
	
	changeFontFamily: function() {
		var selectedOption = this.fontFamilySelection.options[this.fontFamilySelection.selectedIndex];
		this.fontFamily = selectedOption.value;
		this.renderGrid();
	},
	
	changeFontSize: function() {
		var selectedOption = this.fontSizeSelection.options[this.fontSizeSelection.selectedIndex];
		this.fontSize = selectedOption.value;
		this.renderGrid();
	},
	
	toJSON: function() {
		var columnsJSON = [];
		for (var i=0; i < this.columns.length; i++) {
			columnsJSON.push(this.columns[i].toJSON());
		}
		
		var jsonData = {
			type: 'grid',
			dataType: this.selectedDataType,
			sortBy: this.sortBy,
			sortByDirection: this.sortByDirection,
			fontSize: this.fontSize,
			fontFamily: this.fontFamily,
			data: columnsJSON
		}
		
		return jsonData;
	},
	
	loadFromJSON: function(data, dataType, dataSortBy, dataSortByDirection, dataFontSize, dataFontFamily) {
		this.selectedDataType = dataType;
		this.sortBy = dataSortBy;
		this.sortByDirection = dataSortByDirection;
		this.fontSize = dataFontSize;
		this.fontFamily = dataFontFamily;
		for(var currentColumn=0; currentColumn < data.length; currentColumn++) {
			var columnData = data[currentColumn];
			this.addColumn(columnData);
		}
	},
	
	addColumn: function(data) {
		this.numColumns++;
		var newColumn = new GridColumn(this.editController, this, "Column "+this.numColumns, 120, this.possibleFieldOptions.get(this.selectedDataType));
		this.columns.push(newColumn);
		if (data != null)
			newColumn.loadFromJSON(data);
		this.renderGrid();
		newColumn.showEditingFields();
	},
	
	deleteColumn: function(column) {
		for (var index=0; index < this.columns.length; index++) {
			if (this.columns[index] == column) {
				if (this.sortBy == index)
					this.sortBy = 0;
				this.showEditingFields();
				this.columns.splice(index, 1);
				this.numColumns--;
				this.renderGrid();
			}
		}
	},
	
	resizeColumn: function(columnIndex, newSize) {
		this.columns[columnIndex].width = newSize;
	},
	
	getColumnDiv: function(column) {
		for (var index=0; index < this.columns.length; index++) {
			if (this.columns[index] == column) {
				return this.getColumnDivByIndex(index);
			}
		}
		return null;
	},
	
	getColumnDivByIndex: function(columnIndex) {
		var headerColumns = $(this.grid.getGridEl().id).firstChild.firstChild.firstChild.firstChild.firstChild.firstChild.firstChild.firstChild.childNodes;
		return headerColumns.item(columnIndex)
	},
	
	handleColumnClick: function(grid, columnIndex, e) {
		this.columns[columnIndex].showEditingFields(e.target);
	},
	
	activateVisualComponents: function() {
		this.renderGrid();
	},
	
	renderGrid: function() {
		if (this.grid != null) {
			this.grid.destroy();
		}
			
		var columns = this.createDummyDataColumns(this.numColumns);
		var myData = this.createDummyData(this.numColumns); // sample static data for the store
		var store = this.createDummyFields(this.numColumns); // create the data store
		store.loadData(myData); // manually load local data
		
		// create the Grid
		this.grid = new Ext.grid.GridPanel({
			store: store,
			columns: this.createDummyDataColumns(this.numColumns),
			stripeRows: false,
			height: this.fontSizeHeights[this.fontSize],
			width: 900,
			enableColumnHide: false,
			enableColumnMove: false,
			trackMouseOver: false
		});
		
		this.grid.on("headerclick", this.handleColumnClick.bind(this));
		
		//this.grid.on("columnmove", this.moveColumns.bind(this));
		
		this.grid.on("columnresize", this.resizeColumn.bind(this));
		
		// render the grid to the specified div in the page
		var gridElementID = this.gridElement.id;
		this.gridElement.className = "";
		this.gridElement.addClassName("grid"+this.fontFamily);
		this.gridElement.addClassName("grid"+this.fontSize);
		this.grid.render(gridElementID);
	},
	
	createDummyData: function(numColumns) {
		var data = new Array();
		for (var i=1; i <= 3; i++) {
			var rowData = new Array();
			for (var j=1; j <= numColumns; j++) {
				rowData.push(['Data '+i+"-"+j]);
			}
			data.push(rowData);
		}
		return data;
	},
	
	createDummyFields: function(numColumns) {
		var fields = new Array();
		for (var i=0; i < numColumns; i++) {
			fields.push({name: 'Field'+i});
		}
		return new Ext.data.ArrayStore({
			fields: fields
		});
	},
	
	createDummyDataColumns: function(numColumns) {
		var data = new Array();
		for (var i=0; i < numColumns; i++) {
			data.push({header: this.columns[i].name, width: this.columns[i].width});
		}
		return data;
	}
});

var GridColumn = Class.create({
	initialize: function(editController, parentController, name, width, possibleFieldOptions) {
		this.editController = editController;
		this.parentController = parentController;
		this.possibleFieldOptions = $H(possibleFieldOptions);
		var fieldOptions = this.possibleFieldOptions.keys();
		this.selectedDataType = fieldOptions[0];
		
		this.name = this.possibleFieldOptions.get(this.selectedDataType);
		this.width = width;
		this.highlightColor = "#999999";
		
		this.minWidth = 18;
		this.maxWidth = 610;
	},
	
	createEditingFields: function() {
		var mainEditor = new Element("div");
		mainEditor.addClassName("editFields");
		var mainForm = new Element("form");
		
		this.editController.createCommonFields(mainForm, "Edit Column", false);
		
		// text label
		var labelText = new Element("span");
		labelText.innerHTML = "Field: ";
		mainForm.appendChild(labelText);
		
		var fieldTypeSelection = new Element("select");
		fieldTypeSelection.name = "GridFieldSelection";
		var selectedDataType = this.selectedDataType;
		this.possibleFieldOptions.each(function(data) {
			var fieldTypeOption = new Element("option");
			fieldTypeOption.value = data.key;
			fieldTypeOption.innerHTML = data.value;
			if (data.key == selectedDataType) {
				fieldTypeOption.selected = "selected";
			}
			fieldTypeSelection.appendChild(fieldTypeOption);
		});
		this.fieldTypeSelection = fieldTypeSelection;
		this.fieldTypeSelection.observe("change", this.changeValue.bind(this));
		mainForm.appendChild(fieldTypeSelection);
		
		// text label
		var labelText = new Element("span");
		labelText.innerHTML = "&nbsp;&nbsp;Column Header: ";
		mainForm.appendChild(labelText);
		
		this.columnNameText = new Element("input");
		this.columnNameText.type = "text";
		this.columnNameText.writeAttribute("value", this.name);
        this.columnNameText.onchange = this.changeName(this.value);
		this.columnNameText.observe("keyup", this.changeName.bind(this));
		mainForm.appendChild(this.columnNameText);
		
		mainEditor.appendChild(mainForm);
		return mainEditor;
	},
	
	loadFromJSON: function(data) {
		if (data.name != null) {
			this.name = data.name;
			this.selectedDataType = data.value;
			this.width = data.width;
		}
	},
	
	remove: function() {
		this.parentController.deleteColumn(this);
	},
	
	changeName: function(name) {
		this.name = this.columnNameText.value;
        
		this.parentController.renderGrid();
		this.highlight();
		
	},
	
	changeValue: function() {
		var selectedOption = this.fieldTypeSelection.options[this.fieldTypeSelection.selectedIndex];
		this.selectedDataType = selectedOption.value;
        jQuery('select[name="GridFieldSelection"]').parent().find('input[type="text"]').val(jQuery('select[name="GridFieldSelection"]').find('option:selected').html());
        this.changeName('');
        
		this.parentController.renderGrid();
		this.highlight();
		
	},
	
	showEditingFields: function() {
        var columnDiv = this.parentController.getColumnDiv(this);
		columnDiv.setStyle({background: '#c1c1e0'});
		this.editController.showEditingFields(this);
	},
	
	highlight: function() {
		var columnDiv = this.parentController.getColumnDiv(this);
		columnDiv.setStyle({background: '#c1c1e0'});
	},
	
	clearHighlight: function() {
		var columnDiv = this.parentController.getColumnDiv(this);
		columnDiv.setStyle({ background: '#f0f1f3'});
	},
	
	toJSON: function() {
		return {
			name: this.name,
			value: this.selectedDataType,
			width: this.width,
		}
	}
});

var SpaceFieldSet = Class.create(FieldSet, {
	initialize: function($super, editController, data) {
		$super(editController);
		this.minHeight = 2;
		this.maxHeight = 400;
		if (data == null)
			this.height = 6;
		else
		{
			this.height = data;
			//alert(data);
		}
		this.baseElement.setStyle({background: '#F0E9F4'});
		this.highlightColor = "#704582";
		var editElement = new EditElement("space");
		this.baseElement.appendChild(editElement.getBaseElement());
		editElement.setClickHandler(this.showEditingFields.bind(this));
		
		var hrDiv = new Element("div");
		hrDiv.addClassName("hr");
		this.baseElement.appendChild(hrDiv);
	},
	
	loadFromJSON: function(data) {
		//@Mathew 11-12-2010, Bruce asked that the height of the space elements not be changed every time he opened the report builder.
		this.baseElement.setStyle({height: data + "px"});
		//this.changeHeight(data/this.maxHeight);
		this.showEditingFields();
	},
	
	createEditingFields: function() {
		var mainEditor = new Element("div");
		mainEditor.addClassName("editFields");
		
		var mainForm = new Element("form");
		this.editController.createCommonFields(mainForm, "Edit Space", true);
		
		this.totalHeightTrack = new Element("div");
		this.totalHeightTrack.addClassName("totalTrack");
		
		var labelText = new Element("span");
		labelText.innerHTML = "Height: ";
		this.totalHeightTrack.appendChild(labelText);
		
		this.heightTrack = new Element("div");
		this.heightTrack.addClassName("track");
		this.heightTrack.id = generateUniqueID("heightTrack");
		this.totalHeightTrack.appendChild(this.heightTrack);
		
		this.heightHandle = new Element("div");
		this.heightHandle.addClassName("trackHandle");
		this.heightHandle.id = generateUniqueID("heightHandle");
		this.heightTrack.appendChild(this.heightHandle);
		
		this.debugHeight = new Element("div");
		//this.debugHeight.id = "debug1";
		this.totalHeightTrack.appendChild(this.debugHeight);
		
		mainForm.appendChild(this.totalHeightTrack);
		
		mainEditor.appendChild(mainForm);
		
		return mainEditor;
	},
	
	activateEditorComponents: function() {
		var debugHeight = this.debugHeight;
		new Control.Slider(this.heightHandle.id, this.heightTrack.id, {
			sliderValue: (this.height-this.minHeight)/(this.maxHeight-this.minHeight),
			onSlide: this.changeHeight.bind(this),
			onChange: this.changeHeight.bind(this)
		});
	},
	
	changeHeight: function(heightPercentage) {
		this.height = Math.floor((heightPercentage*(this.maxHeight-this.minHeight))+this.minHeight);
		this.debugHeight.innerHTML = "changed: " + this.height;
		this.baseElement.setStyle({height: this.height + "px"});
	},
	
	toJSON: function() {
		return {
			type: 'space',
			data: this.height
		}
	}
});

var PageBreakFieldSet = Class.create(FieldSet, {
	initialize: function($super, editController) {
		$super(editController);
		this.highlightColor = "#822525";
		
		this.baseElement.setStyle({background: '#F4E4E4', height: this.height+"px"});
		this.baseElement.innerHTML = "&nbsp;";
		
		var editElement = new EditElement("pageBreak");
		this.baseElement.appendChild(editElement.getBaseElement());
		editElement.setClickHandler(this.showEditingFields.bind(this));
		
		var hrDiv = new Element("div");
		hrDiv.addClassName("hr");
		this.baseElement.appendChild(hrDiv);
	},
	
	createEditingFields: function() {
		var mainEditor = new Element("div");
		mainEditor.addClassName("editFields");
		
		var mainForm = new Element("form");
		
		this.editController.createCommonFields(mainForm, "Edit Page Break", true);
		
		mainEditor.appendChild(mainForm);
		
		return mainEditor;
	},
	
	toJSON: function() {
		return {
			'type': 'pageBreak',
			'data': null
		}
	}
});

var EditElement = Class.create({
	initialize: function(type) {
		this.baseElement = new Element("a");
		this.baseElement.addClassName("editElement");
		this.baseElement.addClassName(type+"Edit");
		this.baseElement.href = "#";
		var innerElement = new Element("div");
		innerElement.innerHTML = "&nbsp;";
		this.baseElement.appendChild(innerElement);
	},
	
	getBaseElement: function() {
		return this.baseElement;
	},
	
	setClickHandler: function(handlingFunction) {
		this.baseElement.observe("click", handlingFunction);
	}
});

function generateUniqueID(prefix) {
	var uniqueID = null;
	do {
		uniqueID = prefix+Math.floor(Math.random()*10000);
	} while($(uniqueID) != null);
	return uniqueID;
}
