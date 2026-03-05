<?php

namespace App\Services;

use App\Models\Admin;
use App\Repositories\AdminRepository;

class AdminService extends BaseService
{
    protected $adminRepository;

    public function __construct(AdminRepository $adminRepository)
    {
        parent::__construct($adminRepository);
        $this->adminRepository = $adminRepository;
    }

    public function storeOrUpdateAdmin($data)
    {
        // Ghi vào Master
        $admin = isset($data['id']) ? Admin::find($data['id']) : new Admin();

        $admin->name = $data['name'];
        $admin->email = $data['email'];
        $admin->password = isset($data['password']) && $data['password'] !== null
            ? bcrypt($data['password'])
            : optional($admin)->password;
        $admin->status = $data['status'];
        $admin->phone = $data['phone'] ?? null;

        $admin->save();
        return $admin;
    }
}
