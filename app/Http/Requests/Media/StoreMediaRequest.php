<?php

namespace App\Http\Requests\Media;

use App\Http\Requests\BaseApiRequest;

class StoreMediaRequest extends BaseApiRequest
{
    public function all($keys = null)
    {
        $data = parent::all($keys);
        if (empty($data['sede_id']) && $this->query('sede_id')) {
            $data['sede_id'] = $this->query('sede_id');
        }
        return $data;
    }

    public function rules(): array
    {
        return [
            'sede_id' => 'required|integer|exists:sedes,id',
            'file' => 'nullable|file|mimes:mp4,jpg,jpeg,png|max:1048576',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:mp4,jpg,jpeg,png|max:1048576',
            'title' => 'nullable|string|max:200',
            'duration' => 'nullable|integer|min:0',
            'fit_mode' => 'nullable|string|in:contain,cover,fill',
            'order_num' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Formato no compatible con Smart TVs. Solo se permiten videos en formato MP4 (.mp4) e imágenes en JPG o PNG (.jpg, .jpeg, .png).',
            'files.*.mimes' => 'Uno o más archivos no son compatibles con Smart TVs. Solo se admiten videos MP4 (.mp4) e imágenes JPG o PNG (.jpg, .jpeg, .png).',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->hasFile('file') && !$this->hasFile('files')) {
                $validator->errors()->add('file', 'Debe seleccionar al menos un archivo para subir.');
            }
        });
    }
}
