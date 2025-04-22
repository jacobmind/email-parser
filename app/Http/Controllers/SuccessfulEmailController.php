<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\SuccessfulEmailRepositoryInterface;
use App\Http\Requests\UpdateSuccessfulEmailRequest;
use App\Models\SuccessfulEmail;
use App\Services\EmailParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SuccessfulEmailController extends Controller
{
    protected EmailParserService $parser;
    protected SuccessfulEmailRepositoryInterface $repository;

    public function __construct(
        EmailParserService $parser,
        SuccessfulEmailRepositoryInterface $repository
    ) {
        $this->parser = $parser;
        $this->repository = $repository;
    }

    /**
     * Retrieve a paginated list of successful emails.
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function index(Request $request): LengthAwarePaginator
    {
        return $this->repository->getPaginated($request);
    }

    /**
     * Store a new successful email by parsing raw email content.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string',
        ]);

        $parsed = $this->parser->parse($validated['email']);

        if (!$parsed) {
            return response()->json(['error' => 'Failed to parse email'], 422);
        }

        $email = $this->repository->create([
            'email' => $validated['email'],
            'raw_text' => $parsed->raw_text ?? '-',
            'from' => $parsed->from,
            'to' => $parsed->to,
            'subject' => $parsed->subject,
            'timestamp' => $parsed->date ? $parsed->date->timestamp : time(),
            'affiliate_id' => 1,
            'envelope' => $parsed->envelope,
            'SPF' => $parsed->spf,
            'dkim' => $parsed->dkim,
            'sender_ip' => $parsed->sender_ip,
            'spam_score' => $parsed->spam_score,
        ]);

        return response()->json($email, 201);
    }

    /**
     * Retrieve a specific successful email by ID.
     *
     * @param int $id
     * @return SuccessfulEmail
     */
    public function show(int $id): SuccessfulEmail
    {
        return $this->repository->find($id);
    }

    /**
     * Update a specific successful email by ID.
     *
     * @param UpdateSuccessfulEmailRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateSuccessfulEmailRequest $request, $id): JsonResponse
    {
        $email = $this->repository->update($id, $request->validated());
        return response()->json($email);
    }

    /**
     * Soft delete a specific successful email by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);
        return response()->json(['message' => 'Soft deleted']);
    }
}
