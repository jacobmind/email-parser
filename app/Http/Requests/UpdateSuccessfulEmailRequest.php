<?php

namespace App\Http\Requests;

use App\Services\EmailParserService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateSuccessfulEmailRequest extends FormRequest
{
    protected EmailParserService $parser;

    /**
     * Create a new form request instance.
     */
    public function __construct(EmailParserService $parser)
    {
        parent::__construct();
        $this->parser = $parser;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust for authorization logic if needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'affiliate_id' => 'sometimes|integer|exists:affiliates,id',
            'envelope' => 'sometimes|string',
            'from' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string',
            'dkim' => 'sometimes|nullable|string|max:255',
            'SPF' => 'sometimes|nullable|string|max:255',
            'spam_score' => 'sometimes|nullable|numeric',
            'email' => 'sometimes|string',
            'raw_text' => 'sometimes|string',
            'sender_ip' => 'sometimes|nullable|string|max:50',
            'to' => 'sometimes|string',
            'timestamp' => 'sometimes|integer',
        ];
    }

    /**
     * Prepare the data for validation by parsing email and merging raw_text.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $parsed = $this->parser->parse($this->input('email'));
            if (!$parsed) {
                throw ValidationException::withMessages([
                    'email' => ['Failed to parse email'],
                ]);
            }
            $this->merge([
                'raw_text' => $parsed->raw_text ?? '-',
            ]);
        }
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'affiliate_id.integer' => 'The affiliate ID must be an integer.',
            'affiliate_id.exists' => 'The selected affiliate ID does not exist.',
            'envelope.string' => 'The envelope must be a string.',
            'from.string' => 'The from field must be a string.',
            'from.max' => 'The from field must not exceed 255 characters.',
            'subject.string' => 'The subject must be a string.',
            'dkim.string' => 'The DKIM field must be a string.',
            'dkim.max' => 'The DKIM field must not exceed 255 characters.',
            'SPF.string' => 'The SPF field must be a string.',
            'SPF.max' => 'The SPF field must not exceed 255 characters.',
            'spam_score.numeric' => 'The spam score must be a number.',
            'email.string' => 'The email must be a string.',
            'raw_text.string' => 'The raw text must be a string.',
            'sender_ip.string' => 'The sender IP must be a string.',
            'sender_ip.max' => 'The sender IP must not exceed 50 characters.',
            'to.string' => 'The to field must be a string.',
            'timestamp.integer' => 'The timestamp must be an integer.',
        ];
    }
}
