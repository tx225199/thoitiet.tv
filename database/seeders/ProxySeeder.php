<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Proxy;

class ProxySeeder extends Seeder
{
    public function run(): void
    {
        Proxy::truncate();

        $proxies = [
            [
                'name'            => 'p1',
                'ip'              => '103.74.107.58:8135',
                'username'        => 'tombkp3Js',
                'password'        => 'OX2VOX3V',
                'rotate_url'      => 'https://api.zingproxy.com/getip/vn/16465e07dc954f9743b2ba77f3250de3fa191d85',
                'active'          => true,
                'rotate_cooldown' => 60,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'name'            => 'p2',
                'ip'              => '103.74.107.58:8844',
                'username'        => 'tombuy5A6',
                'password'        => '0ohcSonF',
                'rotate_url'      => 'https://api.zingproxy.com/getip/vn/3f171f051adad6ef2d9d398a999cbb902aa2426a',
                'active'          => true,
                'rotate_cooldown' => 60,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'name'            => 'p3',
                'ip'              => '103.74.107.58:8935',
                'username'        => 'tomb6K355',
                'password'        => '6A6D9PxO',
                'rotate_url'      => 'https://api.zingproxy.com/getip/vn/28e92ca023b20ec120dee1cd959199ff76f0dedb',
                'active'          => true,
                'rotate_cooldown' => 60,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ];

        Proxy::insert($proxies);
    }
}
