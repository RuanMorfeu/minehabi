    async move(){
        // Basic checks
        if (this.cur_status !== 'game' || !this.alife || !CHICKEN.alife || this.isMoving) return;
        
        var $chick = $('#chick'); 
        if (!$chick.length) return;
        var $state = $chick.attr('state'); 
        if( $state !== "idle" ) return;

        this.isMoving = true;
        
        // Call API
        const response = await this.callApi('play', 'POST', { game_id: this.gameId });
        
        if (!response || !response.success) {
            console.error('API Play Error', response);
            this.isMoving = false;
            return;
        }

        // --- Visual Update Logic ---
        var $cur_x = parseInt( $chick.css('left') );
        
        // Update local step from API response
        this.stp = response.step; 
        
        if( SETTINGS.volume.sound ){ SOUNDS.step.play(); }
        $chick.attr('state', "go");
        
        var $sector = $('.sector').eq(this.stp); // Sector we moved TO
        
        // Move chick visually
        var $nx = $cur_x + SETTINGS.segw + 'px';
        $chick.css('left', $nx);
        $chick.css('bottom', '50px');
        
        // Highlight sectors
        $('.sector').removeClass('active');
        if(this.stp > 0) $('.sector').eq(this.stp-1).addClass('complete');
        $sector.addClass('active');
        $sector.next().removeClass('far');
        $('.trigger', $sector).addClass('activated');
        
        // --- Handle Result ---
        if (response.status === 'lost') {
            // LOSE
            var $flame_x = $sector[0].offsetLeft;
            $('#fire').css('left', $flame_x + 'px').addClass('active');
            
            CHICKEN.alife = 0;
            $chick.attr('state', 'dead');
            $sector.removeClass('active').removeClass('complete').addClass('dead');
            $('.sector.finish').addClass('lose');
            
            // Show trap if available
            // if (response.trap_position) ...
            
            this.finish(false, true); // skipApi = true
        } else {
            // CONTINUE / WIN
            // Check if it's the finish line
            if( $sector.hasClass('finish') || response.status === 'won' ){
                $sector.addClass('win');
                // If status is won, backend auto-cashed out
                this.finish(true, true, response); 
            } else {
                // Just playing
                // Update cashout button value
                if (response.potential_win) {
                    $('#close_bet span').html(parseFloat(response.potential_win).toFixed(2) +' '+ SETTINGS.currency);
                }
            }
        }
        
        // Reset state after animation
        setTimeout(function(){
            if( CHICKEN.alife ){
                $chick.attr('state', 'idle');
            }
            GAME.isMoving = false;
            // Scroll battlefield if needed
            if(
                parseInt( $chick.css('left') ) > ( SETTINGS.w / 3 ) &&
                parseInt( $('#battlefield').css('left') ) > -( parseInt( $('#battlefield').css('width') ) - SETTINGS.w -SETTINGS.segw )
            ){
                var $field_x = parseInt( $('#battlefield').css('left') );
                var $nfx = $field_x - SETTINGS.segw +'px';
                $('#battlefield').css('left', $nfx);
            }
            
            GAME.update();
        }, 500);
    }
