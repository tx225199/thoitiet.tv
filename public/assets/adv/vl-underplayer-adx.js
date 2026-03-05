
        var underPlayer = function() {
            (function() {
                var x = document.getElementById('vl-underplayer-adx');
                x.style.margin = '0 5px';
                x.style.textAlign = 'center';

                var htmlSmallScreen = `
                    
                `;

                var htmlLargeScreen = `
                    
                `;

                if (window.innerWidth < 300) {
                    x.innerHTML = '';
                } else {
                    x.innerHTML = window.innerWidth < 768 ? htmlSmallScreen : htmlLargeScreen;
                }
            })();
        }

        // Initialize the underPlayer function when the document is ready
        $(document).ready(function() {
            underPlayer();
        });
        