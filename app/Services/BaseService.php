<?php

namespace App\Services;

use App\Repositories\EloquentRepository;

abstract class BaseService
{
    protected $repository;

    public function __construct(EloquentRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Lấy tất cả dữ liệu, có thể tìm kiếm theo field
     *
     * @param array $filters Danh sách điều kiện tìm kiếm
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(array $filters = [], array $columns = ['*'], array $relations = [])
    {
        return $this->repository->getAll($filters, $columns, $relations);
    }

    /**
     * Lấy dữ liệu có phân trang và tìm kiếm
     *
     * @param array $filters Điều kiện tìm kiếm
     * @param int $limit Số lượng item trên mỗi trang (mặc định 30)
     * @param array $columns Các cột cần lấy (mặc định lấy tất cả)
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $limit = 30, array $columns = ['*'], array $relations = [], bool $onlyTrashed = false)
    {
        return $this->repository->paginateWithFilters($filters, $limit, $columns, $relations, $onlyTrashed);
    }

    public function findById($id)
    {
        return $this->repository->find($id);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }

}
