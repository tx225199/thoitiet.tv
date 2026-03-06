<header class="header-wrap header-type-12 ">
    <div class="header-wrap-inner">
        <div class="container">
            <div class="no-margin-ads">
            </div>
        </div>
        <div class="header-top-row">
            <div class="container">
                <div class="general-header-inner">
                    <div class="header-column-left">
                        <a title="Dự báo thời tiết 63 tỉnh và thành phố chính xác nhất Việt Nam" href="/"
                            class="header-logo">

                            <img alt="trang chu xo so" class="header-logo-img"
                                src="{{ isset($settings['logo']) && $settings['logo'] != '' ? sourceSetting($settings['logo']) : '/images/logo.svg' }}">

                        </a>
                        <div class="cursor-pointer text-center show-search-form">

                            <div class="tdb-drop-down-search" aria-labelledby="td-header-search-button">
                                <div class="tdb-drop-down-search-inner">
                                    <form method="get" class="tdb-search-form">
                                        <div class="tdb-search-form-inner">
                                            <input class="tdb-head-search-form-input" type="text" value=""
                                                name="keyword" placeholder="Nhập tên địa điểm..." autocomplete="off"
                                                city="" district="">
                                            <ul class="list-search-location" style="display: none;"></ul>
                                            <button class="wpb_button wpb_btn-inverse btn tdb-head-search-form-btn"
                                                type="submit">
                                                <i class="far fa-search"></i>
                                            </button>
                                        </div>
                                    </form>
                                    <div class="tdb-aj-search"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="header-column-right">
                        <div class="header-select-city">
                            <div class="header-select-unit">
                                Đơn vị:
                                <select name="select-degree" id="select_degree">
                                    <option value="" selected="">°C</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="header-bottom">
            <div class="container">
                <div class="general-header-inner">
                    <div class="header-column-left">
                        <div class="header-select-city">
                            <div class="menu-select-city">
                                <div class="menu-select-city__title" data-city="">
                                    <svg width="20" height="21" viewBox="0 0 20 21" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">

                                        <path
                                            d="M1.9082 6.98323V15.0916C1.9082 16.6749 3.0332 17.3249 4.39987 16.5416L6.3582 15.4249C6.7832 15.1832 7.49154 15.1582 7.9332 15.3832L12.3082 17.5749C12.7499 17.7916 13.4582 17.7749 13.8832 17.5332L17.4915 15.4666C17.9499 15.1999 18.3332 14.5499 18.3332 14.0166V5.90823C18.3332 4.3249 17.2082 3.6749 15.8415 4.45823L13.8832 5.5749C13.4582 5.81657 12.7499 5.84156 12.3082 5.61656L7.9332 3.43323C7.49154 3.21656 6.7832 3.23323 6.3582 3.4749L2.74987 5.54157C2.2832 5.80823 1.9082 6.45823 1.9082 6.98323Z"
                                            stroke="white" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round"></path>
                                        <path d="M7.13281 3.83325V14.6666" stroke="white" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round"></path>
                                        <path d="M13.1074 6.0166V17.1666" stroke="white" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>

                                    Tỉnh - Thành phố
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14"
                                        viewBox="0 0 384 512"><!-- Font Awesome Pro 6.0.0-alpha2 by @fontawesome  - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) -->
                                        <path
                                            d="M362.71 203.889L202.719 347.898C196.594 353.367 187.407 353.367 181.282 347.898L21.292 203.889C14.729 197.982 14.198 187.857 20.104 181.295C26.376 174.377 36.499 174.512 42.729 180.107L192.001 314.475L341.272 180.107C347.866 174.23 357.96 174.746 363.897 181.295C369.803 187.857 369.272 197.982 362.71 203.889Z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="menu-select-city__list">
                                    <ul>
                                        <li data-city="ha-noi">Hà Nội</li>
                                        <li data-city="ha-giang">Hà Giang</li>
                                        <li data-city="cao-bang">Cao Bằng</li>
                                        <li data-city="bac-kan">Bắc Kạn</li>
                                        <li data-city="tuyen-quang">Tuyên Quang</li>
                                        <li data-city="lao-cai">Lào Cai</li>
                                        <li data-city="dien-bien">Điện Biên</li>
                                        <li data-city="lai-chau">Lai Châu</li>
                                        <li data-city="son-la">Sơn La</li>
                                        <li data-city="yen-bai">Yên Bái</li>
                                        <li data-city="hoa-binh">Hoà Bình</li>
                                        <li data-city="thai-nguyen">Thái Nguyên</li>
                                        <li data-city="lang-son">Lạng Sơn</li>
                                        <li data-city="quang-ninh">Quảng Ninh</li>
                                        <li data-city="bac-giang">Bắc Giang</li>
                                        <li data-city="phu-tho">Phú Thọ</li>
                                        <li data-city="vinh-phuc">Vĩnh Phúc</li>
                                        <li data-city="bac-ninh">Bắc Ninh</li>
                                        <li data-city="hai-duong">Hải Dương</li>
                                        <li data-city="hai-phong">Hải Phòng</li>
                                        <li data-city="hung-yen">Hưng Yên</li>
                                        <li data-city="thai-binh">Thái Bình</li>
                                        <li data-city="ha-nam">Hà Nam</li>
                                        <li data-city="nam-dinh">Nam Định</li>
                                        <li data-city="ninh-binh">Ninh Bình</li>
                                        <li data-city="thanh-hoa">Thanh Hóa</li>
                                        <li data-city="nghe-an">Nghệ An</li>
                                        <li data-city="ha-tinh">Hà Tĩnh</li>
                                        <li data-city="quang-binh">Quảng Bình</li>
                                        <li data-city="quang-tri">Quảng Trị</li>
                                        <li data-city="thua-thien-hue">Thừa Thiên Huế</li>
                                        <li data-city="da-nang">Đà Nẵng</li>
                                        <li data-city="quang-nam">Quảng Nam</li>
                                        <li data-city="quang-ngai">Quảng Ngãi</li>
                                        <li data-city="binh-dinh">Bình Định</li>
                                        <li data-city="phu-yen">Phú Yên</li>
                                        <li data-city="khanh-hoa">Khánh Hòa</li>
                                        <li data-city="ninh-thuan">Ninh Thuận</li>
                                        <li data-city="binh-thuan">Bình Thuận</li>
                                        <li data-city="kon-tum">Kon Tum</li>
                                        <li data-city="gia-lai">Gia Lai</li>
                                        <li data-city="dak-lak">Đắk Lắk</li>
                                        <li data-city="dak-nong">Đắk Nông</li>
                                        <li data-city="lam-dong">Lâm Đồng</li>
                                        <li data-city="binh-phuoc">Bình Phước</li>
                                        <li data-city="tay-ninh">Tây Ninh</li>
                                        <li data-city="binh-duong">Bình Dương</li>
                                        <li data-city="dong-nai">Đồng Nai</li>
                                        <li data-city="ba-ria-vung-tau">Bà Rịa - Vũng Tàu</li>
                                        <li data-city="ho-chi-minh">Hồ Chí Minh</li>
                                        <li data-city="long-an">Long An</li>
                                        <li data-city="tien-giang">Tiền Giang</li>
                                        <li data-city="ben-tre">Bến Tre</li>
                                        <li data-city="tra-vinh">Trà Vinh</li>
                                        <li data-city="vinh-long">Vĩnh Long</li>
                                        <li data-city="dong-thap">Đồng Tháp</li>
                                        <li data-city="an-giang">An Giang</li>
                                        <li data-city="kien-giang">Kiên Giang</li>
                                        <li data-city="can-tho">Cần Thơ</li>
                                        <li data-city="hau-giang">Hậu Giang</li>
                                        <li data-city="soc-trang">Sóc Trăng</li>
                                        <li data-city="bac-lieu">Bạc Liêu</li>
                                        <li data-city="ca-mau">Cà Mau</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="header-menu-link">
                                @foreach ($genres as $g)
                                    <a href="{{route('genre', ['slug' => $g->slug])}}">
                                        {!! pickBySlug($g->slug) !!}
                                        {{ $g->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="header-column-right">
                        <div class="header-select-city">

                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

</header>
