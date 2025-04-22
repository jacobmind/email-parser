<?php

namespace App\Http\Controllers;

use App\Models\SuccessfulEmail;
use App\Services\EmailParserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuccessfulEmailController extends Controller
{
    protected EmailParserService $parser;

    public function __construct(EmailParserService $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Retrieve a paginated list of successful emails.
     *
     * @param Request $request HTTP request containing pagination parameters
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated collection of SuccessfulEmail models
     */
    public function index(Request $request)
    {
        // Default to 5 items per page, adjustable via ?per_page= query parameter.
        $perPage = $request->query('per_page', 5);

        return SuccessfulEmail::paginate($perPage);
    }

    /**
     * Store a new successful email by parsing raw email content.
     *
     * Validates the input email and uses EmailParserService to parse it into a SuccessfulEmail model.
     * Creates a new record with parsed fields, timestamp, and default values.
     *
     * @param Request $request HTTP request containing raw email content
     * @return JsonResponse JSON response with the created SuccessfulEmail model
     * @throws \Illuminate\Validation\ValidationException If validation fails
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string',
        ]);

        // Parse raw email using EmailParserService
        $parsed = $this->parser->parse($validated['email']);

        if (!$parsed) {
            return response()->json(['error' => 'Failed to parse email'], 422);
        }

        // Create new SuccessfulEmail record with parsed fields
        $email = SuccessfulEmail::create([
            'email' => $validated['email'],
            'raw_text' => $parsed->raw_text ?? '-',
            'from' => $parsed->from,
            'to' => $parsed->to,
            'subject' => $parsed->subject,
            'timestamp' => $parsed->date ? $parsed->date->timestamp : time(),
            'affiliate_id' => 1, // Hardcoded as per original logic
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
     * @param int $id ID of the SuccessfulEmail model
     * @return SuccessfulEmail The requested SuccessfulEmail model
     * @throws ModelNotFoundException If email not found
     */
    public function show($id): SuccessfulEmail
    {
        return SuccessfulEmail::findOrFail($id);
    }

    /**
     * Update a specific successful email by ID.
     *
     * @param Request $request HTTP request containing updated fields
     * @param int $id ID of the SuccessfulEmail model
     * @return JsonResponse JSON response with the updated SuccessfulEmail model
     * @throws ModelNotFoundException If email not found
     */
    public function update(Request $request, $id): JsonResponse
    {
        $email = SuccessfulEmail::findOrFail($id);
        $email->update($request->all());

        return response()->json($email);
    }

    /**
     * Soft delete a specific successful email by ID.
     *
     * @param int $id ID of the SuccessfulEmail model
     * @return JsonResponse JSON response confirming deletion
     * @throws ModelNotFoundException If email not found
     */
    public function destroy($id): JsonResponse
    {
        $email = SuccessfulEmail::findOrFail($id);
        $email->delete();

        return response()->json(['message' => 'Soft deleted']);
    }
}
