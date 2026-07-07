<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'        => ['required', 'in:flight,train'],
            'origin'      => ['required', 'string'],       // kode lokasi, e.g. "CGK"
            'destination' => ['required', 'string'],       // kode lokasi, e.g. "DPS"
            'date'        => ['required', 'date', 'after_or_equal:today'],
            'passengers'  => ['sometimes', 'integer', 'min:1', 'max:9'],
            'class'       => ['sometimes', 'string'],      // "Economy", "Business", dst
            'sort'        => ['sometimes', 'in:price_asc,price_desc,departure_asc,departure_desc,duration_asc'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'        => 'Jenis transportasi wajib dipilih.',
            'type.in'              => 'Jenis transportasi harus flight atau train.',
            'origin.required'      => 'Kota asal wajib diisi.',
            'destination.required' => 'Kota tujuan wajib diisi.',
            'date.required'        => 'Tanggal keberangkatan wajib diisi.',
            'date.after_or_equal'  => 'Tanggal tidak boleh di masa lalu.',
            'passengers.min'       => 'Minimal 1 penumpang.',
            'passengers.max'       => 'Maksimal 9 penumpang.',
        ];
    }
}
