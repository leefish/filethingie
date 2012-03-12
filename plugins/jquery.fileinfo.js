;(function($) {
  $.fn.ft_fileinfo = function(options) {
    return this.each(function() {
      $(this).find('span.addfileinfo').click(function(){
        parent = $(this).parents('tr');
        if (parent.find('p.fileinfo').length > 0) {
          // Description already present. Cancel.
          parent.find('p.fileinfo').remove();
        } else {
          $(this).after('<p class="fileinfo"><input type="text" size="30" name="fileinfo" value="Add description"></p>');
        }
      });
  		
    });
  };
})(jQuery);