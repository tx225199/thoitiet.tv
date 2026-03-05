/**
 *  function show message
 * @param {*} type
 * @param {*} message
 *
 */
function showMessage(type, message){
    if(type == false){
        var html = "";
        html += "<div id='alert' class='alert alert-success alert-dismissible' style='display: block;'>";
        html += "<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>"+message+"</div>";
    }
    if(type == true){
        var html = "";
        html += "<div id='alert' class='alert alert-danger alert-dismissible' style='display: block;'>";
        html += "<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>"+message+"</div>";
    }
    $('#showMessage').html(html);

    $(".alert").fadeTo(100, 100).fadeOut(2000, function(){
        $(".alert").fadeOut(2000);
    });
}
/**
 * function replace ckeditor
 * @param array ids
 */
function replaceCkeditors(ids){
    $.each(ids,function(index, id){
        CKEDITOR.replace(id);
    })
}
const NumberInputUtil = {
    arrNumber: ["không", "một", "hai", "ba", "bốn", "năm", "sáu", "bảy", "tám", "chín"],
    readDozens: function (t, e) {
        let i = "",
            n = Math.floor(t / 10),
            o = t % 10;
        return n > 1 ? (i = " " + this.arrNumber[n] + " mươi", 1 == o && (i += " mốt")) : 1 == n ? (i = " mười", 1 == o && (i += " một")) : e && o > 0 && (i = " lẻ"), 5 == o && n >= 1 ? i += " lăm" : (o > 1 || 1 == o && 0 == n) && (i += " " + this.arrNumber[o]), i
    },
    readBlock: function (t, e) {
        let i = "",
            n = Math.floor(t / 100);
        return t %= 100, e || n > 0 ? (i = " " + this.arrNumber[n] + " trăm", i += this.readDozens(t, !0)) : i = this.readDozens(t, !1), i
    },
    readMillions: function (t, e) {
        let i = "",
            n = Math.floor(t / 1e6);
        return t %= 1e6, n > 0 && (i = this.readBlock(n, e) + " triệu", e = !0), thousand = Math.floor(t / 1e3), t %= 1e3, thousand > 0 && (i += this.readBlock(thousand, e) + " ngàn", e = !0), t > 0 && (i += this.readBlock(t, e)), i
    },
    numberToText: function (t) {
        if (0 == t) return this.arrNumber[0];
        let e = "",
            i = "";
        do {
            billion = t % 1e9, e = (t = Math.floor(t / 1e9)) > 0 ? this.readMillions(billion, !0) + i + e : this.readMillions(billion, !1) + i + e, i = " tỷ"
        } while (t > 0);
        return e + " đồng"
    },
    numberToLabel: function (t) {
        if ($(t).length > 0) {
            let e = $(t).val().replace(/\./g, ""),
                i = $(t).val().length > 0 ? this.numberToText(e) : "",
                n = $(t).parent().find(".lblTextNumber");
            n.length > 0 ? n.html(this.capitalize(i.trim())) : $("<label class='lblTextNumber' style='font-weight:300;font-style:italic;font-size: 14px;color: #c51414;'>" + this.capitalize(i.trim()) + "</label>").insertAfter(t)
        }
    },
    capitalize: function (t) {
        return "string" != typeof t ? "" : t.charAt(0).toUpperCase() + t.slice(1)
    }
};

onChangeInput = function (t) {
    let e = t.target.name;
    ("commissionText" != e || "commissionText" == e && $("#" + e).hasClass("disable-float")) && NumberInputUtil.numberToLabel("#" + e)
};

function changeToSlug(string) {

    //Đổi chữ hoa thành chữ thường
    slug = string.toLowerCase();

    //Đổi ký tự có dấu thành không dấu
    slug = slug.replace(/á|à|ả|ạ|ã|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ/gi, 'a');
    slug = slug.replace(/é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ/gi, 'e');
    slug = slug.replace(/i|í|ì|ỉ|ĩ|ị/gi, 'i');
    slug = slug.replace(/ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ/gi, 'o');
    slug = slug.replace(/ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự/gi, 'u');
    slug = slug.replace(/ý|ỳ|ỷ|ỹ|ỵ/gi, 'y');
    slug = slug.replace(/đ/gi, 'd');
    //Xóa các ký tự đặt biệt
    slug = slug.replace(/\`|\~|\!|\@|\#|\||\$|\%|\^|\&|\*|\(|\)|\+|\=|\,|\.|\/|\?|\>|\<|\'|\"|\:|\;|_/gi, '');
    //Đổi khoảng trắng thành ký tự gạch ngang
    slug = slug.replace(/ /gi, "-");
    //Đổi nhiều ký tự gạch ngang liên tiếp thành 1 ký tự gạch ngang
    //Phòng trường hợp người nhập vào quá nhiều ký tự trắng
    slug = slug.replace(/\-\-\-\-\-/gi, '-');
    slug = slug.replace(/\-\-\-\-/gi, '-');
    slug = slug.replace(/\-\-\-/gi, '-');
    slug = slug.replace(/\-\-/gi, '-');
    //Xóa các ký tự gạch ngang ở đầu và cuối
    slug = '@' + slug + '@';
    slug = slug.replace(/\@\-|\-\@|\@/gi, '');
    //In slug ra textbox có id “slug”
    return slug;
}

function checkRequired(id) {
    let temp = 0;
    $(id + ' .form-control:not(div)').each(function () {
        if ($(this).prop('required') === true) {
            let value = $(this).val();
            if (value.trim().length == 0) {
                temp++;
                $(this).css('border-color', 'red');
            } else {
                $(this).css('border-color', '#828bb2');
            }
        }
    });
}

$(document).on('keyup', '.is-number', function () {
    this.value = this.value.replace(/[^0-9]*/g, '');
})

$(document).ready(function () {
    // $('.select2').select2();

    // CKEDITOR.replace("description");

});