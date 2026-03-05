<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class EloquentRepository
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getAll(
        array $filters = [],
        array $columns = ['*'],
        array $relations = []
    ) {
        $query = $this->model->with($relations) // Eager load quan hệ nếu có
            ->orderBy('id', 'ASC'); // Sắp xếp giảm dần theo created_at

        foreach ($filters as $field => $conditions) {
            // Nếu điều kiện là một Closure, gọi trực tiếp
            if ($conditions instanceof \Closure) {
                $query->where($conditions);
                continue;
            }

            // Nếu là mảng nhiều điều kiện cho cùng một field
            $conditions = is_array($conditions) && isset($conditions[0]) ? $conditions : [$conditions];

            foreach ($conditions as $condition) {
                if (is_array($condition) && isset($condition['operator'], $condition['value'])) {
                    [$operator, $value] = [$condition['operator'], $condition['value']];

                    match ($operator) {
                        'IN' => $query->whereIn($field, is_array($value) ? $value : [$value]),
                        'BETWEEN' => (is_array($value) && count($value) === 2)
                            ? $query->whereBetween($field, $value)
                            : null,
                        default => $query->where($field, $operator, $value),
                    };
                } else {
                    // Sử dụng ILIKE để tìm kiếm không phân biệt hoa thường (PostgreSQL)
                    $query->whereRaw("LOWER({$field}) ILIKE ?", ["%" . strtolower($condition) . "%"]);
                }
            }
        }

        return $query->select($columns)->get();
    }

    public function find($id)
    {
        // Đọc từ Slave
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        // Ghi vào Master
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        // Ghi vào Master
        $item = $this->find($id);
        return $item->update($data);
    }

    public function delete($id)
    {
        // Ghi vào Master
        return $this->find($id)->delete();
    }

    /**
     * Phân trang và tìm kiếm theo điều kiện đầu vào
     *
     * @param array $filters Mảng điều kiện tìm kiếm
     * @param int $limit Số lượng item trên mỗi trang (mặc định 30)
     * @param array $columns Các cột cần lấy (mặc định lấy tất cả)
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateWithFilters(
        array $filters = [],
        int $limit = 30,
        array $columns = ['*'],
        array $relations = [],
        bool $onlyTrashed = false
    ) {
        $query = $this->model->with($relations)
            ->orderBy('id', 'DESC');

        // 🛠 Kiểm tra xem chỉ lấy dữ liệu bị xóa hay không
        if ($onlyTrashed) {
            $query = $query->onlyTrashed();
        }

        foreach ($filters as $field => $conditions) {
            if ($conditions instanceof \Closure) {
                $query->where($conditions);
                continue;
            }

            $conditions = is_array($conditions) && isset($conditions[0]) ? $conditions : [$conditions];

            foreach ($conditions as $condition) {
                if (is_array($condition) && isset($condition['operator'], $condition['value'])) {
                    [$operator, $value] = [$condition['operator'], $condition['value']];

                    match ($operator) {
                        'IN' => $query->whereIn($field, is_array($value) ? $value : [$value]),
                        'BETWEEN' => (is_array($value) && count($value) === 2)
                            ? $query->whereBetween($field, $value)
                            : null,
                        default => $query->where($field, $operator, $value),
                    };
                } else {
                    $query->whereRaw("LOWER({$field}) ILIKE ?", ["%{$condition}%"]);
                }
            }
        }


        return $query->select($columns)->paginate($limit);
    }


    /**
     * 1. Tìm kiếm LIKE mặc định
     * $filters = ['name' => 'John'];
     *
     * 2. Điều kiện WHERE =
     * $filters = ['status' => ['operator' => '=', 'value' => 'active']];
     *
     * 3. WHERE IN
     * $filters = ['role' => ['operator' => 'IN', 'value' => ['admin', 'user']]];
     *
     * 4. WHERE BETWEEN
     * $filters = ['created_at' => ['operator' => 'BETWEEN', 'value' => ['2024-01-01', '2024-12-31']]];
     *
     * 5. Nhiều điều kiện trên cùng một field
     *
     * $filters = [
            'status' => [
                ['operator' => '=', 'value' => 'active'],
                ['operator' => '=', 'value' => 'verified'],
            ],
        ];
     * 6. Nhiều điều kiện trên cùng khác field
     *
     * $filters = [
            'role' => ['operator' => '=', 'value' => 'admin'],
            'status' => [
                ['operator' => '=', 'value' => 'active'],
                ['operator' => '=', 'value' => 'verified'],
            ],
            'created_at' => ['operator' => 'BETWEEN', 'value' => ['2024-01-01', '2024-12-31']],
        ];
     */
}
