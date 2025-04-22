<?php

namespace App\Contracts\Repositories;

use App\Models\SuccessfulEmail;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface SuccessfulEmailRepositoryInterface
{
    /**
     * Retrieve a paginated list of successful emails.
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginated(Request $request): LengthAwarePaginator;

    /**
     * Create a new successful email.
     *
     * @param array $data
     * @return SuccessfulEmail
     */
    public function create(array $data): SuccessfulEmail;

    /**
     * Find a successful email by ID.
     *
     * @param int $id
     * @return SuccessfulEmail
     */
    public function find(int $id): SuccessfulEmail;

    /**
     * Update a successful email by ID.
     *
     * @param int $id
     * @param array $data
     * @return SuccessfulEmail
     */
    public function update(int $id, array $data): SuccessfulEmail;

    /**
     * Delete a successful email by ID.
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void;
}
