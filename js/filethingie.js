;(function($) {
  /** 
   * Upload functions.
   **/
  $.fn.ft_upload = function(options) {
    return this.each(function() {
      $(this).find('input[type=file]').change(function(){
  			$(this).parent().after("<h3>"+options.header+"</h3><ul id=\"files_list\"></ul>");
        uploadCallback(this, options);
  		});
  		$(this).find("#uploadbutton input").click(function(){
  		  // Hide upload button.
        $("#uploadbutton").hide();
        $("#create .info").hide();
        $("#uploadbutton").after("<p class='error'>"+options.upload+"</p>");
  		});
  		
    });
  };
	function niceFileName(name) { // Truncates a file name to 20 characters.
    var noext = name;
    var ext = '';
    if (name.match('.')) {
      noext = name.substr(0, name.lastIndexOf('.'));
      ext = name.substr(name.lastIndexOf('.'));
    }
    if (noext.length > 20) {
      name = noext.substr(0, 20)+'...';
      if (ext != '') {
        name = name+ '.' +ext;
      }
    }
    return name;
	}
	function uploadCallback(obj, options) { // Gets fired every time a new file is selected for upload.
		// Safari has a weird bug so we can't hide the object in the normal fashion:
		$(obj).addClass("safarihide");
		// Make random number: 
		var d = new Date();
		var t = d.getTime();
		$(obj).parent().prepend('<input type="file" size="12" class="upload" name="localfile-'+t+'" id="localfile-'+t+'" />');
		$('#localfile-'+t).change(function() {uploadCallback(this, options)});
		if (obj.value.indexOf("/") != -1) {
			var v = obj.value.substr(obj.value.lastIndexOf("/")+1);
		} else if (obj.value.indexOf("\\") != -1) {
			var v = obj.value.substr(obj.value.lastIndexOf("\\")+1);			
		} else {
			var v = obj.value;
		}
		if(v != '') {
			$("#files_list").append('<li>'+niceFileName(v)+" <span class=\"error\" title=\""+options.cancel+"\">[x]</span></li>").find("span").click(function(){
				$(this).parent().remove();
				$(obj).remove();
				return true;
			});
		}
	};
	/** 
   * File list functions.
   **/
	$.fn.ft_filelist = function(options) {
    return this.each(function() {
      // Make background color on table rows show up nicely on hover
  		$(this).find("tr").hover(
        function(){$(this).toggleClass('rowhover');},
        function(){$(this).toggleClass('rowhover')}
  		);
      // Hover on the diamond.
      $(this).find("td.details span.show").hover(
        function(){$(this).toggleClass('hover')}, 
        function(){$(this).toggleClass('hover')}
      );
      // Hide file details on second diamond click.
  		$(this).find("td.details span.hide").hover(
  		  function(){$(this).toggleClass('hover')}, 
  		  function(){$(this).toggleClass('hover')}
  		).click(function(){
  			$(this).parent().parent().next().remove();
  			$(this).hide();
  			$(this).prev().show();
  		});
  		// Build file details box on diamond click.
      $(this).find("td.details span.show").click(function(){
        if ($(this).hasClass("writeable")) {
          $(this).parent().parent().after("<tr class='filedetails'></tr>");
          // Default actions.
    			var actions = {
    			  rename: options.rename_link,
    			  move: options.move_link,
    			  del: options.del_link
    			};
    			// Add 'duplicate' for files only.
    			if ($(this).parent().parent().hasClass('file')) {
  			    actions.duplicate = options.duplicate_link;
  			  }
  			  // Add unzip.
  			  if (
  			    $(this).parent().parent().find("td.name").text().substr(
  			      $(this).parent().parent().find("td.name").text().lastIndexOf(".")+1
  			    ).toLowerCase() == 'zip') {
  			    actions.unzip = options.unzip_link;
  			  }
  			  // Add chmod and symlink.
  			  if (options.advancedactions == 'true') {
  			    actions.chmod = options.chmod_link;
  			    actions.symlink = options.symlink_link;
    			}
    			
    			// Add other options.
          for (i in options.fileactions) {
            if ($(this).hasClass(i)) {
              actions[i] = options.fileactions[i].link;
            }
          }

    			// Convert actions list into html list.
    			var list = '';
    			for (i in actions) {
    			  list = list+'<li class="'+i+'">'+actions[i]+'</li>';
    			}
    			// Append file actions box.
    			var filename = $(this).parent().parent().find("td.name").text();
    			$(this).parent().parent().next("tr.filedetails").append("<td colspan=\"3\"><ul class=\"navigation\">"+list+"</ul><form method=\"post\" action=\""+options.formpost+"\"><div><label for='newvalue'>"+options.rename+"</label><input type=\"text\" value=\""+filename+"\" size=\"18\" class='newvalue' name=\"newvalue\" /><input type=\"hidden\" value=\""+filename+"\" class='file' name=\"file\" /><input type=\"submit\" class='submit' value=\""+options.ok+"\" /><input type=\"hidden\" name=\"dir\" value=\""+options.directory+"\" /><input type=\"hidden\" name=\"act\" class=\"act\" value=\"rename\" /></div></form></td>")
    			.find("li").hover(
    			  function(){$(this).toggleClass('hover')}, 
    			  function(){$(this).toggleClass('hover')}
    			).click(function(){
    			  showOption(this, options);
    			});

  				// Focus on new value field.
  				$(this).parent().parent().next("tr.filedetails").find("input.newvalue").get(0).focus();
  				$(this).parent().parent().next("tr.filedetails").find("input.newvalue").get(0).select();
				
  				// Hide one diamond, show the other.
  				$(this).hide();
    			$(this).next().show();
    		}
      });
    });
  };
  function showOption(obj, options) { // Shows a selection from the file details menu.
    var section = $(obj).attr('class').replace('hover', '').replace(' ', '');
		var act = $(obj).parent().parent().find("input.act");
		var newval = $(obj).parent().parent().find("input.newvalue");
		var file = $(obj).parent().parent().find("input.file").val();
		var label = $(obj).parent().parent().find("label");
		var submit = $(obj).parent().parent().find("input.submit");
		// Un-select all <li>
		$(obj).parent().find("li").removeClass("selected");
		$(obj).addClass("selected");
		// Show/hide the new value field and change the text of the submit button.
		if (section.match('rename') || section.match('move') || section.match('duplicate') || section.match('chmod') || section.match('symlink')) {
			// Show new value field.
			newval.show();
			label.empty();
			submit.show();
			if (section.match('rename')) {
				label.append(options.rename);
				newval.val(file);
    		act.val('rename');
			} else if (section.match('move')) {
				label.append(options.move);
				newval.val("");
    		act.val('move');
			} else if (section.match('duplicate')) {
				label.append(options.duplicate);
				if (file.indexOf(".") != -1) {
					newval.val(file.substring(0, file.lastIndexOf("."))+"(copy)"+file.substr(file.lastIndexOf(".")));
				} else {
					newval.val(file+"(copy)");
				}
    		act.val('duplicate');
			} else if (section.match('chmod')) {
				label.append(options.chmod);
				newval.val($(obj).parents('tr').prev().find('td.details span.show').attr('class').match(/perm-[0-9]../).toString().substr(5));
    		act.val('chmod');
			} else if (section.match('symlink')) {
				label.append(options.symlink);
				if (file.indexOf(".") != -1) {
					newval.val(file.substring(0, file.lastIndexOf("."))+"(link)"+file.substr(file.lastIndexOf(".")));
				} else {
					newval.val(file+"(link)");
				}
    		act.val('symlink');
			}
			submit.val(options.ok);
			// Set focus on new value field.
			newval.get(0).focus();
			newval.get(0).select();
		} else if (section.match('del')) {
			// Hide new value field.
			newval.hide();
			label.empty();
			if (!$(obj).parents('tr.filedetails').prev().find('td.details span.show').eq(0).hasClass('empty') && $(obj).parents('tr.filedetails').prev().find('td.details span.show').eq(0).hasClass('dir')) {
  			label.append(options.del_warning);
  			submit.hide();
			} else {
  			label.append(options.del);
			}
			submit.val(options.del_button);
  		act.val('delete');
		} else if (section.match('unzip')) {
  		// Hide new value field.
  		newval.hide();
  		label.empty();
  		label.append(options.unzip);
  		submit.val(options.unzip_button);
  		submit.show();
  		act.val('unzip');
    } else {
      // See if plugin has defined this section.
      if (options.fileactions[section]) {
        if (options.fileactions[section].type == 'sendoff') {
           // Simple sendoff. Hide new value field.
           newval.hide();
           label.empty();
           label.append(options.fileactions[section].text);
           submit.val(options.fileactions[section].button)
           act.val(section);
        }
      }
    }
	};
	/** 
   * Search functions.
   **/
  $.fn.ft_search = function(options) {
    return this.each(function() {
  		$("#searchform").submit(function(){
  		  $("#dosearch").click();
  			return false;
  		});
      $("#dosearch").click(function(){
  			$("#searchresults").empty();
  			$("#searchresults").prepend("<h3>"+options.header+"</h3>").append("<dl id='searchlist'><dt class='error'>"+options.loading+"</dt></dl>");
  			$.post(options.formpost, {method:'ajax', act: 'search', q:$("#q").val(), type: $("#type").is(":checked"), dir:options.directory}, function(data){
  				$("#searchlist").empty();
  				$("#searchlist").append(data);
  				return false;
  			});
  			return false;
      });      
    });
  };
  

})(jQuery);