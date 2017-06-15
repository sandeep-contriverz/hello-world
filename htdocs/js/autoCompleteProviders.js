$( document ).ready(function() {
	/** 041116 Code to auto complete provider name in advanced search when typing **/
	$(document).on('click focus', ".providerAutoComp", function(e){
		$(document).unbind('keydown');
		$(this).select();
	});
	$(document).on('keyup', ".providerAutoComp", function(e){
		$('.fancybox-outer .fancybox-inner').css('overflow', 'visible');
		if(e.which == 40){
			// Move focus to first element in Div
			var providerList = $(".providerAutoCompList");
			var firstItem = providerList.find('a:first-child');
			firstItem.addClass('hover').focus();
			// Disable scrolling while in the auto-suggest
            $(document).keydown(function(e) {
                e.preventDefault();
            });
		} else {
			var searchValue = $(this).val();
			postString = 'action=providers&search=' + searchValue;
			$.post(
				'index.php', postString, function(data){
				if(! $.isEmptyObject(data)){
					providerList = $('.providerAutoCompList');
					providerList.find('a').remove(); // resets the list
					curClass = '';
					$.each(data, function(key, val) {
						if(curClass == 'odd'){
							curClass = 'even';
						} else {
							curClass = 'odd';
						}
						$('<a href="#" class="pitem ' + curClass + '" id="select-provider-' + val.id + '">' + val.name + '</a>').appendTo(providerList);
					});
					if(searchValue){
						$('.providerAutoCompList').show();
					} else {
						$('.providerAutoCompList').hide();
					}
				} else {
					$('.providerAutoCompList').hide();
				}
			});
		}
	});
    $(document).on('keyup', '.pitem', function(event){
	    var keyCode = event.which;
	    var currentNode = $(this);
	    switch(keyCode){
	        case 40:
	            nextNode = currentNode.next();
	            if(nextNode){
	                currentNode.removeClass('hover');
	                nextNode.focus().addClass('hover');
	                if(currentNode.attr('id') == $('.providerAutoCompList a:last-child').attr('id')){
	                    $('.providerAutoCompList').hide();
	                    $('.providerAutoComp').focus();
	                }
	                //console.log(currentNode.attr('id') + '=' + $(suggestDiv + ' a:first-child').attr('id'));
	            }
	            break;
	        case 38:
	            prevNode = currentNode.prev();
	            if(prevNode){
	                currentNode.removeClass('hover');
	                prevNode.focus().addClass('hover');
	                if(currentNode.attr('id') == $('.providerAutoCompList a:first-child').attr('id')){
	                    $('.providerAutoCompList').hide();
	                    $('.providerAutoComp').focus();
	                }
	                //console.log(currentNode.attr('id') + '=' + $(suggestDiv + ' a:first-child').attr('id'));
	            }
	            break;
	        case 13:
	        case 9:
	        	$(this).click();
	        	break;
	        default:
        }
    });
	$(document).on('click', ".providerAutoCompList a", function(e){
		e.preventDefault();
		var provider_id = $(this).attr('id').replace('select-provider-', '');
		var provider = $(this).text();
		$('.providerAutoComp').val(provider);
		$('#addProvider').attr('data-id', provider_id);
		$('.providerAutoCompList').hide();
		$('.providerAutoComp').focus();
	});
});