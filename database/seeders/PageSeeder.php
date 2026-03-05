<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        Page::insert([
            [
                'slug' => 'contact',
                'title' => 'Liên hệ',
                'meta_title' => 'Liên hệ Xổ Số VN - Gửi phản hồi & hợp tác | xosovn.net',
                'meta_description' => 'Liên hệ xosovn.net để gửi phản hồi, đóng góp hoặc hợp tác quảng cáo. Chúng tôi luôn lắng nghe ý kiến từ người dùng để cải thiện dịch vụ tốt hơn.',
                'content' => '
                    <h2>Liên hệ Xổ Số VN</h2>
                    <p>Nếu bạn có thắc mắc, phản hồi hoặc muốn hợp tác quảng cáo, vui lòng liên hệ với chúng tôi qua các kênh dưới đây:</p>
                    <p><strong>Email:</strong> support@xosovn.net</p>
                    <p><strong>Hotline:</strong> (+84) 909 999 222</p>
                    <p><strong>Địa chỉ:</strong> Tầng 5, Toà nhà CTC, 82 Duy Tân, Cầu Giấy, Hà Nội</p>
                    <p>Đội ngũ <strong>xosovn.net</strong> luôn sẵn sàng hỗ trợ bạn 24/7 để mang đến trải nghiệm tốt nhất khi tra cứu kết quả xổ số.</p>
                ',
            ],
            [
                'slug' => 'term',
                'title' => 'Điều khoản sử dụng',
                'meta_title' => 'Điều khoản sử dụng - Xổ Số VN | xosovn.net',
                'meta_description' => 'Điều khoản sử dụng của website xosovn.net – Cổng thông tin kết quả xổ số 3 miền minh bạch, chính xác và đáng tin cậy.',
                'content' => '
                    <h2>Điều khoản sử dụng</h2>
                    <p>Website <strong>xosovn.net</strong> cung cấp thông tin kết quả xổ số 3 miền Bắc - Trung - Nam nhằm mục đích tham khảo và giải trí.</p>
                    <p>Chúng tôi tổng hợp dữ liệu từ nhiều nguồn chính thống, tuy nhiên người dùng nên đối chiếu với kết quả mở thưởng chính thức của các công ty xổ số kiến thiết địa phương.</p>
                    <p>Người dùng không được đăng tải, bình luận hoặc chia sẻ nội dung vi phạm pháp luật, spam hoặc gây hiểu lầm.</p>
                    <p>Mọi nội dung, hình ảnh và dữ liệu thuộc quyền sở hữu của <strong>xosovn.net</strong>. Vui lòng không sao chép lại khi chưa có sự đồng ý bằng văn bản.</p>
                    <p>Khi truy cập và sử dụng website, bạn đồng ý tuân thủ các điều khoản này.</p>
                ',
            ],
        ]);
    }
}
