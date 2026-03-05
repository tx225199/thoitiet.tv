<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdvTypeSeeder extends Seeder
{
    public function run()
    {
        DB::table('adv_types')->truncate();

        // Other chi kem theo khi co Preload (la link khi click X)
        $data = ['Banner', 'Banner Script', 'Catfish', 'Preload', 'Push Js', 'Popup Js', 'Textlink', 'Header', 'Bottom', 'PopUnder'];

        foreach($data as $item){
            $adv = ['name' => $item, 'slug' => makeSlug($item)];
            DB::table('adv_types')->insert($adv);
        }
    }
}
