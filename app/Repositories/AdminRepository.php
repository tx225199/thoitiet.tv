<?php

namespace App\Repositories;

use App\Models\Admin;
class AdminRepository extends EloquentRepository
{
    protected $model;

    public function __construct(Admin $model)
    {
        $this->model = $model;
    }

    public function storeOrUpdateAdmin($data)
    {
        // Ghi vào Master
        $admin = isset($data['id']) ? $this->model->find($data['id']) : new Admin();

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
