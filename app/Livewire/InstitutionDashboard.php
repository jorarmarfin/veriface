<?php

namespace App\Livewire;

use App\Models\Institution;
use App\Models\ValidationLog;
use Livewire\Component;

class InstitutionDashboard extends Component
{
    public int $institutionId;

    public function mount(Institution $institution): void
    {
        $this->institutionId = $institution->id;
    }

    public function render()
    {
        $institution = Institution::with('rekognitionCollection')->findOrFail($this->institutionId);

        $lastLog = ValidationLog::where('institution_id', $this->institutionId)
            ->latest()
            ->first();

        $total   = ValidationLog::where('institution_id', $this->institutionId)->count();
        $matched = ValidationLog::where('institution_id', $this->institutionId)->where('matched', true)->count();
        $today   = ValidationLog::where('institution_id', $this->institutionId)
            ->whereDate('validated_at', today())
            ->count();

        $stats = [
            'total'        => $total,
            'matched'      => $matched,
            'failed'       => $total - $matched,
            'success_rate' => $total > 0 ? round(($matched / $total) * 100, 1) : 0,
            'today'        => $today,
        ];

        return view('livewire.institution-dashboard', compact('institution', 'lastLog', 'stats'));
    }
}
