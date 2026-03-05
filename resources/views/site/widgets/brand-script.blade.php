<script type="text/javascript" src="//code.jquery.com/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

<script>
    $(function() {
        var $slider = $('.brands-slider');
        var show = 7;

        var $items = $slider.children();
        var total = $items.length;

        if (total > 0 && total <= show) {
            var need = (show + 1) - total;
            for (var i = 0; i < need; i++) {
                $slider.append($items.eq(i % total).clone());
            }
        }

        $slider.slick({
            slidesToShow: show,
            slidesToScroll: 1,
            infinite: true,
            autoplay: true,
            autoplaySpeed: 3000,
            arrows: true,
            prevArrow: $('.brand-prev'),
            nextArrow: $('.brand-next'),
            responsive: [{
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 6
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 5
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 4
                    }
                }
            ]
        });
    });
</script>
