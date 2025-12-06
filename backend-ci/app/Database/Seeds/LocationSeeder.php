<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seed Vietnam administrative divisions data.
 * Data source: https://danhmuchanhchinh.gso.gov.vn/
 */
class LocationSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // Insert provinces
        $provinces = $this->getProvinces();
        foreach ($provinces as &$p) {
            $p['created_at'] = $now;
            $p['updated_at'] = $now;
        }
        $this->db->table('provinces')->insertBatch($provinces);

        // Get province IDs
        $provinceMap = [];
        $rows = $this->db->table('provinces')->select('id, code')->get()->getResultArray();
        foreach ($rows as $r) {
            $provinceMap[$r['code']] = $r['id'];
        }

        // Insert districts
        $districts = $this->getDistricts($provinceMap);
        foreach ($districts as &$d) {
            $d['created_at'] = $now;
            $d['updated_at'] = $now;
        }
        if (!empty($districts)) {
            $this->db->table('districts')->insertBatch($districts);
        }

        // Get district IDs
        $districtMap = [];
        $rows = $this->db->table('districts')->select('id, code')->get()->getResultArray();
        foreach ($rows as $r) {
            $districtMap[$r['code']] = $r['id'];
        }

        // Insert wards
        $wards = $this->getWards($districtMap);
        foreach ($wards as &$w) {
            $w['created_at'] = $now;
            $w['updated_at'] = $now;
        }
        if (!empty($wards)) {
            // Insert in batches of 500
            $chunks = array_chunk($wards, 500);
            foreach ($chunks as $chunk) {
                $this->db->table('wards')->insertBatch($chunk);
            }
        }

        echo "LocationSeeder: Seeded " . count($provinces) . " provinces, " . count($districts) . " districts, " . count($wards) . " wards.\n";
    }

    private function getProvinces(): array
    {
        return [
            // Thành phố trực thuộc Trung ương
            ['code' => '01', 'name' => 'Hà Nội', 'full_name' => 'Thành phố Hà Nội', 'name_en' => 'Ha Noi', 'sort_order' => 1],
            ['code' => '79', 'name' => 'Hồ Chí Minh', 'full_name' => 'Thành phố Hồ Chí Minh', 'name_en' => 'Ho Chi Minh', 'sort_order' => 2],
            ['code' => '48', 'name' => 'Đà Nẵng', 'full_name' => 'Thành phố Đà Nẵng', 'name_en' => 'Da Nang', 'sort_order' => 3],
            ['code' => '92', 'name' => 'Cần Thơ', 'full_name' => 'Thành phố Cần Thơ', 'name_en' => 'Can Tho', 'sort_order' => 4],
            ['code' => '31', 'name' => 'Hải Phòng', 'full_name' => 'Thành phố Hải Phòng', 'name_en' => 'Hai Phong', 'sort_order' => 5],

            // Miền Bắc
            ['code' => '02', 'name' => 'Hà Giang', 'full_name' => 'Tỉnh Hà Giang', 'name_en' => 'Ha Giang', 'sort_order' => 10],
            ['code' => '04', 'name' => 'Cao Bằng', 'full_name' => 'Tỉnh Cao Bằng', 'name_en' => 'Cao Bang', 'sort_order' => 11],
            ['code' => '06', 'name' => 'Bắc Kạn', 'full_name' => 'Tỉnh Bắc Kạn', 'name_en' => 'Bac Kan', 'sort_order' => 12],
            ['code' => '08', 'name' => 'Tuyên Quang', 'full_name' => 'Tỉnh Tuyên Quang', 'name_en' => 'Tuyen Quang', 'sort_order' => 13],
            ['code' => '10', 'name' => 'Lào Cai', 'full_name' => 'Tỉnh Lào Cai', 'name_en' => 'Lao Cai', 'sort_order' => 14],
            ['code' => '11', 'name' => 'Điện Biên', 'full_name' => 'Tỉnh Điện Biên', 'name_en' => 'Dien Bien', 'sort_order' => 15],
            ['code' => '12', 'name' => 'Lai Châu', 'full_name' => 'Tỉnh Lai Châu', 'name_en' => 'Lai Chau', 'sort_order' => 16],
            ['code' => '14', 'name' => 'Sơn La', 'full_name' => 'Tỉnh Sơn La', 'name_en' => 'Son La', 'sort_order' => 17],
            ['code' => '15', 'name' => 'Yên Bái', 'full_name' => 'Tỉnh Yên Bái', 'name_en' => 'Yen Bai', 'sort_order' => 18],
            ['code' => '17', 'name' => 'Hoà Bình', 'full_name' => 'Tỉnh Hoà Bình', 'name_en' => 'Hoa Binh', 'sort_order' => 19],
            ['code' => '19', 'name' => 'Thái Nguyên', 'full_name' => 'Tỉnh Thái Nguyên', 'name_en' => 'Thai Nguyen', 'sort_order' => 20],
            ['code' => '20', 'name' => 'Lạng Sơn', 'full_name' => 'Tỉnh Lạng Sơn', 'name_en' => 'Lang Son', 'sort_order' => 21],
            ['code' => '22', 'name' => 'Quảng Ninh', 'full_name' => 'Tỉnh Quảng Ninh', 'name_en' => 'Quang Ninh', 'sort_order' => 22],
            ['code' => '24', 'name' => 'Bắc Giang', 'full_name' => 'Tỉnh Bắc Giang', 'name_en' => 'Bac Giang', 'sort_order' => 23],
            ['code' => '25', 'name' => 'Phú Thọ', 'full_name' => 'Tỉnh Phú Thọ', 'name_en' => 'Phu Tho', 'sort_order' => 24],
            ['code' => '26', 'name' => 'Vĩnh Phúc', 'full_name' => 'Tỉnh Vĩnh Phúc', 'name_en' => 'Vinh Phuc', 'sort_order' => 25],
            ['code' => '27', 'name' => 'Bắc Ninh', 'full_name' => 'Tỉnh Bắc Ninh', 'name_en' => 'Bac Ninh', 'sort_order' => 26],
            ['code' => '30', 'name' => 'Hải Dương', 'full_name' => 'Tỉnh Hải Dương', 'name_en' => 'Hai Duong', 'sort_order' => 27],
            ['code' => '33', 'name' => 'Hưng Yên', 'full_name' => 'Tỉnh Hưng Yên', 'name_en' => 'Hung Yen', 'sort_order' => 28],
            ['code' => '34', 'name' => 'Thái Bình', 'full_name' => 'Tỉnh Thái Bình', 'name_en' => 'Thai Binh', 'sort_order' => 29],
            ['code' => '35', 'name' => 'Hà Nam', 'full_name' => 'Tỉnh Hà Nam', 'name_en' => 'Ha Nam', 'sort_order' => 30],
            ['code' => '36', 'name' => 'Nam Định', 'full_name' => 'Tỉnh Nam Định', 'name_en' => 'Nam Dinh', 'sort_order' => 31],
            ['code' => '37', 'name' => 'Ninh Bình', 'full_name' => 'Tỉnh Ninh Bình', 'name_en' => 'Ninh Binh', 'sort_order' => 32],

            // Miền Trung
            ['code' => '38', 'name' => 'Thanh Hóa', 'full_name' => 'Tỉnh Thanh Hóa', 'name_en' => 'Thanh Hoa', 'sort_order' => 40],
            ['code' => '40', 'name' => 'Nghệ An', 'full_name' => 'Tỉnh Nghệ An', 'name_en' => 'Nghe An', 'sort_order' => 41],
            ['code' => '42', 'name' => 'Hà Tĩnh', 'full_name' => 'Tỉnh Hà Tĩnh', 'name_en' => 'Ha Tinh', 'sort_order' => 42],
            ['code' => '44', 'name' => 'Quảng Bình', 'full_name' => 'Tỉnh Quảng Bình', 'name_en' => 'Quang Binh', 'sort_order' => 43],
            ['code' => '45', 'name' => 'Quảng Trị', 'full_name' => 'Tỉnh Quảng Trị', 'name_en' => 'Quang Tri', 'sort_order' => 44],
            ['code' => '46', 'name' => 'Thừa Thiên Huế', 'full_name' => 'Tỉnh Thừa Thiên Huế', 'name_en' => 'Thua Thien Hue', 'sort_order' => 45],
            ['code' => '49', 'name' => 'Quảng Nam', 'full_name' => 'Tỉnh Quảng Nam', 'name_en' => 'Quang Nam', 'sort_order' => 46],
            ['code' => '51', 'name' => 'Quảng Ngãi', 'full_name' => 'Tỉnh Quảng Ngãi', 'name_en' => 'Quang Ngai', 'sort_order' => 47],
            ['code' => '52', 'name' => 'Bình Định', 'full_name' => 'Tỉnh Bình Định', 'name_en' => 'Binh Dinh', 'sort_order' => 48],
            ['code' => '54', 'name' => 'Phú Yên', 'full_name' => 'Tỉnh Phú Yên', 'name_en' => 'Phu Yen', 'sort_order' => 49],
            ['code' => '56', 'name' => 'Khánh Hòa', 'full_name' => 'Tỉnh Khánh Hòa', 'name_en' => 'Khanh Hoa', 'sort_order' => 50],
            ['code' => '58', 'name' => 'Ninh Thuận', 'full_name' => 'Tỉnh Ninh Thuận', 'name_en' => 'Ninh Thuan', 'sort_order' => 51],
            ['code' => '60', 'name' => 'Bình Thuận', 'full_name' => 'Tỉnh Bình Thuận', 'name_en' => 'Binh Thuan', 'sort_order' => 52],

            // Tây Nguyên
            ['code' => '62', 'name' => 'Kon Tum', 'full_name' => 'Tỉnh Kon Tum', 'name_en' => 'Kon Tum', 'sort_order' => 60],
            ['code' => '64', 'name' => 'Gia Lai', 'full_name' => 'Tỉnh Gia Lai', 'name_en' => 'Gia Lai', 'sort_order' => 61],
            ['code' => '66', 'name' => 'Đắk Lắk', 'full_name' => 'Tỉnh Đắk Lắk', 'name_en' => 'Dak Lak', 'sort_order' => 62],
            ['code' => '67', 'name' => 'Đắk Nông', 'full_name' => 'Tỉnh Đắk Nông', 'name_en' => 'Dak Nong', 'sort_order' => 63],
            ['code' => '68', 'name' => 'Lâm Đồng', 'full_name' => 'Tỉnh Lâm Đồng', 'name_en' => 'Lam Dong', 'sort_order' => 64],

            // Miền Nam
            ['code' => '70', 'name' => 'Bình Phước', 'full_name' => 'Tỉnh Bình Phước', 'name_en' => 'Binh Phuoc', 'sort_order' => 70],
            ['code' => '72', 'name' => 'Tây Ninh', 'full_name' => 'Tỉnh Tây Ninh', 'name_en' => 'Tay Ninh', 'sort_order' => 71],
            ['code' => '74', 'name' => 'Bình Dương', 'full_name' => 'Tỉnh Bình Dương', 'name_en' => 'Binh Duong', 'sort_order' => 72],
            ['code' => '75', 'name' => 'Đồng Nai', 'full_name' => 'Tỉnh Đồng Nai', 'name_en' => 'Dong Nai', 'sort_order' => 73],
            ['code' => '77', 'name' => 'Bà Rịa - Vũng Tàu', 'full_name' => 'Tỉnh Bà Rịa - Vũng Tàu', 'name_en' => 'Ba Ria - Vung Tau', 'sort_order' => 74],
            ['code' => '80', 'name' => 'Long An', 'full_name' => 'Tỉnh Long An', 'name_en' => 'Long An', 'sort_order' => 75],
            ['code' => '82', 'name' => 'Tiền Giang', 'full_name' => 'Tỉnh Tiền Giang', 'name_en' => 'Tien Giang', 'sort_order' => 76],
            ['code' => '83', 'name' => 'Bến Tre', 'full_name' => 'Tỉnh Bến Tre', 'name_en' => 'Ben Tre', 'sort_order' => 77],
            ['code' => '84', 'name' => 'Trà Vinh', 'full_name' => 'Tỉnh Trà Vinh', 'name_en' => 'Tra Vinh', 'sort_order' => 78],
            ['code' => '86', 'name' => 'Vĩnh Long', 'full_name' => 'Tỉnh Vĩnh Long', 'name_en' => 'Vinh Long', 'sort_order' => 79],
            ['code' => '87', 'name' => 'Đồng Tháp', 'full_name' => 'Tỉnh Đồng Tháp', 'name_en' => 'Dong Thap', 'sort_order' => 80],
            ['code' => '89', 'name' => 'An Giang', 'full_name' => 'Tỉnh An Giang', 'name_en' => 'An Giang', 'sort_order' => 81],
            ['code' => '91', 'name' => 'Kiên Giang', 'full_name' => 'Tỉnh Kiên Giang', 'name_en' => 'Kien Giang', 'sort_order' => 82],
            ['code' => '93', 'name' => 'Hậu Giang', 'full_name' => 'Tỉnh Hậu Giang', 'name_en' => 'Hau Giang', 'sort_order' => 83],
            ['code' => '94', 'name' => 'Sóc Trăng', 'full_name' => 'Tỉnh Sóc Trăng', 'name_en' => 'Soc Trang', 'sort_order' => 84],
            ['code' => '95', 'name' => 'Bạc Liêu', 'full_name' => 'Tỉnh Bạc Liêu', 'name_en' => 'Bac Lieu', 'sort_order' => 85],
            ['code' => '96', 'name' => 'Cà Mau', 'full_name' => 'Tỉnh Cà Mau', 'name_en' => 'Ca Mau', 'sort_order' => 86],
        ];
    }

    private function getDistricts(array $provinceMap): array
    {
        $districts = [];

        // Hà Nội (01)
        if (isset($provinceMap['01'])) {
            $pid = $provinceMap['01'];
            $districts = array_merge($districts, [
                ['province_id' => $pid, 'code' => '001', 'name' => 'Ba Đình', 'full_name' => 'Quận Ba Đình'],
                ['province_id' => $pid, 'code' => '002', 'name' => 'Hoàn Kiếm', 'full_name' => 'Quận Hoàn Kiếm'],
                ['province_id' => $pid, 'code' => '003', 'name' => 'Tây Hồ', 'full_name' => 'Quận Tây Hồ'],
                ['province_id' => $pid, 'code' => '004', 'name' => 'Long Biên', 'full_name' => 'Quận Long Biên'],
                ['province_id' => $pid, 'code' => '005', 'name' => 'Cầu Giấy', 'full_name' => 'Quận Cầu Giấy'],
                ['province_id' => $pid, 'code' => '006', 'name' => 'Đống Đa', 'full_name' => 'Quận Đống Đa'],
                ['province_id' => $pid, 'code' => '007', 'name' => 'Hai Bà Trưng', 'full_name' => 'Quận Hai Bà Trưng'],
                ['province_id' => $pid, 'code' => '008', 'name' => 'Hoàng Mai', 'full_name' => 'Quận Hoàng Mai'],
                ['province_id' => $pid, 'code' => '009', 'name' => 'Thanh Xuân', 'full_name' => 'Quận Thanh Xuân'],
                ['province_id' => $pid, 'code' => '016', 'name' => 'Sóc Sơn', 'full_name' => 'Huyện Sóc Sơn'],
                ['province_id' => $pid, 'code' => '017', 'name' => 'Đông Anh', 'full_name' => 'Huyện Đông Anh'],
                ['province_id' => $pid, 'code' => '018', 'name' => 'Gia Lâm', 'full_name' => 'Huyện Gia Lâm'],
                ['province_id' => $pid, 'code' => '019', 'name' => 'Nam Từ Liêm', 'full_name' => 'Quận Nam Từ Liêm'],
                ['province_id' => $pid, 'code' => '020', 'name' => 'Thanh Trì', 'full_name' => 'Huyện Thanh Trì'],
                ['province_id' => $pid, 'code' => '021', 'name' => 'Bắc Từ Liêm', 'full_name' => 'Quận Bắc Từ Liêm'],
                ['province_id' => $pid, 'code' => '250', 'name' => 'Mê Linh', 'full_name' => 'Huyện Mê Linh'],
                ['province_id' => $pid, 'code' => '268', 'name' => 'Hà Đông', 'full_name' => 'Quận Hà Đông'],
                ['province_id' => $pid, 'code' => '269', 'name' => 'Sơn Tây', 'full_name' => 'Thị xã Sơn Tây'],
                ['province_id' => $pid, 'code' => '271', 'name' => 'Ba Vì', 'full_name' => 'Huyện Ba Vì'],
                ['province_id' => $pid, 'code' => '272', 'name' => 'Phúc Thọ', 'full_name' => 'Huyện Phúc Thọ'],
                ['province_id' => $pid, 'code' => '273', 'name' => 'Đan Phượng', 'full_name' => 'Huyện Đan Phượng'],
                ['province_id' => $pid, 'code' => '274', 'name' => 'Hoài Đức', 'full_name' => 'Huyện Hoài Đức'],
                ['province_id' => $pid, 'code' => '275', 'name' => 'Quốc Oai', 'full_name' => 'Huyện Quốc Oai'],
                ['province_id' => $pid, 'code' => '276', 'name' => 'Thạch Thất', 'full_name' => 'Huyện Thạch Thất'],
                ['province_id' => $pid, 'code' => '277', 'name' => 'Chương Mỹ', 'full_name' => 'Huyện Chương Mỹ'],
                ['province_id' => $pid, 'code' => '278', 'name' => 'Thanh Oai', 'full_name' => 'Huyện Thanh Oai'],
                ['province_id' => $pid, 'code' => '279', 'name' => 'Thường Tín', 'full_name' => 'Huyện Thường Tín'],
                ['province_id' => $pid, 'code' => '280', 'name' => 'Phú Xuyên', 'full_name' => 'Huyện Phú Xuyên'],
                ['province_id' => $pid, 'code' => '281', 'name' => 'Ứng Hòa', 'full_name' => 'Huyện Ứng Hòa'],
                ['province_id' => $pid, 'code' => '282', 'name' => 'Mỹ Đức', 'full_name' => 'Huyện Mỹ Đức'],
            ]);
        }

        // Hồ Chí Minh (79)
        if (isset($provinceMap['79'])) {
            $pid = $provinceMap['79'];
            $districts = array_merge($districts, [
                ['province_id' => $pid, 'code' => '760', 'name' => 'Quận 1', 'full_name' => 'Quận 1'],
                ['province_id' => $pid, 'code' => '761', 'name' => 'Quận 12', 'full_name' => 'Quận 12'],
                ['province_id' => $pid, 'code' => '764', 'name' => 'Gò Vấp', 'full_name' => 'Quận Gò Vấp'],
                ['province_id' => $pid, 'code' => '765', 'name' => 'Bình Thạnh', 'full_name' => 'Quận Bình Thạnh'],
                ['province_id' => $pid, 'code' => '766', 'name' => 'Tân Bình', 'full_name' => 'Quận Tân Bình'],
                ['province_id' => $pid, 'code' => '767', 'name' => 'Tân Phú', 'full_name' => 'Quận Tân Phú'],
                ['province_id' => $pid, 'code' => '768', 'name' => 'Phú Nhuận', 'full_name' => 'Quận Phú Nhuận'],
                ['province_id' => $pid, 'code' => '769', 'name' => 'Thủ Đức', 'full_name' => 'Thành phố Thủ Đức'],
                ['province_id' => $pid, 'code' => '770', 'name' => 'Quận 3', 'full_name' => 'Quận 3'],
                ['province_id' => $pid, 'code' => '771', 'name' => 'Quận 10', 'full_name' => 'Quận 10'],
                ['province_id' => $pid, 'code' => '772', 'name' => 'Quận 11', 'full_name' => 'Quận 11'],
                ['province_id' => $pid, 'code' => '773', 'name' => 'Quận 4', 'full_name' => 'Quận 4'],
                ['province_id' => $pid, 'code' => '774', 'name' => 'Quận 5', 'full_name' => 'Quận 5'],
                ['province_id' => $pid, 'code' => '775', 'name' => 'Quận 6', 'full_name' => 'Quận 6'],
                ['province_id' => $pid, 'code' => '776', 'name' => 'Quận 8', 'full_name' => 'Quận 8'],
                ['province_id' => $pid, 'code' => '777', 'name' => 'Bình Tân', 'full_name' => 'Quận Bình Tân'],
                ['province_id' => $pid, 'code' => '778', 'name' => 'Quận 7', 'full_name' => 'Quận 7'],
                ['province_id' => $pid, 'code' => '783', 'name' => 'Củ Chi', 'full_name' => 'Huyện Củ Chi'],
                ['province_id' => $pid, 'code' => '784', 'name' => 'Hóc Môn', 'full_name' => 'Huyện Hóc Môn'],
                ['province_id' => $pid, 'code' => '785', 'name' => 'Bình Chánh', 'full_name' => 'Huyện Bình Chánh'],
                ['province_id' => $pid, 'code' => '786', 'name' => 'Nhà Bè', 'full_name' => 'Huyện Nhà Bè'],
                ['province_id' => $pid, 'code' => '787', 'name' => 'Cần Giờ', 'full_name' => 'Huyện Cần Giờ'],
            ]);
        }

        // Đà Nẵng (48)
        if (isset($provinceMap['48'])) {
            $pid = $provinceMap['48'];
            $districts = array_merge($districts, [
                ['province_id' => $pid, 'code' => '490', 'name' => 'Liên Chiểu', 'full_name' => 'Quận Liên Chiểu'],
                ['province_id' => $pid, 'code' => '491', 'name' => 'Thanh Khê', 'full_name' => 'Quận Thanh Khê'],
                ['province_id' => $pid, 'code' => '492', 'name' => 'Hải Châu', 'full_name' => 'Quận Hải Châu'],
                ['province_id' => $pid, 'code' => '493', 'name' => 'Sơn Trà', 'full_name' => 'Quận Sơn Trà'],
                ['province_id' => $pid, 'code' => '494', 'name' => 'Ngũ Hành Sơn', 'full_name' => 'Quận Ngũ Hành Sơn'],
                ['province_id' => $pid, 'code' => '495', 'name' => 'Cẩm Lệ', 'full_name' => 'Quận Cẩm Lệ'],
                ['province_id' => $pid, 'code' => '497', 'name' => 'Hòa Vang', 'full_name' => 'Huyện Hòa Vang'],
                ['province_id' => $pid, 'code' => '498', 'name' => 'Hoàng Sa', 'full_name' => 'Huyện Hoàng Sa'],
            ]);
        }

        return $districts;
    }

    private function getWards(array $districtMap): array
    {
        $wards = [];

        // Quận Ba Đình (001) - Hà Nội
        if (isset($districtMap['001'])) {
            $did = $districtMap['001'];
            $wards = array_merge($wards, [
                ['district_id' => $did, 'code' => '00001', 'name' => 'Phúc Xá', 'full_name' => 'Phường Phúc Xá'],
                ['district_id' => $did, 'code' => '00004', 'name' => 'Trúc Bạch', 'full_name' => 'Phường Trúc Bạch'],
                ['district_id' => $did, 'code' => '00006', 'name' => 'Vĩnh Phúc', 'full_name' => 'Phường Vĩnh Phúc'],
                ['district_id' => $did, 'code' => '00007', 'name' => 'Cống Vị', 'full_name' => 'Phường Cống Vị'],
                ['district_id' => $did, 'code' => '00008', 'name' => 'Liễu Giai', 'full_name' => 'Phường Liễu Giai'],
                ['district_id' => $did, 'code' => '00010', 'name' => 'Nguyễn Trung Trực', 'full_name' => 'Phường Nguyễn Trung Trực'],
                ['district_id' => $did, 'code' => '00013', 'name' => 'Quán Thánh', 'full_name' => 'Phường Quán Thánh'],
                ['district_id' => $did, 'code' => '00016', 'name' => 'Ngọc Hà', 'full_name' => 'Phường Ngọc Hà'],
                ['district_id' => $did, 'code' => '00019', 'name' => 'Điện Biên', 'full_name' => 'Phường Điện Biên'],
                ['district_id' => $did, 'code' => '00022', 'name' => 'Đội Cấn', 'full_name' => 'Phường Đội Cấn'],
                ['district_id' => $did, 'code' => '00025', 'name' => 'Ngọc Khánh', 'full_name' => 'Phường Ngọc Khánh'],
                ['district_id' => $did, 'code' => '00028', 'name' => 'Kim Mã', 'full_name' => 'Phường Kim Mã'],
                ['district_id' => $did, 'code' => '00031', 'name' => 'Giảng Võ', 'full_name' => 'Phường Giảng Võ'],
                ['district_id' => $did, 'code' => '00034', 'name' => 'Thành Công', 'full_name' => 'Phường Thành Công'],
            ]);
        }

        // Quận Hoàn Kiếm (002) - Hà Nội
        if (isset($districtMap['002'])) {
            $did = $districtMap['002'];
            $wards = array_merge($wards, [
                ['district_id' => $did, 'code' => '00037', 'name' => 'Phúc Tân', 'full_name' => 'Phường Phúc Tân'],
                ['district_id' => $did, 'code' => '00040', 'name' => 'Đồng Xuân', 'full_name' => 'Phường Đồng Xuân'],
                ['district_id' => $did, 'code' => '00043', 'name' => 'Hàng Mã', 'full_name' => 'Phường Hàng Mã'],
                ['district_id' => $did, 'code' => '00046', 'name' => 'Hàng Buồm', 'full_name' => 'Phường Hàng Buồm'],
                ['district_id' => $did, 'code' => '00049', 'name' => 'Hàng Đào', 'full_name' => 'Phường Hàng Đào'],
                ['district_id' => $did, 'code' => '00052', 'name' => 'Hàng Bồ', 'full_name' => 'Phường Hàng Bồ'],
                ['district_id' => $did, 'code' => '00055', 'name' => 'Cửa Đông', 'full_name' => 'Phường Cửa Đông'],
                ['district_id' => $did, 'code' => '00058', 'name' => 'Lý Thái Tổ', 'full_name' => 'Phường Lý Thái Tổ'],
                ['district_id' => $did, 'code' => '00061', 'name' => 'Hàng Bạc', 'full_name' => 'Phường Hàng Bạc'],
                ['district_id' => $did, 'code' => '00064', 'name' => 'Hàng Gai', 'full_name' => 'Phường Hàng Gai'],
                ['district_id' => $did, 'code' => '00067', 'name' => 'Chương Dương', 'full_name' => 'Phường Chương Dương'],
                ['district_id' => $did, 'code' => '00070', 'name' => 'Hàng Trống', 'full_name' => 'Phường Hàng Trống'],
                ['district_id' => $did, 'code' => '00073', 'name' => 'Cửa Nam', 'full_name' => 'Phường Cửa Nam'],
                ['district_id' => $did, 'code' => '00076', 'name' => 'Hàng Bông', 'full_name' => 'Phường Hàng Bông'],
                ['district_id' => $did, 'code' => '00079', 'name' => 'Tràng Tiền', 'full_name' => 'Phường Tràng Tiền'],
                ['district_id' => $did, 'code' => '00082', 'name' => 'Trần Hưng Đạo', 'full_name' => 'Phường Trần Hưng Đạo'],
                ['district_id' => $did, 'code' => '00085', 'name' => 'Phan Chu Trinh', 'full_name' => 'Phường Phan Chu Trinh'],
                ['district_id' => $did, 'code' => '00088', 'name' => 'Hàng Bài', 'full_name' => 'Phường Hàng Bài'],
            ]);
        }

        // Quận 1 HCM (760)
        if (isset($districtMap['760'])) {
            $did = $districtMap['760'];
            $wards = array_merge($wards, [
                ['district_id' => $did, 'code' => '26734', 'name' => 'Tân Định', 'full_name' => 'Phường Tân Định'],
                ['district_id' => $did, 'code' => '26737', 'name' => 'Đa Kao', 'full_name' => 'Phường Đa Kao'],
                ['district_id' => $did, 'code' => '26740', 'name' => 'Bến Nghé', 'full_name' => 'Phường Bến Nghé'],
                ['district_id' => $did, 'code' => '26743', 'name' => 'Bến Thành', 'full_name' => 'Phường Bến Thành'],
                ['district_id' => $did, 'code' => '26746', 'name' => 'Nguyễn Thái Bình', 'full_name' => 'Phường Nguyễn Thái Bình'],
                ['district_id' => $did, 'code' => '26749', 'name' => 'Phạm Ngũ Lão', 'full_name' => 'Phường Phạm Ngũ Lão'],
                ['district_id' => $did, 'code' => '26752', 'name' => 'Cầu Ông Lãnh', 'full_name' => 'Phường Cầu Ông Lãnh'],
                ['district_id' => $did, 'code' => '26755', 'name' => 'Cô Giang', 'full_name' => 'Phường Cô Giang'],
                ['district_id' => $did, 'code' => '26758', 'name' => 'Nguyễn Cư Trinh', 'full_name' => 'Phường Nguyễn Cư Trinh'],
                ['district_id' => $did, 'code' => '26761', 'name' => 'Cầu Kho', 'full_name' => 'Phường Cầu Kho'],
            ]);
        }

        return $wards;
    }
}
