<?php

namespace SAHM\ImageOptimizer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Override in your app if auth needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $config = config('image-optimizer.validation');

        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:' . implode(',', $config['allowed_extensions']),
                'max:' . $config['max_file_size'],
            ],
            'quality' => 'sometimes|integer|min:1|max:100',
            'format' => 'sometimes|string|in:webp,avif',
            'preset' => 'sometimes|string',
            'is_lcp' => 'sometimes|boolean',
            'alt' => 'sometimes|string|max:255',
            'sizes_attr' => 'sometimes|string',
            'sizes_preset' => 'sometimes|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Please upload an image',
            'image.image' => 'File must be an image',
            'image.mimes' => 'Image must be a valid format (JPG, PNG, WebP)',
            'image.max' => 'Image size must not exceed ' . config('image-optimizer.validation.max_file_size') . 'KB',
            'quality.integer' => 'Quality must be a number',
            'quality.min' => 'Quality must be at least 1',
            'quality.max' => 'Quality must not exceed 100',
        ];
    }
}
