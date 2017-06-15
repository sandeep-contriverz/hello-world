$(document).ready(function(){
	// Auto suggest code
	$("#referralDiv, #followUpDiv, .setting-autosuggest").on('click', ".autosuggest-input", function(e){
		$(document).unbind('keydown');
		$(this).select();
	});
	$("#referralDiv, #followUpDiv, .setting-autosuggest").on('blur', ".autosuggest-input", function(e){
		if($(this).attr('name') == 'provider[name]') {
			return false;
		}
		var autoSuggestValue = $(this).parent().find('.autosuggest-value');
		if (!autoSuggestValue.val()) {
			e.currentTarget.value = '';
		} else {
			
			var searchValue = $(this).val();
			postString = $(this).data('query-string') + searchValue;
			$.post(
				'index.php', postString, function(data){
				if($.isEmptyObject(data)){
					//e.currentTarget.value = '';
				}
			});
		}
	});
	$("#referralDiv, #followUpDiv, .setting-autosuggest").on('keydown', ".autosuggest-input", function(e){
		var keycode = e.which;
		if(keycode == 13){
			e.preventDefault();
		}
		if(keycode == 9){
			var listContainer = $(this).next('.autosuggest-selection-container');
			listContainer.hide();
		}
	});
	$("#referralDiv, #followUpDiv, .setting-autosuggest").on('keyup', ".autosuggest-input", function(e){
		var keycode = e.which;
		var autoSuggestInput = $(this);
		var listContainer = $(this).next('.autosuggest-selection-container');
		var autoSuggestValue = $(this).prev('.autosuggest-value');
		if(keycode == 40){
			// Move focus to first element in Div
			var firstItem = listContainer.find('a:first-child');
			firstItem.addClass('hover').focus();
			// Disable scrolling while in the auto-suggest
            $(document).keydown(function(e) {
                e.preventDefault();
            });
		} else {
			if(keycode == 13 || keycode == 9){
				// check to see if we have an id
				// if we do, then submit the form
				// var id = $('#referralListSearchId').val();
				// submit the form
				// $('#create-report').click();
				return;
			}
			var searchValue = $(this).val();
			
			postString = autoSuggestInput.data('query-string') + searchValue;
			$.post(
				'index.php', postString, function(data){
				if(! $.isEmptyObject(data)){
					listContainer.find('a').remove(); // resets the list
					curClass = '';
					$.each(data, function(key, val) {
						if(curClass == 'odd'){
							curClass = 'even';
						} else {
							curClass = 'odd';
						}
						if( val.hasOwnProperty('type') ){
							$('<a href="#" class="pitem ' + curClass + '" data-id="' + val.id + '" data-type="' + val.type + '">' + val.name +'</a>').appendTo(listContainer);
						}
						else{
							$('<a href="#" class="pitem ' + curClass + '" data-id="' + val.id + '" >' + val.name +'</a>').appendTo(listContainer);
						}
					});
					if(searchValue){
						autoSuggestValue.val(' ');
						listContainer.show();
					} else {
						listContainer.hide();
					}
				} else {
					listContainer.hide();
				}
			});
		}
	});

    $('#referralDiv, #followUpDiv, .setting-autosuggest').on('keydown', '.pitem', function(event){
    	event.preventDefault();
	    var keyCode = event.which;
		var listContainer = $(this).parent('.autosuggest-selection-container');
	    var autoSuggestInput = listContainer.prev('.autosuggest-input');
	    var currentNode = $(this);
	    switch(keyCode){
	        case 40:
	             // stop when we get to the end.
	        	if(currentNode.attr('data-id') == listContainer.find('a:last-child').attr('data-id')){
	        		break;
	        	}
	            nextNode = currentNode.next();
	            /*if(nextNode){
	                currentNode.removeClass('hover');
	                nextNode.focus().addClass('hover');
	            }*/
	            break;
	        case 38:
	            prevNode = currentNode.prev();
	            if(prevNode){
	                /*currentNode.removeClass('hover');
	                prevNode.focus().addClass('hover');*/
	                if(currentNode.attr('data-id') == listContainer.find('a:first-child').attr('data-id')){
	                    listContainer.hide();
	                    autoSuggestInput.focus();
	                }
	            }
	            break;
	        case 13:
	        case 9:
	        	$(this).click();
	        	break;
	        default:
        }
    });
	$("#referralDiv, #followUpDiv, .setting-autosuggest").on('click', ".autosuggest-selection-container a", function(e){
		e.preventDefault();
		var listContainer = $(this).parent('.autosuggest-selection-container');
	    var autoSuggestInput = listContainer.prev('.autosuggest-input');
	    var autoSuggestValue = autoSuggestInput.parent().find('.autosuggest-value');
		if( $(this).attr('data-type') !== undefined ){
			var autoSuggestType = autoSuggestInput.prev('.autosuggest-type');
			var type = $(this).attr('data-type');
			autoSuggestType.val(type);
		}
		var id = $(this).attr('data-id');
		
		var name = $(this).text();
		
		autoSuggestInput.val(name);
		autoSuggestValue.val(id);			
		
		autoSuggestValue.trigger('change');
		listContainer.hide();
		autoSuggestInput.focus();
		$(document).unbind('keydown');
	});
	// End auto suggest
});
