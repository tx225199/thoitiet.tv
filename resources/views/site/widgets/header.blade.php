<header class="header-wrap header-type-12 ">
    <form id="logout-form" action="https://thoitiet.tv/logout" method="POST" style="display: none;">
       @csrf
    </form>
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
                            <img height="75"
                                src="/uploads/images/setting/Mazart/2024/08/14/thoitietvn-1000x500-3-1723606827.png?ver=1754472522"
                                alt="">
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
                                    <option value="f">°F</option>
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
                                <a href="https://thoitiet.tv/tin-tong-hop/tin-thoi-tiet"><svg width="15"
                                        height="16" viewBox="0 0 20 21" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M10.0007 15.9166C12.9922 15.9166 15.4173 13.4915 15.4173 10.4999C15.4173 7.50838 12.9922 5.08325 10.0007 5.08325C7.00911 5.08325 4.58398 7.50838 4.58398 10.4999C4.58398 13.4915 7.00911 15.9166 10.0007 15.9166Z"
                                            stroke="white" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round"></path>
                                        <path
                                            d="M15.9493 16.4501L15.841 16.3417M15.841 4.65841L15.9493 4.55008L15.841 4.65841ZM4.04935 16.4501L4.15768 16.3417L4.04935 16.4501ZM9.99935 2.23341V2.16675V2.23341ZM9.99935 18.8334V18.7667V18.8334ZM1.73268 10.5001H1.66602H1.73268ZM18.3327 10.5001H18.266H18.3327ZM4.15768 4.65841L4.04935 4.55008L4.15768 4.65841Z"
                                            stroke="white" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round"></path>
                                    </svg> Tin thời tiết</a>
                                <a href="https://thoitiet.tv/tin-tong-hop">
                                    <svg width="15" height="16" viewBox="0 0 20 21" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M10 0.5C10.3452 0.5 10.625 0.779822 10.625 1.125V1.76697C15.7812 2.04761 20 5.77609 20 10.5C20 10.5 20 11.125 19.375 11.125C19.1891 11.125 18.9348 10.9436 18.9348 10.9436L18.9295 10.9385C18.9237 10.933 18.9134 10.9234 18.8987 10.9101C18.8693 10.8836 18.8227 10.8429 18.76 10.7927C18.6342 10.6921 18.4461 10.5551 18.2055 10.4177C17.7218 10.1412 17.0476 9.875 16.25 9.875C15.4524 9.875 14.7782 10.1412 14.2945 10.4177C14.0539 10.5551 13.8658 10.6921 13.74 10.7927C13.6773 10.8429 13.6307 10.8836 13.6013 10.9101C13.5866 10.9234 13.5763 10.933 13.5705 10.9385L13.5659 10.943C13.5659 10.943 13.3113 11.125 13.125 11.125C12.9391 11.125 12.6848 10.9436 12.6848 10.9436L12.6795 10.9385C12.6737 10.933 12.6634 10.9234 12.6487 10.9101C12.6193 10.8836 12.5727 10.8429 12.51 10.7927C12.3842 10.6921 12.1961 10.5551 11.9555 10.4177C11.604 10.2168 11.152 10.0213 10.625 9.92944V17.375H10C10.625 17.375 10.625 17.375 10.625 17.375L10.625 17.3763L10.625 17.3779L10.625 17.3818L10.6248 17.3923L10.624 17.4239C10.6232 17.4496 10.6216 17.4844 10.6185 17.527C10.6125 17.6119 10.6006 17.7292 10.5774 17.8684C10.5315 18.1437 10.4386 18.5203 10.2465 18.9045C10.0532 19.2912 9.7534 19.6968 9.292 20.0044C8.82669 20.3146 8.23437 20.5 7.5 20.5C6.76563 20.5 6.17331 20.3146 5.708 20.0044C5.2466 19.6968 4.94681 19.2912 4.75348 18.9045C4.56138 18.5203 4.46845 18.1437 4.42257 17.8684C4.39938 17.7292 4.38754 17.6119 4.38147 17.527C4.37843 17.4844 4.37681 17.4496 4.37596 17.4239L4.37516 17.3923L4.37503 17.3818L4.37501 17.3779L4.375 17.3763C4.375 17.3763 4.375 17.375 5 17.375H4.375V16.75C4.375 16.4048 4.65482 16.125 5 16.125C5.34518 16.125 5.625 16.4048 5.625 16.75V17.3723L5.62526 17.3823C5.62563 17.3932 5.62645 17.4121 5.62829 17.4379C5.63199 17.4896 5.63969 17.5676 5.65556 17.6629C5.6878 17.8563 5.75111 18.1047 5.87152 18.3455C5.9907 18.5838 6.15965 18.8032 6.40137 18.9643C6.63919 19.1229 6.98437 19.25 7.5 19.25C8.01563 19.25 8.36081 19.1229 8.59863 18.9643C8.84035 18.8032 9.00931 18.5838 9.12848 18.3455C9.24889 18.1047 9.3122 17.8563 9.34444 17.6629C9.36031 17.5676 9.36801 17.4896 9.37171 17.4379C9.37355 17.4121 9.37437 17.3932 9.37474 17.3823L9.375 17.3723V9.92944C8.84805 10.0213 8.39596 10.2168 8.04446 10.4177C7.80391 10.5551 7.61584 10.6921 7.49004 10.7927C7.42734 10.8429 7.38068 10.8836 7.3513 10.9101C7.33663 10.9234 7.32632 10.933 7.32053 10.9385L7.31541 10.9435C7.31541 10.9435 7.06103 11.125 6.875 11.125C6.68906 11.125 6.43477 10.9436 6.43477 10.9436L6.42947 10.9385C6.42368 10.933 6.41337 10.9234 6.3987 10.9101C6.36932 10.8836 6.32266 10.8429 6.25996 10.7927C6.13416 10.6921 5.94609 10.5551 5.70554 10.4177C5.22183 10.1412 4.54764 9.875 3.75 9.875C2.95236 9.875 2.27817 10.1412 1.79446 10.4177C1.55391 10.5551 1.36584 10.6921 1.24004 10.7927C1.17734 10.8429 1.13068 10.8836 1.1013 10.9101C1.08663 10.9234 1.07632 10.933 1.07053 10.9385L1.06542 10.9435C1.06542 10.9435 0.811031 11.125 0.625 11.125C0 11.125 0 10.5 0 10.5C0 5.77609 4.21876 2.04761 9.375 1.76697V1.125C9.375 0.779822 9.65482 0.5 10 0.5ZM8.22132 3.15398C4.68034 3.77767 1.98417 6.22656 1.3782 9.22126C1.97536 8.91152 2.78718 8.625 3.75 8.625C4.7571 8.625 5.599 8.93848 6.20283 9.26412C6.22205 8.39076 6.34142 7.3575 6.63244 6.31616C6.9355 5.23172 7.4332 4.11036 8.22132 3.15398ZM7.45199 9.31662C8.06108 8.97305 8.93883 8.625 10 8.625C11.0612 8.625 11.9389 8.97305 12.548 9.31662C12.5326 8.5361 12.4275 7.59672 12.1637 6.65259C11.7923 5.32359 11.1223 4.03804 10 3.14751C8.8777 4.03804 8.20772 5.32359 7.83631 6.65259C7.57246 7.59672 7.46735 8.5361 7.45199 9.31662ZM11.7787 3.15398C12.5668 4.11036 13.0645 5.23172 13.3676 6.31616C13.6586 7.3575 13.778 8.39076 13.7972 9.26412C14.401 8.93849 15.2429 8.625 16.25 8.625C17.2128 8.625 18.0246 8.91152 18.6218 9.22126C18.0158 6.22656 15.3197 3.77767 11.7787 3.15398Z"
                                            fill="white"></path>
                                    </svg>
                                    Tin tổng hợp</a>
                                <a href="https://thoitiet.tv/widget"><svg width="15" height="16"
                                        viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M18.3333 7.60008V3.81675C18.3333 2.64175 17.8 2.16675 16.475 2.16675H13.1083C11.7833 2.16675 11.25 2.64175 11.25 3.81675V7.59175C11.25 8.77508 11.7833 9.24175 13.1083 9.24175H16.475C17.8 9.25008 18.3333 8.77508 18.3333 7.60008Z"
                                            stroke="white" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round"></path>
                                        <path
                                            d="M18.3333 16.975V13.6083C18.3333 12.2833 17.8 11.75 16.475 11.75H13.1083C11.7833 11.75 11.25 12.2833 11.25 13.6083V16.975C11.25 18.3 11.7833 18.8333 13.1083 18.8333H16.475C17.8 18.8333 18.3333 18.3 18.3333 16.975Z"
                                            stroke="white" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round"></path>
                                        <path
                                            d="M8.74935 7.60008V3.81675C8.74935 2.64175 8.21602 2.16675 6.89102 2.16675H3.52435C2.19935 2.16675 1.66602 2.64175 1.66602 3.81675V7.59175C1.66602 8.77508 2.19935 9.24175 3.52435 9.24175H6.89102C8.21602 9.25008 8.74935 8.77508 8.74935 7.60008Z"
                                            stroke="white" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round"></path>
                                        <path
                                            d="M8.74935 16.975V13.6083C8.74935 12.2833 8.21602 11.75 6.89102 11.75H3.52435C2.19935 11.75 1.66602 12.2833 1.66602 13.6083V16.975C1.66602 18.3 2.19935 18.8333 3.52435 18.8333H6.89102C8.21602 18.8333 8.74935 18.3 8.74935 16.975Z"
                                            stroke="white" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round"></path>
                                    </svg>Widget</a>
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
