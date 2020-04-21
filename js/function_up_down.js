			$(document).ready(function() {
				var $elem = $('.scrollwrap');
				
				$('#nav_up').fadeIn('slow');
				$('#nav_down').fadeIn('slow');  
				
				$(window).bind('scrollstart', function(){
					$('#nav_up,#nav_down').stop().animate({'opacity':'0.2'});
				});
				$(window).bind('scrollstop', function(){
					$('#nav_up,#nav_down').stop().animate({'opacity':'1'});
				});
				
				$('#nav_down').click(
					function nav_down(e) {
					   
						$('.scrollcontent').animate({scrollTop: $('.scrollwrap').height()}, 0);
					}
				);
				$('#nav_up').click(
					function nav_up(e) {
						$('.scrollcontent').animate({scrollTop: '0px'}, 0);
        			}
				);
            });	
            
            function nav_down(){
				$('.scrollcontent').animate({scrollTop: $('.scrollwrap').height()}, 0);
            }
         	
            function nav_up() {
				$('.scrollcontent').animate({scrollTop: '0px'}, 0);
    		}
