<?php

namespace App\Http\Controllers;

use App\Models\SuccessfulEmail;
use App\Services\EmailParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuccessfulEmailController extends Controller
{
    protected EmailParserService $parser;

    public function __construct(EmailParserService $parser)
    {
        $this->parser = $parser;
    }

    public function index()
    {
        return SuccessfulEmail::whereNull('deleted_at')->get();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string',
        ]);

        $parsed = $this->parser->parse($validated['email']);

        $email = SuccessfulEmail::create([
            'email' => $validated['email'],
            'raw_text' => $parsed['raw_text'],
            'from' => $parsed['from'],
            'to' => $parsed['to'],
            'subject' => $parsed['subject'],
            'timestamp' => time(),
            'affiliate_id' => 1,
            'envelope' => '',
        ]);

        return response()->json($email, 201);
    }

    public function show($id)
    {
        return SuccessfulEmail::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $email = SuccessfulEmail::findOrFail($id);
        $email->update($request->all());

        return response()->json($email);
    }

    public function destroy($id): JsonResponse
    {
        $email = SuccessfulEmail::findOrFail($id);
        $email->delete();

        return response()->json(['message' => 'Soft deleted']);
    }
}
