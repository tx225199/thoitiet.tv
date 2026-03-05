<style>
    .brand-list {
        width: 67%;
        margin: 0 auto;
    }

    .brands-section {
        max-width: 100%;
        margin: 0 auto;
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 5px 0px;
    }

    .brands-badge img {
        display: block;
        height: 74px;
        width: auto;
        object-fit: contain;
    }

    .brands-strip {
        position: relative;
        flex: 1 1 auto;
    }

    .brands-slider .brand-item {
        display: block;
        padding: 6px 10px;
    }

    .brands-slider {
        width: 95%;
        margin: 0 auto;
    }

    .brands-slider .brand-item img {
        display: block;
        width: 100%;
        height: 50px;
        object-fit: scale-down;
        margin: 0 auto;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, .12));
    }

    /* Ẩn arrow mặc định của slick */
    .brands-slider .slick-prev:before,
    .brands-slider .slick-next:before {
        display: none;
    }

    /* Mũi tên tam giác đỏ */
    .brands-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border: none;
        cursor: pointer;
        z-index: 5;
        opacity: .9;
    }

    .brands-arrow.prev {
        left: 6px;
        border-right: 20px solid #e53935;
    }

    .brands-arrow.next {
        right: 6px;
        border-left: 20px solid #e53935;
    }

    .brands-arrow:hover {
        opacity: 1;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .brands-badge img {
            height: 64px;
        }

        .brands-slider .brand-item img {
            width: 100px;
            height: 56px;
        }
    }

    @media (max-width: 768px) {
        .brands-section {
            flex-direction: column;
            align-items: stretch;
        }

        .brands-badge {
            display: flex;
            justify-content: center;
        }

        .brands-strip {
            padding: 8px 30px;
        }
    }

    @media (max-width: 480px) {
        .brands-slider .brand-item img {
            width: 86px;
            height: 50px;
        }
    }

    .brands-strip {
        width: 75%;
    }

    .brands-badge {
        width: 25%;
    }

    section.brands-section {
        display: flex;
    }

    .brands-slider-wrapper {
        position: relative;
        max-width: 100%;
        margin: 0 auto;
        padding: 10px 40px;
    }

    .brands-slider .slick-slide {
        text-align: center;
    }

    .brands-slider img {
        max-width: 120px;
        margin: 0 auto;
        transition: transform 0.3s ease;
    }

    .brands-slider img:hover {
        transform: scale(1.05);
    }

    /* ==== Arrow Buttons ==== */
    .brand-prev,
    .brand-next {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        width: 34px;
        height: 34px;
        cursor: pointer;
        z-index: 10;
        outline: none;
    }

    /* Viền xanh bo tròn */
    .brand-prev::before,
    .brand-next::before {
        content: "";
        display: block;
        width: 0;
        height: 0;
        margin: auto;
        border-style: solid;
        border-width: 8px 10px 8px 0;
        border-color: transparent #c80505 transparent transparent;
        background: transparent;
        position: relative;
    }

    /* Viền xanh bên ngoài */
    .brand-prev,
    .brand-next {
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    /* Mũi tên phải */
    .brand-next::before {
        border-width: 8px 0 8px 10px;
        border-color: transparent transparent transparent #c80505;
    }

    .brand-prev {
        left: 0;
    }

    .brand-next {
        right: 0;
    }

    @media screen and (max-width:600px) {
        section.brands-section {
            display: flex;
            width: 100%;
        }

        .brand-list {
            width: 100%;
        }

        .brands-badge {
            width: 100%;
        }

        .brands-strip {
            width: 100%;
        }

        .brands-badge img {
        height: auto;
        width: 70%;
    }

    }
</style>
<section class="brands-section">
    <div class="brands-badge">
        @if($isDesktop)
        <img src="/images/Top.png" alt="LÔ ĐỀ UY TÍN" loading="lazy">
        @else
        <img src="/images/topmobile.png" alt="LÔ ĐỀ UY TÍN" loading="lazy">
        @endif
    </div>

    <div class="brands-strip">
        <button class="brand-prev" aria-label="Previous"></button>

        <div class="brands-slider">

            @foreach ($brands as $brand)
                <a class="brand-item" href="{{ $brand->url }}">
                    <img src="{{ url('/storage/' . $brand->logo) }}" alt="{{ $brand->name }}" />
                </a>
            @endforeach
        </div>

        <button class="brand-next" aria-label="Next"></button>
    </div>

</section>
