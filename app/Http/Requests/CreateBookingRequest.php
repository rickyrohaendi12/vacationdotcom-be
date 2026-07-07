<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // sudah dilindungi middleware auth:sanctum di route
    }

    public function rules(): array
    {
        return [
            'schedule_class_id'          => ['required', 'integer', 'exists:schedule_classes,id'],
            'seat_ids'                   => ['required', 'array', 'min:1'],
            'seat_ids.*'                 => ['integer', 'exists:seats,id'],
            'passengers'                 => ['required', 'array', 'min:1'],
            'passengers.*.name'          => ['required', 'string', 'max:100'],
            'passengers.*.id_number'     => ['required', 'string', 'max:30'],
            'passengers.*.seat_id'       => ['required', 'integer', 'exists:seats,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'schedule_class_id.required'      => 'Kelas jadwal wajib dipilih.',
            'schedule_class_id.exists'        => 'Kelas jadwal tidak valid.',
            'seat_ids.required'               => 'Pilih minimal 1 kursi.',
            'passengers.required'             => 'Data penumpang wajib diisi.',
            'passengers.*.name.required'      => 'Nama penumpang wajib diisi.',
            'passengers.*.id_number.required' => 'Nomor identitas penumpang wajib diisi.',
            'passengers.*.seat_id.required'   => 'Kursi penumpang wajib dipilih.',
        ];
    }
}
