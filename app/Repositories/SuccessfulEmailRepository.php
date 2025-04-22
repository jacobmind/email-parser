<?php

namespace App\Repositories;

use App\Contracts\Repositories\SuccessfulEmailRepositoryInterface;
use App\Models\SuccessfulEmail;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SuccessfulEmailRepository implements SuccessfulEmailRepositoryInterface
{
    protected SuccessfulEmail $model;

    public function __construct(SuccessfulEmail $model)
    {
        $this->model = $model;
    }

    public function getPaginated(Request $request): LengthAwarePaginator
    {
        $perPage = $request->query('per_page', 5);
        return $this->model->paginate($perPage);
    }

    public function create(array $data): SuccessfulEmail
    {
        return $this->model->create($data);
    }

    public function find(int $id): SuccessfulEmail
    {
        return $this->model->findOrFail($id);
    }

    public function update(int $id, array $data): SuccessfulEmail
    {
        $email = $this->model->findOrFail($id);
        $email->update($data);
        return $email;
    }

    public function delete(int $id): void
    {
        $email = $this->model->findOrFail($id);
        $email->delete();
    }
}
