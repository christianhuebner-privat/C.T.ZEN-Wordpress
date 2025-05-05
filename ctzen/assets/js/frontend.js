jQuery(function($){
    $('.ctz-theme-list a').click(function(){
      $('.ctz-theme-list li').removeClass('active');
      $(this).parent().addClass('active');
    });
  });
  